<?php

namespace App\Http\Traits;

use App\Models\center\Center;

trait BelongsToCenterTrait
{
    /**
     * Relationship belongs to Center model
     *
     * @return response()
     */
    public function Center()
    {
        return $this->belongsTo(Center::class)->withTrashed();
    }
}
