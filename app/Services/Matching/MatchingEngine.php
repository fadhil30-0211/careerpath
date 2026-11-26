<?php

namespace App\Services\Matching;

use App\Models\MatchingResult;
use App\Models\MatchingRun;
use App\Models\Occupation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchingEngine
{
    public const VERSION = 'careerpath-match-v1.0';

    public function policy(): array
    {
        return [
            'version' => self::VERSION,
            'weights' => [
                'education' => 20,
                'skills' => 45,
                'experience' => 15,
                'certifications' => 10,
                'interests' => 10,
            ],
            'skill_scale' => [1, 5],
            'top_results' => 10,
            'primary_results' => 5,
        ];
    }

    public function run(User $user, string $type = 'matching', ?int $targetOccupationId = null): MatchingRun
    {
        $input = $this->input($user);
        $candidates = $this->candidates($targetOccupationId);
        $catalogSnapshot = [
            'candidate_count' => $candidates->count(),
            'occupations' => $candidates->map(fn (Occupation $occupation) => [
                'id' => $occupation->id,
                'code' => $occupation->code,
                'name' => $occupation->name,
                'version' => $occupation->version,
                'summary' => $occupation->summary,
                'skills' => $occupation->skills->map(fn ($skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'target_level' => $skill->pivot->target_level,
                    'requirement_type' => $skill->pivot->requirement_type,
                    'importance' => $skill->pivot->importance,
                ])->values(),
                'educations' => $occupation->educations->map(fn ($education) => [
                    'id' => $education->id,
                    'name' => $education->name,
                    'education_level' => $education->education_level,
                    'relationship_type' => $education->pivot->relationship_type,
                ])->values(),
                'certifications' => $occupation->certifications->map(fn ($certification) => [
                    'id' => $certification->id,
                    'name' => $certification->name,
                    'requirement_type' => $certification->pivot->requirement_type,
                ])->values(),
            ])->values(),
        ];

        $results = $candidates->map(fn (Occupation $occupation) => $this->score($occupation, $input))
            ->sort(fn (array $a, array $b) => ($b['score'] ?? -1) <=> ($a['score'] ?? -1)
                ?: $b['coverage'] <=> $a['coverage']
                ?: $a['profession']['id'] <=> $b['profession']['id'])
            ->take($this->policy()['top_results'])
            ->values();

        return DB::transaction(function () use ($user, $type, $input, $catalogSnapshot, $results) {
            $run = MatchingRun::create([
                'user_id' => $user->id,
                'type' => $type,
                'algorithm_version' => self::VERSION,
                'policy_snapshot' => $this->policy(),
                'input_snapshot' => $input,
                'catalog_snapshot' => $catalogSnapshot,
                'result_count' => $results->count(),
                'created_at' => now(),
            ]);

            foreach ($results as $index => $result) {
                MatchingResult::create([
                    'run_id' => $run->id,
                    'occupation_id' => $result['profession']['id'],
                    'rank' => $index + 1,
                    'score' => $result['score'],
                    'coverage' => $result['coverage'],
                    'profession_snapshot' => $result['profession'],
                    'breakdown' => $result['breakdown'],
                    'skill_gaps' => $result['skill_gaps'],
                    'mandatory_requirements' => $result['mandatory_requirements'],
                    'reasons' => $result['reasons'],
                    'unknown_fields' => $result['unknown_fields'],
                ]);
            }

            return $run->load('results');
        });
    }

    public function input(User $user): array
    {
        $user->load(['profile', 'skills', 'experiences', 'certifications']);

        return [
            'education_level' => $user->profile?->education_level,
            'field_of_study' => $user->profile?->field_of_study,
            'interests' => $user->profile?->interests ?? [],
            'work_preferences' => $user->profile?->work_preferences ?? [],
            'experiences_reviewed' => (bool) ($user->profile?->experiences_reviewed ?? false),
            'certifications_reviewed' => (bool) ($user->profile?->certifications_reviewed ?? false),
            'skills' => $user->skills->map(fn ($skill) => [
                'skill_id' => $skill->skill_id,
                'level' => $skill->level,
                'possession_status' => $skill->possession_status,
            ])->values(),
            'experiences' => $user->experiences->map(fn ($experience) => [
                'type' => $experience->type,
                'title' => $experience->title,
                'organization' => $experience->organization,
                'description' => $experience->description,
            ])->values(),
            'certifications' => $user->certifications->map(fn ($certification) => [
                'certification_id' => $certification->certification_id,
                'custom_name' => $certification->custom_name,
                'issuer' => $certification->issuer,
            ])->values(),
        ];
    }

    public function candidates(?int $targetOccupationId = null): Collection
    {
        $query = Occupation::public()->with([
            'group.subcategory.category',
            'skills' => fn ($query) => $query->published(),
            'educations' => fn ($query) => $query->published(),
            'certifications' => fn ($query) => $query->published(),
        ]);

        if ($targetOccupationId) {
            $query->whereKey($targetOccupationId);
        }

        return $query->orderBy('id')->get();
    }

    public function analyze(User $user, int $occupationId): array
    {
        $occupation = $this->candidates($occupationId)->firstOrFail();

        $result = $this->score($occupation, $this->input($user));

        return array_merge($result, [
            'algorithm_version' => self::VERSION,
            'disclaimer' => 'Analisis ini adalah bahan pertimbangan berbasis data profil, bukan jaminan pekerjaan atau penilaian kemampuan secara mutlak.',
        ]);
    }

    public function score(Occupation $occupation, array $input): array
    {
        $weights = $this->policy()['weights'];
        $factors = [];
        $knownWeight = 0;
        $weightedScore = 0;
        $factorCoverage = [];
        $unknown = [];

        $userSkills = collect($input['skills'])->keyBy('skill_id');
        $skillGaps = $occupation->skills->map(function ($skill) use ($userSkills) {
            $userSkill = $userSkills->get($skill->id);
            $status = $userSkill['possession_status'] ?? 'unknown';

            if ($status === 'unknown' || ! $userSkill) {
                $gapStatus = 'unknown';
                $label = 'Belum diketahui';
                $action = 'Konfirmasi apakah kamu sudah memiliki skill ini dan pada level apa.';
            } elseif ($status === 'not_yet') {
                $gapStatus = 'not_yet_possessed';
                $label = 'Belum dimiliki';
                $action = 'Mulai dari materi dasar dan latihan terstruktur.';
            } elseif ((int) ($userSkill['level'] ?? 0) < (int) $skill->pivot->target_level) {
                $gapStatus = 'needs_improvement';
                $label = 'Perlu ditingkatkan';
                $action = 'Perkuat skill melalui proyek, latihan, atau pengalaman relevan.';
            } else {
                $gapStatus = 'aligned';
                $label = 'Sesuai';
                $action = 'Pertahankan dan terapkan pada pengalaman nyata.';
            }

            return [
                'skill_id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'user_level' => $userSkill['level'] ?? null,
                'target_level' => $skill->pivot->target_level,
                'requirement_type' => $skill->pivot->requirement_type,
                'status' => $gapStatus,
                'status_label' => $label,
                'action' => $action,
            ];
        })->values()->all();

        $skillRatio = $this->weightedRatio($occupation->skills, function ($skill) use ($userSkills) {
            $userSkill = $userSkills->get($skill->id);
            $status = $userSkill['possession_status'] ?? 'unknown';

            return [
                'weight' => (int) $skill->pivot->importance,
                'known' => $status !== 'unknown',
                'value' => $status === 'possessed'
                    ? min((float) ($userSkill['level'] ?? 0) / max(1, (int) $skill->pivot->target_level), 1)
                    : 0,
            ];
        });
        $factors['skills'] = $skillRatio['value'];
        $factorCoverage['skills'] = $skillRatio['coverage'];
        if ($skillRatio['coverage'] < 1) {
            $unknown[] = 'Sebagian informasi skill belum diisi.';
        }

        $educationScore = $this->educationScore($occupation, $input);
        $factors['education'] = $educationScore['value'];
        $factorCoverage['education'] = $educationScore['coverage'];
        if ($educationScore['coverage'] < 1) {
            $unknown[] = 'Pendidikan atau jurusan belum lengkap.';
        }

        $experienceScore = $this->experienceScore($occupation, $input);
        $factors['experience'] = $experienceScore['value'];
        $factorCoverage['experience'] = $experienceScore['coverage'];
        if ($experienceScore['coverage'] < 1) {
            $unknown[] = 'Pengalaman relevan belum dikonfirmasi.';
        }

        $certificationScore = $this->certificationScore($occupation, $input);
        $factors['certifications'] = $certificationScore['value'];
        $factorCoverage['certifications'] = $certificationScore['coverage'];
        if ($certificationScore['coverage'] < 1) {
            $unknown[] = 'Informasi sertifikasi belum dikonfirmasi.';
        }

        $interestScore = $this->interestScore($occupation, $input);
        $factors['interests'] = $interestScore['value'];
        $factorCoverage['interests'] = $interestScore['coverage'];
        if ($interestScore['coverage'] < 1) {
            $unknown[] = 'Minat atau preferensi aktivitas belum diisi.';
        }

        foreach ($factors as $factor => $value) {
            if ($value !== null) {
                $effectiveWeight = $weights[$factor] * $factorCoverage[$factor];
                $knownWeight += $effectiveWeight;
                $weightedScore += $effectiveWeight * max(0, min(1, $value));
            }
        }

        $score = $knownWeight > 0 ? round($weightedScore / $knownWeight * 100, 2) : null;
        $coverage = round($knownWeight / array_sum($weights) * 100, 2);
        $mandatory = $this->mandatoryRequirements($occupation, $input, $userSkills);
        $reasons = $this->reasons($occupation, $input, $skillGaps, $mandatory, $factors, $unknown);
        $readiness = $this->readiness($skillGaps, $mandatory, $unknown);

        return [
            'profession' => array_merge($this->professionSnapshot($occupation), ['readiness' => $readiness]),
            'score' => $score,
            'coverage' => $coverage,
            'breakdown' => collect($factors)->map(fn ($value, $factor) => [
                'factor' => $factor,
                'weight' => $weights[$factor],
                'value' => $value,
                'coverage' => $factorCoverage[$factor],
                'known' => $value !== null,
            ])->values(),
            'skill_gaps' => $skillGaps,
            'mandatory_requirements' => $mandatory,
            'reasons' => $reasons,
            'unknown_fields' => array_values(array_unique($unknown)),
            'readiness' => $readiness,
        ];
    }

    private function weightedRatio(Collection $items, callable $resolver): array
    {
        if ($items->isEmpty()) {
            return ['value' => null, 'coverage' => 0];
        }

        $totalWeight = 0;
        $knownWeight = 0;
        $weightedValue = 0;

        foreach ($items as $item) {
            $resolved = $resolver($item);
            $totalWeight += $resolved['weight'];
            if ($resolved['known']) {
                $knownWeight += $resolved['weight'];
                $weightedValue += $resolved['weight'] * $resolved['value'];
            }
        }

        return [
            'value' => $knownWeight > 0 ? $weightedValue / $knownWeight : null,
            'coverage' => $totalWeight > 0 ? $knownWeight / $totalWeight : 0,
        ];
    }

    private function educationScore(Occupation $occupation, array $input): array
    {
        if ($occupation->educations->isEmpty()) {
            return ['value' => null, 'coverage' => 0];
        }
        if (! $input['education_level']) {
            return ['value' => null, 'coverage' => 0];
        }

        $userLevel = $this->levelValue($input['education_level']);
        $best = 0;
        foreach ($occupation->educations as $education) {
            $targetLevel = $this->levelValue($education->education_level);
            $levelRatio = $targetLevel > 0 ? min($userLevel / $targetLevel, 1) : 1;
            $fieldRelevance = $input['field_of_study']
                ? $this->textSimilarity($input['field_of_study'], $education->name.' '.$education->description.' '.$occupation->group->name)
                : null;
            $best = max($best, $fieldRelevance === null ? $levelRatio * 0.6 : $levelRatio * 0.6 + $fieldRelevance * 0.4);
        }

        return ['value' => $best, 'coverage' => $input['field_of_study'] ? 1 : 0.6];
    }

    private function experienceScore(Occupation $occupation, array $input): array
    {
        if (empty($input['experiences']) && ! $input['experiences_reviewed']) {
            return ['value' => null, 'coverage' => 0];
        }

        $corpus = $occupation->name.' '.$occupation->summary.' '.implode(' ', $occupation->main_activities ?? [])
            .' '.$occupation->skills->pluck('name')->implode(' ');
        $matches = 0;
        foreach ($input['experiences'] as $experience) {
            $text = implode(' ', [$experience['title'], $experience['organization'], $experience['description']]);
            $matches += count(array_intersect($this->tokens($text), $this->tokens($corpus)));
        }

        return ['value' => min($matches / 3, 1), 'coverage' => 1];
    }

    private function certificationScore(Occupation $occupation, array $input): array
    {
        if ($occupation->certifications->isEmpty()) {
            return ['value' => null, 'coverage' => 0];
        }
        if (empty($input['certifications']) && ! $input['certifications_reviewed']) {
            return ['value' => null, 'coverage' => 0];
        }

        $ownedIds = collect($input['certifications'])->pluck('certification_id')->filter();
        $ownedNames = collect($input['certifications'])->pluck('custom_name')->filter()->map(fn ($name) => Str::slug($name));

        $total = 0;
        $matched = 0;
        foreach ($occupation->certifications as $certification) {
            $weight = $certification->pivot->requirement_type === 'mandatory' ? 2 : 1;
            $total += $weight;
            if ($ownedIds->contains($certification->id) || $ownedNames->contains(Str::slug($certification->name))) {
                $matched += $weight;
            }
        }

        return ['value' => $total ? $matched / $total : null, 'coverage' => 1];
    }

    private function interestScore(Occupation $occupation, array $input): array
    {
        $categoryId = $occupation->group->subcategory->category_id;
        $hasInterest = ! empty($input['interests']);
        $hasPreference = ! empty($input['work_preferences']);

        if (! $hasInterest && ! $hasPreference) {
            return ['value' => null, 'coverage' => 0];
        }

        $categoryMatch = in_array($categoryId, $input['interests'], true);
        $preferenceOverlap = count(array_intersect($input['work_preferences'], $occupation->work_preferences ?? []));
        $knownSignals = (int) $hasInterest + (int) $hasPreference;
        $matchedSignals = (int) $categoryMatch + (int) ($preferenceOverlap > 0);

        return ['value' => $knownSignals ? $matchedSignals / $knownSignals : null, 'coverage' => 1];
    }

    private function mandatoryRequirements(Occupation $occupation, array $input, Collection $userSkills): array
    {
        $requirements = [];

        foreach ($occupation->skills->where('pivot.requirement_type', 'mandatory') as $skill) {
            $userSkill = $userSkills->get($skill->id);
            $status = ! $userSkill || ($userSkill['possession_status'] ?? 'unknown') === 'unknown'
                ? 'unknown'
                : (($userSkill['possession_status'] === 'possessed' && ($userSkill['level'] ?? 0) >= $skill->pivot->target_level) ? 'satisfied' : 'not_met');
            $requirements[] = [
                'type' => 'skill', 'id' => $skill->id, 'name' => $skill->name,
                'status' => $status, 'status_label' => $this->mandatoryLabel($status),
            ];
        }

        foreach ($occupation->educations->where('pivot.relationship_type', 'mandatory') as $education) {
            $status = ! $input['education_level'] ? 'unknown'
                : ($this->levelValue($input['education_level']) >= $this->levelValue($education->education_level) ? 'satisfied' : 'not_met');
            $requirements[] = [
                'type' => 'education', 'id' => $education->id, 'name' => $education->name,
                'status' => $status, 'status_label' => $this->mandatoryLabel($status),
            ];
        }

        foreach ($occupation->certifications->where('pivot.requirement_type', 'mandatory') as $certification) {
            $owned = collect($input['certifications'])->contains(fn ($item) => $item['certification_id'] == $certification->id)
                || collect($input['certifications'])->contains(fn ($item) => $item['custom_name'] && Str::slug($item['custom_name']) === Str::slug($certification->name));
            $status = $owned ? 'satisfied' : ($input['certifications_reviewed'] ? 'not_met' : 'unknown');
            $requirements[] = [
                'type' => $certification->credential_type, 'id' => $certification->id, 'name' => $certification->name,
                'status' => $status, 'status_label' => $this->mandatoryLabel($status),
            ];
        }

        return array_values($requirements);
    }

    private function readiness(array $skillGaps, array $mandatory, array $unknown): array
    {
        if (collect($mandatory)->contains('status', 'unknown') || ! empty($unknown)) {
            return ['status' => 'incomplete_information', 'label' => 'Perlu data tambahan'];
        }
        if (collect($mandatory)->contains('status', 'not_met')
            || collect($skillGaps)->where('requirement_type', 'mandatory')->contains('status', '!=', 'aligned')) {
            return ['status' => 'needs_development', 'label' => 'Perlu pengembangan'];
        }

        return ['status' => 'ready_to_explore', 'label' => 'Siap untuk dieksplorasi'];
    }

    private function reasons(Occupation $occupation, array $input, array $skillGaps, array $mandatory, array $factors, array $unknown): array
    {
        $reasons = [];
        $aligned = collect($skillGaps)->where('status', 'aligned')->pluck('name');
        $gaps = collect($skillGaps)->whereIn('status', ['needs_improvement', 'not_yet_possessed'])->pluck('name');

        if ($aligned->isNotEmpty()) {
            $reasons[] = $aligned->take(3)->implode(', ').' sudah sesuai dengan kebutuhan profesi.';
        }
        if ($factors['education'] !== null && $factors['education'] >= 0.6) {
            $reasons[] = 'Latar pendidikan kamu memiliki keterkaitan dengan jalur profesi ini.';
        }
        if ($factors['experience'] !== null && $factors['experience'] > 0) {
            $reasons[] = 'Pengalaman yang dicantumkan memiliki istilah atau aktivitas yang relevan.';
        }
        if ($factors['interests'] !== null && $factors['interests'] > 0) {
            $reasons[] = 'Minat atau preferensi aktivitasmu selaras dengan pekerjaan ini.';
        }
        if ($gaps->isNotEmpty()) {
            $reasons[] = 'Perlu pengembangan pada '.$gaps->take(3)->implode(', ').'.';
        }
        if (collect($mandatory)->contains('status', 'not_met')) {
            $reasons[] = 'Ada persyaratan wajib yang belum terpenuhi; periksa detailnya terpisah dari skor.';
        }
        if (empty($reasons)) {
            $reasons[] = 'Profesi ini muncul karena masih relevan untuk dieksplorasi berdasarkan data katalog.';
        }
        if (! empty($unknown)) {
            $reasons[] = 'Skor belum memperhitungkan informasi yang belum kamu konfirmasi.';
        }

        return $reasons;
    }

    private function professionSnapshot(Occupation $occupation): array
    {
        return [
            'id' => $occupation->id,
            'code' => $occupation->code,
            'slug' => $occupation->slug,
            'name' => $occupation->name,
            'summary' => $occupation->summary,
            'category' => $occupation->group->subcategory->category->name,
            'category_id' => $occupation->group->subcategory->category_id,
            'subcategory' => $occupation->group->subcategory->name,
            'group' => $occupation->group->name,
            'work_preferences' => $occupation->work_preferences ?? [],
            'is_demo' => $occupation->is_demo,
            'version' => $occupation->version,
            'skill_preview' => $occupation->skills->take(4)->pluck('name')->values(),
        ];
    }

    private function mandatoryLabel(string $status): string
    {
        return match ($status) {
            'satisfied' => 'Terpenuhi',
            'not_met' => 'Belum terpenuhi',
            default => 'Belum diketahui',
        };
    }

    private function levelValue(?string $level): int
    {
        return match (strtoupper(trim((string) $level))) {
            'SMA', 'SMK', 'MA' => 1,
            'D1' => 2,
            'D2' => 2,
            'D3' => 3,
            'S1', 'SARJANA' => 4,
            'S2', 'MAGISTER' => 5,
            'S3', 'DOKTOR' => 6,
            default => 0,
        };
    }

    private function tokens(string $text): array
    {
        $stopWords = ['yang', 'dan', 'atau', 'untuk', 'dengan', 'dalam', 'pada', 'adalah', 'sebagai', 'serta', 'ini', 'itu'];
        $words = preg_split('/[^a-zA-Z0-9]+/', Str::ascii(mb_strtolower($text))) ?: [];

        return array_values(array_unique(array_filter($words, fn ($word) => strlen($word) > 3 && ! in_array($word, $stopWords, true))));
    }

    private function textSimilarity(string $left, string $right): float
    {
        $leftTokens = $this->tokens($left);
        $rightTokens = $this->tokens($right);
        if (! $leftTokens || ! $rightTokens) {
            return 0;
        }
        $matches = count(array_intersect($leftTokens, $rightTokens));

        return min($matches / max(1, min(count($leftTokens), 3)), 1);
    }
}
