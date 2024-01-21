<?php

namespace App\Http\Traits;

use App\Models\client\LoanAccount;

trait BelongsToLoanAccountTrait
{
    /**
     * Relationship belongs to LoanAccount model
     *
     * @return response()
     */
    public function LoanAccount()
    {
        return $this->belongsTo(LoanAccount::class)->withTrashed();
    }
}
