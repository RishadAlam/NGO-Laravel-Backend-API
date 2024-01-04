<?php

namespace App\Http\Controllers\Collections;

use App\Models\AppConfig;
use App\Models\field\Field;
use Illuminate\Http\Request;
use App\Models\center\Center;
use App\Models\category\Category;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Collections\SavingCollection;
use App\Http\Requests\collection\SavingCollectionStoreRequest;

class SavingCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavingCollectionStoreRequest $request)
    {
        $data = (object) $request->validated();
        $is_approved    = AppConfig::get_config('saving_collection_approval');
        $field_map = [
            'field_id'                  => $data->field_id,
            'center_id'                 => $data->center_id,
            'category_id'               => $data->category_id,
            'saving_account_id'         => $data->saving_account_id,
            'client_registration_id'    => $data->client_registration_id,
            'acc_no'                    => $data->acc_no,
            'installment'               => $data->installment,
            'deposit'                   => $data->deposit,
            'description'               => $data->description ?? null,
            'creator_id'                => auth()->id()

        ];

        if ($is_approved) {
            $field_map['is_approved'] = $is_approved;
            $field_map['approved_by'] = auth()->id();
        }

        SavingCollection::create($field_map);
        return create_response(__('customValidations.client.collection.successful'));
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

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        SavingCollection::find($id)->forceDelete();
        return create_response(__('customValidations.client.collection.p_delete'));
    }

    /**
     * Regular Category Report
     */
    public function regularCategoryReport()
    {
        $categoryReport = Category::regularCategorySavingReport()
            ->get(['id', 'name', 'is_default']);

        return response([
            'success'   => true,
            'data'      => $categoryReport
        ], 200);
    }

    /**
     * Regular Field Report
     */
    public function regularFieldReport($category_id)
    {
        $fieldReport = Field::regularFieldSavingReport($category_id)->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $fieldReport
        ], 200);
    }

    /**
     * Regular Collection Sheet
     */
    public function regularCollectionSheet($category_id, $field_id)
    {
        $collections = Center::regularCollectionSheet($category_id, $field_id)->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $collections
        ], 200);
    }
}
