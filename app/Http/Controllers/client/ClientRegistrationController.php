<?php

namespace App\Http\Controllers\client;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
use App\Models\client\ClientRegistration;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\client\AccNoUpdateRequest;
use App\Http\Requests\client\FieldUpdateRequest;
use App\Http\Requests\client\CenterUpdateRequest;
use App\Models\client\ClientRegistrationActionHistory;
use App\Http\Requests\client\ClientRegistrationStoreRequest;
use App\Http\Requests\client\ClientRegistrationUpdateRequest;

class ClientRegistrationController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_client_registration_list_view|pending_client_registration_list_view_as_admin')->only('pending_forms');
        $this->middleware('can:client_registration')->only('store');
        $this->middleware('permission:pending_client_registration_update|client_register_account_update')->only('update');
        $this->middleware('can:client_register_account_delete')->only('destroy');
        $this->middleware('can:pending_client_registration_permanently_delete')->only('permanently_destroy');
        $this->middleware('can:pending_client_registration_approval')->only('approved');
        $this->middleware('can:client_register_account_field_update')->only('fieldUpdate');
        $this->middleware('can:client_register_account_center_update')->only('centerUpdate');
        $this->middleware('can:client_register_account_acc_no_update')->only('accNoUpdate');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "registration_id"   => $id,
            "author_id"         => auth()->id(),
            "name"              => auth()->user()->name,
            "image_uri"         => auth()->user()->image_uri,
            "action_type"       => $action,
            "action_details"    => $histData,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $client = ClientRegistration::approve()
            ->field('id', 'name')
            ->center('id', 'name')
            ->author('id', 'name')
            ->filter()
            ->get();

        return create_response(null, $client);
    }

    /**
     * Show the specified resource from storage.
     */
    public function show(string $id)
    {
        $client = ClientRegistration::client()->withTrashed()->find($id);
        return create_response(null, $client);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientRegistrationStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::get_config('client_registration_approval');
        $img            = Helper::storeImage($data->image, "client", "client");
        $signature      = isset($data->signature)
            ? Helper::storeSignature($data->signature, "client_signature", "client")
            : (object) ["name" => null, "uri" => null];

        ClientRegistration::create(
            self::set_field_map(
                $data,
                $data->field_id,
                $data->center_id,
                $data->acc_no,
                $img->name,
                $img->uri,
                $signature->name,
                $signature->uri,
                $is_approved,
                auth()->id()
            )
        );

        return create_response(__('customValidations.client.registration.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClientRegistrationUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $client     = ClientRegistration::find($id);
        $histData   = self::set_update_hist($data, $client);

        DB::transaction(function () use ($id, $client, $data, $histData) {
            self::update_file($client, $data->image, 'image', 'image', 'image_uri', 'client', $histData);
            self::update_file($client, $data->signature, 'signature', 'signature', 'signature_uri', 'client', $histData);

            $client->update(self::set_field_map($data));
            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.client.registration.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $accounts = ClientRegistration::countAllAccounts($id);

        if ($accounts->activeSavings > 0 || $accounts->pendingSavings > 0 || $accounts->holdSavings > 0 || $accounts->activeLoans > 0 || $accounts->pendingLoans > 0 || $accounts->holdLoans > 0) {
            return create_validation_error_response(__('customValidations.client.registration.counted_accounts'));
        }

        DB::transaction(function () use ($id) {
            $client = ClientRegistration::find($id);
            $client->delete();
            $client->SavingAccount()->delete();
            $client->LoanAccount()->delete();
            $client->SavingCollection()->delete();
            $client->LoanCollection()->delete();
            $client->SavingWithdrawal()->delete();
            $client->LoanSavingWithdrawal()->delete();

            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return create_response(__('customValidations.client.registration.delete'));
    }

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        ClientRegistration::find($id)->forceDelete();
        return create_response(__('customValidations.client.registration.p_delete'));
    }

    /**
     * Count All Account
     */
    public function countAccounts(string $id)
    {
        return create_response(null, ClientRegistration::countAllAccounts($id));
    }

    /**
     * Update the Field.
     */
    public function fieldUpdate(FieldUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $client     = ClientRegistration::with('Field:id,name')->find($id);

        if (Helper::areValuesEqual($data->id, $client->field_id)) {
            return create_validation_error_response(__('customValidations.field.choose_new_field'));
        } else {
            $histData = [
                'field' => "<p class='text-danger'>- {$client->field->name}</p><p class='text-success'>+ {$data->name}</p>"
            ];
        }

        DB::transaction(function () use ($id, $client, $data, $histData) {
            $client->update(['field_id' => $data->id]);
            $client->SavingAccount()->update(['field_id' => $data->id]);
            $client->LoanAccount()->update(['field_id' => $data->id]);
            $client->SavingCollection()->update(['field_id' => $data->id]);
            $client->LoanCollection()->update(['field_id' => $data->id]);
            $client->SavingWithdrawal()->update(['field_id' => $data->id]);
            $client->LoanSavingWithdrawal()->update(['field_id' => $data->id]);

            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.client.registration.update'));
    }

    /**
     * Update the center.
     */
    public function centerUpdate(CenterUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $client     = ClientRegistration::with('Center:id,name')->find($id);

        if (Helper::areValuesEqual($data->id, $client->center_id)) {
            return create_validation_error_response(__('customValidations.center.choose_new_center'));
        } else {
            $histData = [
                'center' => "<p class='text-danger'>- {$client->center->name}</p><p class='text-success'>+ {$data->name}</p>"
            ];
        }

        DB::transaction(function () use ($id, $client, $data, $histData) {
            $client->update(['center_id' => $data->id]);
            $client->SavingAccount()->update(['center_id' => $data->id]);
            $client->LoanAccount()->update(['center_id' => $data->id]);
            $client->SavingCollection()->update(['center_id' => $data->id]);
            $client->LoanCollection()->update(['center_id' => $data->id]);
            $client->SavingWithdrawal()->update(['center_id' => $data->id]);
            $client->LoanSavingWithdrawal()->update(['center_id' => $data->id]);

            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.client.registration.update'));
    }

    /**
     * Update the accNo.
     */
    public function accNoUpdate(AccNoUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $client     = ClientRegistration::find($id);

        if (Helper::areValuesEqual($data->acc_no, $client->acc_no)) {
            return create_validation_error_response(__('customValidations.acc_no.choose_new_acc_no'));
        } else {
            $histData = [
                'acc_no' => "<p class='text-danger'>- {$client->acc_no}</p><p class='text-success'>+ {$data->acc_no}</p>"
            ];
        }

        DB::transaction(function () use ($id, $client, $data, $histData) {
            $client->update(['acc_no' => $data->acc_no]);
            $client->SavingAccount()->update(['acc_no' => $data->acc_no]);
            $client->LoanAccount()->update(['acc_no' => $data->acc_no]);
            $client->SavingCollection()->update(['acc_no' => $data->acc_no]);
            $client->LoanCollection()->update(['acc_no' => $data->acc_no]);
            $client->SavingWithdrawal()->update(['acc_no' => $data->acc_no]);
            $client->LoanSavingWithdrawal()->update(['acc_no' => $data->acc_no]);

            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.client.registration.update'));
    }

    /**
     * Get all Occupations
     */
    public function get_client_occupations()
    {
        $occupations = ClientRegistration::distinct('occupation')
            ->orderBy('occupation', 'asc')
            ->pluck('occupation');

        return create_response(null, $occupations);
    }

    /**
     * Pending Forms
     */
    public function pending_forms()
    {
        $pendingForms = ClientRegistration::fetchPendingForms()->get();
        return create_response(null, $pendingForms);
    }

    /**
     * get accounts
     */
    public function clientAccounts($field_id = null, $center_id = null)
    {
        $clientAccounts = ClientRegistration::fetchAccounts($field_id, $center_id)
            ->get(['id', 'acc_no', 'name', 'image_uri']);

        return create_response(null, $clientAccounts);
    }

    /**
     * Pending Forms
     */
    public function clientInfo()
    {
        $clientInfo = ClientRegistration::info()->get();
        return create_response(null, $clientInfo);
    }

    /**
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        ClientRegistration::find($id)->update(
            [
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now('Asia/Dhaka')
            ]
        );
        return create_response(__('customValidations.client.registration.approved'));
    }

    /**
     * Get saving accounts
     */
    public function getLoanAccounts($fieldId = null, $centerId = null, $categoryId = null)
    {
        $accounts = LoanAccount::approve()
            ->clientRegistration('id', 'name')
            ->fieldId($fieldId)
            ->centerId($centerId)
            ->categoryId($categoryId)
            ->orderedBy('acc_no', 'ASC')
            ->get(['id', 'acc_no', 'client_registration_id', 'field_id', 'center_id', 'category_id']);

        return create_response(null, $accounts);
    }

    /**
     * Set Saving Acc Field Map
     *
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     * @return array
     */
    private static function set_field_map($data, $field_id = null, $center_id = null, $acc_no = null, $image = null, $image_uri = null, $signature = null, $signature_uri = null, $is_approved = null, $creator_id = null)
    {
        $map = [
            'name'              => $data->name,
            'father_name'       => $data->father_name,
            'husband_name'      => isset($data->husband_name) ? $data->husband_name : '',
            'mother_name'       => $data->mother_name,
            'nid'               => $data->nid,
            'dob'               => date('Y-m-d', strtotime($data->dob)),
            'occupation'        => $data->occupation,
            'religion'          => $data->religion,
            'gender'            => $data->gender,
            'primary_phone'     => $data->primary_phone,
            'secondary_phone'   => $data->secondary_phone,
            'annual_income'     => $data->annual_income,
            'bank_acc_no'       => $data->bank_acc_no,
            'bank_check_no'     => $data->bank_check_no,
            'share'             => $data->share,
            'present_address'   => $data->present_address,
            'permanent_address' => $data->permanent_address,
        ];

        if (isset($field_id)) {
            $map['field_id'] = $field_id;
        }
        if (isset($center_id)) {
            $map['center_id'] = $center_id;
        }
        if (isset($acc_no)) {
            $map['acc_no'] = $acc_no;
        }
        if (isset($image, $image_uri)) {
            $map += ['image' => $image, 'image_uri' => $image_uri];
        }
        if (isset($signature, $signature_uri)) {
            $map += ['signature' => $signature, 'signature_uri' => $signature_uri];
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
     * @param object $client
     * @return array
     */
    private static function set_update_hist($data, $client)
    {
        $histData                   = [];
        $data->present_address      = (object) $data->present_address;
        $data->permanent_address    = (object) $data->permanent_address;
        $fieldsToCompare            = ['name', 'husband_name', 'father_name', 'mother_name', 'nid', 'dob', 'occupation', 'religion', 'gender', 'primary_phone', 'secondary_phone', 'share', 'present_address', 'permanent_address'];
        $addressFields              = ['street_address', 'city', 'word_no', 'post_office', 'police_station', 'district', 'division'];

        foreach ($fieldsToCompare as $field) {
            if ($field === 'present_address' || $field === 'permanent_address') {
                $clientValue = '';
                $dataValue = '';

                foreach ($addressFields as $subField) {
                    $clientValue = $client->{$field}->{$subField} ?? '';
                    $dataValue = $data->{$field}->{$subField} ?? '';

                    if (!Helper::areValuesEqual($clientValue, $dataValue)) {
                        $histData[$field][$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>";
                    }
                }
            } else {
                $clientValue = $client->{$field} ?? '';
                $dataValue = $data->{$field} ?? '';

                if (!Helper::areValuesEqual($clientValue, $dataValue)) {
                    $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>";
                }
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
                ? Helper::storeImage($newImg, $fieldName, $directory)
                : Helper::storeSignature($newImg, $fieldName, $directory);
            $histData[$histKey] = "<p class='text-danger'>********</p><p class='text-success'>********</p>";

            $model->update([
                $fieldName     => $file->name,
                $uriFieldName  => $file->uri,
            ]);
        }
    }
}
