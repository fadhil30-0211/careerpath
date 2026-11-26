<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRoadmap;
use App\Services\Matching\MatchingEngine;
use App\Services\Roadmap\RoadmapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use CatalogFixture, RefreshDatabase;

    public function test_me_requires_authentication_and_regular_users_cannot_access_admin_overview(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->getJson('/api/admin/overview')->assertForbidden();
    }

    public function test_admin_overview_reports_publication_counts_from_each_resource_table(): void
    {
        $this->minimalPublished('overview');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/overview')
            ->assertOk()
            ->assertJsonPath('data.status_counts.occupations.published', 1)
            ->assertJsonPath('data.status_counts.skills.published', 3)
            ->assertJsonPath('data.status_counts.educations.published', 1)
            ->assertJsonPath('data.status_counts.certifications.published', 1);
    }

    public function test_user_cannot_read_another_users_matching_run(): void
    {
        $this->minimalPublished('ownership-run');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $run = app(MatchingEngine::class)->run($owner);
        Sanctum::actingAs($intruder);

        $this->getJson('/api/career-matching/runs/'.$run->id)->assertNotFound();

        Sanctum::actingAs($owner);
        $this->getJson('/api/career-matching/runs/'.$run->id)
            ->assertOk()
            ->assertJsonPath('data.id', $run->id);
    }

    public function test_user_cannot_read_or_update_another_users_roadmap(): void
    {
        $catalog = $this->minimalPublished('ownership');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $userRoadmap = app(RoadmapService::class)->start($owner, $catalog['roadmap']->id);
        $stage = $catalog['stages'][0];
        Sanctum::actingAs($intruder);

        $this->getJson('/api/me/roadmaps/'.$userRoadmap->id)->assertNotFound();
        $this->patchJson('/api/me/roadmaps/'.$userRoadmap->id.'/stages/'.$stage->id, [
            'status' => 'completed',
        ])->assertNotFound();

        $this->assertDatabaseMissing('roadmap_progress', [
            'user_roadmap_id' => $userRoadmap->id,
            'stage_id' => $stage->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('user_roadmaps', 1);
        $this->assertSame($owner->id, UserRoadmap::sole()->user_id);
    }
}
