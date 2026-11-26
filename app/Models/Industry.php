<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['code', 'name', 'description', 'status'];

    public function workplaces(): HasMany
    {
        return $this->hasMany(WorkplaceType::class);
    }
}
