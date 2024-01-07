<?php

namespace App\Http\Traits;

use App\Models\User;

trait BelongsToApproverTrait
{
    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id')->withTrashed();
    }
}
