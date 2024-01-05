<?php

namespace App\Http\Controllers\Collections;

use App\Models\AppConfig;
use App\Models\field\Field;
use Illuminate\Http\Request;
use App\Models\center\Center;
use App\Models\accounts\Account;
use App\Models\category\Category;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Collections\LoanCollection;
use App\Http\Requests\collection\LoanCollectionStoreRequest;
use App\Http\Requests\collection\LoanCollectionApprovedRequest;

class LoanCollectionController extends Controller
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
    public function store(LoanCollectionStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::get_config('loan_collection_approval');
        $field_map      = self::set_field_map($data, true);

        if ($is_approved) {
            $field_map += [
                'is_approved' => $is_approved,
                'approved_by' => auth()->id()
            ];

            DB::transaction(function () use ($field_map, $data) {
                LoanCollection::create($field_map);
                LoanAccount::find($data->loan_account_id)
                    ->incrementEach([
                        'total_rec_installment' => $data->installment,
                        'total_deposited'       => $data->deposit,
                        'total_loan_rec'        => $data->loan,
                        'total_interest_rec'    => $data->interest,
                    ]);
                Account::find($data->account_id)
                    ->increment('total_deposit', $data->total);
            });
        } else {
            LoanCollection::create($field_map);
        }

        return create_response(__('customValidations.client.collection.successful'));
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
        LoanCollection::find($id)->forceDelete();
        return create_response(__('customValidations.client.collection.p_delete'));
    }

    /**
     * Regular Category Report
     */
    public function regularCategoryReport()
    {
        $categoryReport = Category::RegularCategoryLoanReport()
            ->get(['id', 'name', 'is_default']);

        return response([
            'success'   => true,
            'data'      => $categoryReport
        ], 200);
    }

    /**
     * Approved Collections
     */
    public function approved(LoanCollectionApprovedRequest $request)
    {
        $approvedList = $request->validated()['approvedList'];
        $collections = LoanCollection::whereIn('id', $approvedList)
            ->get(['id', 'loan_account_id', 'account_id', 'deposit', 'loan', 'interest', 'total', 'installment']);

        DB::transaction(function () use ($collections, $approvedList) {
            LoanCollection::whereIn('id', $approvedList)
                ->update(['is_approved' => true, 'approved_by' => auth()->id()]);

            foreach ($collections as  $collection) {
                LoanAccount::find($collection->loan_account_id)
                    ->incrementEach([
                        'total_rec_installment' => $collection->installment,
                        'total_deposited'       => $collection->deposit,
                        'total_loan_rec'        => $collection->loan,
                        'total_interest_rec'    => $collection->interest,
                    ]);
                Account::find($collection->account_id)
                    ->increment('total_deposit', $collection->total);
            }
        });

        return create_response(__('customValidations.client.collection.approved'));
    }

    /**
     * Regular Field Report
     */
    public function regularFieldReport($category_id)
    {
        $fieldReport = Field::regularFieldLoanReport($category_id)->get(['id', 'name']);

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
        $collections = Center::regularLoanCollectionSheet($category_id, $field_id)->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $collections
        ], 200);
    }

    /**
     * Set Loan Collection Field Map
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
            'deposit'                   => $data->deposit,
            'loan'                      => $data->loan,
            'interest'                  => $data->interest,
            'total'                     => $data->total
        ];

        if (!empty($data->description) && $data->description != 'null') {
            $map['description'] = $data->description;
        }
        if ($new_collection) {
            $map += [
                'field_id'                  => $data->field_id,
                'center_id'                 => $data->center_id,
                'category_id'               => $data->category_id,
                'loan_account_id'           => $data->loan_account_id,
                'client_registration_id'    => $data->client_registration_id,
                'account_id'                => $data->account_id,
                'acc_no'                    => $data->acc_no,
                'creator_id'                => auth()->id()
            ];
        }

        return $map;
    }
}
