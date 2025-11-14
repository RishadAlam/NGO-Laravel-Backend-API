<?php

namespace App\Http\Controllers\transactions;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
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
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_client_transactions_list_view|pending_client_transactions_list_view_as_admin')->only('index');
        $this->middleware('permission:make_saving_transactions|make_loan_transactions')->only('store');
        // $this->middleware('can:permission_to_make_saving_withdrawal')->only('store');
        // $this->middleware('can:pending_saving_withdrawal_update')->only('update');
        // $this->middleware('can:pending_saving_withdrawal_delete')->only('destroy');
        // $this->middleware('can:pending_saving_withdrawal_approval')->only('approved');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(string $type)
    {
        $typeMap = [
            'saving_to_saving' =>  SavingToSavingTransaction::class,
            'saving_to_loan'   =>  SavingToLoanTransaction::class,
            'loan_to_saving'   =>  LoanToSavingTransaction::class,
            'loan_to_loan'     =>  LoanToLoanTransaction::class,
        ];

        if (!isset($typeMap[$type])) {
            return create_response(__('customValidations.client.transactions.invalid_transaction_type'), null, 400);
        }

        $transactionModel = $typeMap[$type];

        $transactions = $transactionModel::pending()
            ->with(
                [
                    'txAccount' => function ($query) {
                        $query->select('id', 'balance', 'client_registration_id');
                        $query->ClientRegistration('id', 'name', 'image_uri')
                            ->withTrashed();
                    },
                    'rxAccount' => function ($query) {
                        $query->select('id', 'balance', 'client_registration_id');
                        $query->ClientRegistration('id', 'name', 'image_uri')
                            ->withTrashed();
                    },
                ]
            )
            ->author('id', 'name')
            ->when(!Auth::user()->can('pending_client_transactions_list_view_as_admin'), function ($query) {
                $query->CreatedBy(Auth::user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return create_response(__('customValidations.common.success'), $transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionsRequest $request)
    {
        $data = (object) $request->validated();
        $userId = auth()->id();

        // Map transaction types to models — eliminates long switch-case
        $typeMap = [
            'saving_to_saving' => [SavingAccount::class, SavingAccount::class, SavingToSavingTransaction::class],
            'saving_to_loan'   => [SavingAccount::class, LoanAccount::class, SavingToLoanTransaction::class],
            'loan_to_saving'   => [LoanAccount::class, SavingAccount::class, LoanToSavingTransaction::class],
            'loan_to_loan'     => [LoanAccount::class, LoanAccount::class, LoanToLoanTransaction::class],
        ];

        if (!isset($typeMap[$data->type])) {
            return create_response(__('customValidations.client.transactions.invalid_transaction_type'), null, 400);
        }

        [$txModel, $rxModel, $transactionModel] = $typeMap[$data->type];

        $config = AppConfig::get_config('money_transfer_transaction')->{$data->type} ?? null;
        if (!$config) {
            return create_response(__('customValidations.client.transactions.invalid_transaction_type'), null, 400);
        }

        // Fetch accounts
        $txAccount = $txModel::find($data->tx_acc_id);
        $rxAccount = $rxModel::find($data->rx_acc_id);

        // Validation block
        if (!$txAccount || !$rxAccount) {
            return create_response(__('customValidations.client.transactions.invalid_account'), null, 400);
        }
        if ($data->tx_acc_id == $data->rx_acc_id) {
            return create_response(__('customValidations.client.transactions.same_account_transfer'), null, 400);
        }
        if ($config->min && $data->amount < $config->min) {
            return create_response(__('customValidations.client.transactions.amount_below_minimum'), null, 400);
        }
        if ($config->max && $data->amount > $config->max) {
            return create_response(__('customValidations.client.transactions.amount_exceeds_maximum'), null, 400);
        }
        if ($txAccount->balance < ($data->amount + $config->fee)) {
            return create_response(__('customValidations.client.transactions.insufficient_funds'), null, 400);
        }

        // Prepare transaction data
        $fieldMap = [
            'creator_id'      => $userId,
            'tx_acc_id'       => $data->tx_acc_id,
            'rx_acc_id'       => $data->rx_acc_id,
            'amount'          => $data->amount,
            'tx_prev_balance' => $txAccount->balance,
            'rx_prev_balance' => $rxAccount->balance,
            'description'     => $data->description,
        ];

        if (!$config->approval_required) {
            $fieldMap += [
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $userId,
            ];
        }

        try {
            return DB::transaction(function () use ($transactionModel, $fieldMap, $txAccount, $rxAccount, $data, $config, $userId) {
                $transactionModel::create($fieldMap);

                if (!$config->approval_required) {
                    // Update balances
                    $txAccount->increment('total_withdrawn', $data->amount);
                    $rxAccount->increment('total_deposited', $data->amount);

                    // Handle transaction fee
                    if ($config->fee > 0) {
                        $feeAccount  = Account::find($config->fee_store_acc_id);
                        $description = __('customValidations.common.receiver_account') . ' = ' . Helper::tsNumbers($data->rx_acc_id) .
                            ', ' . __('customValidations.common.amount') . ' = ' . Helper::tsNumbers($data->amount) . __('customValidations.common.send_money') . ' ' .
                            __('customValidations.common.transaction_fee') . ' = ' . Helper::tsNumbers($config->fee);

                        SavingAccountFee::create([
                            'saving_account_id'         => $data->tx_acc_id,
                            'account_fees_category_id'  => AccountFeesCategory::where('name', 'transaction_fee')->value('id'),
                            'creator_id'                => $userId,
                            'amount'                    => $config->fee,
                            'description'               => $description,
                        ]);

                        Income::store(
                            $config->fee_store_acc_id,
                            IncomeCategory::where('name', 'money_transfer_transaction_fee')->value('id'),
                            $config->fee,
                            $feeAccount->balance,
                            $description
                        );

                        $feeAccount->increment('total_deposit', $config->fee);
                        $txAccount->increment('total_withdrawn', $config->fee);
                    }
                }

                return create_response(__('customValidations.client.transactions.success'));
            });
        } catch (\Throwable $e) {
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
