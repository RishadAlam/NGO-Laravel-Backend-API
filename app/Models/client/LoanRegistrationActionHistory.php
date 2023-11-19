<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\client\LoanRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanRegistrationActionHistory extends Model
{
    use HasFactory;

    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
   protected $fillable = [
       'loan_registration_id',
       'author_id',
       'name',
       'image_uri',
       'action_type',
       'action_details'
   ];

   /**
    * Relationship belongs to LoanRegistration model
    *
    * @return response()
    */
   public function LoanRegistration()
   {
       return $this->belongsTo(LoanRegistration::class)->withTrashed();
   }

   /**
    * Relationship belongs to User model
    *
    * @return response()
    */
   public function Author()
   {
       return $this->belongsTo(User::class, 'author_id', 'id')->withTrashed();
   }

   /**
    * Mutator for action Details json Data
    */
   public function setActionDetailsAttribute($value)
   {
       $this->attributes['action_details'] = json_encode($value);
   }

   /**
    * accessor for action Details json Data
    */
   public function getActionDetailsAttribute($value)
   {
       return json_decode($value);
   }
}
