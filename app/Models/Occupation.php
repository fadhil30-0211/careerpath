<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Occupation extends Model
{
    use HasPublicationStatus;

    protected $fillable = [
        'group_id', 'source_id', 'code', 'slug', 'name', 'summary', 'description',
        'main_activities', 'responsibilities', 'work_environment', 'alternative_careers',
        'work_preferences', 'is_demo', 'verified_at', 'version', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'main_activities' => 'array',
            'work_preferences' => 'array',
            'is_demo' => 'boolean',
            'verified_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->published()
            ->whereHas('group', fn (Builder $group) => $group->published()
                ->whereHas('subcategory', fn (Builder $subcategory) => $subcategory->published()
                    ->whereHas('category', fn (Builder $category) => $category->published())));
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(OccupationGroup::class, 'group_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'occupation_skills')
            ->withPivot(['target_level', 'requirement_type', 'importance', 'notes']);
    }

    public function educations(): BelongsToMany
    {
        return $this->belongsToMany(EducationProgram::class, 'occupation_educations', 'occupation_id', 'education_id')
            ->withPivot(['relationship_type', 'notes']);
    }

    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class, 'occupation_certifications')
            ->withPivot(['requirement_type', 'notes']);
    }

    public function workplaces(): BelongsToMany
    {
        return $this->belongsToMany(WorkplaceType::class, 'occupation_workplaces', 'occupation_id', 'workplace_id')
            ->withPivot('relevance_description');
    }

    public function roadmaps(): HasMany
    {
        return $this->hasMany(CareerRoadmap::class);
    }

    public function matchingResults(): HasMany
    {
        return $this->hasMany(MatchingResult::class);
    }
}
