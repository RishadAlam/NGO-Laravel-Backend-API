<?php

namespace App\Http\Controllers\ClientAccountChecks;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\client\LoanAccount;
use App\Http\Controllers\Controller;
use App\Models\client\LoanAccountCheck;
use App\Http\Requests\ClientAccountChecks\LoanAccountCheckStoreRequest;

class LoanAccountCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoanAccountCheckStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $account        = LoanAccount::find($data->account_id);

        if ($data->amount > $account->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }

        LoanAccountCheck::create(
            [
                'saving_account_id'     => $account->id,
                'installment_recovered' => $account->total_rec_installment,
                'installment_remaining' => $account->payable_installment - $account->total_rec_installment,
                'balance'               => $account->balance,
                'loan_recovered'        => $account->total_loan_rec,
                'loan_remaining'        => $account->total_loan_remaining,
                'interest_recovered'    => $account->total_interest_rec,
                'interest_remaining'    => $account->total_interest_remaining,
                'description'           => $data->description,
                'next_check_in_at'      => Carbon::parse($data->next_check_in_at)->startOfDay(),
                'checked_by'            => auth()->id(),
            ]
        );

        return create_response(__('customValidations.client.saving.account_check'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
