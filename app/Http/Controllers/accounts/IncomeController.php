<?php

namespace App\Http\Controllers\accounts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\accounts\IncomeActionHistory;
use App\Http\Requests\accounts\IncomeStoreRequest;
use App\Http\Requests\accounts\IncomeUpdateRequest;

class IncomeController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:income_list_view')->only('index');
        $this->middleware('can:income_registration')->only('store');
        $this->middleware('can:income_data_update')->only(['update']);
        $this->middleware('can:income_soft_delete')->only('destroy');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "income_id"         => $id,
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

        $incomes = Income::with('IncomeCategory:id,name,is_default')
            ->with('Account:id,name,is_default')
            ->with('Author:id,name')
            ->with(['IncomeActionHistory', 'IncomeActionHistory.Author:id,name,image_uri'])
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        return create_response(null, $incomes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(IncomeStoreRequest $request)
    {
        $data = (object) $request->validated();
        DB::transaction(function () use ($data) {
            Income::create(
                [
                    'account_id'            => $data->account_id,
                    'income_category_id'    => $data->income_category_id,
                    'amount'                => $data->amount,
                    'previous_balance'      => $data->previous_balance,
                    'description'           => $data->description ?? null,
                    'date'                  => $data->date,
                    'creator_id'            => auth()->id()
                ]
            );

            Account::find($data->account_id)
                ->increment('total_deposit', $data->amount);
        });

        return create_response(__('customValidations.income.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(IncomeUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $income     = Income::with('IncomeCategory:id,name,is_default')->find($id);
        $histData   = [];
        $date       = Carbon::parse($data->date)->setTimezone('+06:00')->toIso8601String();
        $amountDef  = $data->amount - $income->amount;
        $incomeDate = date('d/m/Y', strtotime($income->date));
        $newDate    = date('d/m/Y', strtotime($date));
        $oldCat     = $income->IncomeCategory->is_default ? __("customValidations.income_category.default.{$income->IncomeCategory->name}") : $income->IncomeCategory->name;
        $newCat     = $data->category['is_default'] ? __("customValidations.income_category.default.{$data->category['name']}") : $data->category['name'];

        $income->income_category_id !== $data->income_category_id ? $histData['category'] = "<p class='text-danger'>{$oldCat}</p><p class='text-success'>{$newCat}</p>" : '';
        $income->amount             !== $data->amount ? $histData['amount'] = "<p class='text-danger'>{$income->amount}</p><p class='text-success'>{$data->amount}</p>" : '';
        $income->previous_balance   !== $data->previous_balance ? $histData['previous_balance'] = "<p class='text-danger'>{$income->previous_balance}</p><p class='text-success'>{$data->previous_balance}</p>" : '';
        $income->balance            !== $data->balance ? $histData['balance'] = "<p class='text-danger'>{$income->balance}</p><p class='text-success'>{$data->balance}</p>" : '';
        $income->description        !== $data->description ? $histData['description'] = "<p class='text-danger'>{$income->description}</p><p class='text-success'>{$data->description}</p>" : '';
        $incomeDate                 !== $newDate ? $histData['date'] = "<p class='text-danger'>{$incomeDate}</p><p class='text-success'>{$newDate}</p>" : '';

        DB::transaction(function () use ($id, $data, $income, $amountDef, $histData, $date) {
            $income->update(
                [
                    'income_category_id'    => $data->income_category_id,
                    'amount'                => $data->amount,
                    'description'           => $data->description ?? null,
                    'date'                  => $date,
                ]
            );

            if ($amountDef) {
                Account::find($income->account_id)
                    ->increment('total_deposit', $amountDef);
            }
            IncomeActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.income.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $income = Income::find($id);
            Account::find($income->account_id)
                ->decrement('total_deposit', $income->amount);
            $income->delete();
            IncomeActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return create_response(__('customValidations.income.delete'));
    }
}
