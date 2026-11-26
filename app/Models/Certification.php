<?php

namespace App\Models;

use App\Support\HasPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Certification extends Model
{
    use HasPublicationStatus;

    protected $fillable = ['code', 'name', 'issuer', 'credential_type', 'validity_notes', 'source_id', 'status'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(Occupation::class, 'occupation_certifications')
            ->withPivot(['requirement_type', 'notes']);
    }
}
