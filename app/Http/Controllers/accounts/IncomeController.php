<?php

namespace App\Http\Controllers\accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\accounts\IncomeStoreRequest;
use App\Http\Requests\accounts\IncomeUpdateRequest;
use App\Models\accounts\Income;
use App\Models\accounts\IncomeActionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
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
        $incomes = Income::with('IncomeCategory:id,name')
            ->with('Author:id,name')
            ->with(['IncomeActionHistory', 'IncomeActionHistory.Author:id,name,image_uri'])
            ->get();

        return response(
            [
                'success'   => true,
                'data'      => $incomes
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(IncomeStoreRequest $request)
    {
        $data = (object) $request->validated();
        Income::create(
            [
                'income_category_id'    => $data->income_category_id,
                'amount'                => $data->amount,
                'description'           => $data->description ?? null,
                'date'                  => $data->date,
                'creator_id'            => auth()->id()
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income.successful'),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(IncomeUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $income     = Income::find($id);
        $histData   = [];

        $income->amount         !== $data->amount ? $histData['amount'] = "<p class='text-danger'>{$income->amount}</p><p class='text-success'>{$data->amount}</p>" : '';
        $income->description    !== $data->description ? $histData['description'] = "<p class='text-danger'>{$income->description}</p><p class='text-success'>{$data->description}</p>" : '';
        $income->date           !== $data->date ? $histData['date'] = "<p class='text-danger'>{$income->date}</p><p class='text-success'>{$data->date}</p>" : '';

        DB::transaction(function () use ($id, $data, $income, $histData) {
            $income->update(
                [
                    'income_category_id'    => $data->income_category_id,
                    'amount'                => $data->amount,
                    'description'           => $data->description ?? null,
                    'date'                  => $data->date,
                ]
            );
            IncomeActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income.update')
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
            Income::find($id)->delete();
            IncomeActionHistory::create(self::setActionHistory($id, 'delete', []));
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
