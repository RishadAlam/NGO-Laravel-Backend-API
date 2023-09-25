<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    use HasFactory;


    /**
     * Mutator for App Config json Data
     */
    public function setMetaValueAttribute($value)
    {
        $this->attributes['meta_value'] = json_encode($value);
    }

    /**
     * accessor for App Config json Data
     */
    public function getMetaValueAttribute($value)
    {
        return json_decode($value);
    }
}
