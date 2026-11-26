<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponder;
use App\Models\AuditLog;
use App\Models\CareerRoadmap;
use App\Models\Certification;
use App\Models\ContentReview;
use App\Models\EducationProgram;
use App\Models\Industry;
use App\Models\Occupation;
use App\Models\OccupationCategory;
use App\Models\OccupationGroup;
use App\Models\OccupationSubcategory;
use App\Models\RoadmapStage;
use App\Models\Skill;
use App\Models\DataSource;
use App\Models\User;
use App\Models\WorkplaceType;
use App\Services\Catalog\CatalogWriteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    use ApiResponder;

    private const RESOURCES = [
        'categories' => [OccupationCategory::class, 'name'],
        'subcategories' => [OccupationSubcategory::class, 'name'],
        'groups' => [OccupationGroup::class, 'name'],
        'occupations' => [Occupation::class, 'name'],
        'skills' => [Skill::class, 'name'],
        'educations' => [EducationProgram::class, 'name'],
        'certifications' => [Certification::class, 'name'],
        'industries' => [Industry::class, 'name'],
        'workplaces' => [WorkplaceType::class, 'name'],
        'sources' => [DataSource::class, 'name'],
        'roadmaps' => [CareerRoadmap::class, 'name'],
    ];

    public function __construct(private CatalogWriteService $writes) {}

    public function overview(): JsonResponse
    {
        $statusCounts = [];
        foreach (['occupations' => Occupation::class, 'skills' => Skill::class, 'educations' => EducationProgram::class, 'certifications' => Certification::class] as $table => $model) {
            $statusCounts[$table] = $model::query()->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        }

        $pendingReviewQuery = ContentReview::query()
            ->where('decision', 'submitted')
            ->whereHasMorph('reviewable', array_column(self::RESOURCES, 0), fn (Builder $query) => $query->where('status', 'review'))
            ->select('reviewable_type', 'reviewable_id')
            ->distinct();

        return $this->success([
            'counts' => [
                'categories' => OccupationCategory::count(),
                'occupations' => Occupation::count(),
                'published_occupations' => Occupation::published()->count(),
                'skills' => Skill::count(),
                'users' => User::count(),
                'pending_reviews' => ContentReview::query()->fromSub($pendingReviewQuery, 'pending_reviews')->count(),
                'demo_occupations' => Occupation::where('is_demo', true)->count(),
            ],
            'status_counts' => $statusCounts,
            'latest_reviews' => ContentReview::with('reviewable')->latest()->limit(8)->get()->map(fn (ContentReview $review) => [
                'id' => $review->id,
                'decision' => $review->decision,
                'notes' => $review->notes,
                'entity_type' => class_basename($review->reviewable_type),
                'entity_id' => $review->reviewable_id,
                'entity_name' => $review->reviewable?->name,
                'created_at' => $review->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        [$model, $searchColumn] = $this->resource($resource);
        $query = $model::query()->with($this->relations($resource));

        if ($request->filled('search')) {
            $query->where($searchColumn, 'like', '%'.$request->string('search').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $items = $query->latest('id')->paginate(max(1, min(100, $request->integer('per_page', 20))))->through(fn (Model $item) => $this->present($resource, $item));

        return $this->success([
            'items' => $items->items(),
            'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()],
        ]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        [$model] = $this->resource($resource);
        $data = $this->validated($request, $resource);
        $data = $this->normalize($model, $data);
        $item = $model::create($data);
        AuditLog::record($request->user()->id, 'created', $item, ['after' => $data]);

        return $this->success($this->present($resource, $item->refresh()), 'Data berhasil dibuat sebagai Draft.', 201);
    }

    public function show(string $resource, int $id): JsonResponse
    {
        [$model] = $this->resource($resource);
        $item = $model::with($this->relations($resource))->findOrFail($id);

        return $this->success($this->present($resource, $item));
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        [$model] = $this->resource($resource);
        $item = $model::findOrFail($id);
        $data = $this->normalize($model, $this->validated($request, $resource, $id));
        $before = $item->getAttributes();
        $item->update($data);
        AuditLog::record($request->user()->id, 'updated', $item, ['before' => $before, 'after' => $data]);

        return $this->success($this->present($resource, $item->refresh()), 'Data berhasil diperbarui.');
    }

    public function transition(Request $request, string $resource, int $id): JsonResponse
    {
        [$model] = $this->resource($resource);
        $data = $request->validate([
            'action' => ['required', 'in:submit,approve,return,archive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $item = $this->writes->transition($model::findOrFail($id), $data['action'], $request->user(), $data['notes'] ?? null);

        return $this->success($this->present($resource, $item), 'Status data berhasil diperbarui.');
    }

    public function occupationOptions(): JsonResponse
    {
        return $this->success([
            'skills' => Skill::orderBy('name')->get(['id', 'name', 'status']),
            'educations' => EducationProgram::orderBy('name')->get(['id', 'name', 'education_level', 'status']),
            'certifications' => Certification::orderBy('name')->get(['id', 'name', 'status']),
            'workplaces' => WorkplaceType::with('industry')->orderBy('name')->get()->map(fn (WorkplaceType $workplace) => [
                'id' => $workplace->id, 'name' => $workplace->name, 'status' => $workplace->status,
                'industry' => $workplace->industry?->name,
            ]),
        ]);
    }

    public function requirements(int $id): JsonResponse
    {
        $occupation = Occupation::with(['skills', 'educations', 'certifications', 'workplaces'])->findOrFail($id);

        return $this->success([
            'skills' => $occupation->skills->map(fn (Skill $skill) => [
                'skill_id' => $skill->id, 'name' => $skill->name, 'target_level' => $skill->pivot->target_level,
                'requirement_type' => $skill->pivot->requirement_type, 'importance' => $skill->pivot->importance,
                'notes' => $skill->pivot->notes, 'source_id' => $skill->pivot->source_id,
            ]),
            'educations' => $occupation->educations->map(fn (EducationProgram $education) => [
                'education_id' => $education->id, 'name' => $education->name,
                'relationship_type' => $education->pivot->relationship_type, 'notes' => $education->pivot->notes,
            ]),
            'certifications' => $occupation->certifications->map(fn (Certification $certification) => [
                'certification_id' => $certification->id, 'name' => $certification->name,
                'requirement_type' => $certification->pivot->requirement_type, 'notes' => $certification->pivot->notes,
            ]),
            'workplaces' => $occupation->workplaces->map(fn (WorkplaceType $workplace) => [
                'workplace_id' => $workplace->id, 'name' => $workplace->name,
                'relevance_description' => $workplace->pivot->relevance_description,
            ]),
        ]);
    }

    public function updateRequirements(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'skills' => ['sometimes', 'array', 'max:100'],
            'skills.*.skill_id' => ['required', 'integer', 'exists:skills,id'],
            'skills.*.target_level' => ['required', 'integer', 'min:1', 'max:5'],
            'skills.*.requirement_type' => ['required', 'in:mandatory,recommended,optional'],
            'skills.*.importance' => ['required', 'integer', 'min:1', 'max:5'],
            'skills.*.source_id' => ['nullable', 'integer', 'exists:data_sources,id'],
            'skills.*.notes' => ['nullable', 'string', 'max:1000'],
            'educations' => ['sometimes', 'array', 'max:50'],
            'educations.*.education_id' => ['required', 'integer', 'exists:education_programs,id'],
            'educations.*.relationship_type' => ['required', 'in:mandatory,formal,relevant,alternative'],
            'educations.*.notes' => ['nullable', 'string', 'max:1000'],
            'certifications' => ['sometimes', 'array', 'max:50'],
            'certifications.*.certification_id' => ['required', 'integer', 'exists:certifications,id'],
            'certifications.*.requirement_type' => ['required', 'in:mandatory,recommended,optional'],
            'certifications.*.notes' => ['nullable', 'string', 'max:1000'],
            'workplaces' => ['sometimes', 'array', 'max:50'],
            'workplaces.*.workplace_id' => ['required', 'integer', 'exists:workplace_types,id'],
            'workplaces.*.relevance_description' => ['nullable', 'string', 'max:1000'],
        ]);
        $occupation = Occupation::findOrFail($id);

        DB::transaction(function () use ($request, $data, $occupation) {
            if (array_key_exists('skills', $data)) {
                $occupation->skills()->sync(collect($data['skills'])->mapWithKeys(fn ($skill) => [
                    $skill['skill_id'] => collect($skill)->except('skill_id')->all(),
                ])->all());
            }
            if (array_key_exists('educations', $data)) {
                $occupation->educations()->sync(collect($data['educations'])->mapWithKeys(fn ($education) => [
                    $education['education_id'] => collect($education)->except('education_id')->all(),
                ])->all());
            }
            if (array_key_exists('certifications', $data)) {
                $occupation->certifications()->sync(collect($data['certifications'])->mapWithKeys(fn ($certification) => [
                    $certification['certification_id'] => collect($certification)->except('certification_id')->all(),
                ])->all());
            }
            if (array_key_exists('workplaces', $data)) {
                $occupation->workplaces()->sync(collect($data['workplaces'])->mapWithKeys(fn ($workplace) => [
                    $workplace['workplace_id'] => collect($workplace)->except('workplace_id')->all(),
                ])->all());
            }
            AuditLog::record($request->user()->id, 'updated_requirements', $occupation, ['after' => $data]);
        });

        return $this->success(null, 'Relasi profesi berhasil diperbarui.');
    }

    public function roadmap(int $id): JsonResponse
    {
        $roadmap = CareerRoadmap::with('stages')->findOrFail($id);

        return $this->success($roadmap);
    }

    public function updateRoadmap(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'stages' => ['required', 'array', 'min:1', 'max:50'],
            'stages.*.code' => ['required', 'string', 'max:50'],
            'stages.*.sequence' => ['required', 'integer', 'min:1', 'max:100'],
            'stages.*.title' => ['required', 'string', 'max:150'],
            'stages.*.objective' => ['required', 'string', 'max:2000'],
            'stages.*.action' => ['required', 'string', 'max:2000'],
            'stages.*.resource_title' => ['nullable', 'string', 'max:200'],
            'stages.*.resource_url' => ['nullable', 'url', 'max:500'],
            'stages.*.estimated_weeks' => ['nullable', 'integer', 'min:1', 'max:104'],
        ]);
        $roadmap = CareerRoadmap::findOrFail($id);

        DB::transaction(function () use ($request, $data, $roadmap) {
            foreach ($data['stages'] as $stage) {
                RoadmapStage::updateOrCreate(['roadmap_id' => $roadmap->id, 'code' => $stage['code']], collect($stage)->except('code')->all());
            }
            AuditLog::record($request->user()->id, 'updated_roadmap', $roadmap, ['after' => $data['stages']]);
        });

        return $this->success($roadmap->load('stages'), 'Tahapan roadmap berhasil disimpan.');
    }

    public function reviews(): JsonResponse
    {
        $reviews = ContentReview::with(['reviewable', 'reviewer'])->latest()->paginate(20)
            ->through(fn (ContentReview $review) => [
                'id' => $review->id,
                'decision' => $review->decision,
                'notes' => $review->notes,
                'reviewer' => $review->reviewer?->name,
                'entity_type' => class_basename($review->reviewable_type),
                'entity_id' => $review->reviewable_id,
                'entity_name' => $review->reviewable?->name,
                'status' => $review->reviewable?->status,
                'created_at' => $review->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'items' => $reviews->items(),
            'meta' => ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()],
        ]);
    }

    public function users(): JsonResponse
    {
        $users = User::with('profile')->latest('id')->paginate(20)->through(fn (User $user) => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'status' => $user->status, 'created_at' => $user->created_at?->toIso8601String(),
        ]);

        return $this->success([
            'items' => $users->items(),
            'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'total' => $users->total()],
        ]);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'role' => ['sometimes', 'in:user,admin'],
            'status' => ['sometimes', 'in:active,suspended'],
        ]);
        $user = User::findOrFail($id);
        abort_if($user->id === $request->user()->id && (($data['role'] ?? $user->role) !== 'admin' || ($data['status'] ?? $user->status) !== 'active'), 422, 'Akun Admin yang sedang login tidak dapat ditangguhkan atau diturunkan rolenya.');
        $before = $user->only(['role', 'status']);
        $user->update($data);
        AuditLog::record($request->user()->id, 'updated_user', $user, ['before' => $before, 'after' => $data]);

        return $this->success(['id' => $user->id, 'role' => $user->role, 'status' => $user->status], 'Akun diperbarui.');
    }

    public function auditLogs(): JsonResponse
    {
        $logs = AuditLog::with('user')->latest('id')->paginate(30)->through(fn (AuditLog $log) => [
            'id' => $log->id, 'action' => $log->action, 'user' => $log->user?->name,
            'entity_type' => class_basename($log->entity_type), 'entity_id' => $log->entity_id,
            'changes' => $log->changes, 'ip_address' => $log->ip_address,
            'created_at' => $log->created_at?->toIso8601String(),
        ]);

        return $this->success([
            'items' => $logs->items(),
            'meta' => ['current_page' => $logs->currentPage(), 'last_page' => $logs->lastPage(), 'total' => $logs->total()],
        ]);
    }

    private function resource(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function relations(string $resource): array
    {
        return match ($resource) {
            'occupations' => ['group.subcategory.category', 'source'],
            'subcategories' => ['category'],
            'groups' => ['subcategory.category'],
            'workplaces' => ['industry'],
            'roadmaps' => ['occupation', 'stages'],
            default => [],
        };
    }

    private function validated(Request $request, string $resource, ?int $id = null): array
    {
        $unique = $id ? ",{$id}" : '';
        $rules = match ($resource) {
            'categories' => [
                'code' => ['required', 'string', 'max:30', 'unique:occupation_categories,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000'],
                'icon' => ['nullable', 'string', 'max:40'], 'color' => ['nullable', 'string', 'max:20'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            ],
            'subcategories' => [
                'category_id' => ['required', 'integer', 'exists:occupation_categories,id'],
                'code' => ['required', 'string', 'max:40', 'unique:occupation_subcategories,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            ],
            'groups' => [
                'subcategory_id' => ['required', 'integer', 'exists:occupation_subcategories,id'],
                'code' => ['required', 'string', 'max:40', 'unique:occupation_groups,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            ],
            'occupations' => [
                'group_id' => ['required', 'integer', 'exists:occupation_groups,id'],
                'source_id' => ['required', 'integer', 'exists:data_sources,id'],
                'code' => ['required', 'string', 'max:50', 'unique:occupations,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'summary' => ['required', 'string', 'max:500'],
                'description' => ['required', 'string', 'max:10000'],
                'main_activities' => ['nullable', 'array', 'max:20'], 'main_activities.*' => ['string', 'max:500'],
                'responsibilities' => ['nullable', 'string', 'max:5000'], 'work_environment' => ['nullable', 'string', 'max:5000'],
                'alternative_careers' => ['nullable', 'string', 'max:2000'], 'work_preferences' => ['nullable', 'array', 'max:7'],
                'work_preferences.*' => ['string', 'max:50'], 'is_demo' => ['sometimes', 'boolean'],
                'verified_at' => ['nullable', 'date'],
            ],
            'skills' => [
                'code' => ['required', 'string', 'max:50', 'unique:skills,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'category' => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:2000'], 'level_framework' => ['nullable', 'string', 'max:50'],
            ],
            'educations' => [
                'code' => ['required', 'string', 'max:50', 'unique:education_programs,code'.$unique],
                'name' => ['required', 'string', 'max:200'], 'education_level' => ['required', 'string', 'max:50'],
                'program_type' => ['nullable', 'string', 'max:50'], 'description' => ['nullable', 'string', 'max:2000'],
                'source_id' => ['nullable', 'integer', 'exists:data_sources,id'],
            ],
            'certifications' => [
                'code' => ['required', 'string', 'max:50', 'unique:certifications,code'.$unique],
                'name' => ['required', 'string', 'max:200'], 'issuer' => ['nullable', 'string', 'max:200'],
                'credential_type' => ['required', 'in:license,certification'], 'validity_notes' => ['nullable', 'string', 'max:2000'],
                'source_id' => ['required', 'integer', 'exists:data_sources,id'],
            ],
            'industries' => [
                'code' => ['required', 'string', 'max:50', 'unique:industries,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000'],
            ],
            'workplaces' => [
                'industry_id' => ['required', 'integer', 'exists:industries,id'],
                'code' => ['required', 'string', 'max:50', 'unique:workplace_types,code'.$unique],
                'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000'],
            ],
            'sources' => [
                'code' => ['required', 'string', 'max:50', 'unique:data_sources,code'.$unique],
                'name' => ['required', 'string', 'max:200'], 'source_type' => ['nullable', 'string', 'max:50'],
                'url' => ['nullable', 'url', 'max:1000'], 'publisher' => ['nullable', 'string', 'max:200'],
                'verified_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:5000'],
            ],
            'roadmaps' => [
                'occupation_id' => ['required', 'integer', 'exists:occupations,id'],
                'code' => ['required', 'string', 'max:50', 'unique:career_roadmaps,code'.$unique],
                'name' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string', 'max:3000'],
            ],
        };

        return $request->validate($rules);
    }

    private function normalize(string $model, array $data): array
    {
        if ($model === OccupationCategory::class) {
            $data['icon'] ??= 'briefcase';
            $data['color'] ??= 'teal';
        }

        if (in_array($model, [OccupationCategory::class, Occupation::class, Skill::class], true) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::slug($data['code'] ?? $data['name']);
        }

        return $data;
    }

    private function present(string $resource, Model $item): array
    {
        $data = $item->toArray();
        if ($resource === 'occupations') {
            $data['category'] = $item->group?->subcategory?->category?->name;
            $data['subcategory'] = $item->group?->subcategory?->name;
            $data['group'] = $item->group?->name;
        }
        if ($resource === 'workplaces') {
            $data['industry'] = $item->industry?->name;
        }

        return $data;
    }
}
