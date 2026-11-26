<?php

namespace App\Services\Roadmap;

use App\Models\CareerRoadmap;
use App\Models\RoadmapProgress;
use App\Models\User;
use App\Models\UserRoadmap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RoadmapService
{
    public function start(User $user, int $roadmapId): UserRoadmap
    {
        $roadmap = CareerRoadmap::published()
            ->whereHas('occupation', fn (Builder $query) => $query->public())
            ->with('stages')
            ->findOrFail($roadmapId);

        return DB::transaction(function () use ($user, $roadmap) {
            $userRoadmap = UserRoadmap::firstOrCreate([
                'user_id' => $user->id,
                'roadmap_id' => $roadmap->id,
            ], [
                'name' => $roadmap->name,
                'status' => 'active',
            ]);

            foreach ($roadmap->stages as $stage) {
                RoadmapProgress::firstOrCreate([
                    'user_roadmap_id' => $userRoadmap->id,
                    'stage_id' => $stage->id,
                ], ['status' => 'not_started']);
            }

            return $userRoadmap;
        });
    }

    public function updateProgress(User $user, int $userRoadmapId, int $stageId, string $status): RoadmapProgress
    {
        $userRoadmap = UserRoadmap::where('user_id', $user->id)->findOrFail($userRoadmapId);
        $stage = $userRoadmap->roadmap->stages()->findOrFail($stageId);

        return DB::transaction(function () use ($userRoadmap, $stage, $status) {
            $progress = RoadmapProgress::updateOrCreate([
                'user_roadmap_id' => $userRoadmap->id,
                'stage_id' => $stage->id,
            ], [
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            $completed = $userRoadmap->progress()->where('status', 'completed')->count();
            $userRoadmap->update(['status' => $completed === $userRoadmap->roadmap->stages()->count() ? 'completed' : 'active']);

            return $progress;
        });
    }

    public function payload(UserRoadmap $userRoadmap): array
    {
        $userRoadmap->load(['roadmap.occupation.group.subcategory.category', 'roadmap.stages', 'progress']);

        return [
            'id' => $userRoadmap->id,
            'name' => $userRoadmap->name,
            'status' => $userRoadmap->status,
            'occupation' => [
                'id' => $userRoadmap->roadmap->occupation->id,
                'name' => $userRoadmap->roadmap->occupation->name,
                'category' => $userRoadmap->roadmap->occupation->group->subcategory->category->name,
            ],
            'roadmap' => [
                'id' => $userRoadmap->roadmap->id,
                'name' => $userRoadmap->roadmap->name,
                'description' => $userRoadmap->roadmap->description,
            ],
            'stages' => $userRoadmap->roadmap->stages->map(fn ($stage) => [
                'id' => $stage->id,
                'sequence' => $stage->sequence,
                'title' => $stage->title,
                'objective' => $stage->objective,
                'action' => $stage->action,
                'resource_title' => $stage->resource_title,
                'resource_url' => $stage->resource_url,
                'estimated_weeks' => $stage->estimated_weeks,
                'progress_status' => $userRoadmap->progress->firstWhere('stage_id', $stage->id)?->status ?? 'not_started',
            ])->values(),
        ];
    }
}
