<?php

namespace App\Http\Controllers\ClientAccountChecks;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\client\SavingAccountCheck;
use App\Http\Requests\ClientAccountChecks\SavingAccountCheckStoreRequest;

class SavingAccountCheckController extends Controller
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
    public function store(SavingAccountCheckStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $account        = SavingAccount::find($data->account_id);

        if ($data->amount > $account->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }

        SavingAccountCheck::create(
            [
                'saving_account_id'     => $account->id,
                'balance'               => $account->balance,
                'installment_recovered' => $account->total_installment,
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
