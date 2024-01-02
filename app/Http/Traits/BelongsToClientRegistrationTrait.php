<?php

namespace App\Http\Traits;


use App\Models\client\ClientRegistration;

trait BelongsToClientRegistrationTrait
{
    /**
     * Relationship belongs to ClientRegistration model.
     *
     * @return response()
     */
    public function ClientRegistration()
    {
        return $this->belongsTo(ClientRegistration::class)->withTrashed();
    }
}
