<?php

namespace App\Http\Traits;


use App\Models\field\Field;

trait BelongsToFieldTrait
{

    /**
     * Relationship belongs to Field model
     *
     * @return response()
     */
    public function Field()
    {
        return $this->belongsTo(Field::class)->withTrashed();
    }
}
