<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapStage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'roadmap_id', 'code', 'sequence', 'title', 'objective', 'action',
        'resource_title', 'resource_url', 'estimated_weeks',
    ];

    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(CareerRoadmap::class, 'roadmap_id');
    }
}
