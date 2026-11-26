<?php

namespace Tests\Feature;

use App\Models\CareerRoadmap;
use App\Models\User;
use App\Services\Roadmap\RoadmapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoadmapTest extends TestCase
{
    use CatalogFixture, RefreshDatabase;

    public function test_start_accepts_a_published_roadmap_and_initializes_its_stages_once(): void
    {
        $catalog = $this->minimalPublished('roadmap-start');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/me/roadmaps', [
            'roadmap_id' => $catalog['roadmap']->id,
        ])->assertCreated()
            ->assertJsonPath('data.occupation.id', $catalog['occupation']->id)
            ->assertJsonCount(2, 'data.stages')
            ->assertJsonPath('data.stages.0.progress_status', 'not_started')
            ->assertJsonPath('data.stages.1.progress_status', 'not_started');

        $userRoadmapId = $response->json('data.id');
        $this->postJson('/api/me/roadmaps', [
            'roadmap_id' => $catalog['roadmap']->id,
        ])->assertCreated()->assertJsonPath('data.id', $userRoadmapId);
        $this->assertDatabaseCount('user_roadmaps', 1);
        $this->assertDatabaseCount('roadmap_progress', 2);
    }

    public function test_start_rejects_roadmaps_that_are_not_published_or_whose_occupation_is_not_public(): void
    {
        $catalog = $this->minimalPublished('roadmap-ineligible');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['draft', 'review', 'archived'] as $status) {
            $catalog['roadmap']->update(['status' => $status]);
            $this->postJson('/api/me/roadmaps', [
                'roadmap_id' => $catalog['roadmap']->id,
            ])->assertNotFound();
        }

        $catalog['roadmap']->update(['status' => 'published']);
        $catalog['occupation']->update(['status' => 'draft']);
        $this->postJson('/api/me/roadmaps', [
            'roadmap_id' => $catalog['roadmap']->id,
        ])->assertNotFound();

        $catalog['occupation']->update(['status' => 'published']);
        $catalog['group']->update(['status' => 'draft']);
        $this->postJson('/api/me/roadmaps', [
            'roadmap_id' => $catalog['roadmap']->id,
        ])->assertNotFound();

        $this->assertDatabaseCount('user_roadmaps', 0);
        $this->assertDatabaseCount('roadmap_progress', 0);
    }

    public function test_progress_only_accepts_stages_owned_by_the_users_roadmap(): void
    {
        $catalog = $this->minimalPublished('roadmap-progress');
        $otherRoadmap = CareerRoadmap::create([
            'occupation_id' => $catalog['occupation']->id,
            'code' => 'roadmap-progress-other-roadmap',
            'name' => 'Other Fixture Roadmap',
            'status' => 'published',
        ]);
        $otherStage = $catalog['stages'][0]->replicate();
        $otherStage->roadmap_id = $otherRoadmap->id;
        $otherStage->code = 'roadmap-progress-other-stage';
        $otherStage->save();
        $user = User::factory()->create();
        $userRoadmap = app(RoadmapService::class)->start($user, $catalog['roadmap']->id);
        Sanctum::actingAs($user);

        $this->patchJson('/api/me/roadmaps/'.$userRoadmap->id.'/stages/'.$otherStage->id, [
            'status' => 'completed',
        ])->assertNotFound();
        $this->assertDatabaseMissing('roadmap_progress', [
            'user_roadmap_id' => $userRoadmap->id,
            'stage_id' => $otherStage->id,
        ]);

        foreach ($catalog['stages'] as $stage) {
            $this->patchJson('/api/me/roadmaps/'.$userRoadmap->id.'/stages/'.$stage->id, [
                'status' => 'completed',
            ])->assertOk()
                ->assertJsonPath('data.stage_id', $stage->id)
                ->assertJsonPath('data.status', 'completed');
        }

        $this->assertSame('completed', $userRoadmap->fresh()->status);
        $this->assertDatabaseCount('roadmap_progress', 2);
        $this->getJson('/api/me/roadmaps/'.$userRoadmap->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.stages.0.progress_status', 'completed')
            ->assertJsonPath('data.stages.1.progress_status', 'completed');

        $this->patchJson('/api/me/roadmaps/'.$userRoadmap->id.'/stages/'.$catalog['stages'][0]->id, [
            'status' => 'in_progress',
        ])->assertOk();
        $this->assertSame('active', $userRoadmap->fresh()->status);
        $this->assertNull($userRoadmap->progress()->where('stage_id', $catalog['stages'][0]->id)->sole()->completed_at);
    }
}
