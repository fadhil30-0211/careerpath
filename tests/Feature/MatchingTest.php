<?php

namespace Tests\Feature;

use App\Models\MatchingRun;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\UserSkill;
use App\Services\Matching\MatchingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use CatalogFixture, RefreshDatabase;

    private function runWithInput(array $catalog, array $input)
    {
        $this->putJson('/api/me', array_merge($input, [
            'skills' => array_map(fn (array $skill) => [
                'skill_id' => $skill['skill_id'],
                'possession_status' => $skill['possession_status'],
                'level' => $skill['level'] ?? null,
            ], $input['skills']),
            'experiences' => $input['experiences'] ?? [],
            'certifications' => array_map(fn (array $certification) => [
                'certification_id' => $certification['certification_id'],
                'custom_name' => $certification['custom_name'],
                'issuer' => $certification['issuer'] ?? null,
            ], $input['certifications']),
        ]))->assertOk();

        return $this->postJson('/api/career-matching/runs', ['type' => 'matching'])
            ->assertCreated()
            ->assertJsonPath('data.candidate_count', 1)
            ->assertJsonCount(1, 'data.results')
            ->json('data.results.0');
    }

    public function test_unknown_skill_is_reported_as_unknown_and_does_not_count_as_a_known_gap(): void
    {
        $catalog = $this->minimalPublished('unknown-skill');
        $user = User::factory()->create();
        $unknownSkill = Skill::create([
            'code' => 'unknown-skill-extra',
            'slug' => 'unknown-skill-extra',
            'name' => 'Fixture Unconfirmed Skill',
            'category' => 'Fixture General',
            'status' => 'published',
        ]);
        $catalog['occupation']->skills()->attach($unknownSkill->id, [
            'source_id' => $catalog['source']->id,
            'target_level' => 3,
            'requirement_type' => 'recommended',
            'importance' => 1,
        ]);
        $input = $this->completeProfileInput($catalog);
        $input['skills'][] = [
            'skill_id' => $unknownSkill->id,
            'level' => null,
            'possession_status' => 'unknown',
        ];
        Sanctum::actingAs($user);

        $result = $this->runWithInput($catalog, $input);

        $this->assertContains('Sebagian informasi skill belum diisi.', $result['unknown_fields']);
        $gaps = collect($result['skill_gaps'])->where('skill_id', $unknownSkill->id);
        $this->assertCount(1, $gaps);
        $this->assertSame('unknown', $gaps->first()['status']);
        $this->assertNull($gaps->first()['user_level']);
        $this->assertNotSame('aligned', $gaps->first()['status']);
        $this->assertNotSame('not_yet_possessed', $gaps->first()['status']);
        $this->assertSame('incomplete_information', $result['readiness']['status']);
        $this->assertGreaterThan(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
        $this->assertGreaterThan(0, $result['coverage']);
        $this->assertLessThan(100, $result['coverage']);
    }

    public function test_an_absent_required_skill_is_unknown_not_a_failed_alignment(): void
    {
        $catalog = $this->minimalPublished('absent-skill');
        $user = User::factory()->create();
        $input = $this->completeProfileInput($catalog);
        $input['skills'] = array_values(array_filter(
            $input['skills'],
            fn (array $skill) => $skill['skill_id'] !== $catalog['skills']['analysis']->id,
        ));
        Sanctum::actingAs($user);

        $result = $this->runWithInput($catalog, $input);

        $mandatorySkill = collect($result['mandatory_requirements'])
            ->firstWhere('id', $catalog['skills']['analysis']->id);
        $skillGap = collect($result['skill_gaps'])
            ->firstWhere('skill_id', $catalog['skills']['analysis']->id);
        $this->assertSame('unknown', $mandatorySkill['status']);
        $this->assertSame('unknown', $skillGap['status']);
        $this->assertNotSame('aligned', $skillGap['status']);
        $this->assertNotSame('not_yet_possessed', $skillGap['status']);
        $this->assertSame('incomplete_information', $result['readiness']['status']);
        $this->assertIsNumeric($result['score']);
        $this->assertIsNumeric($result['coverage']);
        $this->assertLessThan(100, $result['coverage']);
    }

    public function test_mandatory_requirements_keep_satisfied_not_met_and_unknown_separate_from_a_high_score(): void
    {
        $catalog = $this->minimalPublished('mandatory-status');
        $catalog['occupation']->certifications()->updateExistingPivot($catalog['certification']->id, [
            'requirement_type' => 'mandatory',
        ]);
        $input = $this->completeProfileInput($catalog);
        $engine = app(MatchingEngine::class);
        $occupation = $catalog['occupation']->fresh();
        $occupation->load(['skills', 'educations', 'certifications', 'group.subcategory.category']);

        $cases = [
            'satisfied' => [
                'input' => $input,
                'statuses' => ['skill' => 'satisfied', 'education' => 'satisfied', 'certification' => 'satisfied'],
            ],
            'not_met' => [
                'input' => array_merge($input, [
                    'education_level' => 'SMA',
                    'skills' => array_map(fn (array $skill) => array_merge($skill, [
                        'level' => $skill['skill_id'] === $catalog['skills']['analysis']->id ? 1 : 5,
                    ]), $input['skills']),
                    'certifications' => [],
                ]),
                'statuses' => ['skill' => 'not_met', 'education' => 'not_met', 'certification' => 'not_met'],
            ],
            'unknown' => [
                'input' => array_merge($input, [
                    'education_level' => null,
                    'field_of_study' => null,
                    'certifications_reviewed' => false,
                    'skills' => array_map(fn (array $skill) => array_merge($skill, [
                        'level' => $skill['skill_id'] === $catalog['skills']['analysis']->id ? null : 5,
                        'possession_status' => $skill['skill_id'] === $catalog['skills']['analysis']->id ? 'unknown' : 'possessed',
                    ]), $input['skills']),
                    'certifications' => [],
                ]),
                'statuses' => ['skill' => 'unknown', 'education' => 'unknown', 'certification' => 'unknown'],
            ],
        ];

        foreach ($cases as $label => $case) {
            $result = $engine->score($occupation, $case['input']);
            $mandatory = collect($result['mandatory_requirements'])->keyBy('type');
            $this->assertSame(['skill', 'education', 'certification'], $mandatory->keys()->all(), $label);
            foreach ($case['statuses'] as $type => $status) {
                $this->assertSame($status, $mandatory[$type]['status'], "{$label}: {$type}");
            }

            if ($label === 'not_met') {
                $this->assertGreaterThan(50, $result['score']);
                $this->assertSame(100.0, $result['coverage']);
                $this->assertSame([], $result['unknown_fields']);
                $this->assertSame('needs_development', $result['readiness']['status']);
            } elseif ($label === 'unknown') {
                $this->assertSame('incomplete_information', $result['readiness']['status']);
            } else {
                $this->assertSame('ready_to_explore', $result['readiness']['status']);
            }
        }

        $catalog['education']->update(['education_level' => 'S2']);
        $higherEducation = $engine->score(
            $occupation->fresh()->load(['skills', 'educations', 'certifications', 'group.subcategory.category']),
            $input,
        );
        $this->assertSame('not_met', collect($higherEducation['mandatory_requirements'])->firstWhere('type', 'education')['status']);
        $this->assertSame('needs_development', $higherEducation['readiness']['status']);
    }

    public function test_matching_rank_order_is_deterministic_and_history_snapshots_do_not_change(): void
    {
        $catalog = $this->minimalPublished('determinism');
        $secondOccupation = $this->draftOccupation('determinism-second', $catalog);
        $secondOccupation->update(['status' => 'published', 'published_at' => now()]);

        foreach ($catalog['skills'] as $suffix => $skill) {
            $secondOccupation->skills()->attach($skill->id, [
                'source_id' => $catalog['source']->id,
                'target_level' => $suffix === 'analysis' ? 5 : ($suffix === 'communication' ? 3 : 4),
                'requirement_type' => $suffix === 'analysis' ? 'mandatory' : 'recommended',
                'importance' => $suffix === 'analysis' ? 5 : ($suffix === 'communication' ? 3 : 4),
            ]);
        }
        $secondOccupation->educations()->attach($catalog['education']->id, [
            'relationship_type' => 'mandatory',
        ]);
        $secondOccupation->certifications()->attach($catalog['certification']->id, [
            'requirement_type' => 'recommended',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->putJson('/api/me', $this->completeProfileInput($catalog))->assertOk();

        $firstResponse = $this->postJson('/api/career-matching/runs', ['type' => 'matching'])->assertCreated();
        $firstRun = MatchingRun::where('user_id', $user->id)->sole();
        $firstPayload = $firstResponse->json('data');
        $originalSnapshot = $firstRun->fresh()->getAttributes();

        $this->assertSame([1, 2], collect($firstPayload['results'])->pluck('rank')->all());
        $this->assertSame(
            [$catalog['occupation']->id, $secondOccupation->id],
            collect($firstPayload['results'])->pluck('profession.id')->all(),
        );
        $this->assertSame(
            $firstPayload['results'][0]['score'],
            $firstPayload['results'][1]['score'],
            'Equal scores must be ordered deterministically by occupation id.',
        );

        $secondResponse = $this->postJson('/api/career-matching/runs', ['type' => 'matching'])->assertCreated();
        $snapshot = fn (array $payload) => collect($payload['results'])->map(fn (array $result) => [
            'rank' => $result['rank'],
            'profession' => $result['profession'],
            'score' => $result['score'],
            'coverage' => $result['coverage'],
        ])->all();
        $this->assertSame($snapshot($firstPayload), $snapshot($secondResponse->json('data')));

        $catalog['occupation']->update([
            'name' => 'Changed Master Occupation',
            'summary' => 'Changed master summary.',
            'version' => 99,
        ]);
        $catalog['skills']['analysis']->update(['name' => 'Changed Master Skill']);
        $catalog['occupation']->skills()->updateExistingPivot($catalog['skills']['analysis']->id, [
            'target_level' => 1,
            'importance' => 1,
        ]);
        UserSkill::where('user_id', $user->id)->update(['level' => 1]);
        UserCertification::where('user_id', $user->id)->update(['custom_name' => 'Changed profile credential']);
        $user->profile()->update(['education_level' => 'SMA', 'field_of_study' => 'Changed Field']);

        $this->assertSame($originalSnapshot, $firstRun->fresh()->getAttributes());
        $history = $this->getJson('/api/me/analysis-history')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
        $historicalRun = collect($history->json('data.items'))->firstWhere('id', $firstRun->id);
        $this->assertNotNull($historicalRun);
        $this->assertSame($firstPayload['results'], $historicalRun['results']);
    }
}
