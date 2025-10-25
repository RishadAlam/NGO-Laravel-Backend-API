<?php

namespace App\Http\Controllers\transactions;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\accounts\IncomeCategory;
use App\Models\client\SavingAccountFee;
use App\Models\client\AccountFeesCategory;
use App\Models\transactions\LoanToLoanTransaction;
use App\Models\transactions\LoanToSavingTransaction;
use App\Models\transactions\SavingToLoanTransaction;
use App\Models\transactions\SavingToSavingTransaction;
use App\Http\Requests\transactions\TransactionsRequest;

class TransactionsController extends Controller
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
    public function store(TransactionsRequest $request)
    {
        $data = (object) $request->validated();
        $transactionType = $data->type;

        $savingAccountModel = new SavingAccount();
        $loanAccountModel = new LoanAccount();

        switch ($transactionType) {
            case 'saving_to_saving':
                $txModel = $savingAccountModel;
                $rxModel = $savingAccountModel;
                $transactionModel = new SavingToSavingTransaction();

                break;

            case 'saving_to_loan':
                $txModel = $savingAccountModel;
                $rxModel = $loanAccountModel;
                $transactionModel = new SavingToLoanTransaction();

                break;

            case 'loan_to_saving':
                $txModel = $loanAccountModel;
                $rxModel = $savingAccountModel;
                $transactionModel = new LoanToSavingTransaction();

                break;

            case 'loan_to_loan':
                $txModel = $loanAccountModel;
                $rxModel = $loanAccountModel;
                $transactionModel = new LoanToLoanTransaction();

                break;

            default:
                return create_response(__('customValidations.transaction.invalid_transaction_type'), null, 400);
        }

        $configs = AppConfig::get_config('money_transfer_transaction');
        $config = $configs[$transactionType] ?? null;

        if (empty($config)) {
            return create_response(__('customValidations.transaction.invalid_transaction_type'), null, 400);
        }

        $txAccount = $txModel->find($data->tx_acc_id);
        $rxAccount = $rxModel->find($data->rx_acc_id);

        // Validate accounts
        if (empty($txAccount) || empty($rxAccount)) {
            return create_response(__('customValidations.transaction.invalid_account'), null, 400);
        }
        if ($config->min !== 0 && $data->amount < $config->min) {
            return create_response(__('customValidations.transaction.amount_below_minimum'), null, 400);
        }
        if ($config->max !== 0 && $data->amount > $config->max) {
            return create_response(__('customValidations.transaction.amount_exceeds_maximum'), null, 400);
        }
        if ($txAccount->balance < $data->amount || $txAccount->balance <= $config->fee) {
            return create_response(__('customValidations.transaction.insufficient_funds'), null, 400);
        }
        if ($data->tx_acc_id === $data->rx_acc_id) {
            return create_response(__('customValidations.transaction.same_account_transfer'), null, 400);
        }

        $fieldMap = [
            'creator_id'        => auth()->id(),
            'tx_acc_id'         => $data->tx_acc_id,
            'rx_acc_id'         => $data->rx_acc_id,
            'amount'            => $data->amount,
            'tx_prev_balance'   => $txAccount->balance,
            'rx_prev_balance'   => $rxAccount->balance,
            'description'       => $data->description,
        ];

        if (!$config->approval_required) {
            $fieldMap['is_approved'] = true;
            $fieldMap['approved_at'] = now();
            $fieldMap['approved_by'] = auth()->id();
        }

        try {
            return DB::transaction(function () use ($transactionModel, $fieldMap, $txAccount, $rxAccount, $data, $config) {
                $transactionModel::create($fieldMap);
                $txAccount->increment('total_withdrawn', $data->amount);
                $rxAccount->increment('total_deposited', $data->amount);

                if ($config->fee > 0) {
                    $categoryId     = AccountFeesCategory::where('name', 'transaction_fee')->value('id');
                    $feeAccount     = Account::find($config->fee_store_acc_id);
                    $incomeCatId    = IncomeCategory::where('name', 'money_transfer_transaction_fee')->value('id');
                    $description    = __('customValidations.common.receiver_account') . ' = ' . $data->rx_acc_id . ', ' . __('customValidations.common.amount') . ' = ' . $data->amount;

                    SavingAccountFee::create([
                        'saving_account_id'         => $data->tx_acc_id,
                        'account_fees_category_id'  => $categoryId,
                        'creator_id'                => auth()->id(),
                        'amount'                    => $config->fee,
                        'description'               => $description
                    ]);

                    Income::store(
                        $config->fee_store_acc_id,
                        $incomeCatId,
                        $config->fee,
                        $feeAccount->balance,
                        $description
                    );

                    $feeAccount->increment('total_deposit', $config->fee);
                    $txAccount->increment('total_withdrawn', $config->fee);
                }

                return create_response(__('customValidations.transaction.success'));
            });
        } catch (\Exception $e) {
            return create_response($e->getMessage(), null, 400, false);
        }
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
