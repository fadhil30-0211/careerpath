<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataSource extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['code', 'name', 'source_type', 'url', 'publisher', 'verified_at', 'notes', 'status'];

    protected function casts(): array
    {
        return ['verified_at' => 'date'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->published();
    }
}
