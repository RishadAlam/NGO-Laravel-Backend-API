<?php

namespace App\Helpers;

use Illuminate\Support\Facades\URL;

class Helper
{
    /**
     * Create Action History
     * 
     * @param string $foreignIdKey
     * @param integer $foreignId
     * @param string $action
     * @param array $histData
     * @return array
     */
    public static function setActionHistory(string $foreignIdKey, int $foreignId, string $action, array $histData = array())
    {
        return [
            $foreignIdKey       => $foreignId,
            "author_id"         => auth()->id(),
            "name"              => auth()->user()->name,
            "image_uri"         => auth()->user()->image_uri,
            "action_type"       => $action,
            "action_details"    => $histData,
        ];
    }

    /**
     * Set Nominee/Guarantor Field Map
     * 
     * @param object $data
     * @param string $foreignIdKey
     * @param integer $id
     * @param boolean $jsonAddress
     * @param string $image
     * @param string $image_uri
     * @param string $signature
     * @param string $signature_uri
     * @return array
     */
    public static function set_nomi_field_map(object $data, string $foreignIdKey = null, int $id = null, bool $jsonAddress = false, string $image = null, string $image_uri = null, string $signature = null, string $signature_uri = null)
    {
        $map = [
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
            'address'                   => $jsonAddress ? json_encode($data->address) : $data->address,
        ];

        if (isset($foreignIdKey, $id)) {
            $map += [$foreignIdKey => $id];
        }
        if (isset($image, $image_uri)) {
            $map += ['image' => $image, 'image_uri' => $image_uri];
        }
        if (isset($signature, $signature_uri)) {
            $map += ['signature' => $signature, 'signature_uri' => $signature_uri];
        }

        return $map;
    }

    /**
     * Set Saving & loan Acc update Nominee/Guarantor hist
     * 
     * @param array $histData
     * @param object $nomineeData
     * @param object $nominee
     * 
     * @return array
     */
    public static function set_update_nomiguarantor_hist(&$histData, $nomineeData, $nominee)
    {
        $nomineeData->address   = (object) $nomineeData->address;
        $nominee->address       = (object) $nominee->address;
        $fieldsToCompare        = ['name', 'husband_name', 'father_name', 'mother_name', 'nid', 'dob', 'occupation', 'relation', 'gender', 'primary_phone', 'secondary_phone', 'address'];
        $addressFields          = ['street_address', 'city', 'word_no', 'post_office', 'police_station', 'district', 'division'];

        foreach ($fieldsToCompare as $field) {
            if ($field === 'address') {
                foreach ($addressFields as $subField) {
                    $clientValue    = $nominee->{$field}->{$subField} ?? '';
                    $dataValue      = $nomineeData->{$field}->{$subField} ?? '';
                    !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
                }
            } else {
                $clientValue    = $nominee->{$field} ?? '';
                $dataValue      = $nomineeData->{$field} ?? '';
                !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
            }
        }
    }

    /**
     * Create nested array from properties in array formate.
     *
     * @param array &$rootObj
     * @param array $properties
     * @param string $value
     * @return void
     */
    public static function createNestedArray(&$rootObj, $properties, $value)
    {
        $lastKey = array_pop($properties);
        $current = &$rootObj;

        foreach ($properties as $nestedKey) {
            $current = &$current[$nestedKey];
        }

        $current[$lastKey] = $value;

        // $firstProperty = array_shift($properties);
        // $current = &$rootObj;

        // if (is_array($current)) {
        //     if (!array_key_exists($firstProperty, $current)) {
        //         $current[$firstProperty] = [];
        //     }
        //     if (count($properties) > 0) {
        //         $this->createNestedArray($current[$firstProperty], $properties, $value);
        //     } else {
        //         $current[$firstProperty] = $value;
        //     }
        // }
    }

    /**
     * Store Image
     * 
     * @param object $image
     * @param string $prefix
     * @param string $folder
     * @return object 
     */
    public static function storeImage($image, $prefix, $folder)
    {
        $extension  = $image->extension();
        $img_name   = $prefix . '_' . time() . '.' . $extension;
        $image->move(public_path() . "/storage/{$folder}/", $img_name);
        $img_uri    = URL::to("/storage/{$folder}/", $img_name);

        return (object) [
            "name"  => $img_name,
            "uri"   => $img_uri,
        ];
    }

    /**
     * Store Signature
     * 
     * @param string $signature
     * @param string $prefix
     * @param string $folder
     * @return object 
     */
    public static function storeSignature($signature, $prefix, $folder)
    {
        $folder_path    = public_path() . "/storage/{$folder}/";
        $image_parts    = explode(";base64,", $signature);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type     = $image_type_aux[1];
        $image_base64   = base64_decode($image_parts[1]);
        $sign           = $prefix . '_' . time() . '.' . $image_type;
        file_put_contents($folder_path . $sign, $image_base64);
        $sign_uri       = URL::to("/storage/{$folder}/", $sign);

        return (object) [
            "name"  => $sign,
            "uri"   => $sign_uri,
        ];
    }

    /**
     * Store Signature
     * 
     * @param string $$path
     * @return void 
     */
    public static function unlinkImage($path = null)
    {
        if (!empty($path)) {
            unlink($path);
        }
    }

    /**
     * Store Signature
     * 
     * @param mixed $value1
     * @param mixed $value2
     * @param boolean $strict
     * @return boolean
     */
    public static function areValuesEqual($value1, $value2, $strict = false)
    {
        if ($strict) {
            return $value1 === $value2;
        } else {
            return $value1 == $value2;
        }
    }
}
