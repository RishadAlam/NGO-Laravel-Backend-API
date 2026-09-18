<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerifyMail;
use App\Models\User;
use App\Models\UsersVerify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->freezeTime();
        $this->withHeaders(['Accept-Language' => 'en']);
    }

    private function user(): User
    {
        return User::factory()->create(['status' => true, 'email_verified_at' => now(), 'password' => Hash::make('OldPassword7!')]);
    }

    private function payload(User $user, ?string $proof = null): array
    {
        return ['user_id' => $user->id, 'reset_token' => $proof, 'new_password' => 'NewPassword8!', 'confirm_password' => 'NewPassword8!'];
    }

    private function proof(User $user): string
    {
        $this->postJson('/api/forget-password', ['email' => $user->email])->assertOk();
        $code = UsersVerify::where('user_id', $user->id)->sole();
        $response = $this->postJson('/api/account-verification', ['user_id' => $user->id, 'otp' => $code->otp, 'purpose' => 'recovery'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['reset_token']);
        Mail::assertSent(EmailVerifyMail::class, fn ($mail) => $mail->hasTo($user->email));

        return $response->json('reset_token');
    }

    public function test_reset_without_proof_preserves_password_and_login(): void
    {
        $user = $this->user();
        $hash = $user->password;
        $token = $user->createToken('auth_token');
        $this->putJson('/api/reset-password', $this->payload($user))->assertUnprocessable();
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_recovery_proof_is_account_bound_and_single_use(): void
    {
        $owner = $this->user();
        $victim = $this->user();
        $hash = $victim->password;
        $proof = $this->proof($owner);
        $this->putJson('/api/reset-password', $this->payload($victim, $proof))->assertUnprocessable();
        $this->assertSame($hash, $victim->fresh()->password);
        $login = $owner->createToken('auth_token');
        $this->putJson('/api/reset-password', $this->payload($owner, $proof))->assertOk();
        $this->assertTrue(Hash::check('NewPassword8!', $owner->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $login->accessToken->id]);
        $this->putJson('/api/reset-password', $this->payload($owner, $proof))->assertUnprocessable();
        $this->postJson('/api/login', ['email' => $owner->email, 'password' => 'OldPassword7!'])->assertUnauthorized();
        $this->postJson('/api/login', ['email' => $owner->email, 'password' => 'NewPassword8!'])->assertOk();
    }

    public function test_recovery_proof_expires_and_is_stored_only_as_a_hash(): void
    {
        $user = $this->user();
        $hash = $user->password;
        $proof = $this->proof($user);
        $this->assertDatabaseMissing('password_reset_tokens', ['token' => $proof]);
        $this->travel(6)->minutes();
        $this->putJson('/api/reset-password', $this->payload($user, $proof))->assertUnprocessable();
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_codes_are_bound_to_user_and_purpose_and_cannot_be_replayed(): void
    {
        $owner = $this->user();
        $victim = $this->user();
        $this->postJson('/api/forget-password', ['email' => $owner->email])->assertOk();
        $code = UsersVerify::where('user_id', $owner->id)->sole();
        $this->postJson('/api/account-verification', ['user_id' => $victim->id, 'otp' => $code->otp, 'purpose' => 'recovery'])
            ->assertStatus(202)->assertJsonPath('success', false)->assertJsonMissingPath('reset_token');
        $this->postJson('/api/account-verification', ['user_id' => $owner->id, 'otp' => $code->otp, 'purpose' => 'verification'])
            ->assertStatus(202)->assertJsonPath('success', false);
        $payload = ['user_id' => $owner->id, 'otp' => $code->otp, 'purpose' => 'recovery'];
        $this->postJson('/api/account-verification', $payload)->assertOk()->assertJsonStructure(['reset_token']);
        $this->postJson('/api/account-verification', $payload)->assertStatus(202)->assertJsonPath('success', false);
    }

    public function test_verification_code_verifies_email_without_granting_password_reset(): void
    {
        $user = $this->user();
        $user->update(['email_verified_at' => null]);
        $this->getJson('/api/otp-resend/'.$user->id.'?purpose=verification')->assertOk();
        $code = UsersVerify::where('user_id', $user->id)->sole();
        $this->postJson('/api/account-verification', ['user_id' => $user->id, 'otp' => $code->otp, 'purpose' => 'verification'])
            ->assertOk()->assertJsonMissingPath('reset_token');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->putJson('/api/reset-password', $this->payload($user))->assertUnprocessable();
    }

    public function test_resend_invalidates_previous_codes_and_reset_proofs(): void
    {
        $user = $this->user();
        $proof = $this->proof($user);
        $this->getJson('/api/otp-resend/'.$user->id.'?purpose=recovery')->assertOk();
        $firstCodeId = UsersVerify::where('user_id', $user->id)->sole()->id;
        $this->getJson('/api/otp-resend/'.$user->id.'?purpose=recovery')->assertOk();
        $this->assertDatabaseMissing('users_verifies', ['id' => $firstCodeId]);
        $this->assertDatabaseCount('users_verifies', 1);
        $this->putJson('/api/reset-password', $this->payload($user, $proof))->assertUnprocessable();
    }

    public function test_expired_code_and_disabled_account_cannot_issue_or_use_reset_proof(): void
    {
        $user = $this->user();
        $this->postJson('/api/forget-password', ['email' => $user->email])->assertOk();
        $code = UsersVerify::where('user_id', $user->id)->sole();
        $this->travel(5)->minutes();
        $this->postJson('/api/account-verification', ['user_id' => $user->id, 'otp' => $code->otp, 'purpose' => 'recovery'])
            ->assertStatus(202)->assertJsonPath('success', false);
        $proof = $this->proof($user);
        $user->update(['status' => false]);
        $this->putJson('/api/reset-password', $this->payload($user, $proof))->assertUnprocessable();
    }

    public function test_code_guessing_is_rate_limited_per_account(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/account-verification', ['user_id' => $user->id, 'otp' => '999999', 'purpose' => 'recovery'])
                ->assertStatus(202);
        }
        $this->postJson('/api/account-verification', ['user_id' => $user->id, 'otp' => '999999', 'purpose' => 'recovery'])
            ->assertStatus(429);
        $this->postJson('/api/account-verification', ['user_id' => '+'.$user->id, 'otp' => '999999', 'purpose' => 'recovery'])
            ->assertStatus(429);
    }

    public function test_login_rechecks_password_before_issuing_a_token_after_a_concurrent_reset(): void
    {
        $user = $this->user();
        $reads = 0;
        User::retrieved(function (User $retrieved) use ($user, &$reads) {
            if ($retrieved->id === $user->id && ++$reads === 2) {
                // Reset wins after credential lookup but before token issuance.
                DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('ChangedByReset9!')]);
            }
        });
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'OldPassword7!'])->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
