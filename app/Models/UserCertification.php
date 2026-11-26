<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCertification extends Model
{
    protected $fillable = ['user_id', 'certification_id', 'custom_name', 'issuer', 'valid_until', 'self_reported'];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'self_reported' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }
}
