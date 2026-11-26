<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EducationProgram extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['code', 'name', 'education_level', 'program_type', 'description', 'source_id', 'status'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(Occupation::class, 'occupation_educations')
            ->withPivot(['relationship_type', 'notes']);
    }
}
