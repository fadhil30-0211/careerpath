<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OccupationGroup extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['subcategory_id', 'code', 'name', 'description', 'sort_order', 'status'];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(OccupationSubcategory::class, 'subcategory_id');
    }

    public function occupations(): HasMany
    {
        return $this->hasMany(Occupation::class, 'group_id');
    }
}
