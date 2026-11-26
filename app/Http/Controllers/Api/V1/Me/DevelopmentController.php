<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponder;
use App\Models\SavedCareer;
use App\Models\UserRoadmap;
use App\Services\Catalog\PublicCatalogService;
use App\Services\Matching\MatchingEngine;
use App\Services\Roadmap\RoadmapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevelopmentController extends Controller
{
    use ApiResponder;

    public function __construct(
        private MatchingEngine $matching,
        private RoadmapService $roadmaps,
        private PublicCatalogService $catalog,
    ) {}

    public function skillGap(Request $request, int $occupation): JsonResponse
    {
        return $this->success($this->matching->analyze($request->user(), $occupation));
    }

    public function educationAndCertifications(int $occupation): JsonResponse
    {
        $profession = $this->catalog->occupation($occupation);

        return $this->success([
            'profession' => $profession,
            'educations' => $profession['educations'],
            'certifications' => $profession['certifications'],
        ]);
    }

    public function savedCareers(Request $request): JsonResponse
    {
        $saved = SavedCareer::where('user_id', $request->user()->id)
            ->with(['occupation' => fn ($query) => $query->with(['group.subcategory.category', 'skills'])])
            ->latest('id')->get()
            ->map(fn (SavedCareer $career) => [
                'id' => $career->id,
                'occupation_id' => $career->occupation_id,
                'saved_at' => $career->created_at?->toIso8601String(),
                'profession' => $this->catalog->card($career->occupation),
            ]);

        return $this->success($saved);
    }

    public function saveCareer(Request $request, int $occupation): JsonResponse
    {
        $this->catalog->occupation($occupation);
        $saved = SavedCareer::firstOrCreate([
            'user_id' => $request->user()->id,
            'occupation_id' => $occupation,
        ]);

        return $this->success(['id' => $saved->id, 'occupation_id' => $occupation], 'Profesi disimpan.', 201);
    }

    public function unsaveCareer(Request $request, int $occupation): JsonResponse
    {
        SavedCareer::where('user_id', $request->user()->id)->where('occupation_id', $occupation)->delete();

        return $this->success(null, 'Profesi dihapus dari daftar simpanan.');
    }

    public function roadmaps(Request $request): JsonResponse
    {
        $roadmaps = UserRoadmap::where('user_id', $request->user()->id)
            ->latest('id')->get()->map(fn (UserRoadmap $roadmap) => $this->roadmaps->payload($roadmap));

        return $this->success($roadmaps);
    }

    public function startRoadmap(Request $request): JsonResponse
    {
        $data = $request->validate(['roadmap_id' => ['required', 'integer', 'exists:career_roadmaps,id']]);
        $roadmap = $this->roadmaps->start($request->user(), $data['roadmap_id']);

        return $this->success($this->roadmaps->payload($roadmap), 'Roadmap berhasil disimpan.', 201);
    }

    public function roadmap(Request $request, int $roadmap): JsonResponse
    {
        $roadmap = UserRoadmap::where('user_id', $request->user()->id)->findOrFail($roadmap);

        return $this->success($this->roadmaps->payload($roadmap));
    }

    public function updateProgress(Request $request, int $roadmap, int $stage): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:not_started,in_progress,completed']]);
        $progress = $this->roadmaps->updateProgress($request->user(), $roadmap, $stage, $data['status']);

        return $this->success([
            'stage_id' => $stage,
            'status' => $progress->status,
            'completed_at' => $progress->completed_at?->toIso8601String(),
        ], 'Progres roadmap diperbarui.');
    }
}
