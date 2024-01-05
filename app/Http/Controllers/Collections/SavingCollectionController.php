<?php

namespace App\Http\Controllers\Collections;

use App\Helpers\Helper;
use App\Models\AppConfig;
use App\Models\field\Field;
use Illuminate\Http\Request;
use App\Models\center\Center;
use App\Models\accounts\Account;
use App\Models\category\Category;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\Collections\SavingCollection;
use App\Models\Collections\SavingCollectionActionHistory;
use App\Http\Requests\collection\SavingCollectionStoreRequest;
use App\Http\Requests\collection\SavingCollectionUpdateRequest;

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
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::get_config('saving_collection_approval');
        $field_map      = self::set_field_map($data, true);

        if ($is_approved) {
            $field_map += [
                'is_approved' => $is_approved,
                'approved_by' => auth()->id()
            ];

            DB::transaction(function () use ($field_map, $data) {
                SavingCollection::create($field_map);
                SavingAccount::find($data->saving_account_id)
                    ->incrementEach([
                        'total_installment' => $data->installment,
                        'total_deposited'   => $data->deposit,
                    ]);
                Account::find($data->account_id)
                    ->increment('total_deposit', $data->deposit);
            });
        } else {
            SavingCollection::create($field_map);
        }

        return create_response(__('customValidations.client.collection.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SavingCollectionUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $collection = SavingCollection::find($id);
        $histData   = self::set_update_hist($data, $collection);

        DB::transaction(
            function () use ($id, $collection, $data, $histData) {
                $collection->update(self::set_field_map($data));
                SavingCollectionActionHistory::create(Helper::setActionHistory('saving_collection_id', $id, 'update', $histData));
            }
        );

        return create_response(__('customValidations.client.collection.update'));
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

    /**
     * Set Saving Collection update hist
     * 
     * @param object $data
     * @param object $collection
     * 
     * @return array
     */
    private static function set_update_hist($data, $collection)
    {
        $histData           = [];
        $fieldsToCompare    = ['installment', 'deposit', 'description'];

        foreach ($fieldsToCompare as $field) {
            $clientValue    = $collection->{$field} ?? '';
            $dataValue      = $data->{$field} ?? '';
            !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
        }

        return $histData;
    }

    /**
     * Set Saving Collection Field Map
     * 
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     * 
     * @return array
     */
    private static function set_field_map($data, $new_collection = false)
    {
        $map = [
            'installment'               => $data->installment,
            'deposit'                   => $data->deposit
        ];

        if (!empty($data->description) && $data->description != 'null') {
            $map['description'] = $data->description;
        }
        if ($new_collection) {
            $map += [
                'field_id'                  => $data->field_id,
                'center_id'                 => $data->center_id,
                'category_id'               => $data->category_id,
                'saving_account_id'         => $data->saving_account_id,
                'client_registration_id'    => $data->client_registration_id,
                'account_id'                => $data->account_id,
                'acc_no'                    => $data->acc_no,
                'creator_id'                => auth()->id()
            ];
        }

        return $map;
    }
}
