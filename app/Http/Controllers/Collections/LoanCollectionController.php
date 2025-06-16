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
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Collections\LoanCollection;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use App\Models\Collections\LoanCollectionActionHistory;
use App\Http\Requests\collection\LoanCollectionStoreRequest;
use App\Http\Requests\collection\LoanCollectionUpdateRequest;
use App\Http\Requests\collection\LoanCollectionApprovedRequest;

class LoanCollectionController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:regular_loan_collection_list_view|regular_loan_collection_list_view_as_admin')->only(['regularCategoryReport', 'regularFieldReport', 'regularCollectionSheet']);
        $this->middleware('permission:pending_loan_collection_list_view|pending_loan_collection_list_view_as_admin')->only(['pendingCategoryReport', 'pendingFieldReport', 'pendingCollectionSheet']);
        $this->middleware('can:permission_to_do_loan_collection')->only('store');
        $this->middleware('permission:regular_loan_collection_update|pending_loan_collection_update')->only('update');
        $this->middleware('permission:regular_loan_collection_permanently_delete|pending_loan_collection_permanently_delete')->only('permanently_destroy');
        $this->middleware('permission:regular_loan_collection_approval|pending_loan_collection_approval')->only('approved');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (empty(request('loan_account_id'))) {
            return create_response(__('customValidations.common.somethingWentWrong'), null, 401, false);
        }

        $dateRange = Helper::getDateRange(request('date_range'));
        $collections = LoanCollection::where('loan_account_id', request('loan_account_id'))
            ->whereBetween('created_at', $dateRange)
            ->approve()
            ->field('id', 'name',)
            ->center('id', 'name',)
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->account('id', 'name', 'is_default')
            ->approver('id', 'name')
            ->orderedBy('id', 'DESC')
            ->get();

        return create_response(null, $collections);
    }

    /**
     * Show the specified resource from storage.
     */
    public function show(string $id)
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
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now('Asia/Dhaka')
            ];

            DB::transaction(function () use ($field_map, $data) {
                LoanCollection::create($field_map);
                $loanAccount = LoanAccount::find($data->loan_account_id);
                if ($loanAccount) {
                    $loanAccount->increment('total_rec_installment', $data->installment);
                    $loanAccount->increment('total_deposited', $data->deposit);
                    $loanAccount->increment('total_loan_rec', $data->loan);
                    $loanAccount->increment('total_interest_rec', $data->interest);
                }
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
    public function update(LoanCollectionUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $collection = LoanCollection::find($id);
        $histData   = self::set_update_hist($data, $collection);

        DB::transaction(
            function () use ($id, $collection, $data, $histData) {
                $collection->update(self::set_field_map($data));
                LoanCollectionActionHistory::create(Helper::setActionHistory('loan_collection_id', $id, 'update', $histData));
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
        LoanCollection::find($id)->forceDelete();
        return create_response(__('customValidations.client.collection.p_delete'));
    }

    /**
     * Regular Category Report
     */
    public function regularCategoryReport()
    {
        $categoryReport = Category::categoryLoanReport()->get(['id', 'name', 'is_default']);
        return create_response(null, $categoryReport);
    }

    /**
     * Pending Category Report
     */
    public function pendingCategoryReport()
    {
        $categoryReport = Category::categoryLoanReport(false)->get(['id', 'name', 'is_default']);
        return create_response(null, $categoryReport);
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
                ->update(['is_approved' => true, 'approved_by' => auth()->id(), 'approved_at' => Carbon::now('Asia/Dhaka')]);

            foreach ($collections as  $collection) {
                $loanAccount = LoanAccount::find($collection->loan_account_id);

                if ($loanAccount) {
                    $loanAccount->increment('total_rec_installment', $collection->installment);
                    $loanAccount->increment('total_deposited', $collection->deposit);
                    $loanAccount->increment('total_loan_rec', $collection->loan);
                    $loanAccount->increment('total_interest_rec', $collection->interest);
                }
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
        $fieldReport = Field::fieldLoanReport($category_id)->get(['id', 'name']);
        return create_response(null, $fieldReport);
    }

    /**
     * Pending Field Report
     */
    public function pendingFieldReport($category_id)
    {
        $fieldReport = Field::fieldLoanReport($category_id, false)->get(['id', 'name']);
        return create_response(null, $fieldReport);
    }

    /**
     * Regular Collection Sheet
     */
    public function regularCollectionSheet($category_id, $field_id)
    {
        $collections = Center::loanCollectionSheet($category_id, $field_id, request('user_id'))->get(['id', 'name']);
        return create_response(null, $collections);
    }

    /**
     * Pending Collection Sheet
     */
    public function pendingCollectionSheet($category_id, $field_id)
    {
        $collections = Center::loanCollectionSheet(
            $category_id,
            $field_id,
            request('user_id'),
            false,
            request('date') ? Carbon::parse(request('date'))->format('y-m-d') : Carbon::yesterday()->format('y-m-d')
        )->get(['id', 'name']);

        return create_response(null, $collections);
    }

    /**
     * Set Loan Collection update hist
     *
     * @param object $data
     * @param object $collection
     *
     * @return array
     */
    private static function set_update_hist($data, $collection)
    {
        $histData           = [];
        $fieldsToCompare    = ['installment', 'deposit', 'loan', 'interest', 'total', 'description'];

        foreach ($fieldsToCompare as $field) {
            $clientValue    = $collection->{$field} ?? '';
            $dataValue      = $data->{$field} ?? '';

            if (!Helper::areValuesEqual($clientValue, $dataValue)) {
                $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>";
            }
        }

        return $histData;
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
