<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkplaceType extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['industry_id', 'code', 'name', 'description', 'status'];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(Occupation::class, 'occupation_workplaces')
            ->withPivot('relevance_description');
    }
}
