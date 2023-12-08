<?php

namespace App\Http\Controllers\client;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\client\Nominee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Validator;
use App\Models\client\SavingAccountActionHistory;
use App\Http\Requests\client\SavingAccountStoreRequest;
use App\Http\Requests\client\SavingAccountUpdateRequest;

class SavingAccountController extends Controller
{
    /**
     * Action History Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "saving_account_id" => $id,
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
        $saving_accounts = SavingAccount::with('Author:id,name')
            ->with("ClientRegistration:id,acc_no,name,image_uri")
            ->with("Field:id,name")
            ->with("Center:id,name")
            ->with("Category:id,name,is_default")
            ->with("Nominees:id,saving_account_id,name,father_name,husband_name,mother_name,nid,dob,occupation,relation,gender,primary_phone,secondary_phone,image,image_uri,signature,signature_uri,address")
            ->when(request('fetch_pending_forms'), function ($query) {
                $query->where('is_approved', false);
            })
            ->when(request('field_id'), function ($query) {
                $query->where('field_id', request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->where('center_id', request('center_id'));
            })
            ->when(request('category_id'), function ($query) {
                $query->where('category_id', request('category_id'));
            })
            ->when(request('user_id'), function ($query) {
                $query->where('creator_id', request('user_id'));
            })
            ->orderBy('id', 'DESC')
            ->get();

        return response(
            [
                'success'   => true,
                'data'      => $saving_accounts
            ],
            200
        );
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
            $saving_account = SavingAccount::create(self::set_saving_field_map($data, $is_approved, $data->creator_id));

            foreach ($nominees as $nominee) {
                $nominee    = (object) $nominee;
                $img        = Helper::storeImage($nominee->image, "nominee", "nominees");
                $signature  = !empty($nominee->signature)
                    ? Helper::storeSignature($nominee->signature, "nominee_signature", "nominees")
                    : (object) ["name" => null, "uri" => null];

                Nominee::create(Helper::set_nomi_field_map(
                    $nominee,
                    'saving_account_id',
                    $saving_account->id,
                    false,
                    $img->name,
                    $img->uri,
                    $signature->name,
                    $signature->uri
                ));
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
                foreach ($nominees as $nominee) {
                    self::updateNominee((object) $nominee, $histData);
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
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        SavingAccount::find($id)->update(['is_approved' => true]);
        return create_response(__('customValidations.client.saving.approved'));
    }

    /**
     * Get all Occupations
     */
    public function get_nominee_occupations()
    {
        $occupations = Nominee::distinct('occupation')->orderBy('occupation', 'asc')->pluck('occupation');
        return create_response(null, $occupations);
    }

    /**
     * Get all Relation
     */
    public function get_nominee_relations()
    {
        $relations = Nominee::distinct('relation')->orderBy('relation', 'asc')->pluck('relation');
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
    private static function set_saving_field_map($data, $is_approved = null, $creator_id = null)
    {
        $map = [
            'field_id'                          => $data->field_id,
            'center_id'                         => $data->center_id,
            'category_id'                       => $data->category_id,
            'client_registration_id'            => $data->client_registration_id,
            'acc_no'                            => $data->acc_no,
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

        if (isset($is_approved)) {
            $map['is_approved'] = $is_approved;
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
                    $clientValue = $client->{$field}->{$subField};
                    $dataValue = $data->{$field}->{$subField};
                    !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
                }
            } else {
                $clientValue = $client->{$field};
                $dataValue = $data->{$field};
                !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
            }
        }

        return $histData;
    }

    private static function updateNominee($nomineeData, &$histData)
    {
        $nominee = Nominee::find($nomineeData['id']);

        self::updateFile($nominee, $nomineeData['image'], 'nominee_image', 'image', 'image_uri', 'nominees', $histData);
        self::updateFile($nominee, $nomineeData['signature'], 'nominee_signature', 'signature', 'signature_uri', 'nominees', $histData);

        $nominee->update(Helper::set_nomi_field_map($nominee));
    }

    private static function updateFile($model, $filename, $histKey, $fieldName, $uriFieldName, $directory, &$histData)
    {
        if (!empty($filename) && !empty($model->{$fieldName})) {
            Helper::unlinkImage(public_path("storage/nominees/{$model->{$fieldName}}"));
        }

        if (!empty($filename)) {
            $file = Helper::storeImage($filename, $fieldName, $directory);
            $histData[$histKey] = "<p class='text-danger'>********</p><p class='text-success'>********</p>";

            $model->update([
                $fieldName     => $file->name,
                $uriFieldName  => $file->uri,
            ]);
        }
    }
}
