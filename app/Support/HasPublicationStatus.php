<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

trait HasPublicationStatus
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
