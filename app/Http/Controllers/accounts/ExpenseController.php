<?php

namespace App\Http\Controllers\accounts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\accounts\ExpenseActionHistory;
use App\Http\Requests\accounts\ExpenseStoreRequest;
use App\Http\Requests\accounts\ExpenseUpdateRequest;

class ExpenseController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:expense_list_view')->only('index');
        $this->middleware('can:expense_registration')->only('store');
        $this->middleware('can:expense_data_update')->only(['update']);
        $this->middleware('can:expense_soft_delete')->only('destroy');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "expense_id"        => $id,
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

        $expenses = Expense::with('ExpenseCategory:id,name,is_default')
            ->with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->with(['ExpenseActionHistory', 'ExpenseActionHistory.Author:id,name,image_uri'])
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        return response(
            [
                'success'   => true,
                'data'      => $expenses
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExpenseStoreRequest $request)
    {
        $data = (object) $request->validated();

        DB::transaction(function () use ($data) {
            Expense::create(
                [
                    'account_id'            => $data->account_id,
                    'expense_category_id'   => $data->expense_category_id,
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

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense.successful'),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpenseUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $expense    = Expense::with('ExpenseCategory:id,name,is_default')->find($id);
        $histData   = [];
        $date       = Carbon::parse($data->date)->setTimezone('+06:00')->toIso8601String();
        $amountDef  = $data->amount - $expense->amount;
        $oldDate    = date('d/m/Y', strtotime($expense->date));
        $newDate    = date('d/m/Y', strtotime($date));
        $oldCat     = $expense->ExpenseCategory->is_default ? __("customValidations.expense_category.default.{$expense->ExpenseCategory->name}") : $expense->ExpenseCategory->name;
        $newCat     = $data->category['is_default'] ? __("customValidations.expense_category.default.{$data->category['name']}") : $data->category['name'];

        $expense->expense_category_id   !== $data->expense_category_id ? $histData['category'] = "<p class='text-danger'>{$oldCat}</p><p class='text-success'>{$newCat}</p>" : '';
        $expense->amount                !== $data->amount ? $histData['amount'] = "<p class='text-danger'>{$expense->amount}</p><p class='text-success'>{$data->amount}</p>" : '';
        $expense->previous_balance      !== $data->previous_balance ? $histData['previous_balance'] = "<p class='text-danger'>{$expense->previous_balance}</p><p class='text-success'>{$data->previous_balance}</p>" : '';
        $expense->balance               !== $data->balance ? $histData['balance'] = "<p class='text-danger'>{$expense->balance}</p><p class='text-success'>{$data->balance}</p>" : '';
        $expense->description           !== $data->description ? $histData['description'] = "<p class='text-danger'>{$expense->description}</p><p class='text-success'>{$data->description}</p>" : '';
        $expense->date                  !== $data->date ? $histData['date'] = "<p class='text-danger'>{$oldDate}</p><p class='text-success'>{$newDate}</p>" : '';

        DB::transaction(function () use ($id, $data, $expense, $amountDef, $histData, $date) {
            $expense->update(
                [
                    'expense_category_id'   => $data->expense_category_id,
                    'amount'                => $data->amount,
                    'previous_balance'      => $data->previous_balance,
                    'description'           => $data->description ?? null,
                    'date'                  => $date
                ]
            );

            if ($amountDef) {
                Account::find($expense->account_id)
                    ->increment('total_withdrawal', $amountDef);
            }
            ExpenseActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense.update')
            ],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $expense = Expense::find($id);
            Account::find($expense->account_id)
                ->increment('total_withdrawal', $expense->amount);
            $expense->delete();
            ExpenseActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense.delete')
            ],
            200
        );
    }
}
