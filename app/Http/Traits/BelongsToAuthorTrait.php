<?php

namespace App\Http\Traits;

use App\Models\User;

trait BelongsToAuthorTrait
{
    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }
}
