<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['meta_key', 'meta_value',];

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
