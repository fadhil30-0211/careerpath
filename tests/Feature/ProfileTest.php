<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use CatalogFixture, RefreshDatabase;

    public function test_possessed_skill_requires_a_level_from_one_to_five(): void
    {
        $catalog = $this->minimalPublished('profile-skill-level');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach ([null, 0, 6] as $invalidLevel) {
            $skill = ['skill_id' => $catalog['skills']['analysis']->id, 'possession_status' => 'possessed'];
            if ($invalidLevel !== null) {
                $skill['level'] = $invalidLevel;
            }

            $this->putJson('/api/me', ['skills' => [$skill]])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('skills.0.level');
            $this->assertDatabaseMissing('user_skills', [
                'user_id' => $user->id,
                'skill_id' => $catalog['skills']['analysis']->id,
            ]);
        }

        foreach ([1, 5] as $validLevel) {
            $this->putJson('/api/me', ['skills' => [[
                'skill_id' => $catalog['skills']['analysis']->id,
                'possession_status' => 'possessed',
                'level' => $validLevel,
            ]]])->assertOk()
                ->assertJsonPath('data.skills.0.skill_id', $catalog['skills']['analysis']->id)
                ->assertJsonPath('data.skills.0.level', $validLevel)
                ->assertJsonPath('data.skills.0.possession_status', 'possessed');
            $this->assertDatabaseHas('user_skills', [
                'user_id' => $user->id,
                'skill_id' => $catalog['skills']['analysis']->id,
                'level' => $validLevel,
                'possession_status' => 'possessed',
            ]);
        }

        $this->putJson('/api/me', ['skills' => [[
            'skill_id' => $catalog['skills']['analysis']->id,
            'possession_status' => 'not_yet',
        ]]])->assertOk()->assertJsonPath('data.skills.0.level', null);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_id' => $catalog['skills']['analysis']->id,
            'level' => null,
            'possession_status' => 'not_yet',
        ]);
    }

    public function test_certification_requires_catalog_id_or_custom_name(): void
    {
        $catalog = $this->minimalPublished('profile-certification');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/me', ['certifications' => [['issuer' => 'Fixture Issuer']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('certifications.0.custom_name');
        $this->assertDatabaseCount('user_certifications', 0);

        $this->putJson('/api/me', ['certifications' => [[
            'certification_id' => $catalog['certification']->id,
        ]]])->assertOk()
            ->assertJsonPath('data.certifications.0.certification_id', $catalog['certification']->id)
            ->assertJsonPath('data.certifications.0.custom_name', null);
        $this->assertDatabaseHas('user_certifications', [
            'user_id' => $user->id,
            'certification_id' => $catalog['certification']->id,
        ]);

        $this->putJson('/api/me', ['certifications' => [[
            'custom_name' => 'Fixture Custom Certification',
            'issuer' => 'Custom Issuer',
        ]]])->assertOk()
            ->assertJsonPath('data.certifications.0.certification_id', null)
            ->assertJsonPath('data.certifications.0.custom_name', 'Fixture Custom Certification');
        $this->assertDatabaseHas('user_certifications', [
            'user_id' => $user->id,
            'certification_id' => null,
            'custom_name' => 'Fixture Custom Certification',
        ]);
        $this->assertDatabaseCount('user_certifications', 1);
    }
}
