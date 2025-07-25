<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\field\Field;
use App\Models\category\Category;
use Illuminate\Support\Facades\Log;
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
     * Set Delete hist
     *
     * @param object $data
     * @param object $withdrawal
     *
     * @return array
     */
    public static function setDeleteHistory(object $data, array $fieldsToCompare, array $histData = [])
    {
        foreach ($fieldsToCompare as $field) {
            $dataValue      = $data->{$field} ?? '';

            if (!empty($dataValue)) {
                $histData[$field] = "<p class='text-danger'>{$dataValue}</p>";
            }
        }

        return $histData;
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
        if (!empty($image) || !empty($image_uri)) {
            $map += ['image' => $image, 'image_uri' => $image_uri];
        }
        if (!empty($signature) || !empty($signature_uri)) {
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

                    if (!Helper::areValuesEqual($clientValue, $dataValue)) {
                        $histData[$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>";
                    }
                }
            } else {
                $clientValue    = $nominee->{$field} ?? '';
                $dataValue      = $nomineeData->{$field} ?? '';

                if (!Helper::areValuesEqual($clientValue, $dataValue)) {
                    $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>";
                }
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

        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }

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

    /**
     * Translate Numbers
     *
     * @param string $numbers
     * @param bool $en
     *
     * @return string
     */
    public static function tsNumbers($numbers, $en = false)
    {
        $bn_to_en = [
            '১' => '1',
            '২' => '2',
            '৩' => '3',
            '৪' => '4',
            '৫' => '5',
            '৬' => '6',
            '৭' => '7',
            '৮' => '8',
            '৯' => '9',
            '০' => '0',
            '৳' => '$'
        ];
        $en_to_bn = [
            1 => '১',
            2 => '২',
            3 => '৩',
            4 => '৪',
            5 => '৫',
            6 => '৬',
            7 => '৭',
            8 => '৮',
            9 => '৯',
            0 => '০',
            '$' => '৳'
        ];

        return app()->getLocale() !== 'bn' || $en
            ? preg_replace_callback('/[১২৩৪৫৬৭৮৯০৳]/u', function ($matches) use ($bn_to_en) {
                return $bn_to_en[$matches[0]] ?? $matches[0];
            }, $numbers)
            : preg_replace_callback('/[1234567890$]/u', function ($matches) use ($en_to_bn) {
                return $en_to_bn[$matches[0]] ?? $matches[0];
            }, $numbers);
    }

    /**
     * Set default Name
     *
     * @param bool $isDefault
     * @param string $name
     * @param string $path
     *
     * @return string
     */
    public static function setDefaultName(bool $isDefault, string $name, string $path)
    {
        return $isDefault ? __("{$path}{$name}") : $name;
    }

    public static function getPermissionPrefix($isRegular = true)
    {
        return $isRegular ? 'regular' : 'pending';
    }

    public static function getDateRange($dateRange = null)
    {
        if ($dateRange) {
            $dateRange = json_decode($dateRange);
            $startDate = Carbon::parse($dateRange[0])->startOfDay();
            $endDate   = Carbon::parse($dateRange[1])->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate   = Carbon::now()->endOfDay();
        }

        return [$startDate, $endDate];
    }

    public static function calculateTransactionBalance($transactions, &$balance)
    {
        $transactions = collect($transactions);

        $totalCredit = $transactions->where('type', 'credit')->sum('amount');
        $totalDebit  = $transactions->where('type', 'debit')->sum('amount');
        $balance     -= $totalCredit - $totalDebit;

        return $transactions->map(function ($tx) use (&$balance) {
            if ($tx->type === 'credit') {
                $balance += $tx->amount;
            } elseif ($tx->type === 'debit') {
                $balance -= $tx->amount;
            }

            $tx->balance = $balance;

            return $tx;
        })->sortByDesc('created_at')->values();
    }

    /**
     * check the object null value
     */
    public static function getObject($object = null, $data = [])
    {
        if (empty($object)) {
            return;
        }

        return $object->only($data);
    }

    /**
     * Get Category Name by id
     */
    public static function getCategoryName($id)
    {
        $category = Category::find($id, ['name', 'is_default']);

        return $category->is_default ? __("customValidations.category.default.{$category->name}")  : $category->name;
    }

    /**
     * Get Field Name by id
     */
    public static function getFieldName($id)
    {
        return Field::find($id)->name ?? null;
    }
}
