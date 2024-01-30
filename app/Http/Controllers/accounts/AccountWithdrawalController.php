<?php

namespace App\Http\Controllers\accounts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\accounts\Account;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\accounts\AccountWithdrawal;
use App\Models\accounts\AccountWithdrawalActionHistory;
use App\Http\Requests\accounts\AccountWithdrawalStoreRequest;
use App\Http\Requests\accounts\AccountWithdrawalUpdateRequest;

class AccountWithdrawalController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:account_withdrawal_list_view')->only('index');
        $this->middleware('can:account_withdrawal_registration')->only('store');
        $this->middleware('can:account_withdrawal_data_update')->only(['update']);
        $this->middleware('can:account_withdrawal_soft_delete')->only('destroy');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "withdrawal_id"     => $id,
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
        if (request('date_range')) {
            $date_range = json_decode(request('date_range'));
            $start_date = Carbon::parse($date_range[0])->startOfDay();
            $end_date   = Carbon::parse($date_range[1])->endOfDay();
        } else {
            $start_date = Carbon::now()->startOfMonth();
            $end_date   = Carbon::now()->endOfDay();
        }

        $withdrawals = AccountWithdrawal::with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->with(['AccountWithdrawalActionHistory', 'AccountWithdrawalActionHistory.Author:id,name,image_uri'])
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        return create_response(null, $withdrawals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountWithdrawalStoreRequest $request)
    {
        $data = (object) $request->validated();

        DB::transaction(function () use ($data) {
            AccountWithdrawal::create(
                [
                    'account_id'            => $data->account_id,
                    'amount'                => $data->amount,
                    'amount'                => $data->amount,
                    'previous_balance'      => $data->previous_balance,
                    'description'           => $data->description ?? null,
                    'date'                  => $data->date,
                    'creator_id'            => auth()->id()
                ]
            );

            Account::find($data->account_id)
                ->increment('total_withdrawal', $data->amount);
        });

        return create_response(__('customValidations.account_withdrawal.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountWithdrawalUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $withdrawal = AccountWithdrawal::find($id);
        $histData   = [];
        $date       = Carbon::parse($data->date)->setTimezone('+06:00')->toIso8601String();
        $amountDef  = $data->amount - $withdrawal->amount;
        $oldDate    = date('d/m/Y', strtotime($withdrawal->date));
        $newDate    = date('d/m/Y', strtotime($date));

        $withdrawal->amount                !== $data->amount ? $histData['amount'] = "<p class='text-danger'>{$withdrawal->amount}</p><p class='text-success'>{$data->amount}</p>" : '';
        $withdrawal->previous_balance      !== $data->previous_balance ? $histData['previous_balance'] = "<p class='text-danger'>{$withdrawal->previous_balance}</p><p class='text-success'>{$data->previous_balance}</p>" : '';
        $withdrawal->balance               !== $data->balance ? $histData['balance'] = "<p class='text-danger'>{$withdrawal->balance}</p><p class='text-success'>{$data->balance}</p>" : '';
        $withdrawal->description           !== $data->description ? $histData['description'] = "<p class='text-danger'>{$withdrawal->description}</p><p class='text-success'>{$data->description}</p>" : '';
        $withdrawal->date                  !== $data->date ? $histData['date'] = "<p class='text-danger'>{$oldDate}</p><p class='text-success'>{$newDate}</p>" : '';

        DB::transaction(function () use ($id, $data, $withdrawal, $amountDef, $histData, $date) {
            $withdrawal->update(
                [
                    'amount'                => $data->amount,
                    'previous_balance'      => $data->previous_balance,
                    'description'           => $data->description ?? null,
                    'date'                  => $date,
                ]
            );

            if ($amountDef) {
                Account::find($withdrawal->account_id)
                    ->increment('total_withdrawal', $amountDef);
            }
            AccountWithdrawalActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.account_withdrawal.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $withdrawal = AccountWithdrawal::find($id);
            Account::find($withdrawal->account_id)
                ->increment('total_withdrawal', $withdrawal->amount);
            $withdrawal->delete();
            AccountWithdrawalActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return create_response(__('customValidations.account_withdrawal.delete'));
    }
}
