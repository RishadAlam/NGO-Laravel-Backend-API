<?php

namespace App\Http\Controllers\accounts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\accounts\Account;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\accounts\AccountTransfer;
use App\Http\Requests\accounts\AccountTransferStoreRequest;

class AccountTransferController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:account_transfer_list_view')->only('index');
        $this->middleware('can:account_transfer_registration')->only('store');
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

        $account_transfers = AccountTransfer::with('Author:id,name')
            ->with('TxAccount:id,name,is_default')
            ->with('RxAccount:id,name,is_default')
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        $transfers = [];
        foreach ($account_transfers as $transfer) {
            $transfers[] = (object) [
                'id'                    => $transfer->id,
                'type'                  => 'send_money',
                'account_id'            => $transfer->tx_acc_id,
                'amount'                => $transfer->amount,
                'previous_balance'      => $transfer->tx_prev_balance,
                'balance'               => $transfer->tx_balance,
                'description'           => $transfer->description,
                'date'                  => $transfer->date,
                'creator_id'            => $transfer->creator_id,
                'created_at'            => $transfer->created_at,
                'updated_at'            => $transfer->updated_at,
                'author'                => $transfer->author,
                'account'               => $transfer->TxAccount,
                'transaction_account'   => $transfer->RxAccount,
            ];
            $transfers[] = (object) [
                'id'                    => $transfer->id,
                'type'                  => 'received_money',
                'account_id'            => $transfer->rx_acc_id,
                'amount'                => $transfer->amount,
                'previous_balance'      => $transfer->rx_prev_balance,
                'balance'               => $transfer->rx_balance,
                'description'           => $transfer->description,
                'date'                  => $transfer->date,
                'creator_id'            => $transfer->creator_id,
                'created_at'            => $transfer->created_at,
                'updated_at'            => $transfer->updated_at,
                'author'                => $transfer->author,
                'account'               => $transfer->RxAccount,
                'transaction_account'   => $transfer->TxAccount,
            ];
        }

        return create_response(null, $transfers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountTransferStoreRequest $request)
    {
        $data = (object) $request->validated();
        DB::transaction(function () use ($data) {
            AccountTransfer::create(
                [
                    'tx_acc_id'         => $data->tx_acc_id,
                    'rx_acc_id'         => $data->rx_acc_id,
                    'amount'            => $data->amount,
                    'tx_prev_balance'   => $data->tx_prev_balance,
                    'rx_prev_balance'   => $data->rx_prev_balance,
                    'description'       => $data->description ?? null,
                    'date'              => Carbon::parse($data->date),
                    'creator_id'        => auth()->id()
                ]
            );

            Account::find($data->tx_acc_id)
                ->increment('total_withdrawal', $data->amount);
            Account::find($data->rx_acc_id)
                ->increment('total_deposit', $data->amount);
        });

        return create_response(__('customValidations.account_transfer.successful'));
    }
}
