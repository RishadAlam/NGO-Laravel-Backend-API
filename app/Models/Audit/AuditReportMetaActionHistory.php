<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditReportMetaActionHistory extends Model
{
    use HasFactory, BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audit_report_meta_id',
        'author_id',
        'name',
        'image_uri',
        'action_type',
        'action_details'
    ];

    // /**
    //  * Relationship belongs to User model
    //  *
    //  * @return response()
    //  */
    // public function AuditReportMeta()
    // {
    //     return $this->belongsTo(AuditReportMeta::class)->withTrashed();
    // }

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
