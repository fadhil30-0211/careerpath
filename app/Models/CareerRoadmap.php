<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerRoadmap extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['occupation_id', 'code', 'name', 'description', 'status'];

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(RoadmapStage::class, 'roadmap_id')->orderBy('sequence');
    }
}
