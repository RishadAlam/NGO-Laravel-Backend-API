<?php

namespace App\Http\Controllers\client;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\client\Nominee;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use App\Models\client\SavingAccountFee;
use App\Models\client\SavingAccountCheck;
use Illuminate\Support\Facades\Validator;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Models\Collections\SavingCollection;
use App\Models\client\SavingAccountActionHistory;
use App\Http\Requests\client\CategoryUpdateRequest;
use App\Http\Requests\client\SavingAccountStoreRequest;
use App\Http\Requests\client\SavingAccountUpdateRequest;
use App\Http\Requests\client\SavingAccountChangeStatusRequest;

class SavingAccountController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_saving_acc_list_view|pending_saving_acc_list_view_as_admin')->only('pending_forms');
        $this->middleware('can:saving_acc_registration')->only('store');
        $this->middleware('permission:pending_saving_acc_update|client_saving_account_update')->only('update');
        $this->middleware('can:pending_saving_acc_permanently_delete')->only('permanently_destroy');
        $this->middleware('can:pending_saving_acc_approval')->only('approved');
        $this->middleware('can:client_saving_account_category_update')->only('categoryUpdate');
        $this->middleware('can:client_saving_account_change_status')->only('changeStatus');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $savings = SavingAccount::approve()
            ->clientRegistration('id', 'name', 'image_uri', 'primary_phone')
            ->field('id', 'name')
            ->center('id', 'name')
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->approver('id', 'name')
            ->orderedBy('id', 'DESC')
            ->get();

        return create_response(null, $savings);
    }

    /**
     * Show the specified resource from storage.
     */
    public function show(string $id)
    {
        $saving = SavingAccount::withTrashed()
            ->approve()
            ->with(['SavingAccountActionHistory', 'SavingAccountActionHistory.Author:id,name,image_uri'])
            ->clientRegistration('id', 'name', 'image_uri', 'primary_phone')
            ->field('id', 'name')
            ->center('id', 'name')
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->approver('id', 'name')
            ->find($id);

        $saving['closing_req'] = $saving->SavingAccountClosing()->where('is_approved', false)->count();

        return create_response(null, $saving);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavingAccountStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $nominees       = $data->nominees;
        $is_approved    = AppConfig::get_config('saving_account_registration_approval');

        DB::transaction(function () use ($data, $is_approved, $nominees) {
            $saving_account = SavingAccount::create(
                self::set_saving_field_map(
                    $data,
                    $data->field_id,
                    $data->center_id,
                    $data->category_id,
                    $data->acc_no,
                    $data->client_registration_id,
                    $is_approved,
                    $data->creator_id
                )
            );

            foreach ($nominees as $nominee) {
                $nominee    = (object) $nominee;
                $img        = isset($nominee->image)
                    ? Helper::storeImage($nominee->image, "nominee", "nominees")
                    : (object) ["name" => null, "uri" => $nominee->image_uri ?? null];
                $signature  = isset($nominee->signature)
                    ? Helper::storeSignature($nominee->signature, "nominee_signature", "nominees")
                    : (object) ["name" => null, "uri" => $nominee->signature_uri ?? null];

                Nominee::create(
                    Helper::set_nomi_field_map(
                        $nominee,
                        'saving_account_id',
                        $saving_account->id,
                        false,
                        $img->name,
                        $img->uri,
                        $signature->name,
                        $signature->uri
                    )
                );
            }
        });

        return create_response(__('customValidations.client.saving.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SavingAccountUpdateRequest $request, string $id)
    {
        $data               = (object) $request->validated();
        $nominees           = $data->nominees;
        $saving_account     = SavingAccount::find($id);
        $histData           = self::set_update_hist($data, $saving_account);

        DB::transaction(
            function () use ($id, $saving_account, $data, $nominees, $histData) {
                $saving_account->update(self::set_saving_field_map($data));
                foreach ($nominees as $index => $nomineeData) {
                    $nomineeData                    = (object) $nomineeData;
                    $nominee                        = Nominee::find($nomineeData->id);
                    $histData['nominees'][$index]   = [];

                    Helper::set_update_nomiguarantor_hist($histData['nominees'][$index], $nomineeData, $nominee);
                    self::update_file($nominee, $nomineeData->image, 'nominee_image', 'image', 'image_uri', 'nominees', $histData['nominees'][$index]);
                    self::update_file($nominee, $nomineeData->signature, 'nominee_signature', 'signature', 'signature_uri', 'nominees', $histData['nominees'][$index]);

                    $nominee->update(Helper::set_nomi_field_map($nomineeData));
                }
                SavingAccountActionHistory::create(Helper::setActionHistory('saving_account_id', $id, 'update', $histData));
            }
        );

        return create_response(__('customValidations.client.saving.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $client = SavingAccount::find($id);
            $client->delete();
            $client->SavingCollection()->delete();
            $client->SavingWithdrawal()->delete();

            SavingAccountActionHistory::create(Helper::setActionHistory('saving_account_id', $id, 'delete', []));
        });

        return create_response(__('customValidations.client.saving.delete'));
    }

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        SavingAccount::find($id)->forceDelete();
        return create_response(__('customValidations.client.saving.p_delete'));
    }

    /**
     * Update the Category.
     */
    public function categoryUpdate(CategoryUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $account     = SavingAccount::with('Category:id,name,is_default')->find($id);

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
            $account->SavingCollection()->update(['category_id' => $data->id]);
            $account->SavingWithdrawal()->update(['category_id' => $data->id]);

            SavingAccountActionHistory::create(Helper::setActionHistory('saving_account_id', $id, 'update', $histData));
        });

        return create_response(__('customValidations.client.saving.update'));
    }

    /**
     * Pending Forms
     */
    public function pending_forms()
    {
        $pending_forms = SavingAccount::fetchPendingForms()->get();
        return create_response(null, $pending_forms);
    }

    /**
     * Account short Summery
     */
    public function get_short_summery(string $id)
    {
        $account = SavingAccount::withTrashed()->find($id, ['id', 'total_installment', 'total_withdrawn', 'balance']);
        $check = SavingAccountCheck::where('saving_account_id', $id)->orderBy('created_at', 'DESC')->first(['created_at', 'next_check_in_at']);

        return create_response(null, [
            "installment"       => $account->total_installment ?? 0,
            'total_withdrawn'   => $account->total_withdrawn,
            'total_withdraw'    => $account->SavingWithdrawal->sum('amount'),
            'total_fees'        => $account->SavingAccountFee->sum('amount'),
            "balance"           => $account->balance ?? 0,
            "last_check"        => $check->created_at ?? null,
            "next_check"        => $check->next_check_in_at ?? null,
        ]);
    }

    /**
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        $savingAccount = SavingAccount::with([
            'ClientRegistration:id,name',
            'Category:id,name,is_default'
        ])->find($id);

        if (!$savingAccount) {
            return create_response(__('customValidations.client.saving.not_found'));
        }

        $categoryConfig = CategoryConfig::where('category_id', $savingAccount->category_id)
            ->with('saving_reg_fee_store_acc:id,balance')
            ->select('id', 's_reg_fee_acc_id', 'saving_acc_reg_fee')
            ->firstOrFail();

        DB::transaction(function () use ($savingAccount, $categoryConfig) {
            if ($categoryConfig->saving_acc_reg_fee > 0) {
                $incomeCatId    = IncomeCategory::where('name', 'saving_form_fee')->value('id');
                $categoryName   = !$savingAccount->category->is_default ? $savingAccount->category->name :  __("customValidations.category.default.{$savingAccount->category->name}");
                $acc_no         = Helper::tsNumbers($savingAccount->acc_no);
                $description    = __('customValidations.common.acc_no') . ' = ' . $acc_no . ', ' . __('customValidations.common.name') . ' = ' . $savingAccount->clientRegistration->name . ', '  . __('customValidations.common.saving') . ' ' . __('customValidations.common.category') . ' = ' . $categoryName;

                Income::store(
                    $categoryConfig->saving_reg_fee_store_acc->id,
                    $incomeCatId,
                    $categoryConfig->saving_acc_reg_fee,
                    $categoryConfig->saving_reg_fee_store_acc->balance,
                    $description
                );
                Account::find($categoryConfig->saving_reg_fee_store_acc->id)
                    ->increment('total_deposit', $categoryConfig->saving_acc_reg_fee);
            }

            $savingAccount->update(
                [
                    'is_approved' => true,
                    'approved_by' => auth()->id(),
                    'approved_at' => Carbon::now('Asia/Dhaka')
                ]
            );
        });

        return create_response(__('customValidations.client.saving.approved'));
    }

    /**
     * Change Status the specified Resource
     */
    public function changeStatus(SavingAccountChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $savingAccount = SavingAccount::find($id);

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
            SavingAccountActionHistory::create(Helper::setActionHistory('saving_account_id', $id, 'update', $histData));
        });

        return create_response(__('customValidations.client.saving.status'));
    }

    /**
     * Show the specified resource from storage.
     */
    public function activeAccount(string $client_id)
    {
        $saving = SavingAccount::with('Nominees')->activeSaving($client_id)->get();
        return create_response(null, $saving);
    }

    /**
     * Show the specified resource from storage.
     */
    public function pendingAccount(string $client_id)
    {
        $saving = SavingAccount::with('Nominees')->pendingSaving($client_id)->get();
        return create_response(null, $saving);
    }

    /**
     * Show the specified resource from storage.
     */
    public function holdAccount(string $client_id)
    {
        $saving = SavingAccount::with('Nominees')->holdSaving($client_id)->get();
        return create_response(null, $saving);
    }

    /**
     * Show the specified resource from storage.
     */
    public function closedAccount(string $client_id)
    {
        $saving = SavingAccount::with('Nominees')->closedSaving($client_id)->get();
        return create_response(null, $saving);
    }

    /**
     * Get all Occupations
     */
    public function get_nominee_occupations()
    {
        $occupations = Nominee::distinct('occupation')
            ->orderBy('occupation', 'asc')
            ->pluck('occupation');

        return create_response(null, $occupations);
    }

    /**
     * Get all Relation
     */
    public function get_nominee_relations()
    {
        $relations = Nominee::distinct('relation')
            ->orderBy('relation', 'asc')
            ->pluck('relation');

        return create_response(null, $relations);
    }

    /**
     * Get All Transactions
     */
    public function getAllTransaction(string $id)
    {
        $dateRange = Helper::getDateRange(request('date_range'));
        $balance = SavingAccount::withTrashed()->find($id, 'balance')->balance;

        $collections = SavingCollection::where('saving_account_id', $id)
            ->whereBetween('created_at', $dateRange)
            ->approve()
            ->select(
                'id',
                DB::raw("'credit' as type"),
                'account_id',
                'approved_by',
                'installment',
                'deposit',
                'description',
                'creator_id',
                'approved_at',
                'created_at',
                'updated_at'
            )
            ->withTrashed()
            ->get();

        $withdrawals = SavingWithdrawal::where('saving_account_id', $id)
            ->whereBetween('created_at', $dateRange)
            ->approve()
            ->select(
                'id',
                DB::raw("'debit' as type"),
                'account_id',
                'approved_by',
                'balance',
                'amount',
                'balance_remaining',
                'description',
                'creator_id',
                'approved_at',
                'created_at',
                'updated_at'
            )->get();

        $fees = SavingAccountFee::where('saving_account_id', $id)
            ->whereBetween('created_at', $dateRange)
            ->select(
                'id',
                'account_fees_category_id',
                DB::raw("'debit' as type"),
                'amount',
                'description',
                'creator_id',
                'created_at',
                'updated_at'
            )->get();

        $checks = SavingAccountCheck::where('saving_account_id', $id)
            ->whereBetween('created_at', $dateRange)
            ->select(
                'id',
                'checked_by',
                DB::raw("'checked' as type"),
                'installment_recovered',
                'balance',
                'description',
                'next_check_in_at',
                'created_at',
                'updated_at'
            )->get();

        $transactions = collect(self::formatCollections($collections))
            ->merge(self::formatWithdrawals($withdrawals))
            ->merge(self::formatFees($fees))
            ->merge(self::formatChecks($checks))
            ->sortBy('created_at')
            ->values()
            ->all();


        $transactions = Helper::calculateTransactionBalance($transactions, $balance);

        return create_response(null, $transactions);
    }

    /**
     * Set Saving Acc Field Map
     *
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     *
     * @return array
     */
    private static function set_saving_field_map($data, $field_id = null, $center_id = null, $category_id = null, $acc_no = null, $client_registration_id = null, $is_approved = null, $creator_id = null)
    {
        $map = [
            'start_date'                        => $data->start_date,
            'duration_date'                     => $data->duration_date,
            'payable_installment'               => $data->payable_installment,
            'payable_deposit'                   => $data->payable_deposit,
            'payable_interest'                  => $data->payable_interest,
            'total_deposit_without_interest'    => $data->total_deposit_without_interest,
            'total_deposit_with_interest'       => $data->total_deposit_with_interest,
            'is_approved'                       => $is_approved ?? false,
            'creator_id'                        => $data->creator_id ?? auth()->id(),
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
        $fieldsToCompare    = ['start_date', 'duration_date', 'payable_installment', 'payable_deposit', 'payable_interest', 'total_deposit_without_interest', 'total_deposit_with_interest'];

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

    /**
     * Formate Collections data
     */
    private static function formatCollections($collections)
    {
        $collectionsData = [];
        foreach ($collections as $collection) {
            $installment = Helper::tsNumbers($collection->installment);
            $deposit = Helper::tsNumbers("৳{$collection->deposit}/-");
            $desc = '<p>' . __('customValidations.common.installment') . ': ' . $installment . ', ' . __('customValidations.common.deposit') . ': ' . $deposit .  '</p>' . $collection->description;

            $collectionsData[] = (object) [
                'type'          => $collection->type,
                'category'      => ['name' => 'regular_collection', 'is_default' => true],
                'description'   => $desc,
                'amount'        => $collection->deposit,
                'author'        => Helper::getObject($collection->author, ['id', 'name']),
                'approver'      => Helper::getObject($collection->approver, ['id', 'name']),
                'account'       => Helper::getObject($collection->account, ['id', 'name', 'is_default']),
                'approved_at'   => $collection->approved_at,
                'created_at'    => $collection->created_at,
                'updated_at'    => $collection->updated_at
            ];
        }

        return $collectionsData;
    }

    /**
     * Formate withdrawals data
     */
    private static function formatWithdrawals($withdrawals)
    {
        $withdrawalsData = [];
        foreach ($withdrawals as $withdrawal) {
            $balance = Helper::tsNumbers("৳{$withdrawal->balance}/-");
            $amount = Helper::tsNumbers("৳{$withdrawal->amount}/-");
            $balanceRemaining = Helper::tsNumbers("৳{$withdrawal->balance_remaining}/-");
            $desc = '<p>' . __('customValidations.common.balance') . ': ' . $balance . ', ' . __('customValidations.common.amount') . ': ' . $amount . ', ' . __('customValidations.common.balance_remaining') . ': ' . $balanceRemaining .  '</p>' . $withdrawal->description;

            $withdrawalsData[] = (object) [
                'type'          => $withdrawal->type,
                'category'      => ['name' => 'withdrawal', 'is_default' => true],
                'description'   => $desc,
                'amount'        => $withdrawal->amount,
                'author'        => Helper::getObject($withdrawal->author, ['id', 'name']),
                'approver'      => Helper::getObject($withdrawal->approver, ['id', 'name']),
                'account'       => Helper::getObject($withdrawal->account, ['id', 'name', 'is_default']),
                'approved_at'   => $withdrawal->approved_at,
                'created_at'    => $withdrawal->created_at,
                'updated_at'    => $withdrawal->updated_at
            ];
        }

        return $withdrawalsData;
    }

    /**
     * Formate fees data
     */
    private static function formatFees($fees)
    {
        $feesData = [];
        foreach ($fees as $fee) {
            $feesData[] = (object) [
                'type'          => $fee->type,
                'category'      => Helper::getObject($fee->accountFeesCategory, ['id', 'name', 'is_default']),
                'description'   => $fee->description,
                'amount'        => $fee->amount,
                'author'        => Helper::getObject($fee->author, ['id', 'name']),
                'approver'      => null,
                'account'       => null,
                'approved_at'   => null,
                'created_at'    => $fee->created_at,
                'updated_at'    => $fee->updated_at
            ];
        }

        return $feesData;
    }

    /**
     * Formate checks data
     */
    private static function formatChecks($checks)
    {

        $checksData = [];
        foreach ($checks as $check) {
            $installmentRecovered = Helper::tsNumbers($check->installment_recovered);
            $balance = Helper::tsNumbers("৳{$check->balance}/-");
            $nextCheck = Helper::tsNumbers(Carbon::parse($check->next_check_in_at)->format('d/m/Y'));
            $desc = '<p>' . __('customValidations.common.balance') . ': ' . $balance . ', ' . __('customValidations.common.installment_recovered') . ': ' . $installmentRecovered . ', ' . __('customValidations.common.next_check_in_at') . ': ' . $nextCheck .  '</p>' . $check->description;

            $checksData[] = (object) [
                'type'          => $check->type,
                'category'      => null,
                'description'   => $desc,
                'amount'        => null,
                'author'        => Helper::getObject($check->author, ['id', 'name']),
                'approver'      => null,
                'account'       => null,
                'approved_at'   => null,
                'created_at'    => $check->created_at,
                'updated_at'    => $check->updated_at
            ];
        }

        return $checksData;
    }
}
