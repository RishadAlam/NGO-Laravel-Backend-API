<?php

namespace App\Http\Controllers\client;

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
        // return $data;
        // die;
        // $is_approved    = AppConfig::where('meta_key', 'saving_registration_approval')
        //     ->value('meta_value');

        DB::transaction(function () use ($data, $nominees) {
            $saving_account = SavingAccount::create(
                [
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
                    'is_approved'                       => false,
                    'creator_id'                        => $data->creator_id ?? auth()->id(),
                ]
            );

            $nominees_arr = [];
            foreach ($nominees as $nominee) {
                $nominee    = (object) $nominee;
                $extension  = $nominee->image->extension();
                $imgName    = 'nominee_' . time() . '.' . $extension;
                $nominee->image->move(public_path() . '/storage/nominees/', $imgName);

                $sign       = null;
                $sign_uri   = null;
                if (!empty($nominee->signature)) {
                    $folderPath     = public_path() . '/storage/nominees/';
                    $image_parts    = explode(";base64,", $nominee->signature);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type     = $image_type_aux[1];
                    $image_base64   = base64_decode($image_parts[1]);
                    $sign           = 'nominee_signature_' . time() . '.' . $image_type;
                    file_put_contents($folderPath . $sign, $image_base64);
                    $sign_uri       = URL::to('/storage/nominees/', $sign);
                }

                $nominees_arr[] = [
                    'saving_account_id'         => $saving_account->id,
                    'name'                      => $nominee->name,
                    'father_name'               => $nominee->father_name,
                    'husband_name'              => isset($nominee->husband_name) ? $nominee->husband_name : '',
                    'mother_name'               => $nominee->mother_name,
                    'nid'                       => $nominee->nid,
                    'dob'                       => $nominee->dob,
                    'occupation'                => $nominee->occupation,
                    'relation'                  => $nominee->relation,
                    'gender'                    => $nominee->gender,
                    'primary_phone'             => $nominee->primary_phone,
                    'secondary_phone'           => isset($nominee->secondary_phone) ? $nominee->secondary_phone : '',
                    'image'                     => $imgName,
                    'image_uri'                 => URL::to('/storage/nominees/', $imgName),
                    'signature'                 => $sign,
                    'signature_uri'             => $sign_uri,
                    'address'                   => json_encode($nominee->address),
                ];
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
     * Nominees Validation
     */
    private static function nominee_validation($nominees)
    {
        $errors = [];
        foreach ($nominees as $key => $nominee) {
            $nominee = (array) $nominee;
            $validated = Validator::make(
                $nominee,
                [
                    'name'              => 'required',
                    'father_name'       => "required",
                    'husband_name'      => "nullable",
                    'mother_name'       => "required|numeric",
                    'nid'               => "required|numeric",
                    'dob'               => "required|date",
                    'occupation'        => "required",
                    'relation'          => "required",
                    'gender'            => "required",
                    'primary_phone'     => "nullable|phone:BD",
                    'secondary_phone'   => "nullable|phone:BD",
                    'image'             => "required|mimes:jpeg,png,jpg,webp|max:5120",
                    'signature'         => "nullable",
                    'address'           => "required"
                ]
            );
            return $validated->fails();
            // return $nominee;
            die;
            if ($validated->fails()) {
                $errors['nominees'[$key]] = $validated->errors()->toArray();
            } else {
                $result = self::address_validation((array) $nominee['address']);
                if (!empty($result)) {
                    $errors['nominees'[$key]] = $result;
                }
            }
        }
        return $errors;
    }

    /**
     * Address Validation
     */
    private static function address_validation($address)
    {
        $validated = Validator::make(
            $address,
            [
                'street_address'    => 'required',
                'city'              => "required",
                'post_office'       => "required",
                'police_station'    => "required",
                'state'             => "required",
                'division'          => "required",
            ]
        );

        if ($validated->fails()) {
            return $validated->errors()->toArray();
        }
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
}
