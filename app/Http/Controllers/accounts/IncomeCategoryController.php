<?php

namespace App\Http\Controllers\accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\accounts\IncomeCategoryChangeStatusRequest;
use App\Http\Requests\accounts\IncomeCategoryStoreRequest;
use App\Http\Requests\accounts\IncomeCategoryUpdateRequest;
use App\Models\accounts\IncomeCategory;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:expense_category_list_view')->only('index');
        $this->middleware('can:expense_category_registration')->only('store');
        $this->middleware('can:expense_category_data_update')->only(['update', 'change_status']);
        $this->middleware('can:expense_category_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = IncomeCategory::with('Author:id,name')
            ->get();

        return response(
            [
                'success'   => true,
                'data'      => $categories
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(IncomeCategoryStoreRequest $request)
    {
        $data = (object) $request->validated();
        IncomeCategory::create(
            [
                'name'          => $data->name,
                'description'   => $data->description ?? null,
                'creator_id'    => auth()->id(),
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income_category.successful'),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(IncomeCategoryUpdateRequest $request, string $id)
    {
        $data = (object) $request->validated();
        IncomeCategory::find($id)
            ->update([
                'name'          => $data->name,
                'description'   => $data->description ?? null,
            ]);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income_category.update')
            ],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        IncomeCategory::find($id)->delete();
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income_category.delete')
            ],
            200
        );
    }

    /**
     * Change Status the specified Field
     */
    public function change_status(IncomeCategoryChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        IncomeCategory::find($id)->update(['status' => $status]);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.income_category.status')
            ],
            200
        );
    }

    /**
     * Get all active Categories
     */
    public function get_active_categories()
    {
        $categories = IncomeCategory::where('status', true)
            ->get(['id', 'name']);

        return response(
            [
                'success'   => true,
                'data'      => $categories
            ],
            200
        );
    }
}
