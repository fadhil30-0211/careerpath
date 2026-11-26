<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponder;
use App\Models\MatchingRun;
use App\Services\Matching\MatchingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    use ApiResponder;

    public function __construct(private MatchingEngine $engine) {}

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:matching,goal'],
            'occupation_id' => ['nullable', 'integer', 'required_if:type,goal', 'exists:occupations,id'],
        ]);

        $run = $this->engine->run(
            $request->user(),
            $data['type'],
            $data['occupation_id'] ?? null,
        );

        if ($run->results->isEmpty()) {
            return $this->success($this->serialize($run), 'Belum ada profesi Published yang dapat dianalisis.');
        }

        return $this->success($this->serialize($run), 'Analisis karier berhasil dibuat.', 201);
    }

    public function show(Request $request, MatchingRun $matchingRun): JsonResponse
    {
        abort_unless($matchingRun->user_id === $request->user()->id, 404);

        return $this->success($this->serialize($matchingRun->load('results')));
    }

    public function history(Request $request): JsonResponse
    {
        $runs = MatchingRun::where('user_id', $request->user()->id)
            ->with('results')
            ->latest('created_at')
            ->paginate(15)
            ->through(fn (MatchingRun $run) => $this->serialize($run));

        return $this->success([
            'items' => $runs->items(),
            'meta' => ['current_page' => $runs->currentPage(), 'last_page' => $runs->lastPage(), 'total' => $runs->total()],
        ]);
    }

    private function serialize(MatchingRun $run): array
    {
        return [
            'id' => $run->id,
            'type' => $run->type,
            'algorithm_version' => $run->algorithm_version,
            'created_at' => $run->created_at?->toIso8601String(),
            'candidate_count' => $run->catalog_snapshot['candidate_count'] ?? $run->result_count,
            'input_snapshot' => $run->input_snapshot,
            'policy_snapshot' => $run->policy_snapshot,
            'results' => $run->results->map(fn ($result) => [
                'id' => $result->id,
                'rank' => $result->rank,
                'score' => $result->score === null ? null : (float) $result->score,
                'coverage' => (float) $result->coverage,
                'profession' => $result->profession_snapshot,
                'breakdown' => $result->breakdown,
                'skill_gaps' => $result->skill_gaps,
                'mandatory_requirements' => $result->mandatory_requirements,
                'reasons' => $result->reasons,
                'unknown_fields' => $result->unknown_fields,
                'readiness' => $result->profession_snapshot['readiness'] ?? null,
            ])->values(),
        ];
    }
}
