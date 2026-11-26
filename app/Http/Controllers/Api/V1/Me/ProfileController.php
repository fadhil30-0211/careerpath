<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponder;
use App\Models\MatchingRun;
use App\Models\SavedCareer;
use App\Models\UserRoadmap;
use App\Services\Catalog\PublicCatalogService;
use App\Services\Matching\MatchingEngine;
use App\Services\ProfileService;
use App\Services\Roadmap\RoadmapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponder;

    public function __construct(private ProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success($this->profiles->payload($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'display_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'age_range' => ['nullable', 'string', 'max:30'],
            'region' => ['nullable', 'string', 'max:100'],
            'education_level' => ['nullable', 'string', 'max:50'],
            'field_of_study' => ['nullable', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'array', 'max:20'],
            'interests.*' => ['integer', 'exists:occupation_categories,id'],
            'work_preferences' => ['nullable', 'array', 'max:7'],
            'work_preferences.*' => ['string', 'max:50'],
            'experiences_reviewed' => ['sometimes', 'boolean'],
            'certifications_reviewed' => ['sometimes', 'boolean'],
            'onboarding_completed' => ['sometimes', 'boolean'],
            'skills' => ['sometimes', 'array', 'max:200'],
            'skills.*.skill_id' => ['required', 'integer', 'exists:skills,id'],
            'skills.*.level' => ['nullable', 'integer', 'min:1', 'max:5', 'required_if:skills.*.possession_status,possessed'],
            'skills.*.possession_status' => ['required', 'in:unknown,possessed,not_yet'],
            'experiences' => ['sometimes', 'array', 'max:50'],
            'experiences.*.type' => ['required', 'in:work,internship,project,organization,volunteer'],
            'experiences.*.title' => ['required', 'string', 'max:150'],
            'experiences.*.organization' => ['nullable', 'string', 'max:150'],
            'experiences.*.description' => ['nullable', 'string', 'max:2000'],
            'experiences.*.start_date' => ['nullable', 'string', 'max:20'],
            'experiences.*.end_date' => ['nullable', 'string', 'max:20'],
            'certifications' => ['sometimes', 'array', 'max:50'],
            'certifications.*.certification_id' => ['nullable', 'integer', 'exists:certifications,id'],
            'certifications.*.custom_name' => ['nullable', 'string', 'max:150', 'required_without:certifications.*.certification_id'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:150'],
            'certifications.*.valid_until' => ['nullable', 'date'],
        ], [
            'skills.*.level.required_if' => 'Level skill wajib dipilih jika skill dimiliki.',
            'certifications.*.custom_name.required_without' => 'Isi nama sertifikasi atau pilih salah satu dari katalog.',
        ]);

        return $this->success($this->profiles->update($request->user(), $data), 'Profil berhasil disimpan.');
    }
}
