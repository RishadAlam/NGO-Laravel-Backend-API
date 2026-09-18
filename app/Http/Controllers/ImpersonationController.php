<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserImpersonationSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class ImpersonationController extends Controller
{
    public function start(Request $request, string $id)
    {
        $admin = $request->user();
        abort_unless($admin->can('staff_impersonate'), 403);
        $originalToken = $admin->currentAccessToken();
        abort_unless($originalToken instanceof PersonalAccessToken && $originalToken->name !== 'impersonation', 403);
        $target = User::findOrFail($id);
        abort_if($admin->is($target) || ! $target->status || ! $target->hasVerifiedEmail(), 422, __('impersonation.invalid_target'));

        return DB::transaction(function () use ($admin, $originalToken, $target) {
            $expiresAt = now()->addMinutes(15);
            $token = $target->createToken('impersonation', ['impersonation:read-only'], $expiresAt);
            $session = UserImpersonationSession::create([
                'admin_id' => $admin->id,
                'user_id' => $target->id,
                'admin_token_id' => $originalToken->id,
                'token_id' => $token->accessToken->id,
                'expires_at' => $expiresAt,
            ]);

            return response()->json(array_merge(
                AuthController::createAuthorizedRes($target, __('impersonation.started')),
                ['access_token' => $token->plainTextToken, 'token_type' => 'Bearer', 'impersonation' => $session->metadata()]
            ), 201);
        });
    }

    public function stop(Request $request)
    {
        $session = $request->attributes->get('impersonation');
        abort_unless($session instanceof UserImpersonationSession, 409, __('impersonation.not_active'));
        $session->finish('returned');

        return response()->json(['success' => true, 'message' => __('impersonation.ended')]);
    }
}
