<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class UserImpersonationSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'ended_at' => 'datetime'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function finish(string $reason): void
    {
        DB::transaction(function () use ($reason) {
            static::whereKey($this->id)->whereNull('ended_at')->update([
                'ended_at' => $reason === 'expired' ? $this->expires_at : now(),
                'end_reason' => $reason,
            ]);
            PersonalAccessToken::whereKey($this->token_id)->delete();
        });
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id,
            'read_only' => true,
            'expires_at' => $this->expires_at->toIso8601String(),
            'remaining_seconds' => max(0, now()->diffInSeconds($this->expires_at, false)),
            'admin' => ['id' => $this->admin_id, 'name' => $this->admin->name],
        ];
    }
}
