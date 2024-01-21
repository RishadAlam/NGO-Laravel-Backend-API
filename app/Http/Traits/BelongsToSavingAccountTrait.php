<?php

namespace App\Http\Traits;

use App\Models\client\SavingAccount;

trait BelongsToSavingAccountTrait
{

    /**
     * Relationship belongs to SavingAccount model
     *
     * @return response()
     */
    public function SavingAccount()
    {
        return $this->belongsTo(SavingAccount::class)->withTrashed();
    }
}
