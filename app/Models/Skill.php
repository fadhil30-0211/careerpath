<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['code', 'slug', 'name', 'category', 'description', 'level_framework', 'status'];

    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(Occupation::class, 'occupation_skills')
            ->withPivot(['target_level', 'requirement_type', 'importance', 'notes']);
    }
}
