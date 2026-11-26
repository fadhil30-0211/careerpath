<?php

namespace Tests\Feature;

use App\Models\Occupation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use CatalogFixture, RefreshDatabase;

    public function test_only_occupations_with_a_fully_published_hierarchy_are_listed(): void
    {
        $catalog = $this->minimalPublished('public');
        $draft = $this->draftOccupation('public-draft', $catalog);
        $archived = $this->draftOccupation('public-archived', $catalog);
        $archived->update(['status' => 'archived']);
        $orphanedCatalog = $this->minimalPublished('orphan-catalog');
        $orphaned = $this->draftOccupation('public-orphaned', $orphanedCatalog);
        $orphanedCatalog['group']->update(['status' => 'draft']);
        $orphaned->update(['status' => 'published']);

        $response = $this->getJson('/api/public/occupations')->assertOk();

        $response->assertJsonPath('data.meta.total', 1)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $catalog['occupation']->id)
            ->assertJsonPath('data.items.0.category', $catalog['category']->name)
            ->assertJsonPath('data.items.0.subcategory', $catalog['subcategory']->name)
            ->assertJsonPath('data.items.0.group', $catalog['group']->name)
            ->assertJsonMissing(['id' => $draft->id, 'name' => $draft->name])
            ->assertJsonMissing(['id' => $archived->id, 'name' => $archived->name])
            ->assertJsonMissing(['id' => $orphaned->id, 'name' => $orphaned->name]);
    }

    public function test_published_occupation_detail_exposes_the_complete_catalog_fixture(): void
    {
        $catalog = $this->minimalPublished('public-detail');

        $this->getJson('/api/public/occupations/'.$catalog['occupation']->id)
            ->assertOk()
            ->assertJsonPath('data.id', $catalog['occupation']->id)
            ->assertJsonPath('data.category', $catalog['category']->name)
            ->assertJsonPath('data.source.name', $catalog['source']->name)
            ->assertJsonCount(3, 'data.skills')
            ->assertJsonCount(1, 'data.educations')
            ->assertJsonPath('data.educations.0.id', $catalog['education']->id)
            ->assertJsonCount(1, 'data.certifications')
            ->assertJsonPath('data.certifications.0.id', $catalog['certification']->id)
            ->assertJsonCount(1, 'data.roadmaps')
            ->assertJsonPath('data.roadmaps.0.id', $catalog['roadmap']->id)
            ->assertJsonCount(2, 'data.roadmaps.0.stages');
    }

    public function test_draft_and_archived_occupation_details_return_404(): void
    {
        $catalog = $this->minimalPublished('detail');
        $draft = $this->draftOccupation('detail-draft', $catalog);
        $archived = $this->draftOccupation('detail-archived', $catalog);
        $archived->update(['status' => 'archived']);

        $this->getJson('/api/public/occupations/'.$draft->id)->assertNotFound();
        $this->getJson('/api/public/occupations/'.$archived->id)->assertNotFound();
    }

    public function test_published_occupation_is_hidden_when_each_hierarchy_level_is_not_published(): void
    {
        $catalog = $this->minimalPublished('hierarchy');
        $occupation = $catalog['occupation'];

        foreach ([
            'group' => $catalog['group'],
            'subcategory' => $catalog['subcategory'],
            'category' => $catalog['category'],
        ] as $relation => $parent) {
            $parent->update(['status' => 'draft']);

            $this->getJson('/api/public/occupations')
                ->assertOk()
                ->assertJsonPath('data.meta.total', 0)
                ->assertJsonMissing(['id' => $occupation->id]);
            $this->getJson('/api/public/occupations/'.$occupation->id)->assertNotFound();

            $parent->update(['status' => 'published']);
        }

        $this->assertSame(1, Occupation::public()->count());
    }
}
