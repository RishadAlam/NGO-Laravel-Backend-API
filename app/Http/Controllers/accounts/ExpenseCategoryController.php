<?php

namespace App\Http\Controllers\accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\accounts\ExpenseCategoryChangeStatusRequest;
use App\Http\Requests\accounts\ExpenseCategoryStoreRequest;
use App\Http\Requests\accounts\ExpenseCategoryUpdateRequest;
use App\Models\accounts\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:income_category_list_view')->only('index');
        $this->middleware('can:income_category_registration')->only('store');
        $this->middleware('can:income_category_data_update')->only(['update', 'change_status']);
        $this->middleware('can:income_category_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ExpenseCategory::with('Author:id,name')
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
    public function store(ExpenseCategoryStoreRequest $request)
    {
        $data = (object) $request->validated();
        ExpenseCategory::create(
            [
                'name'          => $data->name,
                'description'   => $data->description ?? null,
                'creator_id'    => auth()->id(),
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense_category.successful'),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpenseCategoryUpdateRequest $request, string $id)
    {
        $data = (object) $request->validated();
        ExpenseCategory::find($id)
            ->update([
                'name'          => $data->name,
                'description'   => $data->description ?? null,
            ]);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense_category.update')
            ],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ExpenseCategory::find($id)->delete();
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense_category.delete')
            ],
            200
        );
    }

    /**
     * Change Status the specified Field
     */
    public function change_status(ExpenseCategoryChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        ExpenseCategory::find($id)->update(['status' => $status]);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.expense_category.status')
            ],
            200
        );
    }

    /**
     * Get all active Categories
     */
    public function get_active_categories()
    {
        $categories = ExpenseCategory::where('status', true)
            ->get(['id', 'name', 'is_default']);

        return response(
            [
                'success'   => true,
                'data'      => $categories
            ],
            200
        );
    }
}
