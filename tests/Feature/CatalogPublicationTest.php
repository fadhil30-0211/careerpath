<?php

namespace Tests\Feature;

use App\Models\ContentReview;
use App\Models\Occupation;
use App\Models\User;
use App\Services\Catalog\CatalogWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogPublicationTest extends TestCase
{
    use CatalogFixture, RefreshDatabase;

    public function test_invalid_publication_transitions_are_rejected_without_changing_status(): void
    {
        $category = $this->minimalPublished('transition')['category'];
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);
        $transition = fn (string $action) => $this->postJson('/api/admin/categories/'.$category->id.'/transition', [
            'action' => $action,
        ]);

        $transition('approve')->assertUnprocessable();
        $transition('submit')->assertUnprocessable();
        $transition('return')->assertUnprocessable();
        $transition('invalid-action')->assertUnprocessable()->assertJsonValidationErrors('action');

        $this->assertSame('published', $category->fresh()->status);
        $this->assertDatabaseCount('content_reviews', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_category_creation_supplies_visual_defaults_for_optional_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/categories', [
            'code' => 'optional-visuals',
            'name' => 'Kategori Tanpa Visual',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.icon', 'briefcase')
            ->assertJsonPath('data.color', 'teal');
    }

    public function test_submit_return_and_approve_workflow_publishes_content(): void
    {
        $category = $this->minimalPublished('workflow')['category'];
        $category->update(['status' => 'draft']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);
        $endpoint = '/api/admin/categories/'.$category->id.'/transition';

        $this->postJson($endpoint, ['action' => 'submit', 'notes' => 'Ready for review'])
            ->assertOk()
            ->assertJsonPath('data.status', 'review');
        $this->postJson($endpoint, ['action' => 'return', 'notes' => 'Needs revision'])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');
        $this->postJson($endpoint, ['action' => 'submit'])
            ->assertOk()
            ->assertJsonPath('data.status', 'review');
        $this->postJson($endpoint, ['action' => 'approve', 'notes' => 'Approved fixture'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->getJson('/api/admin/reviews')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 4)
            ->assertJsonPath('data.items.0.reviewer', $admin->name)
            ->assertJsonPath('data.items.0.entity_name', $category->name);

        $this->assertSame(
            ['submitted', 'returned', 'submitted', 'approved'],
            ContentReview::where('reviewable_type', $category::class)
                ->where('reviewable_id', $category->id)
                ->orderBy('id')
                ->pluck('decision')
                ->all(),
        );
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'content.approve',
            'entity_type' => $category::class,
            'entity_id' => $category->id,
        ]);
    }

    public function test_pending_review_count_excludes_published_and_archived_history(): void
    {
        $category = $this->minimalPublished('pending-count')['category'];
        $category->update(['status' => 'draft']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);
        $endpoint = '/api/admin/categories/'.$category->id.'/transition';

        $this->postJson($endpoint, ['action' => 'submit'])->assertOk();
        $this->getJson('/api/admin/overview')->assertOk()->assertJsonPath('data.counts.pending_reviews', 1);

        $this->postJson($endpoint, ['action' => 'approve'])->assertOk();
        $this->getJson('/api/admin/overview')->assertOk()->assertJsonPath('data.counts.pending_reviews', 0);

        $this->postJson($endpoint, ['action' => 'archive'])->assertOk();
        $this->getJson('/api/admin/overview')->assertOk()->assertJsonPath('data.counts.pending_reviews', 0);
    }

    public function test_occupation_publication_rejects_each_unpublished_parent(): void
    {
        foreach (['group', 'subcategory', 'category'] as $parentRelation) {
            $catalog = $this->minimalPublished("parent-{$parentRelation}");
            $catalog[$parentRelation]->update(['status' => 'draft']);
            $occupation = $catalog['occupation'];
            $occupation->update(['status' => 'review', 'published_at' => null]);

            $this->expectPublicationRejected($occupation, 'Kategori, subkategori, dan kelompok');

            $this->assertSame('review', $occupation->fresh()->status);
            $this->assertNull($occupation->fresh()->published_at);
        }
    }

    public function test_occupation_publication_rejects_unpublished_source_and_unverified_occupation(): void
    {
        $catalog = $this->minimalPublished('unpublished-source');
        $catalog['source']->update(['status' => 'draft']);
        $occupation = $catalog['occupation'];
        $occupation->update(['status' => 'review', 'published_at' => null]);

        $this->expectPublicationRejected($occupation, 'Sumber referensi dan tanggal verifikasi');

        $catalog = $this->minimalPublished('unverified-source');
        $occupation = $catalog['occupation'];
        $occupation->update(['status' => 'review', 'published_at' => null, 'verified_at' => null]);

        $this->expectPublicationRejected($occupation, 'Sumber referensi dan tanggal verifikasi');
    }

    public function test_occupation_publication_rejects_mandatory_skill_without_a_source(): void
    {
        $catalog = $this->minimalPublished('mandatory-source');
        $catalog['occupation']->skills()->updateExistingPivot($catalog['skills']['analysis']->id, [
            'source_id' => null,
        ]);
        $occupation = $catalog['occupation'];
        $occupation->update(['status' => 'review', 'published_at' => null]);

        $this->expectPublicationRejected($occupation, 'Setiap skill wajib');
    }

    private function expectPublicationRejected(Occupation $occupation, string $needle): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        try {
            app(CatalogWriteService::class)->transition($occupation, 'approve', $admin);
            $this->fail("Publication should have been rejected: {$needle}.");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertStringContainsStringIgnoringCase($needle, $exception->getMessage());
            $this->assertDatabaseMissing('content_reviews', [
                'reviewable_type' => $occupation::class,
                'reviewable_id' => $occupation->id,
                'decision' => 'approved',
            ]);
            $this->assertDatabaseMissing('audit_logs', [
                'user_id' => $admin->id,
                'action' => 'content.approve',
                'entity_type' => $occupation::class,
                'entity_id' => $occupation->id,
            ]);
        }
    }
}
