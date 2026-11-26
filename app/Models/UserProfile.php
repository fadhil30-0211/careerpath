<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id', 'display_name', 'age_range', 'region', 'education_level',
        'field_of_study', 'institution', 'bio', 'interests', 'work_preferences',
        'experiences_reviewed', 'certifications_reviewed', 'onboarding_completed',
    ];

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'work_preferences' => 'array',
            'experiences_reviewed' => 'boolean',
            'certifications_reviewed' => 'boolean',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
