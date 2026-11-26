<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchingRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'algorithm_version', 'policy_snapshot', 'input_snapshot',
        'catalog_snapshot', 'result_count', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'policy_snapshot' => 'array',
            'input_snapshot' => 'array',
            'catalog_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(MatchingResult::class, 'run_id')->orderBy('rank');
    }
}
