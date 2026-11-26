<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OccupationCategory extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['code', 'slug', 'name', 'description', 'icon', 'color', 'sort_order', 'status'];

    public function subcategories(): HasMany
    {
        return $this->hasMany(OccupationSubcategory::class, 'category_id')->orderBy('sort_order');
    }
}
