<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapProgress extends Model
{
    protected $fillable = ['user_roadmap_id', 'stage_id', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function userRoadmap(): BelongsTo
    {
        return $this->belongsTo(UserRoadmap::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(RoadmapStage::class);
    }
}
