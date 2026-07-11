<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class AccountClosingBalanceValidator
{
    /**
     * Determine whether the main balance will be zero after closing deductions.
     */
    public static function isZeroAfterDeductions($mainBalance, ...$deductions): bool
    {
        $remainingBalance = $mainBalance + 0;

        foreach ($deductions as $deduction) {
            $remainingBalance -= $deduction + 0;
        }

        return $remainingBalance === 0 || $remainingBalance === 0.0;
    }

    /**
     * Validate that the main balance will be zero after closing deductions.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validate($mainBalance, ...$deductions): void
    {
        if (self::isZeroAfterDeductions($mainBalance, ...$deductions)) {
            return;
        }

        throw ValidationException::withMessages([
            'balance' => __('customValidations.client.closing.balance_must_be_zero'),
        ]);
    }
}
