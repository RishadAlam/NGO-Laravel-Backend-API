<?php

namespace App\Http\Controllers\accounts;

use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\accounts\IncomeCategory;
use App\Models\accounts\AccountTransfer;
use App\Models\accounts\ExpenseCategory;
use App\Models\accounts\AccountWithdrawal;
use App\Models\accounts\AccountActionHistory;
use App\Http\Requests\accounts\AccountStoreRequest;
use App\Http\Requests\accounts\AccountUpdateRequest;
use App\Http\Requests\accounts\AccountChangeStatusRequest;

class AccountController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:account_list_view')->only('index');
        $this->middleware('can:account_registration')->only('store');
        $this->middleware('can:account_data_update')->only(['update', 'change_status']);
        $this->middleware('can:account_soft_delete')->only('destroy');
        $this->middleware('can:account_transaction_list_view')->only('get_all_transactions');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "account_id"        => $id,
            "author_id"         => auth()->id(),
            "name"              => auth()->user()->name,
            "image_uri"         => auth()->user()->image_uri,
            "action_type"       => $action,
            "action_details"    => $histData,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Account::with('Author:id,name')
            ->with(['AccountActionHistory', 'AccountActionHistory.Author:id,name,image_uri'])
            ->get();

        return create_response(null, $accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountStoreRequest $request)
    {
        $data = (object) $request->validated();
        Account::create(
            [
                'name'          => $data->name,
                'acc_no'        => $data->acc_no ?? null,
                'acc_details'   => $data->acc_details ?? null,
                'total_deposit' => $data->initial_balance ?? 0,
                'creator_id'    => auth()->id()
            ]
        );

        return create_response(__('customValidations.accounts.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $account    = Account::find($id);
        $histData   = [];

        $account->name          !== $data->name ? $histData['name'] = "<p class='text-danger'>{$account->name}</p><p class='text-success'>{$data->name}</p>" : '';
        $account->acc_no        !== $data->acc_no ? $histData['acc_no'] = "<p class='text-danger'>{$account->acc_no}</p><p class='text-success'>{$data->acc_no}</p>" : '';
        $account->acc_details   !== $data->acc_details ? $histData['acc_details'] = "<p class='text-danger'>{$account->acc_details}</p><p class='text-success'>{$data->acc_details}</p>" : '';

        DB::transaction(function () use ($id, $data, $account, $histData) {
            $account->update(
                [
                    'name'          => $data->name,
                    'acc_no'        => $data->acc_no,
                    'acc_details'   => $data->acc_details
                ]
            );
            AccountActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.accounts.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            Account::find($id)->delete();
            AccountActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return create_response(__('customValidations.accounts.delete'));
    }

    /**
     * Change Status the specified Field
     */
    public function change_status(AccountChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? '<p class="text-danger">Deactive</p><p class="text-success">Active</p>' : '<p class="text-danger">Active</p><p class="text-success">Deactive</p>';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                Account::find($id)->update(['status' => $status]);
                AccountActionHistory::create(self::setActionHistory($id, 'update', ['status' => $changeStatus]));
            }
        );

        return create_response(__('customValidations.accounts.status'));
    }

    /**
     * Get all active Account
     */
    public function get_active_accounts()
    {
        $accounts = Account::where('status', true)
            ->get(['id', 'name', 'balance', 'is_default']);

        return create_response(null, $accounts);
    }

    /**
     * Get all transaction lists
     */
    public function get_all_transactions($account_id = null)
    {
        $dateRange = Helper::getDateRange(request('date_range'));
        $incomes = Income::with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->whereBetween('date', $dateRange)
            ->when($account_id, function ($query) use ($account_id) {
                $query->where('account_id', $account_id);
            })
            ->select(
                'id',
                DB::raw("'income' as type"),
                'account_id',
                'income_category_id as category_id',
                'amount',
                'previous_balance',
                'balance',
                'description',
                'date',
                'creator_id',
                'created_at',
                'updated_at'
            );

        $expenses = Expense::with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->whereBetween('date', $dateRange)
            ->when($account_id, function ($query) use ($account_id) {
                $query->where('account_id', $account_id);
            })
            ->select(
                'id',
                DB::raw("'expense' as type"),
                'account_id',
                'expense_category_id as category_id',
                'amount',
                'previous_balance',
                'balance',
                'description',
                'date',
                'creator_id',
                'created_at',
                'updated_at'
            );

        $withdrawals = AccountWithdrawal::with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->whereBetween('date', $dateRange)
            ->when($account_id, function ($query) use ($account_id) {
                $query->where('account_id', $account_id);
            })
            ->select(
                'id',
                DB::raw("'withdrawal' as type"),
                'account_id',
                DB::raw("NULL as category_id"),
                'amount',
                'previous_balance',
                'balance',
                'description',
                'date',
                'creator_id',
                'created_at',
                'updated_at'
            );

        $transfers = AccountTransfer::with('Author:id,name')
            ->with('TxAccount:id,name,is_default')
            ->with('RxAccount:id,name,is_default')
            ->whereBetween('date', $dateRange)
            ->when($account_id, function ($query) use ($account_id) {
                $query->where('tx_acc_id', $account_id)
                    ->orWhere('rx_acc_id', $account_id);
            })
            ->get();

        $send_money     = [];
        $received_money = [];
        foreach ($transfers as $transfer) {
            if ($account_id) {
                if ($account_id == $transfer->tx_acc_id) {
                    $send_money[] = self::set_send_money_transaction($transfer);
                }
                if ($account_id == $transfer->rx_acc_id) {
                    $received_money[] = self::set_received_money_transaction($transfer);
                }
            } else {
                $send_money[]       = self::set_send_money_transaction($transfer);
                $received_money[]   = self::set_received_money_transaction($transfer);
            }
        }

        $transactions = $incomes
            ->unionAll($expenses)
            ->unionAll($withdrawals)
            ->orderBy('created_at', 'DESC')
            ->get();

        // Transform the data
        $transactions = $transactions->map(function ($transaction) {
            if ($transaction->type == 'income') {
                $transaction->category = IncomeCategory::find($transaction->category_id, ['id', 'name', 'is_default']);
            } elseif ($transaction->type == 'expense') {
                $transaction->category = ExpenseCategory::find($transaction->category_id, ['id', 'name', 'is_default']);
            } else {
                $transaction->category = null;
            }

            unset($transaction->category_id);
            return $transaction;
        });

        $transactions = collect($transactions)
            ->merge($send_money)
            ->merge($received_money)
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return create_response(null, $transactions);
    }

    /**
     * Set Send money transaction data object
     */
    private static function set_send_money_transaction($transfer)
    {
        return (object) [
            'id'                => $transfer->id,
            'type'              => 'send_money',
            'account_id'        => $transfer->tx_acc_id,
            'amount'            => $transfer->amount,
            'previous_balance'  => $transfer->tx_prev_balance,
            'balance'           => $transfer->tx_balance,
            'description'       => $transfer->description,
            'date'              => $transfer->date,
            'creator_id'        => $transfer->creator_id,
            'created_at'        => $transfer->created_at,
            'updated_at'        => $transfer->updated_at,
            'account'           => $transfer->TxAccount,
            'author'            => $transfer->author,
        ];
    }

    /**
     * Set Received money transaction data object
     */
    private static function set_received_money_transaction($transfer)
    {
        return (object) [
            'id'                => $transfer->id,
            'type'              => 'received_money',
            'account_id'        => $transfer->rx_acc_id,
            'amount'            => $transfer->amount,
            'previous_balance'  => $transfer->rx_prev_balance,
            'balance'           => $transfer->rx_balance,
            'description'       => $transfer->description,
            'date'              => $transfer->date,
            'creator_id'        => $transfer->creator_id,
            'created_at'        => $transfer->created_at,
            'updated_at'        => $transfer->updated_at,
            'account'           => $transfer->RxAccount,
            'author'            => $transfer->author,
        ];
    }
}
