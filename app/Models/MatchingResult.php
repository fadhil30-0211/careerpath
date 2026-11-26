<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchingResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'run_id', 'occupation_id', 'rank', 'score', 'coverage', 'profession_snapshot',
        'breakdown', 'skill_gaps', 'mandatory_requirements', 'reasons', 'unknown_fields',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'coverage' => 'decimal:2',
            'profession_snapshot' => 'array',
            'breakdown' => 'array',
            'skill_gaps' => 'array',
            'mandatory_requirements' => 'array',
            'reasons' => 'array',
            'unknown_fields' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(MatchingRun::class, 'run_id');
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }
}
