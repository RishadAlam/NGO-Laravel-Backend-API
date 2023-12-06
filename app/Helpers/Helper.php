<?php

namespace App\Helpers;

use Illuminate\Support\Facades\URL;

class Helper
{
    /**
     * Create Action History
     * 
     * @param integer $foreignId
     * @param integer $id
     * @param string $action
     * @param array $histData
     * @return array
     */
    public static function setActionHistory(int $foreignId, int $id, string $action, array $histData)
    {
        return [
            $foreignId          => $id,
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
    public static function set_nomi_field_map(object $data, string $foreignIdKey, int $id, bool $jsonAddress = false, string $image = null, string $image_uri = null, string $signature = null, string $signature_uri = null)
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
