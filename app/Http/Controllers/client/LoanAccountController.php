<?php

namespace App\Http\Controllers\client;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use App\Models\client\Guarantor;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use App\Models\client\LoanAccountCheck;
use App\Models\accounts\ExpenseCategory;
use App\Models\client\LoanAccountActionHistory;
use App\Http\Requests\client\LoanApprovalRequest;
use App\Http\Requests\client\CategoryUpdateRequest;
use App\Http\Requests\client\LoanAccountStoreRequest;
use App\Http\Requests\client\LoanAccountUpdateRequest;
use App\Http\Requests\client\LoanAccountChangeStatusRequest;

class LoanAccountController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_loan_acc_list_view|pending_loan_acc_list_view_as_admin')->only('pending_forms');
        $this->middleware('permission:pending_loan_view|pending_loan_view_as_admin')->only('pending_loans');
        $this->middleware('can:loan_acc_registration')->only('store');
        $this->middleware('can:pending_loan_acc_update')->only('update');
        $this->middleware('permission:pending_loan_acc_permanently_delete|pending_loan_permanently_delete')->only('permanently_destroy');
        $this->middleware('can:pending_loan_acc_approval')->only('approved');
        $this->middleware('can:pending_loan_approval')->only('loan_approved');
        $this->middleware('can:client_loan_account_category_update')->only('categoryUpdate');
        $this->middleware('can:client_loan_account_change_status')->only('changeStatus');
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
    public function store(LoanAccountStoreRequest $request)
    {
        $data               = (object) $request->validated();
        $guarantors         = $data->guarantors;
        $is_approved        = AppConfig::get_config('loan_account_registration_approval');
        $is_loan_approved   = AppConfig::get_config('loan_approval');

        DB::transaction(function () use ($data, $is_approved, $is_loan_approved, $guarantors) {
            $loan_account = LoanAccount::create(
                self::set_loan_field_map(
                    $data,
                    $data->field_id,
                    $data->center_id,
                    $data->category_id,
                    $data->acc_no,
                    $data->client_registration_id,
                    $is_approved,
                    $is_loan_approved,
                    $data->creator_id
                )
            );

            foreach ($guarantors as $guarantor) {
                $guarantor  = (object) $guarantor;
                $img        = isset($guarantor->image)
                    ? Helper::storeImage($guarantor->image, "guarantor", "guarantors")
                    : (object) ["name" => null, "uri" => $guarantor->image_uri ?? null];
                $signature  = isset($guarantor->signature)
                    ? Helper::storeSignature($guarantor->signature, "guarantor_signature", "guarantors")
                    : (object) ["name" => null, "uri" => $guarantor->signature_uri ?? null];

                Guarantor::create(Helper::set_nomi_field_map(
                    $guarantor,
                    'loan_account_id',
                    $loan_account->id,
                    false,
                    $img->name,
                    $img->uri,
                    $signature->name,
                    $signature->uri
                ));
            }
        });

        return create_response(__('customValidations.client.loan.successful'));
    }

    /**
     * Show the specified resource from storage.
     */
    public function show(string $id)
    {
        $account = LoanAccount::withTrashed()
            ->approve()
            ->with(['LoanAccountActionHistory', 'LoanAccountActionHistory.Author:id,name,image_uri'])
            ->clientRegistration('id', 'name', 'image_uri', 'primary_phone')
            ->field('id', 'name',)
            ->center('id', 'name',)
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->approver('id', 'name')
            ->loanApprover('id', 'name')
            ->find($id);

        return create_response(null, $account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LoanAccountUpdateRequest $request, string $id)
    {
        $data           = (object) $request->validated();
        $guarantors     = $data->guarantors;
        $loan_account   = LoanAccount::find($id);
        $histData       = self::set_update_hist($data, $loan_account);

        DB::transaction(
            function () use ($id, $loan_account, $data, $guarantors, $histData) {
                $loan_account->update(self::set_loan_field_map($data));

                foreach ($guarantors as $index => $guarantorData) {
                    $guarantorData                    = (object) $guarantorData;
                    $guarantor                        = Guarantor::find($guarantorData->id);
                    $histData['guarantors'][$index]   = [];

                    Helper::set_update_nomiguarantor_hist($histData['guarantors'][$index], $guarantorData, $guarantor);
                    self::update_file($guarantor, $guarantorData->image ?? '', 'guarantor_image', 'image', 'image_uri', 'guarantors', $histData['guarantors'][$index]);
                    self::update_file($guarantor, $guarantorData->signature ?? '', 'guarantor_signature', 'signature', 'signature_uri', 'guarantors', $histData['guarantors'][$index]);

                    $guarantor->update(Helper::set_nomi_field_map($guarantorData));
                }
                LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $id, 'update', $histData));
            }
        );

        return create_response(__('customValidations.client.loan.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $client = LoanAccount::find($id);
            $client->delete();
            $client->LoanCollection()->delete();
            $client->LoanSavingWithdrawal()->delete();

            LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $id, 'delete', []));
        });

        return create_response(__('customValidations.client.loan.delete'));
    }

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        LoanAccount::find($id)->forceDelete();
        return create_response(__('customValidations.client.loan.p_delete'));
    }

    /**
     * Update the Category.
     */
    public function categoryUpdate(CategoryUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $account     = LoanAccount::with('Category:id,name,is_default')->find($id);

        if (Helper::areValuesEqual($data->id, $account->category_id)) {
            return create_validation_error_response(__('customValidations.category.choose_new_category'));
        } else {
            $oldCategory =  $account->category->is_default ? __("customValidations.category.default.{$account->category->name}") : $account->category->name;
            $newCategory =  $data->is_default ? __("customValidations.category.default.{$data->name}") : $data->name;

            $histData = [
                'category' => "<p class='text-danger'>- {$oldCategory}</p><p class='text-success'>+ {$newCategory}</p>"
            ];
        }

        DB::transaction(function () use ($id, $account, $data, $histData) {
            $account->update(['category_id' => $data->id]);
            $account->LoanCollection()->update(['category_id' => $data->id]);
            $account->LoanSavingWithdrawal()->update(['category_id' => $data->id]);

            LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $id, 'update', $histData));
        });

        return create_response(__('customValidations.client.loan.update'));
    }

    /**
     * Pending Forms
     */
    public function pending_forms()
    {
        $pending_forms = LoanAccount::fetchPendingForms()->get();
        return create_response(null, $pending_forms);
    }

    /**
     * Pending Forms
     */
    public function pending_loans()
    {
        $month  = !empty(request('date_range')) ? Carbon::parse(request('date_range'))->month : Carbon::now()->month;
        $year   = !empty(request('date_range')) ? Carbon::parse(request('date_range'))->year : Carbon::now()->year;

        $pending_loans = LoanAccount::fetchPendingLoans($month, $year)->get();
        return create_response(null, $pending_loans);
    }

    /**
     * Account short Summery
     */
    public function get_short_summery(string $id)
    {
        $account = LoanAccount::withTrashed()
            ->find($id, ['id', 'loan_given', 'payable_installment', 'total_payable_interest', 'total_rec_installment', 'total_withdrawn', 'balance', 'total_loan_rec', 'total_loan_remaining', 'total_interest_rec', 'total_interest_remaining']);
        $check = LoanAccountCheck::where('loan_account_id', $id)->orderBy('created_at', 'DESC')->first(['created_at', 'next_check_in_at']);

        $installment        = $account->total_rec_installment;
        $total_withdrawn    = $account->total_withdrawn;
        $total_withdraw     = $account->LoanSavingWithdrawal->sum('amount');
        $total_fees         = $account->LoanAccountFee->sum('amount');
        $balance            = $account->balance;
        $loan_recovered     = $account->total_loan_rec;
        $loan_remaining     = $account->total_loan_remaining;
        $interest_recovered = $account->total_interest_rec;
        $interest_remaining = $account->total_interest_remaining;
        $total_recovered    = $loan_recovered + $interest_recovered;
        $total_remaining    = $loan_remaining + $interest_remaining;
        $total              = $account->loan_given + $account->total_payable_interest;


        return create_response(null, [
            "installment"               => "{$installment}/{$account->payable_installment}",
            "total_withdrawn"           => $total_withdrawn,
            "total_withdraw"            => $total_withdraw,
            "total_fees"                => $total_fees,
            "balance"                   => $balance,
            "loan_given"                => $account->loan_given,
            "loan_recovered"            => $loan_recovered,
            "loan_remaining"            => $loan_remaining,
            "total_payable_interest"    => $account->total_payable_interest,
            "interest_recovered"        => $interest_recovered,
            "interest_remaining"        => $interest_remaining,
            "total_payable"             => $total,
            "total_recovered"           => $total_recovered,
            "total_remaining"           => $total_remaining,
            "last_check"                => $check->created_at ?? null,
            "next_check"                => $check->next_check_in_at ?? null,
        ]);
    }

    /**
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        $LoanAccount = LoanAccount::with([
            'ClientRegistration:id,name',
            'Category:id,name,is_default'
        ])->find($id);

        if (!$LoanAccount) {
            return create_response(__('customValidations.client.loan.not_found'));
        }

        $categoryConfig = CategoryConfig::where('category_id', $LoanAccount->category_id)
            ->with('loan_reg_fee_store_acc:id,balance')
            ->select('id', 'l_reg_fee_acc_id', 'loan_acc_reg_fee')
            ->firstOrFail();

        DB::transaction(function () use ($LoanAccount, $categoryConfig) {
            if ($categoryConfig->loan_acc_reg_fee > 0) {
                $incomeCatId    = IncomeCategory::where('name', 'loan_form_fee')->value('id');
                $categoryName   = !$LoanAccount->category->is_default ? $LoanAccount->category->name :  __("customValidations.category.default.{$LoanAccount->category->name}");
                $acc_no         = Helper::tsNumbers($LoanAccount->acc_no);
                $loan_given     = Helper::tsNumbers("৳{$LoanAccount->loan_given}/-");
                $description    = __('customValidations.common.acc_no') . ' = ' . $acc_no . ', ' . __('customValidations.common.name') . ' = ' . $LoanAccount->clientRegistration->name . ', '  . __('customValidations.common.loan') . ' ' . __('customValidations.common.category') . ' = ' . $categoryName . ', ' . __('customValidations.common.loan') . ' = ' . $loan_given;

                Income::store(
                    $categoryConfig->loan_reg_fee_store_acc->id,
                    $incomeCatId,
                    $categoryConfig->loan_acc_reg_fee,
                    $categoryConfig->loan_reg_fee_store_acc->balance,
                    $description
                );
                Account::find($categoryConfig->loan_reg_fee_store_acc->id)
                    ->increment('total_deposit', $categoryConfig->loan_acc_reg_fee);
            }

            $LoanAccount->update(
                [
                    'is_approved' => true,
                    'approved_by' => auth()->id(),
                    'approved_at' => Carbon::now('Asia/Dhaka')
                ]
            );
        });

        return create_response(__('customValidations.client.loan.approved'));
    }

    /**
     * Approved the specified loan
     */
    public function loan_approved(LoanApprovalRequest $request, string $id)
    {
        $account        = null;
        $data           = (object) $request->validated();
        $LoanAccount    = LoanAccount::with([
            'ClientRegistration:id,name',
            'Category:id,name,is_default'
        ])->find($id);

        if (!$LoanAccount) {
            return create_validation_error_response(__('customValidations.client.loan.not_found'));
        }
        if (isset($data->account) && $account = Account::find($data->account)) {
            if ($account->balance < $LoanAccount->loan_given) {
                return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'account');
            }
        }


        DB::transaction(function () use ($LoanAccount, $data, $account) {
            if (isset($data->account) && !empty($account)) {
                $expenseCatId   = ExpenseCategory::where('name', 'loan_given')->value('id');
                $categoryName   = !$LoanAccount->category->is_default ? $LoanAccount->category->name :  __("customValidations.category.default.{$LoanAccount->category->name}");
                $acc_no         = Helper::tsNumbers($LoanAccount->acc_no);
                $loan_given     = Helper::tsNumbers("৳{$LoanAccount->loan_given}/-");
                $description    = __('customValidations.common.acc_no') . ' = ' . $acc_no . ', ' . __('customValidations.common.name') . ' = ' . $LoanAccount->clientRegistration->name . ', '  . __('customValidations.common.loan') . ' ' . __('customValidations.common.category') . ' = ' . $categoryName . ', ' . __('customValidations.common.loan') . ' = ' . $loan_given;

                Expense::store(
                    $data->account,
                    $expenseCatId,
                    $LoanAccount->loan_given,
                    $account->balance,
                    $description
                );
                $account->increment('total_withdrawal', $LoanAccount->loan_given);
            }

            $LoanAccount->update(
                [
                    'is_loan_approved'      => true,
                    'loan_approved_by'      => auth()->id(),
                    'is_loan_approved_at'   => Carbon::now('Asia/Dhaka')
                ]
            );
        });

        return create_response(__('customValidations.client.loan.approved'));
    }

    /**
     * Change Status the specified Resource
     */
    public function changeStatus(LoanAccountChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $savingAccount = LoanAccount::find($id);

        if (!$savingAccount) {
            return create_response(__('customValidations.client.saving.not_found'));
        }

        $oldStatus  = $savingAccount->status ? __('customValidations.common.active') : __('customValidations.common.hold');
        $newStatus  = $status ? __('customValidations.common.active') : __('customValidations.common.hold');
        $histData   = [
            'status' => "<p class='text-danger'>- {$oldStatus}</p><p class='text-success'>+ {$newStatus}</p>"
        ];

        DB::transaction(function () use ($savingAccount, $status, $id, $histData) {
            $savingAccount->update(['status' => $status]);
            LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $id, 'update', $histData));
        });

        return create_response(__('customValidations.client.loan.status'));
    }

    /**
     * Show the specified resource from storage.
     */
    public function activeAccount(string $client_id)
    {
        $loan = LoanAccount::with('Guarantors')->activeLoan($client_id)->get();
        return create_response(null, $loan);
    }

    /**
     * Show the specified resource from storage.
     */
    public function pendingAccount(string $client_id)
    {
        $loan = LoanAccount::with('Guarantors')->pendingLoan($client_id)->get();
        return create_response(null, $loan);
    }

    /**
     * Show the specified resource from storage.
     */
    public function holdAccount(string $client_id)
    {
        $loan = LoanAccount::with('Guarantors')->holdLoan($client_id)->get();
        return create_response(null, $loan);
    }

    /**
     * Show the specified resource from storage.
     */
    public function closedAccount(string $client_id)
    {
        $loan = LoanAccount::with('Guarantors')->closedLoan($client_id)->get();
        return create_response(null, $loan);
    }

    /**
     * Get all Occupations
     */
    public function get_guarantor_occupations()
    {
        $occupations = Guarantor::distinct('occupation')->orderBy('occupation', 'asc')->pluck('occupation');
        return create_response(null, $occupations);
    }

    /**
     * Get all Relation
     */
    public function get_guarantor_relations()
    {
        $relations = Guarantor::distinct('relation')->orderBy('relation', 'asc')->pluck('relation');
        return create_response(null, $relations);
    }

    /**
     * Set Saving Acc Field Map
     *
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     * @return array
     */
    private static function set_loan_field_map($data, $field_id = null, $center_id = null, $category_id = null, $acc_no = null, $client_registration_id = null,  $is_approved = null, $is_loan_approved = null, $creator_id = null)
    {
        $map = [
            'start_date'                        => $data->start_date,
            'duration_date'                     => $data->duration_date,
            'loan_given'                        => $data->loan_given,
            'payable_installment'               => $data->payable_installment,
            'payable_deposit'                   => $data->payable_deposit,
            'payable_interest'                  => $data->payable_interest,
            'total_payable_interest'            => $data->total_payable_interest,
            'total_payable_loan_with_interest'  => $data->total_payable_loan_with_interest,
            'loan_installment'                  => $data->loan_installment,
            'interest_installment'              => $data->interest_installment,
        ];

        if (isset($field_id)) {
            $map['field_id'] = $field_id;
        }
        if (isset($center_id)) {
            $map['center_id'] = $center_id;
        }
        if (isset($category_id)) {
            $map['category_id'] = $category_id;
        }
        if (isset($acc_no, $client_registration_id)) {
            $map += ['acc_no' => $acc_no, 'client_registration_id' => $client_registration_id];
        }
        if (isset($is_approved)) {
            $map['is_approved'] = $is_approved;
        }
        if (isset($is_loan_approved)) {
            $map += ['is_approved' => $is_approved, 'approved_by' => auth()->id()];
        }
        if (isset($creator_id)) {
            $map['creator_id'] = $creator_id ?? auth()->id();
        }

        return $map;
    }

    /**
     * Set Saving Acc update hist
     *
     * @param object $data
     * @param object $account
     *
     * @return array
     */
    private static function set_update_hist($data, $account)
    {
        $histData           = [];
        $fieldsToCompare    = [
            'start_date', 'duration_date', 'loan_given',
            'payable_deposit',
            'payable_installment',
            'payable_interest',
            'total_payable_interest',
            'total_payable_loan_with_interest',
            'loan_installment',
            'interest_installment',
        ];

        foreach ($fieldsToCompare as $field) {
            $clientValue    = $account->{$field} ?? '';
            $dataValue      = $data->{$field} ?? '';

            if (!Helper::areValuesEqual($clientValue, $dataValue)) {
                $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>";
            }
        }

        return $histData;
    }

    /**
     * Update Files
     *
     * @param object $model
     * @param object $newImg
     * @param string $histKey
     * @param string $fieldName
     * @param string $uriFieldName
     * @param string $directory
     *
     * @return void
     */
    private static function update_file($model, $newImg, $histKey, $fieldName, $uriFieldName, $directory, &$histData)
    {
        if (!empty($newImg) && !empty($model->{$fieldName})) {
            Helper::unlinkImage(public_path("storage/{$directory}/{$model->{$fieldName}}"));
        }

        if (!empty($newImg)) {
            $file = $fieldName === 'image'
                ? Helper::storeImage($newImg, $histKey, $directory)
                : Helper::storeSignature($newImg, $histKey, $directory);
            $histData[$histKey] = "<p class='text-danger'>********</p><p class='text-success'>********</p>";

            $model->update([
                $fieldName     => $file->name,
                $uriFieldName  => $file->uri,
            ]);
        }
    }
}
