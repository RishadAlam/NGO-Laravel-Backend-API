<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserImpersonationSession;
use App\Models\UsersVerify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AccountRecovery
{
    public function issueCode(int $userId, string $purpose): array
    {
        return DB::transaction(function () use ($userId, $purpose) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            abort_unless($user->status, 422);
            UsersVerify::where('user_id', $user->id)->delete();
            Password::deleteToken($user);
            $otp = (string) random_int(100000, 999999);
            $expired = now()->addMinutes(5);
            UsersVerify::create(['user_id' => $user->id, 'otp' => $otp, 'purpose' => $purpose, 'expired_at' => $expired]);

            return compact('otp', 'expired');
        });
    }

    public function verify(int $userId, string $otp, string $purpose): ?array
    {
        return DB::transaction(function () use ($userId, $otp, $purpose) {
            // Serialize issuance, verification and reset on the same account.
            $user = User::whereKey($userId)->lockForUpdate()->first();
            if (! $user || ! $user->status) {
                return null;
            }
            $code = UsersVerify::where('user_id', $user->id)->where('otp', $otp)
                ->where('purpose', $purpose)->where('expired_at', '>', now())->first();
            if (! $code) {
                return null;
            }
            UsersVerify::where('user_id', $user->id)->delete();
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

            return $purpose === 'recovery' ? ['reset_token' => Password::createToken($user)] : [];
        });
    }

    public function reset(int $userId, string $proof, string $password): bool
    {
        return DB::transaction(function () use ($userId, $proof, $password) {
            $user = User::whereKey($userId)->lockForUpdate()->first();
            if (! $user || ! $user->status || ! Password::tokenExists($user, $proof)) {
                return false;
            }
            $user->forceFill(['password' => Hash::make($password)])->save();
            Password::deleteToken($user);
            UsersVerify::where('user_id', $user->id)->delete();
            UserImpersonationSession::whereNull('ended_at')
                ->where(fn ($query) => $query->where('admin_id', $user->id)->orWhere('user_id', $user->id))
                ->each(fn ($session) => $session->finish('revoked'));
            $user->tokens()->delete();

            return true;
        });
    }
}
