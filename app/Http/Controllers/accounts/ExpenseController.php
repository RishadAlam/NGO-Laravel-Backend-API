<?php

namespace App\Http\Controllers\accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\accounts\ExpenseStoreRequest;
use App\Http\Requests\accounts\ExpenseUpdateRequest;
use App\Models\accounts\Expense;
use App\Models\accounts\ExpenseActionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $expenses = Expense::with('ExpenseCategory:id,name,is_default')
            ->with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->with(['ExpenseActionHistory', 'ExpenseActionHistory.Author:id,name,image_uri'])
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
        Expense::create(
            [
                'account_id'            => $data->account_id,
                'expense_category_id'   => $data->expense_category_id,
                'amount'                => $data->amount,
                'amount'                => $data->amount,
                'previous_balance'      => $data->previous_balance,
                'description'           => $data->description ?? null,
                'date'                  => $data->date,
                'creator_id'            => auth()->id()
            ]
        );

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
        $expense    = Expense::find($id);
        $histData   = [];

        $expense->amount         !== $data->amount ? $histData['amount'] = "<p class='text-danger'>{$expense->amount}</p><p class='text-success'>{$data->amount}</p>" : '';
        $expense->description    !== $data->description ? $histData['description'] = "<p class='text-danger'>{$expense->description}</p><p class='text-success'>{$data->description}</p>" : '';
        $expense->date           !== $data->date ? $histData['date'] = "<p class='text-danger'>{$expense->date}</p><p class='text-success'>{$data->date}</p>" : '';

        DB::transaction(function () use ($id, $data, $expense, $histData) {
            $expense->update(
                [
                    'expense_category_id'   => $data->expense_category_id,
                    'amount'                => $data->amount,
                    'previous_balance'      => $data->previous_balance,
                    'description'           => $data->description ?? null,
                    'date'                  => $data->date,
                ]
            );
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
            Expense::find($id)->delete();
            ExpenseActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income.delete')
            ],
            200
        );
    }
}
