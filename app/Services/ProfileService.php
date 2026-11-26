<?php

namespace App\Services;

use App\Models\OccupationCategory;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\UserExperience;
use App\Models\UserProfile;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function payload(User $user): array
    {
        $user->load([
            'profile',
            'skills.skill',
            'experiences' => fn ($query) => $query->orderByDesc('id'),
            'certifications.certification' => fn ($query) => $query->orderByDesc('id'),
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile' => $user->profile,
            'skills' => $user->skills->map(fn (UserSkill $skill) => [
                'id' => $skill->id,
                'skill_id' => $skill->skill_id,
                'name' => $skill->skill?->name,
                'level' => $skill->level,
                'possession_status' => $skill->possession_status,
                'self_reported' => $skill->self_reported,
            ])->values(),
            'experiences' => $user->experiences,
            'certifications' => $user->certifications,
        ];
    }

    public function update(User $user, array $data): array
    {
        DB::transaction(function () use ($user, $data) {
            $profileData = collect($data)->only([
                'display_name', 'age_range', 'region', 'education_level', 'field_of_study',
                'institution', 'bio', 'interests', 'work_preferences', 'experiences_reviewed',
                'certifications_reviewed', 'onboarding_completed',
            ])->all();

            if (isset($profileData['display_name']) && $profileData['display_name'] !== $user->name) {
                $user->update(['name' => $profileData['display_name']]);
            }

            if (isset($profileData['interests'])) {
                $profileData['interests'] = OccupationCategory::published()
                    ->whereIn('id', $profileData['interests'])->pluck('id')->values()->all();
            }

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData + ['display_name' => $user->name],
            );

            if (array_key_exists('skills', $data)) {
                $skillIds = collect($data['skills'])->pluck('skill_id')->filter()->unique()->values();
                UserSkill::where('user_id', $user->id)
                    ->whereNotIn('skill_id', $skillIds)->delete();

                foreach ($data['skills'] as $skill) {
                    UserSkill::updateOrCreate([
                        'user_id' => $user->id,
                        'skill_id' => $skill['skill_id'],
                    ], [
                        'level' => $skill['possession_status'] === 'possessed' ? $skill['level'] : null,
                        'possession_status' => $skill['possession_status'],
                        'self_reported' => true,
                    ]);
                }
            }

            if (array_key_exists('experiences', $data)) {
                UserExperience::where('user_id', $user->id)->delete();
                foreach ($data['experiences'] as $experience) {
                    UserExperience::create(array_merge($experience, ['user_id' => $user->id]));
                }
            }

            if (array_key_exists('certifications', $data)) {
                UserCertification::where('user_id', $user->id)->delete();
                foreach ($data['certifications'] as $certification) {
                    UserCertification::create(array_merge($certification, ['user_id' => $user->id]));
                }
            }
        });

        return $this->payload($user->fresh());
    }
}
