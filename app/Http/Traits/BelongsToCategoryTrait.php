<?php

namespace App\Http\Traits;


use App\Models\category\Category;

trait BelongsToCategoryTrait
{
    /**
     * Relationship belongs to Category model
     *
     * @return response()
     */
    public function Category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }
}
