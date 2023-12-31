<?php

namespace App\Http\Traits;

use App\Models\accounts\Account;

trait BelongsToAccountTrait
{
    /**
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function Account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }
}
