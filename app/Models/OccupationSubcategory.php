<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OccupationSubcategory extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['category_id', 'code', 'name', 'description', 'sort_order', 'status'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(OccupationCategory::class, 'category_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(OccupationGroup::class, 'subcategory_id')->orderBy('sort_order');
    }
}
