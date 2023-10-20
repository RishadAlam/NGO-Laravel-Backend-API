<?php

namespace App\Models\accounts;

use App\Models\User;
use App\Models\accounts\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountActionHistory extends Model
{
    use HasFactory;

    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
   protected $fillable = [
       'account_id',
       'author_id',
       'name',
       'image_uri',
       'action_type',
       'action_details'
   ];

   /**
    * Relationship belongs to Account model
    *
    * @return response()
    */
   public function Account()
   {
       return $this->belongsTo(Account::class)->withTrashed();
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
