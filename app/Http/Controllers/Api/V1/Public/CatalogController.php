<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponder;
use App\Models\Skill;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use ApiResponder;

    public function __construct(private PublicCatalogService $catalog) {}

    public function meta(): JsonResponse
    {
        return $this->success([
            'activities' => [
                ['value' => 'analitis', 'label' => 'Analitis dan berpikir', 'description' => 'Menganalisis data, memecahkan masalah, dan menalar.'],
                ['value' => 'praktis', 'label' => 'Praktis dan fisik', 'description' => 'Bekerja dengan tangan, alat, atau proses nyata.'],
                ['value' => 'sosial', 'label' => 'Sosial dan pelayanan', 'description' => 'Membantu, mendampingi, dan berinteraksi dengan orang.'],
                ['value' => 'kreatif', 'label' => 'Kreatif dan desain', 'description' => 'Mencipta ide, visual, cerita, atau karya.'],
                ['value' => 'lapangan', 'label' => 'Lapangan dan alam', 'description' => 'Bekerja di luar ruangan atau dekat lingkungan alam.'],
                ['value' => 'administratif', 'label' => 'Administratif dan pengelolaan', 'description' => 'Mengatur data, dokumen, proses, dan koordinasi.'],
                ['value' => 'kombinasi', 'label' => 'Kombinasi', 'description' => 'Menikmati perpaduan beberapa jenis aktivitas.'],
                ['value' => 'belum_tahu', 'label' => 'Belum tahu', 'description' => 'Ingin dibantu menjelajahi pilihan yang lebih luas.'],
            ],
            'education_levels' => ['SMA/SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'],
            'age_ranges' => ['Di bawah 18 tahun', '18–24 tahun', '25–34 tahun', '35–44 tahun', '45–54 tahun', '55 tahun ke atas'],
            'experience_types' => [
                'work' => 'Pekerjaan', 'internship' => 'Magang', 'project' => 'Proyek',
                'organization' => 'Organisasi', 'volunteer' => 'Kegiatan sukarela',
            ],
            'skill_levels' => [
                1 => 'Dasar', 2 => 'Pemula', 3 => 'Menengah', 4 => 'Mahir', 5 => 'Ahli',
            ],
        ]);
    }

    public function skills(): JsonResponse
    {
        return $this->success(Skill::published()->orderBy('category')->orderBy('name')->get(['id', 'name', 'category', 'description']));
    }

    public function categories(): JsonResponse
    {
        return $this->success($this->catalog->categories());
    }

    public function categoryChildren(int $category): JsonResponse
    {
        return $this->success($this->catalog->categoryChildren($category));
    }

    public function subcategoryChildren(int $subcategory): JsonResponse
    {
        return $this->success($this->catalog->subcategoryChildren($subcategory));
    }

    public function occupations(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:occupation_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:occupation_subcategories,id'],
            'group_id' => ['nullable', 'integer', 'exists:occupation_groups,id'],
            'activity' => ['nullable', 'string', 'max:50'],
            'activities' => ['nullable', 'array', 'max:7'],
            'activities.*' => ['string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $paginator = $this->catalog->occupations($filters);

        return $this->success([
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function occupation(int $occupation): JsonResponse
    {
        return $this->success($this->catalog->occupation($occupation));
    }
}
