<?php

namespace App\Services\Catalog;

use App\Models\CareerRoadmap;
use App\Models\Occupation;
use App\Models\OccupationCategory;
use App\Models\OccupationSubcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PublicCatalogService
{
    public function categories()
    {
        return OccupationCategory::published()->orderBy('sort_order')
            ->with(['subcategories' => fn ($query) => $query->published()->orderBy('sort_order')
                ->with(['groups' => fn ($query) => $query->published()->orderBy('sort_order')
                    ->with(['occupations' => fn ($query) => $query->public()])])])
            ->get()
            ->map(function (OccupationCategory $category) {
                $professions = $category->subcategories
                    ->flatMap->groups
                    ->flatMap->occupations
                    ->filter(fn (Occupation $occupation) => $occupation->status === 'published');

                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'profession_count' => $professions->count(),
                    'sample_professions' => $professions->take(3)->pluck('name')->values(),
                    'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'groups' => $subcategory->groups->map(fn ($group) => [
                            'id' => $group->id,
                            'name' => $group->name,
                        ])->values(),
                    ])->values(),
                ];
            });
    }

    public function categoryChildren(int $categoryId)
    {
        $category = OccupationCategory::published()->findOrFail($categoryId);

        return [
            'category' => ['id' => $category->id, 'name' => $category->name],
            'subcategories' => $category->subcategories()->published()->orderBy('sort_order')
                ->with(['groups' => fn ($query) => $query->published()->orderBy('sort_order')])
                ->get()
                ->map(fn ($subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'groups' => $subcategory->groups->map(fn ($group) => [
                        'id' => $group->id,
                        'name' => $group->name,
                    ])->values(),
                ])->values(),
        ];
    }

    public function subcategoryChildren(int $subcategoryId)
    {
        $subcategory = OccupationSubcategory::published()->findOrFail($subcategoryId);

        return [
            'subcategory' => ['id' => $subcategory->id, 'name' => $subcategory->name],
            'groups' => $subcategory->groups()->published()->orderBy('sort_order')
                ->get()->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                ])->values(),
        ];
    }

    public function occupations(array $filters): LengthAwarePaginator
    {
        $query = Occupation::public()
            ->with(['group.subcategory.category', 'skills' => fn ($query) => $query->published()])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhereHas('skills', fn ($skill) => $skill->published()->where('name', 'like', "%{$search}%"));
            }))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->whereHas('group.subcategory', fn ($query) => $query->where('category_id', $id)))
            ->when($filters['subcategory_id'] ?? null, fn ($query, $id) => $query->whereHas('group', fn ($query) => $query->where('subcategory_id', $id)))
            ->when($filters['group_id'] ?? null, fn ($query, $id) => $query->where('group_id', $id))
            ->when($filters['activity'] ?? null, fn ($query, $activity) => $query->whereJsonContains('work_preferences', $activity));

        if (! empty($filters['activities']) && is_array($filters['activities'])) {
            $query->where(function ($query) use ($filters) {
                foreach ($filters['activities'] as $activity) {
                    $query->orWhereJsonContains('work_preferences', $activity);
                }
            });
        }

        $paginator = $query->orderBy('name')->paginate($filters['per_page'] ?? 12)->withQueryString();

        return $paginator->through(fn ($occupation) => $this->card($occupation));
    }

    public function occupation(int $id): array
    {
        $occupation = Occupation::public()
            ->with([
                'group.subcategory.category',
                'skills' => fn ($query) => $query->published(),
                'educations' => fn ($query) => $query->published(),
                'certifications' => fn ($query) => $query->published(),
                'workplaces' => fn ($query) => $query->published()->with(['industry' => fn ($query) => $query->published()]),
                'roadmaps' => fn ($query) => $query->published()->with(['stages' => fn ($query) => $query->orderBy('sequence')]),
                'source' => fn ($query) => $query->published(),
            ])
            ->findOrFail($id);

        return array_merge($this->card($occupation), [
            'description' => $occupation->description,
            'main_activities' => $occupation->main_activities ?? [],
            'responsibilities' => $occupation->responsibilities,
            'work_environment' => $occupation->work_environment,
            'alternative_careers' => $occupation->alternative_careers,
            'verified_at' => $occupation->verified_at?->toDateString(),
            'version' => $occupation->version,
            'source' => $occupation->source ? [
                'name' => $occupation->source->name,
                'publisher' => $occupation->source->publisher,
                'url' => $occupation->source->url,
                'verified_at' => $occupation->source->verified_at?->toDateString(),
                'notes' => $occupation->source->notes,
            ] : null,
            'skills' => $occupation->skills->map(fn ($skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'description' => $skill->description,
                'target_level' => $skill->pivot->target_level,
                'requirement_type' => $skill->pivot->requirement_type,
                'importance' => $skill->pivot->importance,
            ])->values(),
            'educations' => $occupation->educations->map(fn ($education) => [
                'id' => $education->id,
                'name' => $education->name,
                'education_level' => $education->education_level,
                'program_type' => $education->program_type,
                'relationship_type' => $education->pivot->relationship_type,
                'description' => $education->description,
            ])->values(),
            'certifications' => $occupation->certifications->map(fn ($certification) => [
                'id' => $certification->id,
                'name' => $certification->name,
                'issuer' => $certification->issuer,
                'credential_type' => $certification->pivot->requirement_type === 'mandatory' ? 'license' : $certification->credential_type,
                'requirement_type' => $certification->pivot->requirement_type,
                'validity_notes' => $certification->validity_notes,
            ])->values(),
            'workplaces' => $occupation->workplaces->filter(fn ($workplace) => $workplace->industry)
                ->map(fn ($workplace) => [
                    'id' => $workplace->id,
                    'name' => $workplace->name,
                    'description' => $workplace->description,
                    'industry' => $workplace->industry->name,
                    'relevance_description' => $workplace->pivot->relevance_description,
                ])->values(),
            'roadmaps' => $occupation->roadmaps->map(fn (CareerRoadmap $roadmap) => $this->roadmap($roadmap))->values(),
        ]);
    }

    public function card(Occupation $occupation): array
    {
        $category = $occupation->group?->subcategory?->category;

        return [
            'id' => $occupation->id,
            'code' => $occupation->code,
            'slug' => $occupation->slug,
            'name' => $occupation->name,
            'summary' => $occupation->summary,
            'category' => $category?->name,
            'category_id' => $category?->id,
            'subcategory' => $occupation->group?->subcategory?->name,
            'group' => $occupation->group?->name,
            'work_preferences' => $occupation->work_preferences ?? [],
            'is_demo' => $occupation->is_demo,
            'skill_preview' => $occupation->relationLoaded('skills')
                ? $occupation->skills->take(4)->pluck('name')->values()
                : [],
        ];
    }

    public function roadmap(CareerRoadmap $roadmap): array
    {
        return [
            'id' => $roadmap->id,
            'name' => $roadmap->name,
            'description' => $roadmap->description,
            'occupation_id' => $roadmap->occupation_id,
            'stages' => $roadmap->stages->map(fn ($stage) => [
                'id' => $stage->id,
                'sequence' => $stage->sequence,
                'title' => $stage->title,
                'objective' => $stage->objective,
                'action' => $stage->action,
                'resource_title' => $stage->resource_title,
                'resource_url' => $stage->resource_url,
                'estimated_weeks' => $stage->estimated_weeks,
            ])->values(),
        ];
    }
}
