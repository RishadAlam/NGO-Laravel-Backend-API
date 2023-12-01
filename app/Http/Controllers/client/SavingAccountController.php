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
use App\Http\Requests\client\SavingAccountStoreRequest;

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
            ->with("Field:id,name")
            ->with("Center:id,name")
            ->when(request('fetch_pending'), function ($query) {
                $query->where('is_approved', false);
            })
            ->when(request('field_id'), function ($query) {
                $query->where('field_id', request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->where('center_id', request('center_id'));
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
        $errors         = [];
        $data           = (object) $request->validated();
        $nominees       = $data->nominees;
        $is_approved    = AppConfig::where('meta_key', 'saving_account_registration_approval')
            ->value('meta_value');

        DB::transaction(function () use ($data, $is_approved, $nominees) {
            $saving_account = SavingAccount::create(self::set_saving_field_map($data, $is_approved, $data->creator_id));

            $nominees_arr   = [];
            foreach ($nominees as $nominee) {
                $nominee    = (object) $nominee;
                $img        = Helper::storeImage($nominee->image, "nominee", "nominees");
                $signature  = isset($nominee->signature)
                    ? Helper::storeSignature($nominee->signature, "nominee_signature", "nominees")
                    : (object) ["name" => null, "uri" => null];

                $nominees_arr[] = self::set_nominee_field_map(
                    $saving_account->id,
                    $nominee,
                    true,
                    $img->name,
                    $img->uri,
                    $signature->name,
                    $signature->uri
                );
            }

            Nominee::insert($nominees_arr);
        });

        return create_response(__('customValidations.client.saving.successful'));
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
     * Set Nominee Field Map
     * 
     * @param integer $saving_account_id
     * @param object $data
     * @param boolean $jsonAddress
     * @param string $image
     * @param string $image_uri
     * @param string $signature
     * @param string $signature_uri
     * @return array
     */
    private static function set_nominee_field_map($saving_account_id, $data, $jsonAddress = false, $image = null, $image_uri = null, $signature = null, $signature_uri = null)
    {
        $map = [
            'saving_account_id'         => $saving_account_id,
            'name'                      => $data->name,
            'father_name'               => $data->father_name,
            'husband_name'              => isset($data->husband_name) ? $data->husband_name : '',
            'mother_name'               => $data->mother_name,
            'nid'                       => $data->nid,
            'dob'                       => $data->dob,
            'occupation'                => $data->occupation,
            'relation'                  => $data->relation,
            'gender'                    => $data->gender,
            'primary_phone'             => $data->primary_phone,
            'secondary_phone'           => isset($data->secondary_phone) ? $data->secondary_phone : '',
            'address'                   => $data->address,
        ];

        if ($jsonAddress) {
            $map['address'] = json_encode($data->address);
        }
        if (isset($image) && isset($image_uri)) {
            $map['image'] = $image;
            $map['image_uri'] = $image_uri;
        }
        if (isset($signature) && isset($signature_uri)) {
            $map['signature'] = $signature;
            $map['signature_uri'] = $signature_uri;
        }
        if (isset($creator_id)) {
            $map['creator_id'] = $creator_id ?? auth()->id();
        }

        return $map;
    }
}
