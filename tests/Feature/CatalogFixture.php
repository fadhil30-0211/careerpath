<?php

namespace Tests\Feature;

use App\Models\CareerRoadmap;
use App\Models\Certification;
use App\Models\DataSource;
use App\Models\EducationProgram;
use App\Models\Occupation;
use App\Models\OccupationCategory;
use App\Models\OccupationGroup;
use App\Models\OccupationSubcategory;
use App\Models\RoadmapStage;
use App\Models\Skill;

trait CatalogFixture
{
    /**
     * Build an isolated, publishable catalog without using development seeders.
     *
     * @return array{source: DataSource, category: OccupationCategory, subcategory: OccupationSubcategory, group: OccupationGroup, occupation: Occupation, skills: array<int, Skill>, education: EducationProgram, certification: Certification, roadmap: CareerRoadmap, stages: array<int, RoadmapStage>}
     */
    protected function minimalPublished(string $prefix = 'fixture'): array
    {
        $source = DataSource::create([
            'code' => "{$prefix}-source",
            'name' => 'Fixture Verified Source',
            'source_type' => 'internal',
            'url' => 'https://example.test/catalog',
            'publisher' => 'CareerPath Fixtures',
            'verified_at' => '2026-01-15',
            'status' => 'published',
        ]);
        $category = OccupationCategory::create([
            'code' => "{$prefix}-category",
            'slug' => "{$prefix}-category",
            'name' => 'Fixture Technology',
            'description' => 'Isolated technology category.',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $subcategory = OccupationSubcategory::create([
            'category_id' => $category->id,
            'code' => "{$prefix}-subcategory",
            'name' => 'Fixture Software',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $group = OccupationGroup::create([
            'subcategory_id' => $subcategory->id,
            'code' => "{$prefix}-group",
            'name' => 'Fixture Application Development',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $skillDefinitions = [
            ['analysis', 'Analisis Data', 4, 'mandatory', 5],
            ['communication', 'Komunikasi', 3, 'recommended', 3],
            ['programming', 'Pemrograman', 4, 'recommended', 4],
        ];
        $skills = [];

        $occupation = Occupation::create([
            'group_id' => $group->id,
            'source_id' => $source->id,
            'code' => "{$prefix}-occupation",
            'slug' => "{$prefix}-occupation",
            'name' => 'Fixture Software Engineer',
            'summary' => 'Build reliable applications and analyze product data.',
            'description' => 'An isolated occupation used by Feature tests.',
            'main_activities' => ['Membangun aplikasi', 'Menganalisis data'],
            'responsibilities' => 'Maintain application quality.',
            'work_environment' => 'Hybrid technology workplace.',
            'alternative_careers' => 'Data Analyst',
            'work_preferences' => ['analitis', 'praktis'],
            'verified_at' => '2026-01-15',
            'status' => 'published',
            'published_at' => '2026-01-15 00:00:00',
            'version' => 1,
        ]);

        foreach ($skillDefinitions as [$suffix, $name, $targetLevel, $requirementType, $importance]) {
            $skill = Skill::create([
                'code' => "{$prefix}-{$suffix}",
                'slug' => "{$prefix}-{$suffix}",
                'name' => $name,
                'category' => 'Fixture Technical',
                'description' => "{$name} fixture skill.",
                'status' => 'published',
            ]);
            $skills[$suffix] = $skill;
            $occupation->skills()->attach($skill->id, [
                'source_id' => $source->id,
                'target_level' => $targetLevel,
                'requirement_type' => $requirementType,
                'importance' => $importance,
            ]);
        }

        $education = EducationProgram::create([
            'code' => "{$prefix}-education",
            'name' => 'S1 Teknologi Informasi',
            'education_level' => 'S1',
            'program_type' => 'formal',
            'description' => 'Technology and software fundamentals.',
            'source_id' => $source->id,
            'status' => 'published',
        ]);
        $occupation->educations()->attach($education->id, [
            'relationship_type' => 'mandatory',
        ]);

        $certification = Certification::create([
            'code' => "{$prefix}-certification",
            'name' => 'Fixture Certified Developer',
            'issuer' => 'Fixture Certification Board',
            'credential_type' => 'certification',
            'validity_notes' => 'Fixture credential.',
            'source_id' => $source->id,
            'status' => 'published',
        ]);
        $occupation->certifications()->attach($certification->id, [
            'requirement_type' => 'recommended',
        ]);

        $roadmap = CareerRoadmap::create([
            'occupation_id' => $occupation->id,
            'code' => "{$prefix}-roadmap",
            'name' => 'Fixture Software Engineer Roadmap',
            'description' => 'A two-stage isolated learning roadmap.',
            'status' => 'published',
        ]);
        $stages = [];
        foreach ([
            [1, 'Fixture Foundations', 'Learn programming and data foundations.', 'Complete a beginner project.', 2],
            [2, 'Fixture Portfolio', 'Build and document an application portfolio.', 'Publish two portfolio projects.', 4],
        ] as [$sequence, $title, $objective, $action, $weeks]) {
            $stages[] = RoadmapStage::create([
                'roadmap_id' => $roadmap->id,
                'code' => "{$prefix}-roadmap-stage-{$sequence}",
                'sequence' => $sequence,
                'title' => $title,
                'objective' => $objective,
                'action' => $action,
                'resource_title' => "Fixture Resource {$sequence}",
                'resource_url' => "https://example.test/roadmap/{$sequence}",
                'estimated_weeks' => $weeks,
            ]);
        }

        return compact(
            'source', 'category', 'subcategory', 'group', 'occupation', 'skills',
            'education', 'certification', 'roadmap', 'stages',
        );
    }

    protected function draftOccupation(string $prefix = 'draft', ?array $catalog = null): Occupation
    {
        $catalog ??= $this->minimalPublished("{$prefix}-catalog");

        return Occupation::create([
            'group_id' => $catalog['group']->id,
            'source_id' => $catalog['source']->id,
            'code' => "{$prefix}-occupation",
            'slug' => "{$prefix}-occupation",
            'name' => 'Fixture Draft Occupation',
            'summary' => 'This occupation must not be public.',
            'description' => 'Draft occupation fixture.',
            'work_preferences' => ['analitis'],
            'verified_at' => '2026-01-15',
            'status' => 'draft',
        ]);
    }

    /**
     * Provide enough known profile data for a complete, high-scoring baseline.
     */
    protected function completeProfileInput(array $catalog): array
    {
        return [
            'education_level' => 'S1',
            'field_of_study' => 'Teknologi Informasi',
            'experiences_reviewed' => true,
            'certifications_reviewed' => true,
            'interests' => [$catalog['category']->id],
            'work_preferences' => ['analitis'],
            'skills' => [
                ['skill_id' => $catalog['skills']['analysis']->id, 'level' => 5, 'possession_status' => 'possessed'],
                ['skill_id' => $catalog['skills']['communication']->id, 'level' => 5, 'possession_status' => 'possessed'],
                ['skill_id' => $catalog['skills']['programming']->id, 'level' => 5, 'possession_status' => 'possessed'],
            ],
            'experiences' => [[
                'type' => 'work',
                'title' => 'Application Developer',
                'organization' => 'Fixture Technology Company',
                'description' => 'Membangun aplikasi dan menganalisis data dengan pemrograman.',
            ]],
            'certifications' => [[
                'certification_id' => $catalog['certification']->id,
                'custom_name' => null,
                'issuer' => 'Fixture Certification Board',
            ]],
        ];
    }
}
