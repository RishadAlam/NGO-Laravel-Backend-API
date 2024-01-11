<?php

namespace App\Http\Controllers\Collections;

use Carbon\Carbon;
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
use App\Http\Requests\collection\SavingCollectionApprovedRequest;

class SavingCollectionController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:regular_saving_collection_list_view|regular_saving_collection_list_view_as_admin')->only(['regularCategoryReport', 'regularFieldReport', 'regularCollectionSheet']);
        $this->middleware('permission:pending_saving_collection_list_view|pending_saving_collection_list_view_as_admin')->only(['pendingCategoryReport', 'pendingFieldReport', 'pendingCollectionSheet']);
        $this->middleware('can:permission_to_do_saving_collection')->only('store');
        $this->middleware('permission:regular_saving_collection_update|pending_saving_collection_update')->only('update');
        $this->middleware('permission:regular_saving_collection_permanently_delete|pending_saving_collection_permanently_delete')->only('permanently_destroy');
        $this->middleware('permission:regular_loan_collection_approval|pending_saving_collection_approval')->only('approved');
    }

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
        $categoryReport = Category::categorySavingReport()
            ->get(['id', 'name', 'is_default']);

        return response([
            'success'   => true,
            'data'      => $categoryReport
        ], 200);
    }

    /**
     * Pending Category Report
     */
    public function pendingCategoryReport()
    {
        $categoryReport = Category::categorySavingReport(false)
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
        $fieldReport = Field::fieldSavingReport($category_id)->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $fieldReport
        ], 200);
    }

    /**
     * Pending Field Report
     */
    public function pendingFieldReport($category_id)
    {
        $fieldReport = Field::fieldSavingReport($category_id, false)->get(['id', 'name']);

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
        $collections = Center::savingCollectionSheet($category_id, $field_id, request('user_id'))->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $collections
        ], 200);
    }

    /**
     * Pending Collection Sheet
     */
    public function pendingCollectionSheet($category_id, $field_id)
    {
        $collections = Center::savingCollectionSheet(
            $category_id,
            $field_id,
            request('user_id'),
            false,
            request('date') ? Carbon::parse(request('date'))->format('y-m-d') : Carbon::yesterday()->format('y-m-d')
        )
            ->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $collections
        ], 200);
    }

    /**
     * Approved Collections
     */
    public function approved(SavingCollectionApprovedRequest $request)
    {
        $approvedList = $request->validated()['approvedList'];
        $collections = SavingCollection::whereIn('id', $approvedList)
            ->get(['id', 'saving_account_id', 'account_id', 'deposit', 'installment']);

        DB::transaction(function () use ($collections, $approvedList) {
            SavingCollection::whereIn('id', $approvedList)
                ->update(['is_approved' => true, 'approved_by' => auth()->id()]);

            foreach ($collections as  $collection) {
                SavingAccount::find($collection->saving_account_id)
                    ->incrementEach([
                        'total_installment' => $collection->installment,
                        'total_deposited'   => $collection->deposit,
                    ]);
                Account::find($collection->account_id)
                    ->increment('total_deposit', $collection->deposit);
            }
        });

        return create_response(__('customValidations.client.collection.approved'));
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
