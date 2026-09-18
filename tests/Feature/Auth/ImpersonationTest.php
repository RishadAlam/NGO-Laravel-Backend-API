<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions = [], array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['status' => true, 'email_verified_at' => now(), 'password' => 'password'], $attributes));
        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], [
                'group_name' => 'staff', 'parent_group_name' => 'staff',
            ]));
        }

        return $user;
    }

    private function requestAs(string $token, string $method, string $uri, array $data = [])
    {
        // Exercise real bearer tokens, including expiry, on every request.
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept-Language' => 'en'])
            ->json($method, '/api/'.$uri, $data);
    }

    private function visit(User $admin, User $target): array
    {
        $original = $admin->createToken('auth_token')->plainTextToken;
        $response = $this->requestAs($original, 'POST', "users/{$target->id}/impersonation");
        $response->assertCreated();

        return [$original, $response->json('access_token'), $response];
    }

    public function test_permission_is_required_even_when_the_caller_can_list_staff(): void
    {
        $admin = $this->user(['staff_list_view']);
        $target = $this->user();
        $token = $admin->createToken('auth_token')->plainTextToken;
        $this->requestAs($token, 'POST', "users/{$target->id}/impersonation")->assertForbidden();
        $this->assertDatabaseCount('user_impersonation_sessions', 0);
    }

    public function test_staff_list_exposes_verification_for_the_visit_action(): void
    {
        $admin = $this->user(['staff_list_view', 'staff_impersonate']);
        $target = $this->user();
        $token = $admin->createToken('auth_token')->plainTextToken;
        $response = $this->requestAs($token, 'GET', 'users')->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $target->id);
        $this->assertNotEmpty($row['verified_at'] ?? null);
    }

    public function test_role_permission_allows_a_separate_fifteen_minute_target_session(): void
    {
        $this->freezeTime();
        $admin = $this->user(['staff_list_view']);
        Permission::firstOrCreate(['name' => 'staff_impersonate', 'guard_name' => 'web'], [
            'group_name' => 'staff', 'parent_group_name' => 'staff',
        ]);
        $role = Role::create(['name' => 'Support admin', 'guard_name' => 'web']);
        $role->givePermissionTo('staff_impersonate');
        $admin->assignRole($role);
        $target = $this->user(['field_list_view']);
        $existingTargetToken = $target->createToken('auth_token')->plainTextToken;
        [$original, $temporary, $response] = $this->visit($admin, $target);

        $response->assertJsonPath('id', $target->id)->assertJsonPath('impersonation.read_only', true);
        $this->assertSame(now()->addMinutes(15)->getTimestamp(), PersonalAccessToken::findToken($temporary)->expires_at->getTimestamp());
        $this->requestAs($temporary, 'GET', 'authorization')
            ->assertOk()->assertJsonPath('id', $target->id)
            ->assertJsonPath('permissions', ['field_list_view'])
            ->assertJsonPath('impersonation.admin.id', $admin->id);
        $this->requestAs($original, 'GET', 'authorization')->assertOk()->assertJsonPath('id', $admin->id);
        $this->assertNotNull(PersonalAccessToken::findToken($existingTargetToken));
        $this->assertDatabaseHas('user_impersonation_sessions', [
            'admin_id' => $admin->id, 'user_id' => $target->id, 'ended_at' => null,
        ]);
    }

    public function test_target_permissions_are_enforced_without_inheriting_admin_grants(): void
    {
        $admin = $this->user(['staff_impersonate', 'staff_list_view']);
        [, $temporary] = $this->visit($admin, $this->user());
        $this->requestAs($temporary, 'GET', 'users')->assertForbidden();
    }

    public function test_view_only_sessions_block_writes_public_auth_and_legacy_get_mutations(): void
    {
        $admin = $this->user(['staff_impersonate']);
        $target = $this->user(['staff_impersonate', 'pending_client_transactions_approval']);
        [, $temporary] = $this->visit($admin, $target);
        foreach ([
            ['PUT', 'profile-update'], ['PUT', 'change-password'],
            ['POST', 'registration'], ['POST', 'login'], ['POST', 'forget-password'],
            ['POST', 'add-permission'], ['POST', "users/{$admin->id}/impersonation"],
            ['DELETE', "users/{$admin->id}"], ['GET', 'transactions/approve-transactions/1/saving_to_saving'],
            ['GET', 'cache-clear'], ['HEAD', 'storage-link'], ['GET', "otp-resend/{$target->id}"],
        ] as [$method, $path]) {
            $this->requestAs($temporary, $method, $path)->assertForbidden();
        }
        $this->assertSame($target->name, $target->fresh()->name);
    }

    public function test_expiry_is_enforced_without_a_browser_timer_and_original_token_survives(): void
    {
        $this->freezeTime();
        $admin = $this->user(['staff_impersonate']);
        [$original, $temporary] = $this->visit($admin, $this->user());
        $this->travel(15)->minutes();
        $this->requestAs($temporary, 'GET', 'authorization')->assertUnauthorized();
        $this->requestAs($original, 'GET', 'authorization')->assertOk()->assertJsonPath('id', $admin->id);
        $this->assertDatabaseHas('user_impersonation_sessions', ['end_reason' => 'expired']);
    }

    public function test_stop_revokes_only_temporary_token_and_records_the_end(): void
    {
        $admin = $this->user(['staff_impersonate']);
        [$original, $temporary] = $this->visit($admin, $this->user());
        $this->requestAs($temporary, 'POST', 'impersonation/stop')->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($temporary));
        $this->assertNotNull(PersonalAccessToken::findToken($original));
        $this->assertNotNull(DB::table('user_impersonation_sessions')->value('ended_at'));
        $this->assertDatabaseHas('user_impersonation_sessions', ['end_reason' => 'returned']);
        $this->requestAs($temporary, 'GET', 'authorization')->assertUnauthorized();
    }

    public function test_permission_revocation_immediately_ends_temporary_access(): void
    {
        $admin = $this->user(['staff_impersonate']);
        [, $temporary] = $this->visit($admin, $this->user());
        $admin->revokePermissionTo('staff_impersonate');
        $this->requestAs($temporary, 'GET', 'authorization')->assertUnauthorized();
        $this->assertDatabaseHas('user_impersonation_sessions', ['end_reason' => 'revoked']);
    }

    public function test_revoking_the_original_login_ends_temporary_access(): void
    {
        $admin = $this->user(['staff_impersonate']);
        [$original, $temporary] = $this->visit($admin, $this->user());
        PersonalAccessToken::findToken($original)->delete();
        $this->requestAs($temporary, 'GET', 'authorization')->assertUnauthorized();
    }

    public function test_disabled_deleted_unverified_and_self_targets_cannot_be_visited(): void
    {
        $admin = $this->user(['staff_impersonate']);
        $disabled = $this->user([], ['status' => false]);
        $unverified = $this->user([], ['email_verified_at' => null]);
        $deleted = $this->user();
        $deleted->delete();
        $token = $admin->createToken('auth_token')->plainTextToken;
        foreach ([$admin, $disabled, $unverified] as $target) {
            $this->requestAs($token, 'POST', "users/{$target->id}/impersonation")->assertUnprocessable();
        }
        $this->requestAs($token, 'POST', "users/{$deleted->id}/impersonation")->assertNotFound();
        $this->assertDatabaseCount('user_impersonation_sessions', 0);
    }

    public function test_deactivating_either_user_ends_an_existing_visit(): void
    {
        foreach (['admin', 'target'] as $disabled) {
            $admin = $this->user(['staff_impersonate']);
            $target = $this->user();
            [, $temporary] = $this->visit($admin, $target);
            ${$disabled}->update(['status' => false]);
            $this->requestAs($temporary, 'GET', 'authorization')->assertUnauthorized();
        }
    }

    public function test_abandoned_sessions_are_expired_and_audited_without_another_browser_request(): void
    {
        $this->freezeTime();
        $admin = $this->user(['staff_impersonate']);
        [$original, $temporary] = $this->visit($admin, $this->user());
        $expiresAt = now()->addMinutes(15)->toDateTimeString();
        $this->travel(16)->minutes();
        $this->artisan('impersonation:expire')->assertSuccessful();
        $this->assertNull(PersonalAccessToken::findToken($temporary));
        $this->assertNotNull(PersonalAccessToken::findToken($original));
        $this->assertDatabaseHas('user_impersonation_sessions', ['end_reason' => 'expired', 'ended_at' => $expiresAt]);
    }

    public function test_cleanup_records_revoked_tokens_without_waiting_for_session_expiry(): void
    {
        $this->freezeTime();
        foreach (['original', 'temporary'] as $revoked) {
            $admin = $this->user(['staff_impersonate']);
            [$original, $temporary, $response] = $this->visit($admin, $this->user());
            PersonalAccessToken::findToken(${$revoked})->delete();
            $this->artisan('impersonation:expire')->assertSuccessful();
            $this->assertNull(PersonalAccessToken::findToken($temporary));
            $this->assertDatabaseHas('user_impersonation_sessions', [
                'id' => $response->json('impersonation.id'),
                'end_reason' => 'revoked', 'ended_at' => now()->toDateTimeString(),
            ]);
        }
    }
}
