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
use Illuminate\Support\Facades\Validator;
use App\Models\client\SavingAccountActionHistory;
use App\Http\Requests\client\SavingAccountStoreRequest;
use App\Http\Requests\client\SavingAccountUpdateRequest;

class SavingAccountController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_saving_acc_list_view|pending_saving_acc_list_view_as_admin')->only('pending_forms');
        $this->middleware('can:saving_acc_registration')->only('store');
        $this->middleware('can:pending_saving_acc_update')->only('update');
        $this->middleware('can:pending_saving_acc_permanently_delete')->only('permanently_destroy');
        $this->middleware('can:pending_saving_acc_approval')->only('approved');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the specified resource from storage.
     */
    public function show(string $id)
    {
        $saving = SavingAccount::approve()
            ->clientRegistration('id', 'name', 'image_uri', 'primary_phone')
            ->field('id', 'name',)
            ->center('id', 'name',)
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->approver('id', 'name')
            ->find($id);

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
            SavingAccount::find($id)->delete();
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
     * Pending Forms
     */
    public function pending_forms()
    {
        $pending_forms = SavingAccount::fetchPendingForms()->get();
        return create_response(null, $pending_forms);
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

            $savingAccount->update(['is_approved' => true, 'approved_by' => auth()->id()]);
        });

        return create_response(__('customValidations.client.saving.approved'));
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
            $$map += ['is_approved' => $is_approved, 'approved_by' => auth()->id()];
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
            !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
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
