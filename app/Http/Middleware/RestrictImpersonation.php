<?php

namespace App\Http\Middleware;

use App\Models\UserImpersonationSession;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RestrictImpersonation
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();
        $token = $bearer ? PersonalAccessToken::findToken($bearer) : null;
        if (! $token || $token->name !== 'impersonation') {
            return $next($request);
        }

        $locale = in_array($request->header('Accept-Language'), ['en', 'bn']) ? $request->header('Accept-Language') : 'en';
        $session = UserImpersonationSession::with(['admin', 'user'])->where('token_id', $token->id)->first();
        $original = $session ? PersonalAccessToken::find($session->admin_token_id) : null;
        $expiration = config('sanctum.expiration');
        $expired = $session && ($session->expires_at->lte(now()) || $token->expires_at?->lte(now())
            || ($expiration && $token->created_at->lte(now()->subMinutes($expiration))));
        $valid = $session && ! $session->ended_at && ! $expired
            && $session->admin?->status && $session->admin->hasVerifiedEmail()
            && $session->admin->can('staff_impersonate')
            && $session->user?->status && $session->user->hasVerifiedEmail()
            && $original && $original->name !== 'impersonation'
            && $original->tokenable?->is($session->admin)
            && $token->tokenable?->is($session->user)
            && (! $original->expires_at || $original->expires_at->gt(now()))
            && (! $expiration || $original->created_at->gt(now()->subMinutes($expiration)));

        if (! $valid) {
            if ($session) {
                $session->finish($expired ? 'expired' : 'revoked');
            } else {
                $token->delete();
            }

            return response()->json(['success' => false, 'code' => 'IMPERSONATION_ENDED',
                'message' => __('impersonation.ended', [], $locale)], 401);
        }

        $request->attributes->set('impersonation', $session);
        $path = trim($request->decodedPath(), '/');
        $canRead = in_array($request->method(), ['GET', 'HEAD'])
            && ! $request->is('api/transactions/approve-transactions/*', 'api/otp-resend/*')
            && ! in_array($path, ['api/cache-clear', 'api/config-clear', 'api/route-clear', 'api/optimize-clear', 'api/storage-link']);
        $canPost = $request->isMethod('POST') && (
            in_array($path, ['api/impersonation/stop', 'api/logout'])
            || $request->is('api/categories-config/element/*')
        );
        if (! $canRead && ! $canPost) {
            return response()->json(['success' => false, 'code' => 'IMPERSONATION_READ_ONLY',
                'message' => __('impersonation.read_only', [], $locale)], 403);
        }

        return $next($request);
    }
}
