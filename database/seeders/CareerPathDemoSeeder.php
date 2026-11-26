<?php

namespace Database\Seeders;

use App\Models\CareerRoadmap;
use App\Models\Certification;
use App\Models\DataSource;
use App\Models\EducationProgram;
use App\Models\Industry;
use App\Models\Occupation;
use App\Models\OccupationCategory;
use App\Models\OccupationGroup;
use App\Models\OccupationSubcategory;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WorkplaceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CareerPathDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SOURCE_CODE = 'BPS-KBJI-2026';

    private const SOURCE_URL = 'https://www.bps.go.id/id/publication/2026/09/18/037999e06c01ccb8acd2e7af/klasifikasi-baku-jabatan-indonesia-kbji-2026.html';

    private const SOURCE_NOTES = 'Hanya nama dan kode profesi yang merujuk KBJI 2026. Seluruh deskripsi, aktivitas, skill, jalur pendidikan, sertifikasi, industri, tempat kerja, dan roadmap adalah konten editorial demo, bukan standar resmi KBJI, SKKNI, atau persyaratan legal.';

    private const SKILL_NOTES = 'Skill demonstrasi editorial, bukan klaim SKKNI.';

    private const EDUCATION_NOTES = 'Jalur pendidikan demonstrasi untuk eksplorasi, bukan syarat legal atau klaim kualifikasi resmi.';

    private const CERTIFICATION_NOTES = 'Sertifikasi ilustratif non-mandatory; bukan sertifikasi resmi atau klaim SKKNI.';

    /**
     * Seed an idempotent, publication-valid CareerPath MVP catalog.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $source = $this->seedSource();
            $editorialSource = DataSource::updateOrCreate(['code' => 'CAREERPATH-EDITORIAL-DEMO'], [
                'name' => 'Konten Editorial Demonstrasi CareerPath',
                'source_type' => 'internal',
                'url' => null,
                'publisher' => 'CareerPath',
                'verified_at' => '2026-11-26',
                'notes' => 'Sumber internal untuk skill dan aturan demonstrasi. Bukan standar resmi, sertifikasi, atau persyaratan legal.',
                'status' => 'published',
            ]);
            $this->seedCatalog($source, $editorialSource);
            $this->seedDemoUsers();
        });
    }

    private function seedSource(): DataSource
    {
        return DataSource::updateOrCreate(['code' => self::SOURCE_CODE], [
            'name' => 'Klasifikasi Baku Jabatan Indonesia (KBJI) 2026',
            'source_type' => 'official',
            'url' => self::SOURCE_URL,
            'publisher' => 'Badan Pusat Statistik',
            'verified_at' => '2026-11-26',
            'notes' => self::SOURCE_NOTES,
            'status' => 'published',
        ]);
    }

    private function seedCatalog(DataSource $source, DataSource $editorialSource): void
    {
        $educationIds = $this->seedEducationPrograms();
        $certificationIds = $this->seedCertifications();

        foreach ($this->categories() as $categoryOrder => $categoryDefinition) {
            $category = OccupationCategory::updateOrCreate(['code' => $categoryDefinition['code']], [
                'slug' => Str::slug($categoryDefinition['name']),
                'name' => $categoryDefinition['name'],
                'description' => $categoryDefinition['description'].' Label kategori editorial demo, bukan nama resmi KBJI.',
                'icon' => $categoryDefinition['icon'],
                'color' => $categoryDefinition['color'],
                'sort_order' => $categoryOrder + 1,
                'status' => 'published',
            ]);

            $subcategory = OccupationSubcategory::updateOrCreate(['code' => $categoryDefinition['code'].'-S01'], [
                'category_id' => $category->id,
                'name' => $categoryDefinition['subcategory'],
                'description' => 'Subkategori editorial demo untuk mengelompokkan profesi dalam '.$categoryDefinition['name'].'; bukan nama resmi KBJI.',
                'sort_order' => 1,
                'status' => 'published',
            ]);

            $industry = Industry::updateOrCreate(['code' => 'IND-'.$categoryDefinition['code']], [
                'name' => $categoryDefinition['industry'],
                'description' => 'Industri editorial demo yang relevan dengan kelompok '.$categoryDefinition['name'].'.',
                'status' => 'published',
            ]);

            $workplaceIds = [];
            foreach ($categoryDefinition['workplaces'] as $workplaceOrder => $workplaceDefinition) {
                $workplace = WorkplaceType::updateOrCreate(['code' => 'WP-'.$categoryDefinition['code'].'-'.sprintf('%02d', $workplaceOrder + 1)], [
                    'industry_id' => $industry->id,
                    'name' => $workplaceDefinition['name'],
                    'description' => $workplaceDefinition['description'].' Jenis lingkungan atau organisasi, bukan lowongan pekerjaan.',
                    'status' => 'published',
                ]);
                $workplaceIds[] = $workplace->id;
            }

            foreach ($categoryDefinition['occupations'] as $occupationOrder => $occupationDefinition) {
                $group = OccupationGroup::updateOrCreate(['code' => $categoryDefinition['code'].'-G'.sprintf('%02d', $occupationOrder + 1)], [
                    'subcategory_id' => $subcategory->id,
                    'name' => $occupationDefinition['name'],
                    'description' => 'Kelompok editorial demo untuk profesi '.$occupationDefinition['name'].'; bukan nama resmi KBJI.',
                    'sort_order' => $occupationOrder + 1,
                    'status' => 'published',
                ]);

                $occupation = Occupation::updateOrCreate(['code' => $occupationDefinition['code']], [
                    'group_id' => $group->id,
                    'source_id' => $source->id,
                    'slug' => Str::slug($occupationDefinition['name']),
                    'name' => $occupationDefinition['name'],
                    'summary' => $occupationDefinition['summary'],
                    'description' => $occupationDefinition['description'].' Deskripsi editorial demo, bukan definisi resmi KBJI atau SKKNI.',
                    'main_activities' => $occupationDefinition['activities'],
                    'responsibilities' => 'Tanggung jawab dalam profil demo meliputi: '.implode(' ', $occupationDefinition['activities']),
                    'work_environment' => $occupationDefinition['environment'],
                    'alternative_careers' => $occupationDefinition['alternatives'],
                    'work_preferences' => $occupationDefinition['preferences'],
                    'is_demo' => true,
                    'verified_at' => '2026-11-26',
                    'version' => 1,
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                $this->syncOccupationSkills($occupation, $occupationDefinition, $editorialSource);
                $this->syncOccupationEducations($occupation, $occupationDefinition, $educationIds);
                $this->syncOccupationCertifications($occupation, $occupationDefinition, $certificationIds);
                $this->syncOccupationWorkplaces($occupation, $occupationDefinition, $workplaceIds);
                $this->seedRoadmap($occupation, $occupationDefinition);
            }
        }
    }

    private function syncOccupationSkills(Occupation $occupation, array $definition, DataSource $editorialSource): void
    {
        $relations = [];

        foreach ($definition['skills'] as $skillOrder => [$name, $category, $targetLevel, $importance]) {
            $code = 'SK-'.$occupation->code.'-'.sprintf('%02d', $skillOrder + 1);
            $skill = Skill::updateOrCreate(['code' => $code], [
                'slug' => Str::slug($code.'-'.$name),
                'name' => $name,
                'category' => $category,
                'description' => 'Skill editorial demonstrasi untuk mengeksplorasi profesi '.$definition['name'].', bukan standar resmi atau SKKNI.',
                'level_framework' => '1-5',
                'status' => 'published',
            ]);

            $relations[$skill->id] = [
                'target_level' => $targetLevel,
                'requirement_type' => $skillOrder === 0 ? 'mandatory' : 'recommended',
                'importance' => $importance,
                'notes' => self::SKILL_NOTES,
                'source_id' => $editorialSource->id,
            ];
        }

        $occupation->skills()->sync($relations);
    }

    private function syncOccupationEducations(Occupation $occupation, array $definition, array $educationIds): void
    {
        $relations = [];

        foreach ($definition['educations'] as [$educationCode, $relationshipType]) {
            $relations[$educationIds[$educationCode]] = [
                'relationship_type' => $relationshipType,
                'notes' => self::EDUCATION_NOTES,
            ];
        }

        // The migration names this pivot key education_id, not education_program_id.
        DB::table('occupation_educations')
            ->where('occupation_id', $occupation->id)
            ->whereNotIn('education_id', array_keys($relations))
            ->delete();

        foreach ($relations as $educationId => $attributes) {
            DB::table('occupation_educations')->updateOrInsert(
                ['occupation_id' => $occupation->id, 'education_id' => $educationId],
                $attributes,
            );
        }
    }

    private function syncOccupationCertifications(Occupation $occupation, array $definition, array $certificationIds): void
    {
        $relations = [];

        foreach ($definition['certifications'] as $certificationCode) {
            $relations[$certificationIds[$certificationCode]] = [
                'requirement_type' => 'optional',
                'notes' => self::CERTIFICATION_NOTES,
            ];
        }

        $occupation->certifications()->sync($relations);
    }

    private function syncOccupationWorkplaces(Occupation $occupation, array $definition, array $workplaceIds): void
    {
        $relations = [];

        foreach ($workplaceIds as $workplaceId) {
            $relations[$workplaceId] = [
                'relevance_description' => 'Lingkungan organisasi yang relevan untuk kegiatan '.$definition['name'].', termasuk '.mb_strtolower($definition['activities'][0]),
            ];
        }

        // The migration names this pivot key workplace_id, not workplace_type_id.
        DB::table('occupation_workplaces')
            ->where('occupation_id', $occupation->id)
            ->whereNotIn('workplace_id', array_keys($relations))
            ->delete();

        foreach ($relations as $workplaceId => $attributes) {
            DB::table('occupation_workplaces')->updateOrInsert(
                ['occupation_id' => $occupation->id, 'workplace_id' => $workplaceId],
                $attributes,
            );
        }
    }

    private function seedRoadmap(Occupation $occupation, array $definition): void
    {
        $roadmap = CareerRoadmap::updateOrCreate(['code' => 'RM-'.$occupation->code], [
            'occupation_id' => $occupation->id,
            'name' => 'Jalur Demonstrasi '.$definition['name'],
            'description' => 'Roadmap editorial demo untuk mengeksplorasi '.$definition['name'].'; bukan kurikulum resmi, SKKNI, atau jaminan kesiapan kerja.',
            'status' => 'published',
        ]);

        $skillNames = array_column($definition['skills'], 0);
        $firstActivity = $definition['activities'][0];
        $stages = [
            [
                'sequence' => 1,
                'title' => 'Kenali profesi',
                'objective' => 'Memahami ruang lingkup dan aktivitas inti '.$definition['name'].'.',
                'action' => 'Baca ringkasan profesi lalu petakan aktivitas "'.$firstActivity.'"; tuliskan tiga situasi kerja yang ingin dipelajari lebih jauh.',
                'estimated_weeks' => 1,
            ],
            [
                'sequence' => 2,
                'title' => 'Bangun fondasi',
                'objective' => 'Membangun fondasi '.implode(', ', $skillNames).' untuk '.$definition['name'].'.',
                'action' => 'Susun jadwal latihan untuk '.implode(', ', $skillNames).'; buat satu catatan penerapan masing-masing skill pada aktivitas profesi.',
                'estimated_weeks' => 3,
            ],
            [
                'sequence' => 3,
                'title' => 'Praktik nyata',
                'objective' => 'Menguji pemahaman melalui '.$definition['practice'].'.',
                'action' => 'Kerjakan '.$definition['practice'].'; catat proses, keputusan, hasil, dan umpan balik dari satu pengamat atau rekan.',
                'estimated_weeks' => 4,
            ],
            [
                'sequence' => 4,
                'title' => 'Siapkan portofolio dan eksplorasi',
                'objective' => 'Menyusun bukti pengalaman awal dan rencana eksplorasi '.$definition['name'].'.',
                'action' => 'Rakit '.$definition['portfolio'].'; tambahkan refleksi keterbatasan lalu tentukan tiga organisasi atau komunitas untuk dihubungi.',
                'estimated_weeks' => 2,
            ],
        ];

        foreach ($stages as $stage) {
            // The roadmap_stages table intentionally has no timestamp columns.
            DB::table('roadmap_stages')->updateOrInsert(
                ['code' => 'RM-'.$occupation->code.'-S'.$stage['sequence']],
                [
                    'roadmap_id' => $roadmap->id,
                    'sequence' => $stage['sequence'],
                    'title' => $stage['title'],
                    'objective' => $stage['objective'],
                    'action' => $stage['action'],
                    'resource_title' => 'Latihan mandiri CareerPath',
                    'resource_url' => null,
                    'estimated_weeks' => $stage['estimated_weeks'],
                ],
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function seedEducationPrograms(): array
    {
        $definitions = [
            'EDU-S1-KEDOKTERAN' => ['S1 Kedokteran', 'S1', 'formal', 'Program studi kedokteran sebagai jalur formal yang relevan untuk eksplorasi profesi dokter.'],
            'EDU-S1-BIOMEDIS' => ['S1 Biomedis', 'S1', 'formal', 'Program studi biomedis sebagai jalur alternatif untuk memahami ilmu kesehatan; bukan jalur kualifikasi dokter.'],
            'EDU-D3-KEPERAWATAN' => ['D3 Keperawatan', 'D3', 'formal', 'Program vokasi keperawatan untuk membangun dasar asuhan pasien.'],
            'EDU-S1-KEPERAWATAN' => ['S1 Ilmu Keperawatan', 'S1', 'formal', 'Program studi keperawatan dengan penguatan teori dan praktik asuhan.'],
            'EDU-D3-KEBIDANAN' => ['D3 Kebidanan', 'D3', 'formal', 'Program vokasi kebidanan untuk membangun dasar pelayanan ibu dan bayi.'],
            'EDU-S1-KEBIDANAN' => ['S1 Kebidanan', 'S1', 'formal', 'Program studi kebidanan dengan penguatan analisis dan praktik pelayanan.'],
            'EDU-S1-FARMASI' => ['S1 Farmasi', 'S1', 'formal', 'Program studi farmasi untuk membangun dasar ilmu obat dan pelayanan kefarmasian.'],
            'EDU-D3-FARMASI' => ['D3 Farmasi', 'D3', 'formal', 'Program vokasi farmasi sebagai jalur alternatif eksplorasi; bukan klaim kualifikasi profesi apoteker.'],
            'EDU-D3-MESIN' => ['D3 Teknik Mesin', 'D3', 'formal', 'Program vokasi teknik mesin dengan praktik pemeliharaan dan diagnosis.'],
            'EDU-SMK-MESIN' => ['SMK Teknik Mesin', 'SMA/SMK', 'formal', 'Pendidikan menengah kejuruan teknik mesin sebagai jalur awal keterampilan rekayasa.'],
            'EDU-D3-INDUSTRI' => ['D3 Teknik Industri', 'D3', 'formal', 'Program vokasi teknik industri dengan fokus proses, mutu, dan produktivitas.'],
            'EDU-S1-INDUSTRI' => ['S1 Teknik Industri', 'S1', 'formal', 'Program studi teknik industri dengan penguatan analisis sistem produksi.'],
            'EDU-D3-PERTANIAN' => ['D3 Teknologi Pertanian', 'D3', 'formal', 'Program vokasi pertanian dengan praktik budidaya dan pengendalian hama.'],
            'EDU-S1-AGROTEKNOLOGI' => ['S1 Agroteknologi', 'S1', 'formal', 'Program studi agroteknologi dengan penguatan analisis tanah dan sistem pertanian.'],
            'EDU-PELATIHAN-AGRIBISNIS' => ['Pelatihan Agribisnis Dasar', 'SMA/SMK', 'nonformal', 'Pelatihan singkat untuk memahami pengelolaan usaha tani dan pemasaran hasil.'],
            'EDU-S1-PGSD' => ['S1 Pendidikan Guru Sekolah Dasar', 'S1', 'formal', 'Program studi PGSD untuk membangun fondasi pedagogi dan pembelajaran sekolah dasar.'],
            'EDU-D3-PGSD' => ['D3 Pendidikan Guru Sekolah Dasar', 'D3', 'formal', 'Program vokasi PGSD sebagai jalur alternatif eksplorasi pembelajaran sekolah dasar.'],
            'EDU-S1-DISIPLIN-ILMU' => ['S1 pada Disiplin Ilmu yang Diajarkan', 'S1', 'formal', 'Program sarjana pada disiplin ilmu yang mendalami materi yang akan diajarkan di pendidikan tinggi.'],
            'EDU-S2-DISIPLIN-ILMU' => ['S2 pada Disiplin Ilmu yang Diajarkan', 'S2', 'formal', 'Program magister pada disiplin ilmu terkait sebagai jalur pengembangan akademik.'],
            'EDU-S1-SISTEM-INFORMASI' => ['S1 Sistem Informasi', 'S1', 'formal', 'Program studi yang memadukan analisis bisnis, data, dan pengembangan sistem.'],
            'EDU-S1-TEKNIK-INFORMATIKA' => ['S1 Teknik Informatika', 'S1', 'formal', 'Program studi dengan fondasi pemrograman, algoritma, dan rekayasa perangkat lunak.'],
            'EDU-S1-AKUNTANSI' => ['S1 Akuntansi', 'S1', 'formal', 'Program studi akuntansi untuk membangun dasar pembukuan dan pelaporan keuangan.'],
            'EDU-D3-AKUNTANSI' => ['D3 Akuntansi', 'D3', 'formal', 'Program vokasi akuntansi dengan praktik pembukuan dan administrasi keuangan.'],
            'EDU-S1-KEUANGAN' => ['S1 Keuangan', 'S1', 'formal', 'Program studi keuangan untuk membangun dasar analisis dan pengambilan keputusan finansial.'],
            'EDU-S1-EKONOMI' => ['S1 Ekonomi', 'S1', 'formal', 'Program studi ekonomi sebagai jalur alternatif untuk memahami kebijakan dan perilaku keuangan.'],
            'EDU-S1-ARSITEKTUR' => ['S1 Arsitektur', 'S1', 'formal', 'Program studi arsitektur dengan praktik desain bangunan dan komunikasi desain.'],
            'EDU-D3-SIPIL' => ['D3 Teknik Sipil', 'D3', 'formal', 'Program vokasi teknik sipil dengan praktik gambar, material, dan pengawasan lapangan.'],
            'EDU-SMK-KONSTRUKSI' => ['SMK Teknik Konstruksi', 'SMA/SMK', 'formal', 'Pendidikan kejuruan konstruksi sebagai jalur awal keterampilan gambar dan pelaksanaan proyek.'],
            'EDU-PELATIHAN-TRUK' => ['Pelatihan Mengemudi Truk Berat', 'SMA/SMK', 'nonformal', 'Pelatihan praktik pengoperasian truk berat dan keselamatan berkendara; bukan pengganti izin berkendara resmi.'],
            'EDU-SMK-OTOMOTIF' => ['SMK Otomotif', 'SMA/SMK', 'formal', 'Pendidikan kejuruan otomotif untuk memahami kendaraan, perawatan, dan keselamatan operasional.'],
            'EDU-D3-LOGISTIK' => ['D3 Logistik', 'D3', 'formal', 'Program vokasi logistik dengan praktik inventaris, distribusi, dan administrasi pergudangan.'],
            'EDU-SMK-LOGISTIK' => ['SMK Logistik dan Perkantoran', 'SMA/SMK', 'formal', 'Pendidikan kejuruan yang mengombinasikan administrasi dokumen dan kegiatan logistik.'],
            'EDU-D3-DESAIN-GRAFIS' => ['D3 Desain Grafis', 'D3', 'formal', 'Program vokasi desain grafis dengan praktik visual, tipografi, dan multimedia.'],
            'EDU-S1-DKV' => ['S1 Desain Komunikasi Visual', 'S1', 'formal', 'Program studi desain komunikasi visual dengan penguatan konsep dan produksi karya.'],
            'EDU-PELATIHAN-FOTOGRAFI' => ['Pelatihan Fotografi Dasar', 'SMA/SMK', 'nonformal', 'Pelatihan praktik kamera, komposisi, pencahayaan, dan pengolahan foto.'],
            'EDU-D3-TATA-BOGA' => ['D3 Tata Boga', 'D3', 'formal', 'Program vokasi tata boga dengan praktik memasak dan manajemen dapur.'],
            'EDU-PELATIHAN-HIGIENE-PANGAN' => ['Pelatihan Higiene dan Keamanan Pangan', 'SMA/SMK', 'nonformal', 'Pelatihan singkat tentang kebersihan, penanganan bahan, dan keselamatan pangan.'],
            'EDU-D3-PARIWISATA' => ['D3 Pariwisata', 'D3', 'formal', 'Program vokasi pariwisata dengan praktik layanan tamu dan perencanaan perjalanan.'],
            'EDU-PELATIHAN-PEMANDU' => ['Pelatihan Pemandu Wisata', 'SMA/SMK', 'nonformal', 'Pelatihan komunikasi kelompok, pengetahuan destinasi, dan pengelolaan perjalanan wisata.'],
            'EDU-S1-HUKUM' => ['S1 Ilmu Hukum', 'S1', 'formal', 'Program studi hukum untuk membangun fondasi riset, argumentasi, dan analisis regulasi.'],
            'EDU-D3-HUKUM' => ['D3 Hukum', 'D3', 'formal', 'Program vokasi hukum sebagai jalur alternatif eksplorasi; bukan klaim kualifikasi profesi pengacara.'],
            'EDU-S1-ADMINISTRASI-PUBLIK' => ['S1 Administrasi Publik', 'S1', 'formal', 'Program studi administrasi publik dengan fokus kebijakan, tata kelola, dan pelayanan publik.'],
            'EDU-S1-PEMERINTAHAN' => ['S1 Ilmu Pemerintahan', 'S1', 'formal', 'Program studi pemerintahan sebagai jalur alternatif analisis kebijakan dan institusi publik.'],
            'EDU-S1-LINGKUNGAN' => ['S1 Teknik Lingkungan', 'S1', 'formal', 'Program studi teknik lingkungan dengan praktik pemantauan dan analisis dampak.'],
            'EDU-S1-BIOLOGI' => ['S1 Biologi', 'S1', 'formal', 'Program studi biologi dengan praktik identifikasi organisme dan survei lapangan.'],
            'EDU-D3-LABORATORIUM' => ['D3 Laboratorium', 'D3', 'formal', 'Program vokasi laboratorium untuk membangun keterampilan sampel, observasi, dan analisis data.'],
            'EDU-D3-K3' => ['D3 Keselamatan dan Kesehatan Kerja', 'D3', 'formal', 'Program vokasi K3 dengan praktik identifikasi bahaya dan penanganan insiden.'],
            'EDU-PELATIHAN-DAMKAR' => ['Pelatihan Pemadam Kebakaran Dasar', 'SMA/SMK', 'nonformal', 'Pelatihan praktik penanganan kebakaran, evakuasi, dan kerja sama tanggap darurat.'],
            'EDU-PELATIHAN-KEAMANAN' => ['Pelatihan Keamanan dan Penanganan Insiden', 'SMA/SMK', 'nonformal', 'Pelatihan pengamatan lingkungan, komunikasi keamanan, dan respons insiden.'],
            'EDU-S1-MANAJEMEN' => ['S1 Manajemen', 'S1', 'formal', 'Program studi manajemen dengan fokus strategi, pemasaran, dan kepemimpinan.'],
            'EDU-D3-PEMASARAN' => ['D3 Manajemen Pemasaran', 'D3', 'formal', 'Program vokasi pemasaran dengan praktik analisis penjualan dan pelayanan pelanggan.'],
            'EDU-PELATIHAN-RITEL' => ['Pelatihan Kewirausahaan dan Ritel', 'SMA/SMK', 'nonformal', 'Pelatihan praktik mengelola usaha kecil, stok, pelanggan, dan negosiasi.'],
            'EDU-D3-PERDAGANGAN' => ['D3 Perdagangan', 'D3', 'formal', 'Program vokasi perdagangan dengan praktik pemasaran dan pengelolaan usaha.'],
        ];

        $ids = [];
        foreach ($definitions as $code => [$name, $level, $type, $description]) {
            $program = EducationProgram::updateOrCreate(['code' => $code], [
                'name' => $name,
                'education_level' => $level,
                'program_type' => $type,
                'description' => $description.' Jalur editorial demo, bukan standar resmi atau persyaratan legal.',
                'source_id' => null,
                'status' => 'published',
            ]);
            $ids[$code] = $program->id;
        }

        return $ids;
    }

    /**
     * @return array<string, int>
     */
    private function seedCertifications(): array
    {
        $definitions = [
            'CERT-DEMO-KESEHATAN' => 'Sertifikasi Demo Komunikasi Pasien',
            'CERT-DEMO-K3' => 'Sertifikasi Demo K3 Dasar',
            'CERT-DEMO-AGRIBISNIS' => 'Sertifikasi Demo Budi Daya Berkelanjutan',
            'CERT-DEMO-PENDIDIKAN' => 'Sertifikasi Demo Desain Pembelajaran',
            'CERT-DEMO-DIGITAL' => 'Sertifikasi Demo Dasar Pengembangan Web',
            'CERT-DEMO-KEUANGAN' => 'Sertifikasi Demo Analisis Keuangan',
            'CERT-DEMO-KREATIF' => 'Sertifikasi Demo Portofolio Visual',
            'CERT-DEMO-PARIWISATA' => 'Sertifikasi Demo Layanan Tamu',
            'CERT-DEMO-HUKUM' => 'Sertifikasi Demo Riset Hukum',
            'CERT-DEMO-LINGKUNGAN' => 'Sertifikasi Demo Observasi Lingkungan',
            'CERT-DEMO-BISNIS' => 'Sertifikasi Demo Pemasaran Dasar',
        ];

        $ids = [];
        foreach ($definitions as $code => $name) {
            $certification = Certification::updateOrCreate(['code' => $code], [
                'name' => $name,
                'issuer' => 'CareerPath Demo Academy',
                'credential_type' => 'certification',
                'validity_notes' => 'Sertifikasi fiktif untuk demonstrasi aplikasi; tidak diakui sebagai sertifikasi resmi.',
                'source_id' => null,
                'status' => 'published',
            ]);
            $ids[$code] = $certification->id;
        }

        return $ids;
    }

    private function seedDemoUsers(): void
    {
        $this->seedDemoUser(
            email: 'admin@careerpath.local',
            name: 'Administrator Demo',
            password: 'CareerPath#2026',
            role: 'admin',
        );

        $user = $this->seedDemoUser(
            email: 'user@careerpath.local',
            name: 'Pengguna Demo',
            password: 'UserCareer#2026',
            role: 'user',
        );

        $interestIds = OccupationCategory::query()
            ->whereIn('code', ['C01', 'C04', 'C05', 'C09', 'C12'])
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        UserProfile::updateOrCreate(['user_id' => $user->id], [
            'display_name' => 'Pengguna Demo',
            'age_range' => '18–24 tahun',
            'region' => 'Indonesia',
            'education_level' => 'SMA',
            'field_of_study' => 'IPA',
            'bio' => 'Profil lokal untuk mencoba eksplorasi karier CareerPath.',
            'interests' => $interestIds,
            'work_preferences' => ['analitis', 'sosial', 'kreatif', 'praktis'],
            'experiences_reviewed' => false,
            'certifications_reviewed' => false,
            'onboarding_completed' => false,
        ]);
    }

    private function seedDemoUser(string $email, string $name, string $password, string $role): User
    {
        $attributes = [
            'name' => $name,
            'role' => $role,
            'status' => 'active',
            'email_verified_at' => now(),
        ];

        if (! User::query()->where('email', $email)->exists()) {
            $attributes['password'] = $password;
        }

        $user = User::updateOrCreate(['email' => $email], $attributes);

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if (! Hash::check($password, $user->password)) {
            $user->update(['password' => $password]);
        }

        return $user;
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     icon: string,
     *     color: string,
     *     subcategory: string,
     *     industry: string,
     *     workplaces: list<array{name: string, description: string}>,
     *     occupations: list<array{
     *         code: string,
     *         name: string,
     *         summary: string,
     *         description: string,
     *         activities: list<string>,
     *         environment: string,
     *         alternatives: string,
     *         preferences: list<string>,
     *         skills: list<array{0: string, 1: string, 2: int, 3: int}>,
     *         educations: list<array{0: string, 1: string}>,
     *         certifications: list<string>,
     *         practice: string,
     *         portfolio: string
     *     }>
     * }>
     */
    private function categories(): array
    {
        return [
            [
                'code' => 'C01',
                'name' => 'Kesehatan dan Medis',
                'description' => 'Eksplorasi profesi yang mendukung pemeriksaan, perawatan, dan layanan kesehatan.',
                'icon' => 'heart-pulse',
                'color' => 'red',
                'subcategory' => 'Pelayanan Kesehatan',
                'industry' => 'Layanan Kesehatan',
                'workplaces' => [
                    ['name' => 'Rumah sakit dan klinik', 'description' => 'Organisasi pelayanan kesehatan dengan layanan rawat inap, rawat jalan, dan spesialisasi.'],
                    ['name' => 'Puskesmas dan layanan komunitas', 'description' => 'Fasilitas pelayanan kesehatan primer serta kegiatan pencegahan di masyarakat.'],
                ],
                'occupations' => [
                    [
                        'code' => '2211',
                        'name' => 'Praktisi Dokter Umum',
                        'summary' => 'Profesi pelayanan kesehatan yang menilai kondisi pasien, menegakkan diagnosis awal, dan merencanakan penanganan atau rujukan.',
                        'description' => 'Dokter umum menjadi pintu masuk pasien ke layanan kesehatan dengan menggabungkan wawancara, pemeriksaan fisik, penalaran klinis, dan komunikasi.',
                        'activities' => [
                            'Mengambil riwayat kesehatan dan melakukan pemeriksaan fisik pasien.',
                            'Menilai gejala, menetapkan diagnosis awal, dan menyusun rencana penanganan.',
                            'Memberikan edukasi pencegahan serta merujuk kasus yang memerlukan penanganan spesialis.',
                        ],
                        'environment' => 'Bekerja di rumah sakit, klinik, puskesmas, atau layanan kunjungan dengan interaksi pasien yang intens dan jadwal yang bervariasi.',
                        'alternatives' => 'Peneliti klinis, edukator kesehatan, atau pengelola program kesehatan masyarakat.',
                        'preferences' => ['analitis', 'sosial'],
                        'skills' => [
                            ['Analisis klinis', 'Kognitif', 4, 3],
                            ['Komunikasi pasien', 'Komunikasi', 4, 3],
                            ['Pengambilan keputusan medis', 'Kognitif', 4, 3],
                        ],
                        'educations' => [['EDU-S1-KEDOKTERAN', 'relevant'], ['EDU-S1-BIOMEDIS', 'alternative']],
                        'certifications' => ['CERT-DEMO-KESEHATAN'],
                        'practice' => 'studi kasus pasien fiktif berisi anamnesa, pemeriksaan awal, diagnosis kerja, dan rencana rujukan',
                        'portfolio' => 'portofolio studi kasus pasien fiktif dengan catatan penalaran klinis',
                    ],
                    [
                        'code' => '2221',
                        'name' => 'Profesional Keperawatan',
                        'summary' => 'Profesi yang memberikan asuhan keperawatan, memantau kondisi pasien, dan mendukung pelaksanaan rencana terapi.',
                        'description' => 'Perawat menggabungkan observasi klinis, tindakan asuhan, komunikasi dengan pasien, dan koordinasi dengan tim kesehatan.',
                        'activities' => [
                            'Mengidentifikasi kebutuhan pasien dan menyusun rencana asuhan keperawatan.',
                            'Melaksanakan tindakan keperawatan serta memantau respons pasien.',
                            'Mendokumentasikan kondisi pasien dan berkoordinasi dengan tim kesehatan.',
                        ],
                        'environment' => 'Bekerja di rumah sakit, klinik, puskesmas, atau layanan home care dengan tugas yang menuntut ketelitian dan kesiapan fisik.',
                        'alternatives' => 'Perawat klinis, edukator keperawatan, atau pengelola layanan kesehatan komunitas.',
                        'preferences' => ['sosial', 'praktis', 'analitis'],
                        'skills' => [
                            ['Asuhan keperawatan', 'Klinis', 4, 3],
                            ['Komunikasi pasien', 'Komunikasi', 4, 3],
                            ['Kesiapan tanggap darurat', 'Klinis', 4, 3],
                        ],
                        'educations' => [['EDU-D3-KEPERAWATAN', 'relevant'], ['EDU-S1-KEPERAWATAN', 'alternative']],
                        'certifications' => ['CERT-DEMO-KESEHATAN'],
                        'practice' => 'simulasi asuhan pasien fiktif lengkap dengan pengkajian, tindakan dasar, dan dokumentasi',
                        'portfolio' => 'portofolio simulasi asuhan dan refleksi komunikasi pasien',
                    ],
                    [
                        'code' => '2222',
                        'name' => 'Profesional Kebidanan',
                        'summary' => 'Profesi yang mendukung kesehatan ibu dan bayi melalui pemantauan kehamilan, persalinan, serta perawatan setelahnya.',
                        'description' => 'Bidan bekerja dengan pendekatan klinis dan komunikatif untuk mengenali risiko, mendampingi persalinan, dan memberikan edukasi keluarga.',
                        'activities' => [
                            'Memantau kehamilan dan melakukan pengkajian kesehatan ibu serta bayi.',
                            'Mendampingi proses persalinan dengan tetap memperhatikan keselamatan ibu dan bayi.',
                            'Memberikan edukasi perawatan pasca persalinan, laktasi, dan kesehatan bayi.',
                        ],
                        'environment' => 'Bekerja di rumah sakit, klinik kebidanan, puskesmas, atau layanan komunitas dengan jadwal siaga dan kondisi darurat yang mungkin muncul.',
                        'alternatives' => 'Konselor laktasi, edukator kesehatan ibu dan anak, atau pengelola program kesehatan keluarga.',
                        'preferences' => ['sosial', 'praktis', 'analitis'],
                        'skills' => [
                            ['Asuhan kebidanan', 'Klinis', 4, 3],
                            ['Komunikasi pasien', 'Komunikasi', 4, 3],
                            ['Kesehatan ibu dan bayi', 'Klinis', 4, 3],
                        ],
                        'educations' => [['EDU-D3-KEBIDANAN', 'relevant'], ['EDU-S1-KEBIDANAN', 'alternative']],
                        'certifications' => ['CERT-DEMO-KESEHATAN'],
                        'practice' => 'studi kasus pemantauan kehamilan dan persalinan fiktif dengan pengkajian risiko',
                        'portfolio' => 'portofolio studi kasus ibu dan bayi beserta rencana edukasi keluarga',
                    ],
                    [
                        'code' => '2262',
                        'name' => 'Apoteker',
                        'summary' => 'Profesi yang memastikan penggunaan obat aman melalui peracikan, pemeriksaan resep, dan konseling kefarmasian.',
                        'description' => 'Apoteker memadukan pengetahuan obat, ketelitian, dan komunikasi untuk mendukung terapi yang tepat di apotek atau fasilitas kesehatan.',
                        'activities' => [
                            'Memeriksa keabsahan dan kelengkapan resep serta kebutuhan obat pasien.',
                            'Menyiapkan obat dan memberikan informasi penggunaan yang mudah dipahami.',
                            'Memantau keamanan terapi serta bekerja sama dengan tenaga kesehatan lain.',
                        ],
                        'environment' => 'Bekerja di apotek, rumah sakit, industri farmasi, atau fasilitas layanan obat dengan standar ketelitian yang tinggi.',
                        'alternatives' => 'Peneliti obat, pengembang produk farmasi, atau edukator kefarmasian.',
                        'preferences' => ['analitis', 'sosial', 'praktis'],
                        'skills' => [
                            ['Pengetahuan obat', 'Klinis', 4, 3],
                            ['Pelayanan kefarmasian', 'Klinis', 4, 3],
                            ['Ketelitian perhitungan dosis', 'Kognitif', 4, 3],
                        ],
                        'educations' => [['EDU-S1-FARMASI', 'relevant'], ['EDU-D3-FARMASI', 'alternative']],
                        'certifications' => ['CERT-DEMO-KESEHATAN'],
                        'practice' => 'simulasi pemeriksaan resep fiktif, peracikan sederhana, dan konseling penggunaan obat',
                        'portfolio' => 'portofolio studi kasus resep dan materi edukasi penggunaan obat',
                    ],
                ],
            ],
            [
                'code' => 'C02',
                'name' => 'Industri dan Manufaktur',
                'description' => 'Eksplorasi profesi yang menjaga mesin, proses produksi, mutu, dan keselamatan industri.',
                'icon' => 'factory',
                'color' => 'orange',
                'subcategory' => 'Operasi dan Rekayasa Industri',
                'industry' => 'Manufaktur dan Rekayasa',
                'workplaces' => [
                    ['name' => 'Pabrik dan bengkel industri', 'description' => 'Lingkungan produksi dengan mesin, peralatan, material, dan prosedur keselamatan.'],
                    ['name' => 'Kantor pengawasan produksi', 'description' => 'Ruang kerja pengendalian produksi, pengendalian mutu, dan koordinasi tim lapangan.'],
                ],
                'occupations' => [
                    [
                        'code' => '3115',
                        'name' => 'Teknisi Mekanis',
                        'summary' => 'Profesi teknis yang memasang, merawat, dan memperbaiki mesin agar proses industri berjalan aman dan efisien.',
                        'description' => 'Teknisi mekanis menggunakan pengamatan, alat ukur, dan pengetahuan mesin untuk menemukan gangguan serta melakukan pemeliharaan.',
                        'activities' => [
                            'Memasang dan menyetel komponen mesin sesuai kebutuhan operasi.',
                            'Mendiagnosis gangguan mekanis melalui pemeriksaan dan pengukuran.',
                            'Melakukan pemeliharaan preventif serta mendokumentasikan hasil perbaikan.',
                        ],
                        'environment' => 'Bekerja di pabrik, bengkel, atau area pemeliharaan dengan suara mesin, pekerjaan fisik, dan prosedur K3.',
                        'alternatives' => 'Perencana pemeliharaan, teknisi produksi, atau pemeriksa kualitas komponen.',
                        'preferences' => ['praktis', 'analitis'],
                        'skills' => [
                            ['Mekanika mesin', 'Teknis', 4, 3],
                            ['Diagnosis kerusakan', 'Teknis', 4, 3],
                            ['K3 pemeliharaan', 'Keselamatan', 3, 2],
                        ],
                        'educations' => [['EDU-D3-MESIN', 'relevant'], ['EDU-SMK-MESIN', 'alternative']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'simulasi diagnosis mesin fiktif menggunakan checklist pemeriksaan dan hasil pengukuran',
                        'portfolio' => 'portofolio checklist pemeliharaan dan laporan diagnosis gangguan mekanis',
                    ],
                    [
                        'code' => '3122',
                        'name' => 'Supervisor Industri',
                        'summary' => 'Profesi yang mengoordinasikan tim produksi, memantau target, dan memastikan proses berjalan sesuai standar operasi.',
                        'description' => 'Supervisor industri menghubungkan perencanaan dengan pelaksanaan di lini produksi melalui pengawasan, analisis data, dan kepemimpinan.',
                        'activities' => [
                            'Mengoordinasikan pembagian tugas dan target produksi tim.',
                            'Memantau kualitas, kapasitas, dan hambatan pada lini produksi.',
                            'Menindaklanjuti temuan mutu serta mendokumentasikan perbaikan proses.',
                        ],
                        'environment' => 'Bekerja di area produksi dan kantor pengawasan dengan interaksi lintas fungsi serta jadwal yang mengikuti operasi pabrik.',
                        'alternatives' => 'Manajer operasi, perencana produksi, atau analis peningkatan mutu.',
                        'preferences' => ['administratif', 'analitis', 'sosial'],
                        'skills' => [
                            ['Pengawasan produksi', 'Manajerial', 4, 3],
                            ['Analisis data produksi', 'Kognitif', 3, 2],
                            ['Kepemimpinan tim', 'Manajerial', 4, 3],
                        ],
                        'educations' => [['EDU-D3-INDUSTRI', 'relevant'], ['EDU-S1-INDUSTRI', 'alternative']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'studi kasus lini produksi fiktif dengan data target, temuan mutu, dan kendala tenaga kerja',
                        'portfolio' => 'portofolio papan kendali produksi dan rencana perbaikan lintas tim',
                    ],
                ],
            ],
            [
                'code' => 'C03',
                'name' => 'Pertanian dan Pangan',
                'description' => 'Eksplorasi profesi yang mengelola tanaman, lahan, hasil panen, dan usaha pangan.',
                'icon' => 'sprout',
                'color' => 'green',
                'subcategory' => 'Produksi dan Agribisnis',
                'industry' => 'Pertanian dan Pangan',
                'workplaces' => [
                    ['name' => 'Lahan pertanian dan perkebunan', 'description' => 'Lingkungan produksi terbuka yang dipengaruhi cuaca, musim, tanah, dan kebutuhan tanaman.'],
                    ['name' => 'Laboratorium dan unit pengolahan pangan', 'description' => 'Organisasi pengujian, pengolahan, dan pemantauan mutu hasil pertanian serta pangan.'],
                ],
                'occupations' => [
                    [
                        'code' => '3142',
                        'name' => 'Teknisi Pertanian',
                        'summary' => 'Profesi yang menerapkan teknik budidaya dan analisis pertanian untuk meningkatkan kesehatan tanaman serta hasil produksi.',
                        'description' => 'Teknisi pertanian memadukan praktik lapangan, pengamatan tanah, dan pengendalian hama untuk membantu petani menyelesaikan masalah produksi.',
                        'activities' => [
                            'Menganalisis kondisi tanah, air, dan kebutuhan tanaman.',
                            'Memberikan pendampingan teknik budidaya serta pengendalian hama.',
                            'Mengukur hasil panen dan menyusun rekomendasi perbaikan praktik pertanian.',
                        ],
                        'environment' => 'Bekerja di lahan, kebun, unit pertanian, atau laboratorium dengan paparan cuaca dan interaksi langsung dengan petani.',
                        'alternatives' => 'Konsultan agribisnis, pengelola unit pertanian, atau analis mutu hasil panen.',
                        'preferences' => ['lapangan', 'praktis', 'analitis'],
                        'skills' => [
                            ['Teknik budidaya', 'Pertanian', 4, 3],
                            ['Analisis tanah', 'Pertanian', 3, 2],
                            ['Pengendalian hama', 'Pertanian', 4, 3],
                        ],
                        'educations' => [['EDU-D3-PERTANIAN', 'relevant'], ['EDU-S1-AGROTEKNOLOGI', 'alternative']],
                        'certifications' => ['CERT-DEMO-AGRIBISNIS'],
                        'practice' => 'studi kasus lahan fiktif dengan catatan tanah, serangan hama, dan rencana perbaikan budidaya',
                        'portfolio' => 'portofolio rekomendasi budidaya dan pencatatan hasil pengamatan lapangan',
                    ],
                    [
                        'code' => '6310',
                        'name' => 'Petani Subsisten',
                        'summary' => 'Profesi yang mengelola lahan pertanian skala rumah tangga terutama untuk memenuhi kebutuhan keluarga dan memasok pasar sekitar.',
                        'description' => 'Petani subsisten mengatur seluruh siklus produksi, dari memilih benih sampai panen, dengan sumber daya dan risiko yang dimiliki rumah tangga.',
                        'activities' => [
                            'Mengolah lahan, memilih benih, dan melaksanakan penanaman.',
                            'Mengatur penyiraman, pemupukan, serta pengendalian hama secara mandiri.',
                            'Memanen hasil dan mengelola penjualan atau pertukaran kebutuhan keluarga.',
                        ],
                        'environment' => 'Bekerja di lahan terbuka dengan ritme musim, pekerjaan fisik, penggunaan peralatan pertanian, dan ketergantungan pada cuaca.',
                        'alternatives' => 'Pengelola koperasi tani, pedagang hasil pertanian, atau penyedia jasa pengolahan lahan.',
                        'preferences' => ['lapangan', 'praktis'],
                        'skills' => [
                            ['Pengetahuan budidaya', 'Pertanian', 4, 3],
                            ['Pengelolaan lahan', 'Pertanian', 3, 2],
                            ['Kewirausahaan tani', 'Bisnis', 3, 2],
                        ],
                        'educations' => [['EDU-PELATIHAN-AGRIBISNIS', 'relevant']],
                        'certifications' => ['CERT-DEMO-AGRIBISNIS'],
                        'practice' => 'rencana satu musim tanam fiktif untuk lahan kecil dengan anggaran dan perhitungan hasil panen',
                        'portfolio' => 'portofolio kalender tanam, catatan biaya, dan rencana pemasaran hasil panen',
                    ],
                ],
            ],
            [
                'code' => 'C04',
                'name' => 'Pendidikan',
                'description' => 'Eksplorasi profesi yang merancang pembelajaran, membimbing peserta didik, dan mengembangkan ilmu.',
                'icon' => 'graduation-cap',
                'color' => 'blue',
                'subcategory' => 'Pembelajaran dan Pengajaran',
                'industry' => 'Pendidikan',
                'workplaces' => [
                    ['name' => 'Sekolah dan ruang kelas', 'description' => 'Organisasi pendidikan dengan ruang kelas, kegiatan ekstrakurikuler, dan interaksi peserta didik.'],
                    ['name' => 'Perguruan tinggi dan lembaga riset', 'description' => 'Lingkungan akademik untuk perkuliahan, penelitian, pengembangan materi, dan publikasi ilmiah.'],
                ],
                'occupations' => [
                    [
                        'code' => '2341',
                        'name' => 'Guru Sekolah Dasar',
                        'summary' => 'Profesi yang merancang dan melaksanakan pembelajaran dasar serta mendukung perkembangan akademik dan sosial peserta didik.',
                        'description' => 'Guru SD memadukan penguasaan materi, pedagogi, dan pengamatan anak untuk menciptakan pengalaman belajar yang terstruktur.',
                        'activities' => [
                            'Menyusun rencana pembelajaran yang sesuai tahap perkembangan peserta didik.',
                            'Melaksanakan kegiatan kelas dengan media dan metode yang beragam.',
                            'Mengases kemajuan belajar dan memberikan umpan balik kepada peserta didik serta orang tua.',
                        ],
                        'environment' => 'Bekerja di sekolah dasar dengan interaksi kelas, koordinasi guru, dan kegiatan evaluasi yang berulang.',
                        'alternatives' => 'Pengembang materi ajar, konselor pendidikan, atau pendamping pembelajaran.',
                        'preferences' => ['sosial', 'kreatif', 'administratif'],
                        'skills' => [
                            ['Perencanaan pembelajaran', 'Pendidikan', 4, 3],
                            ['Komunikasi kelas', 'Komunikasi', 4, 3],
                            ['Asesmen pendidikan', 'Pendidikan', 3, 2],
                        ],
                        'educations' => [['EDU-S1-PGSD', 'relevant'], ['EDU-D3-PGSD', 'alternative']],
                        'certifications' => ['CERT-DEMO-PENDIDIKAN'],
                        'practice' => 'microteaching satu materi pelajaran SD dengan rencana ajar, media, dan contoh asesmen',
                        'portfolio' => 'portofolio rencana ajar, media kelas, dan refleksi hasil microteaching',
                    ],
                    [
                        'code' => '2310',
                        'name' => 'Dosen Universitas dan Pendidikan Tinggi',
                        'summary' => 'Profesi akademik yang mengajar, melakukan penelitian, dan menerbitkan ilmu pada perguruan tinggi.',
                        'description' => 'Dosen menghubungkan keahlian disiplin ilmu dengan perkuliahan, pendampingan mahasiswa, penelitian, dan publikasi.',
                        'activities' => [
                            'Menyusun dan melaksanakan perkuliahan pada bidang keahlian.',
                            'Melakukan penelitian serta menerbitkan hasil kajian ilmiah.',
                            'Membimbing mahasiswa dalam tugas akademik dan penelitian.',
                        ],
                        'environment' => 'Bekerja di kampus atau lembaga riset dengan agenda mengajar, diskusi akademik, penelitian, dan publikasi.',
                        'alternatives' => 'Peneliti, pengembang kurikulum tinggi, atau analis kebijakan pendidikan.',
                        'preferences' => ['analitis', 'sosial', 'kreatif'],
                        'skills' => [
                            ['Pengetahuan disiplin ilmu', 'Akademik', 4, 3],
                            ['Metode penelitian', 'Akademik', 4, 3],
                            ['Komunikasi akademik', 'Komunikasi', 4, 3],
                        ],
                        'educations' => [['EDU-S1-DISIPLIN-ILMU', 'relevant'], ['EDU-S2-DISIPLIN-ILMU', 'alternative']],
                        'certifications' => ['CERT-DEMO-PENDIDIKAN'],
                        'practice' => 'rancangan satu pertemuan kuliah dan proposal penelitian kecil pada disiplin ilmu pilihan',
                        'portfolio' => 'portofolio bahan kuliah, proposal penelitian, dan ringkasan publikasi',
                    ],
                ],
            ],
            [
                'code' => 'C05',
                'name' => 'Teknologi dan Digital',
                'description' => 'Eksplorasi profesi yang menganalisis kebutuhan digital dan membangun perangkat lunak yang berguna.',
                'icon' => 'code',
                'color' => 'indigo',
                'subcategory' => 'Sistem dan Rekayasa Digital',
                'industry' => 'Teknologi Informasi',
                'workplaces' => [
                    ['name' => 'Perusahaan teknologi', 'description' => 'Organisasi yang membangun, mengoperasikan, dan mendukung produk atau layanan digital.'],
                    ['name' => 'Studio pengembangan produk digital', 'description' => 'Tim lintas disiplin untuk merancang, membangun, menguji, dan meningkatkan produk digital.'],
                ],
                'occupations' => [
                    [
                        'code' => '2511',
                        'name' => 'Analis Sistem',
                        'summary' => 'Profesi yang mengidentifikasi kebutuhan pengguna dan menerjemahkannya menjadi rancangan sistem yang jelas.',
                        'description' => 'Analis sistem menjembatani bahasa bisnis dan teknis melalui wawancara, pemodelan proses, serta dokumentasi kebutuhan.',
                        'activities' => [
                            'Mengumpulkan dan menganalisis kebutuhan pengguna serta proses bisnis.',
                            'Menyusun spesifikasi, alur proses, dan model rancangan sistem.',
                            'Mengomunikasikan solusi kepada pemangku kepentingan dan mendukung pengujian.',
                        ],
                        'environment' => 'Bekerja di kantor, tim produk, atau lokasi pengguna dengan banyak diskusi, dokumentasi, dan penggunaan alat analisis.',
                        'alternatives' => 'Analis data bisnis, arsitek solusi, atau konsultan transformasi digital.',
                        'preferences' => ['analitis', 'administratif', 'sosial'],
                        'skills' => [
                            ['Analisis kebutuhan', 'Kognitif', 4, 3],
                            ['Pemodelan sistem', 'Teknis', 4, 3],
                            ['Komunikasi pemangku kepentingan', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-S1-SISTEM-INFORMASI', 'relevant'], ['EDU-S1-TEKNIK-INFORMATIKA', 'alternative']],
                        'certifications' => ['CERT-DEMO-DIGITAL'],
                        'practice' => 'studi kasus layanan digital fiktif dengan wawancara kebutuhan, peta proses, dan spesifikasi sederhana',
                        'portfolio' => 'portofolio dokumen kebutuhan, diagram alur, dan rekomendasi perbaikan sistem',
                    ],
                    [
                        'code' => '2512',
                        'name' => 'Pengembang Perangkat Lunak',
                        'summary' => 'Profesi yang merancang, menulis, menguji, dan memelihara kode untuk membangun aplikasi atau layanan digital.',
                        'description' => 'Pengembang perangkat lunak mengubah kebutuhan menjadi fitur yang berfungsi melalui pemrograman, pengujian, dan kolaborasi tim.',
                        'activities' => [
                            'Menerjemahkan kebutuhan menjadi komponen dan logika perangkat lunak.',
                            'Menulis kode serta melakukan pengujian fitur dan perbaikan kesalahan.',
                            'Berkolaborasi dalam pengelolaan versi dan pemeliharaan aplikasi.',
                        ],
                        'environment' => 'Bekerja dengan komputer dalam tim produk atau teknik, menggunakan dokumentasi, pengujian, dan siklus rilis yang iteratif.',
                        'alternatives' => 'Insinyur data, pengembang aplikasi seluler, atau analis sistem.',
                        'preferences' => ['analitis', 'praktis', 'kreatif'],
                        'skills' => [
                            ['Pemrograman', 'Teknis', 4, 3],
                            ['Pengujian perangkat lunak', 'Teknis', 3, 2],
                            ['Kolaborasi versi', 'Teknis', 4, 3],
                        ],
                        'educations' => [['EDU-S1-TEKNIK-INFORMATIKA', 'relevant'], ['EDU-S1-SISTEM-INFORMASI', 'alternative']],
                        'certifications' => ['CERT-DEMO-DIGITAL'],
                        'practice' => 'pembangunan aplikasi web kecil dengan satu alur pengguna dan pengujian fitur dasar',
                        'portfolio' => 'portofolio repositori kode, tangkapan aplikasi, dan catatan pengujian',
                    ],
                ],
            ],
            [
                'code' => 'C06',
                'name' => 'Bisnis dan Keuangan',
                'description' => 'Eksplorasi profesi yang mengelola pembukuan, menganalisis keuangan, dan mendukung keputusan bisnis.',
                'icon' => 'chart-no-axes-combined',
                'color' => 'amber',
                'subcategory' => 'Akuntansi dan Keuangan',
                'industry' => 'Keuangan dan Bisnis',
                'workplaces' => [
                    ['name' => 'Kantor akuntansi dan keuangan', 'description' => 'Organisasi penyedia jasa pembukuan, audit, konsultasi, atau pengelolaan keuangan.'],
                    ['name' => 'Bank dan perusahaan multinasional', 'description' => 'Lingkungan organisasi dengan proses keuangan, kontrol internal, dan pelaporan lintas unit.'],
                ],
                'occupations' => [
                    [
                        'code' => '2411',
                        'name' => 'Akuntan',
                        'summary' => 'Profesi yang mencatat transaksi, menyusun laporan keuangan, dan membantu memastikan informasi keuangan akurat.',
                        'description' => 'Akuntan menggunakan ketelitian dan prinsip akuntansi untuk mengelola data keuangan serta mendukung kepatuhan dan pelaporan.',
                        'activities' => [
                            'Mencatat dan memeriksa transaksi keuangan berdasarkan dokumen sumber.',
                            'Menyusun laporan keuangan dan melakukan rekonsiliasi akun.',
                            'Mendukung pemeriksaan, kewajiban pelaporan, dan pengendalian internal.',
                        ],
                        'environment' => 'Bekerja di kantor akuntansi, perusahaan, atau organisasi publik dengan tenggat pelaporan dan data yang rinci.',
                        'alternatives' => 'Auditor, analis pajak, atau pengelola keuangan operasional.',
                        'preferences' => ['analitis', 'administratif'],
                        'skills' => [
                            ['Pembukuan akuntansi', 'Keuangan', 4, 3],
                            ['Analisis laporan keuangan', 'Keuangan', 4, 3],
                            ['Ketelitian data', 'Kognitif', 3, 2],
                        ],
                        'educations' => [['EDU-S1-AKUNTANSI', 'relevant'], ['EDU-D3-AKUNTANSI', 'alternative']],
                        'certifications' => ['CERT-DEMO-KEUANGAN'],
                        'practice' => 'studi kasus pembukuan usaha fiktif dengan dokumen transaksi, jurnal, dan rekonsiliasi sederhana',
                        'portfolio' => 'portofolio jurnal, laporan keuangan sederhana, dan catatan rekonsiliasi',
                    ],
                    [
                        'code' => '2413',
                        'name' => 'Analis Keuangan',
                        'summary' => 'Profesi yang mengevaluasi data keuangan untuk mendukung penilaian investasi, perencanaan, dan keputusan bisnis.',
                        'description' => 'Analis keuangan menghubungkan laporan, asumsi, dan model untuk menjelaskan kinerja serta risiko finansial.',
                        'activities' => [
                            'Menganalisis laporan keuangan dan tren kinerja organisasi.',
                            'Membangun model atau skenario keuangan untuk kebutuhan perencanaan.',
                            'Menyusun rekomendasi berdasarkan risiko, biaya, dan potensi hasil.',
                        ],
                        'environment' => 'Bekerja di bank, perusahaan, atau firma konsultasi dengan analisis data, presentasi, dan rapat keputusan.',
                        'alternatives' => 'Analis investasi, perencana keuangan, atau konsultan manajemen.',
                        'preferences' => ['analitis', 'administratif'],
                        'skills' => [
                            ['Analisis keuangan', 'Keuangan', 4, 3],
                            ['Pemodelan keuangan', 'Keuangan', 4, 3],
                            ['Interpretasi data', 'Kognitif', 3, 2],
                        ],
                        'educations' => [['EDU-S1-KEUANGAN', 'relevant'], ['EDU-S1-EKONOMI', 'alternative']],
                        'certifications' => ['CERT-DEMO-KEUANGAN'],
                        'practice' => 'analisis laporan keuangan perusahaan fiktif dengan perhitungan rasio dan skenario pertumbuhan',
                        'portfolio' => 'portofolio model keuangan dan ringkasan rekomendasi berbasis data',
                    ],
                ],
            ],
            [
                'code' => 'C07',
                'name' => 'Konstruksi dan Infrastruktur',
                'description' => 'Eksplorasi profesi yang merancang bangunan dan mendukung pelaksanaan infrastruktur yang aman.',
                'icon' => 'hard-hat',
                'color' => 'yellow',
                'subcategory' => 'Desain dan Konstruksi Bangunan',
                'industry' => 'Konstruksi',
                'workplaces' => [
                    ['name' => 'Kantor desain arsitektur', 'description' => 'Organisasi perancangan dengan kegiatan gambar, pemodelan, koordinasi desain, dan konsultasi klien.'],
                    ['name' => 'Lokasi proyek konstruksi', 'description' => 'Lingkungan pelaksanaan proyek dengan material, peralatan, pengawasan lapangan, dan prosedur keselamatan.'],
                ],
                'occupations' => [
                    [
                        'code' => '2161',
                        'name' => 'Arsitek Bangunan',
                        'summary' => 'Profesi yang merancang ruang dan bangunan dengan mempertimbangkan fungsi, estetika, keselamatan, dan kebutuhan pengguna.',
                        'description' => 'Arsitek mengembangkan ide desain menjadi gambar dan spesifikasi melalui komunikasi dengan klien serta koordinasi teknis.',
                        'activities' => [
                            'Mengidentifikasi kebutuhan pengguna dan menyusun konsep desain bangunan.',
                            'Menggambar rancangan serta mengoordinasikan sistem bangunan dengan disiplin lain.',
                            'Mendampingi pemilihan material, revisi desain, dan klarifikasi pelaksanaan.',
                        ],
                        'environment' => 'Bekerja di kantor desain dan lokasi proyek dengan kegiatan gambar, rapat koordinasi, serta kunjungan lapangan.',
                        'alternatives' => 'Desainer interior, perancang kota, atau konsultan bangunan berkelanjutan.',
                        'preferences' => ['kreatif', 'praktis', 'sosial'],
                        'skills' => [
                            ['Desain arsitektur', 'Desain', 4, 3],
                            ['Gambar teknik', 'Teknis', 4, 3],
                            ['Komunikasi klien', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-S1-ARSITEKTUR', 'relevant'], ['EDU-D3-SIPIL', 'alternative']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'rancangan desain rumah fungsional fiktif dengan sketsa, denah sederhana, dan daftar kebutuhan ruang',
                        'portfolio' => 'portofolio sketsa, denah, pemilihan material, dan catatan komunikasi desain',
                    ],
                    [
                        'code' => '3112',
                        'name' => 'Teknisi Sipil',
                        'summary' => 'Profesi teknis yang mendukung pengukuran, pemeriksaan material, dan pengawasan pelaksanaan konstruksi.',
                        'description' => 'Teknisi sipil menerapkan gambar serta data lapangan untuk membantu memastikan pekerjaan mengikuti rencana dan standar keselamatan.',
                        'activities' => [
                            'Melakukan pengukuran dan menggambar detail pekerjaan sipil.',
                            'Memeriksa material, mutu pekerjaan, dan progres di lokasi proyek.',
                            'Mendokumentasikan temuan serta mendukung pengawasan keselamatan kerja.',
                        ],
                        'environment' => 'Bekerja di kantor proyek dan lokasi konstruksi dengan kondisi lapangan, peralatan ukur, dan koordinasi pekerja.',
                        'alternatives' => 'Pengawas lapangan, perencana proyek, atau teknisi pengujian material.',
                        'preferences' => ['praktis', 'administratif', 'analitis'],
                        'skills' => [
                            ['Gambar sipil', 'Teknis', 4, 3],
                            ['Pengawasan lapangan', 'Teknis', 4, 3],
                            ['Keselamatan proyek', 'Keselamatan', 3, 2],
                        ],
                        'educations' => [['EDU-D3-SIPIL', 'relevant'], ['EDU-SMK-KONSTRUKSI', 'alternative']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'studi kasus pengawasan pekerjaan fiktif dengan hasil ukur, pemeriksaan material, dan catatan progres',
                        'portfolio' => 'portofolio gambar detail, checklist pemeriksaan, dan laporan pengawasan lapangan',
                    ],
                ],
            ],
            [
                'code' => 'C08',
                'name' => 'Transportasi dan Logistik',
                'description' => 'Eksplorasi profesi yang mengangkut barang serta mengelola arus dokumen dan inventaris pergudangan.',
                'icon' => 'truck',
                'color' => 'cyan',
                'subcategory' => 'Angkutan dan Pergudangan',
                'industry' => 'Transportasi dan Logistik',
                'workplaces' => [
                    ['name' => 'Armada transportasi dan terminal', 'description' => 'Lingkungan operasional kendaraan, pemuatan, perjalanan, dan koordinasi pengiriman.'],
                    ['name' => 'Gudang dan pusat distribusi', 'description' => 'Organisasi penyimpanan dengan aktivitas penerimaan, inventaris, pengemasan, dan pengiriman.'],
                ],
                'occupations' => [
                    [
                        'code' => '8332',
                        'name' => 'Pengemudi Truk Berat dan Truk Kontainer',
                        'summary' => 'Profesi yang mengoperasikan truk besar untuk mengangkut barang dengan memperhatikan keselamatan dan ketepatan pengiriman.',
                        'description' => 'Pengemudi truk berat memadukan keterampilan berkendara, pemeriksaan kendaraan, perencanaan rute, dan kepatuhan keselamatan.',
                        'activities' => [
                            'Memeriksa kondisi kendaraan, muatan, dan perlengkapan sebelum perjalanan.',
                            'Mengoperasikan truk serta memilih rute yang aman dan efisien.',
                            'Mencatat perjalanan, memantau kondisi muatan, dan berkoordinasi dengan penerima.',
                        ],
                        'environment' => 'Bekerja di jalan, terminal, dan area pemuatan dengan perjalanan panjang, posisi duduk lama, dan keselamatan lalu lintas.',
                        'alternatives' => 'Pengemudi alat berat, koordinator armada, atau penyelia pemuatan.',
                        'preferences' => ['praktis', 'lapangan'],
                        'skills' => [
                            ['Pengoperasian truk berat', 'Teknis', 4, 3],
                            ['K3 berkendara', 'Keselamatan', 4, 3],
                            ['Perawatan kendaraan', 'Teknis', 3, 2],
                        ],
                        'educations' => [['EDU-PELATIHAN-TRUK', 'relevant'], ['EDU-SMK-OTOMOTIF', 'alternative']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'simulasi perencanaan rute dan pemeriksaan praperjalanan truk fiktif dengan data muatan',
                        'portfolio' => 'portofolio rencana rute, checklist kendaraan, dan catatan keselamatan perjalanan',
                    ],
                    [
                        'code' => '4321',
                        'name' => 'Juru Tata Usaha Pergudangan',
                        'summary' => 'Profesi yang mencatat barang masuk dan keluar, mengelola dokumen, serta memantau stok di pergudangan.',
                        'description' => 'Juru tata usaha pergudangan menjaga ketertiban data inventaris melalui administrasi, pemeriksaan dokumen, dan koordinasi fisik barang.',
                        'activities' => [
                            'Mencatat transaksi barang masuk dan keluar berdasarkan dokumen pengiriman.',
                            'Memeriksa kesesuaian data stok dengan kondisi barang di gudang.',
                            'Menyusun laporan inventaris dan menindaklanjuti selisih pencatatan.',
                        ],
                        'environment' => 'Bekerja di kantor gudang atau area penyimpanan dengan penggunaan sistem pencatatan dan koordinasi tim logistik.',
                        'alternatives' => 'Staf administrasi logistik, pengendali inventaris, atau koordinator distribusi.',
                        'preferences' => ['administratif', 'analitis'],
                        'skills' => [
                            ['Administrasi gudang', 'Administrasi', 4, 3],
                            ['Pengelolaan inventaris', 'Administrasi', 4, 3],
                            ['Ketelitian dokumen', 'Administrasi', 3, 2],
                        ],
                        'educations' => [['EDU-D3-LOGISTIK', 'relevant'], ['EDU-SMK-LOGISTIK', 'alternative']],
                        'certifications' => [],
                        'practice' => 'studi kasus transaksi gudang fiktif dengan dokumen masuk, keluar, dan selisih stok',
                        'portfolio' => 'portofolio dokumen transaksi, laporan inventaris, dan prosedur tindak lanjut selisih',
                    ],
                ],
            ],
            [
                'code' => 'C09',
                'name' => 'Kreatif dan Media',
                'description' => 'Eksplorasi profesi yang mengolah ide, visual, foto, dan media untuk berkomunikasi dengan audiens.',
                'icon' => 'palette',
                'color' => 'purple',
                'subcategory' => 'Produksi Visual dan Media',
                'industry' => 'Media dan Industri Kreatif',
                'workplaces' => [
                    ['name' => 'Studio desain dan produksi', 'description' => 'Organisasi kreatif dengan kegiatan konsep, produksi visual, revisi, dan kolaborasi klien.'],
                    ['name' => 'Media house dan kantor kreatif', 'description' => 'Lingkungan produksi konten dengan tenggat kampanye, koordinasi tim, dan kebutuhan audiens.'],
                ],
                'occupations' => [
                    [
                        'code' => '2166',
                        'name' => 'Desainer Grafis dan Multimedia',
                        'summary' => 'Profesi yang mengubah ide dan informasi menjadi komposisi visual serta elemen multimedia yang komunikatif.',
                        'description' => 'Desainer grafis memadukan tipografi, warna, gambar, dan medium digital untuk menghasilkan karya yang sesuai tujuan komunikasi.',
                        'activities' => [
                            'Mengembangkan konsep visual berdasarkan tujuan komunikasi dan audiens.',
                            'Menyusun tipografi, warna, gambar, dan tata letak untuk berbagai medium.',
                            'Mengolah aset multimedia serta menerapkan revisi dari umpan balik.',
                        ],
                        'environment' => 'Bekerja di studio, kantor kreatif, atau tim pemasaran dengan perangkat desain, presentasi ide, dan tenggat produksi.',
                        'alternatives' => 'Desainer UI, ilustrator, animator, atau pengembang konten visual.',
                        'preferences' => ['kreatif', 'praktis'],
                        'skills' => [
                            ['Desain visual', 'Desain', 4, 3],
                            ['Pengolahan multimedia', 'Teknis', 4, 3],
                            ['Komunikasi ide', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-D3-DESAIN-GRAFIS', 'relevant'], ['EDU-S1-DKV', 'alternative']],
                        'certifications' => ['CERT-DEMO-KREATIF'],
                        'practice' => 'perancangan satu set visual kampanye fiktif terdiri dari poster, kartu, dan tata letak media sosial',
                        'portfolio' => 'portofolio konsep visual, karya akhir, dan catatan proses revisi',
                    ],
                    [
                        'code' => '3431',
                        'name' => 'Fotografer',
                        'summary' => 'Profesi yang menangkap gambar untuk menyampaikan cerita, informasi, atau kesan sesuai kebutuhan klien dan media.',
                        'description' => 'Fotografer mengatur cahaya, komposisi, momen, dan pengolahan untuk menghasilkan foto yang memiliki tujuan komunikasi.',
                        'activities' => [
                            'Mempersiapkan peralatan dan rencana pengambilan gambar sesuai objek.',
                            'Mengatur komposisi, cahaya, dan momen saat sesi pemotretan.',
                            'Memilih serta mengolah foto dan menyiapkan berkas untuk kebutuhan publikasi.',
                        ],
                        'environment' => 'Bekerja di studio atau lokasi dengan kondisi cahaya dan cuaca yang beragam, sering kali mengikuti jadwal acara.',
                        'alternatives' => 'Videografer, editor foto, atau kreator konten visual.',
                        'preferences' => ['kreatif', 'praktis', 'lapangan'],
                        'skills' => [
                            ['Teknik fotografi', 'Desain', 4, 3],
                            ['Komposisi visual', 'Desain', 4, 3],
                            ['Pengolahan foto', 'Teknis', 3, 2],
                        ],
                        'educations' => [['EDU-PELATIHAN-FOTOGRAFI', 'relevant'], ['EDU-D3-DESAIN-GRAFIS', 'alternative']],
                        'certifications' => ['CERT-DEMO-KREATIF'],
                        'practice' => 'sesi foto cerita fiktif dengan rencana shot, pengaturan cahaya, dan pilihan foto final',
                        'portfolio' => 'portofolio seri foto, catatan shot, dan contoh hasil pengolahan',
                    ],
                ],
            ],
            [
                'code' => 'C10',
                'name' => 'Pariwisata dan Perhotelan',
                'description' => 'Eksplorasi profesi yang mengelola pengalaman kuliner, layanan tamu, dan perjalanan wisata.',
                'icon' => 'utensils',
                'color' => 'rose',
                'subcategory' => 'Kuliner dan Layanan Wisata',
                'industry' => 'Pariwisata dan Perhotelan',
                'workplaces' => [
                    ['name' => 'Dapur restoran dan hotel', 'description' => 'Lingkungan produksi makanan dengan peralatan dapur, standar kebersihan, dan ritme layanan.'],
                    ['name' => 'Biro wisata dan destinasi', 'description' => 'Organisasi perencanaan perjalanan, pelayanan tamu, dan koordinasi kegiatan di destinasi.'],
                ],
                'occupations' => [
                    [
                        'code' => '3434',
                        'name' => 'Chef',
                        'summary' => 'Profesi yang merancang menu, mengolah bahan, dan mengoordinasikan produksi makanan di dapur profesional.',
                        'description' => 'Chef menggabungkan keterampilan memasak, perencanaan menu, pengelolaan tim, dan kontrol mutu dalam layanan makanan.',
                        'activities' => [
                            'Merancang menu dan menyiapkan bahan sesuai kebutuhan layanan.',
                            'Memasak serta mengatur kualitas, penyajian, dan konsistensi hidangan.',
                            'Mengoordinasikan tim dapur, kebersihan, dan pengelolaan persediaan.',
                        ],
                        'environment' => 'Bekerja di dapur restoran atau hotel dengan panas, peralatan tajam, tenggat layanan, dan koordinasi tim yang cepat.',
                        'alternatives' => 'Juru masak, pengembang menu, atau pengelola usaha kuliner.',
                        'preferences' => ['praktis', 'kreatif', 'sosial'],
                        'skills' => [
                            ['Teknik memasak', 'Kuliner', 4, 3],
                            ['K3 dapur', 'Keselamatan', 3, 2],
                            ['Manajemen bahan baku', 'Manajerial', 4, 3],
                        ],
                        'educations' => [['EDU-D3-TATA-BOGA', 'relevant'], ['EDU-PELATIHAN-HIGIENE-PANGAN', 'alternative']],
                        'certifications' => ['CERT-DEMO-PARIWISATA'],
                        'practice' => 'perancangan dan eksekusi menu layanan fiktif dengan kartu menu, daftar bahan, dan alur dapur',
                        'portfolio' => 'portofolio menu, foto hidangan, perhitungan bahan, dan catatan koordinasi dapur',
                    ],
                    [
                        'code' => '5113',
                        'name' => 'Pramuwisata',
                        'summary' => 'Profesi yang mendampingi perjalanan wisata dengan menjelaskan destinasi dan mengelola pengalaman kelompok.',
                        'description' => 'Pramuwisata memadukan pengetahuan destinasi, komunikasi kelompok, perencanaan itinerary, dan kesiapan menghadapi situasi perjalanan.',
                        'activities' => [
                            'Menyusun dan menjelaskan itinerary serta informasi destinasi.',
                            'Mendampingi kelompok dan mengelola dinamika selama perjalanan.',
                            'Mengoordinasikan layanan lokal serta menangapi perubahan rencana atau kebutuhan tamu.',
                        ],
                        'environment' => 'Bekerja di destinasi, kendaraan wisata, atau ruang informasi dengan perjalanan, interaksi tamu, dan kondisi cuaca yang beragam.',
                        'alternatives' => 'Perencana perjalanan, staf layanan tamu, atau pengelola destinasi wisata.',
                        'preferences' => ['sosial', 'lapangan', 'kreatif'],
                        'skills' => [
                            ['Pengetahuan destinasi', 'Pariwisata', 4, 3],
                            ['Komunikasi kelompok', 'Komunikasi', 4, 3],
                            ['Perencanaan perjalanan', 'Pariwisata', 3, 2],
                        ],
                        'educations' => [['EDU-D3-PARIWISATA', 'relevant'], ['EDU-PELATIHAN-PEMANDU', 'alternative']],
                        'certifications' => ['CERT-DEMO-PARIWISATA'],
                        'practice' => 'simulasi pemanduan perjalanan satu hari dengan itinerary fiktif, penjelasan destinasi, dan penanganan perubahan',
                        'portfolio' => 'portofolio itinerary, naskah pemanduan, dan refleksi pengelolaan kelompok',
                    ],
                ],
            ],
            [
                'code' => 'C11',
                'name' => 'Hukum dan Pelayanan Publik',
                'description' => 'Eksplorasi profesi yang menganalisis hukum, menyampaikan argumentasi, dan merumuskan kebijakan publik.',
                'icon' => 'scale',
                'color' => 'slate',
                'subcategory' => 'Layanan Hukum dan Kebijakan',
                'industry' => 'Hukum dan Pemerintahan',
                'workplaces' => [
                    ['name' => 'Firma hukum', 'description' => 'Organisasi jasa hukum dengan kegiatan riset, konsultasi, penyusunan dokumen, dan pendampingan perkara.'],
                    ['name' => 'Instansi pemerintah dan lembaga kebijakan', 'description' => 'Lingkungan penyusunan kebijakan, analisis regulasi, koordinasi program, dan pelayanan publik.'],
                ],
                'occupations' => [
                    [
                        'code' => '2611',
                        'name' => 'Pengacara',
                        'summary' => 'Profesi yang menganalisis persoalan hukum, memberikan nasihat, dan menyampaikan argumentasi untuk mewakili kepentingan klien.',
                        'description' => 'Pengacara menggunakan riset regulasi, analisis fakta, dan komunikasi untuk menyusun strategi serta pendampingan hukum.',
                        'activities' => [
                            'Meneliti regulasi dan menganalisis fakta perkara klien.',
                            'Menyusun dokumen hukum serta menyampaikan argumentasi.',
                            'Memberikan nasihat dan berkoordinasi dalam proses penyelesaian sengketa.',
                        ],
                        'environment' => 'Bekerja di firma hukum, instansi, atau ruang persidangan dengan dokumen, rapat klien, dan tenggat proses.',
                        'alternatives' => 'Analis kebijakan, konsultan kepatuhan, atau peneliti hukum.',
                        'preferences' => ['analitis', 'sosial', 'administratif'],
                        'skills' => [
                            ['Riset hukum', 'Hukum', 4, 3],
                            ['Argumentasi', 'Komunikasi', 4, 3],
                            ['Komunikasi klien', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-S1-HUKUM', 'relevant'], ['EDU-D3-HUKUM', 'alternative']],
                        'certifications' => ['CERT-DEMO-HUKUM'],
                        'practice' => 'studi kasus sengketa fiktif dengan riset regulasi, peta fakta, dan rancangan argumentasi',
                        'portfolio' => 'portofolio memo hukum, ringkasan regulasi, dan catatan argumentasi perkara',
                    ],
                    [
                        'code' => '2422',
                        'name' => 'Profesional Kebijakan Administrasi',
                        'summary' => 'Profesi yang mengkaji masalah publik dan membantu merumuskan kebijakan administrasi yang dapat dilaksanakan.',
                        'description' => 'Profesional kebijakan menghubungkan data, regulasi, dan kepentingan pemangku kepentingan untuk menghasilkan rekomendasi kebijakan.',
                        'activities' => [
                            'Mengumpulkan data dan menganalisis persoalan administrasi publik.',
                            'Menyusun opsi kebijakan beserta pertimbangan dampak dan risiko.',
                            'Berkomunikasi dengan pemangku kepentingan dan mendokumentasikan rekomendasi.',
                        ],
                        'environment' => 'Bekerja di instansi pemerintah, lembaga kajian, atau organisasi publik dengan rapat, penyusunan dokumen, dan koordinasi kebijakan.',
                        'alternatives' => 'Analis program publik, konsultan tata kelola, atau peneliti administrasi.',
                        'preferences' => ['analitis', 'administratif', 'sosial'],
                        'skills' => [
                            ['Analisis kebijakan', 'Kognitif', 4, 3],
                            ['Penulisan kebijakan', 'Administrasi', 4, 3],
                            ['Komunikasi publik', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-S1-ADMINISTRASI-PUBLIK', 'relevant'], ['EDU-S1-PEMERINTAHAN', 'alternative']],
                        'certifications' => ['CERT-DEMO-HUKUM'],
                        'practice' => 'studi kasus kebijakan layanan publik fiktif dengan data masalah, opsi solusi, dan analisis risiko',
                        'portfolio' => 'portofolio nota kebijakan, matriks opsi, dan rekomendasi implementasi',
                    ],
                ],
            ],
            [
                'code' => 'C12',
                'name' => 'Lingkungan dan Energi',
                'description' => 'Eksplorasi profesi yang memantau lingkungan, mengkaji dampak, dan meneliti keanekaragaman hayati.',
                'icon' => 'leaf',
                'color' => 'emerald',
                'subcategory' => 'Perlindungan Lingkungan dan Keanekaragaman Hayati',
                'industry' => 'Lingkungan dan Energi',
                'workplaces' => [
                    ['name' => 'Laboratorium lingkungan', 'description' => 'Organisasi pengujian sampel, pemantauan mutu, analisis data, dan penyusunan laporan teknis.'],
                    ['name' => 'Lokasi survei dan proyek konservasi', 'description' => 'Lingkungan lapangan untuk observasi ekosistem, inventarisasi organisme, dan pemantauan program lingkungan.'],
                ],
                'occupations' => [
                    [
                        'code' => '2133',
                        'name' => 'Ahli Perlindungan Lingkungan',
                        'summary' => 'Profesi yang menilai risiko lingkungan, memantau dampak aktivitas, dan menyusun rekomendasi perlindungan.',
                        'description' => 'Ahli perlindungan lingkungan menggunakan data lapangan, analisis risiko, dan regulasi untuk mendukung pengelolaan dampak.',
                        'activities' => [
                            'Mengidentifikasi risiko dan melakukan pengamatan kondisi lingkungan.',
                            'Menganalisis data pemantauan serta dampak aktivitas terhadap ekosistem.',
                            'Menyusun laporan teknis dan rekomendasi upaya perlindungan.',
                        ],
                        'environment' => 'Bekerja di kantor konsultan, laboratorium, atau lokasi survei dengan kombinasi analisis data dan pekerjaan lapangan.',
                        'alternatives' => 'Konsultan AMDAL, analis keberlanjutan, atau pengelola program konservasi.',
                        'preferences' => ['analitis', 'lapangan'],
                        'skills' => [
                            ['Analisis dampak lingkungan', 'Lingkungan', 4, 3],
                            ['Pemantauan lingkungan', 'Lingkungan', 4, 3],
                            ['Penulisan laporan teknis', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-S1-LINGKUNGAN', 'relevant'], ['EDU-S1-BIOLOGI', 'alternative']],
                        'certifications' => ['CERT-DEMO-LINGKUNGAN'],
                        'practice' => 'studi kasus dampak aktivitas fiktif dengan data pemantauan, identifikasi risiko, dan rekomendasi pengendalian',
                        'portfolio' => 'portofolio laporan pemantauan, peta risiko, dan rekomendasi perlindungan lingkungan',
                    ],
                    [
                        'code' => '2131',
                        'name' => 'Ahli Biologi, Botani, Zoologi, dan YBDI',
                        'summary' => 'Profesi yang mengkaji organisme dan keanekaragaman hayati melalui identifikasi, survei lapangan, dan analisis data.',
                        'description' => 'Ahli biologi mengamati keragaman hayati, menguji hipotesis, dan mengomunikasikan temuan untuk penelitian atau konservasi.',
                        'activities' => [
                            'Melakukan inventarisasi dan identifikasi organisme di lapangan.',
                            'Mengumpulkan data serta menganalisis pola keanekaragaman hayati.',
                            'Menyusun laporan penelitian dan rekomendasi pengelolaan spesies atau ekosistem.',
                        ],
                        'environment' => 'Bekerja di laboratorium, lembaga riset, atau lokasi survei dengan pekerjaan pengamatan dan analisis data biologis.',
                        'alternatives' => 'Peneliti ekologi, pengelola koleksi biologis, atau konsultan konservasi.',
                        'preferences' => ['analitis', 'lapangan', 'praktis'],
                        'skills' => [
                            ['Identifikasi organisme', 'Biologi', 4, 3],
                            ['Metode survei lapangan', 'Biologi', 4, 3],
                            ['Analisis data biologis', 'Kognitif', 3, 2],
                        ],
                        'educations' => [['EDU-S1-BIOLOGI', 'relevant'], ['EDU-D3-LABORATORIUM', 'alternative']],
                        'certifications' => ['CERT-DEMO-LINGKUNGAN'],
                        'practice' => 'survei mini keanekaragaman hayati pada area fiktif dengan metode, lembar observasi, dan analisis awal',
                        'portfolio' => 'portofolio lembar observasi, identifikasi organisme, dan laporan survei',
                    ],
                ],
            ],
            [
                'code' => 'C13',
                'name' => 'Keamanan dan Keselamatan',
                'description' => 'Eksplorasi profesi yang menanggapi kebakaran, menjaga lingkungan, dan menangani insiden keamanan.',
                'icon' => 'shield-check',
                'color' => 'red',
                'subcategory' => 'Tanggap Darurat dan Keamanan',
                'industry' => 'Keamanan dan Tanggap Darurat',
                'workplaces' => [
                    ['name' => 'Pos pemadam kebakaran', 'description' => 'Organisasi tanggap darurat dengan kesiapan personel, peralatan, latihan, dan koordinasi insiden.'],
                    ['name' => 'Fasilitas dan perusahaan keamanan', 'description' => 'Lingkungan pengamanan dengan kegiatan observasi, patroli, komunikasi, dan penanganan insiden.'],
                ],
                'occupations' => [
                    [
                        'code' => '5411',
                        'name' => 'Petugas Pemadam Kebakaran',
                        'summary' => 'Profesi yang mencegah dan menanggapi kebakaran serta membantu evakuasi dan perlindungan masyarakat.',
                        'description' => 'Petugas pemadam kebakaran bekerja dalam tim untuk menilai situasi, mengendalikan api, dan menjalankan prosedur keselamatan.',
                        'activities' => [
                            'Melakukan pemeriksaan kesiapan peralatan dan area rawan kebakaran.',
                            'Menanggapi insiden dengan mengoordinasikan tim dan menjalankan prosedur pemadaman.',
                            'Membantu evakuasi serta memberikan edukasi pencegahan kebakaran.',
                        ],
                        'environment' => 'Bekerja di pos dan lokasi insiden dengan kondisi fisik berat, peralatan pelindung, dan kesiapan siaga.',
                        'alternatives' => 'Instruktur K3, petugas tanggap darurat, atau penyelia keselamatan fasilitas.',
                        'preferences' => ['praktis', 'sosial', 'lapangan'],
                        'skills' => [
                            ['Penanganan kebakaran', 'Keselamatan', 4, 3],
                            ['K3 tanggap darurat', 'Keselamatan', 4, 3],
                            ['Kerja sama tim', 'Manajerial', 3, 2],
                        ],
                        'educations' => [['EDU-PELATIHAN-DAMKAR', 'relevant'], ['EDU-D3-K3', 'alternative']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'simulasi tanggap darurat kebakaran fiktif dengan pembagian peran, evakuasi, dan evaluasi tindakan',
                        'portfolio' => 'portofolio checklist kesiapan, peta evakuasi, dan laporan evaluasi simulasi',
                    ],
                    [
                        'code' => '5414',
                        'name' => 'Penjaga Keamanan',
                        'summary' => 'Profesi yang menjaga keamanan lingkungan melalui observasi, patroli, komunikasi, dan penanganan insiden awal.',
                        'description' => 'Penjaga keamanan menerapkan prosedur pengamanan, mengenali perubahan lingkungan, dan berkoordinasi saat terjadi insiden.',
                        'activities' => [
                            'Melakukan patroli dan memantau kondisi lingkungan fasilitas.',
                            'Menerapkan prosedur akses serta mendokumentasikan kejadian.',
                            'Berkomunikasi dan berkoordinasi saat menangani insiden keamanan.',
                        ],
                        'environment' => 'Bekerja di pos, area publik, atau fasilitas dengan jadwal siaga, pekerjaan berulang, dan interaksi warga atau pengunjung.',
                        'alternatives' => 'Petugas layanan darurat, penyelia keamanan, atau staf pengendalian akses.',
                        'preferences' => ['praktis', 'sosial'],
                        'skills' => [
                            ['Pengamatan lingkungan', 'Keselamatan', 4, 3],
                            ['Penanganan insiden', 'Keselamatan', 4, 3],
                            ['Komunikasi keamanan', 'Komunikasi', 3, 2],
                        ],
                        'educations' => [['EDU-PELATIHAN-KEAMANAN', 'relevant']],
                        'certifications' => ['CERT-DEMO-K3'],
                        'practice' => 'simulasi patroli dan penanganan insiden fiktif dengan catatan observasi serta eskalasi',
                        'portfolio' => 'portofolio rute patroli, formulir kejadian, dan prosedur komunikasi insiden',
                    ],
                ],
            ],
            [
                'code' => 'C14',
                'name' => 'Perdagangan dan Wirausaha',
                'description' => 'Eksplorasi profesi yang mengelola penjualan, pemasaran, dan usaha perdagangan skala kecil.',
                'icon' => 'store',
                'color' => 'teal',
                'subcategory' => 'Pemasaran dan Perdagangan',
                'industry' => 'Perdagangan dan Wirausaha',
                'workplaces' => [
                    ['name' => 'Kantor penjualan dan pemasaran', 'description' => 'Organisasi dengan kegiatan perencanaan penjualan, analisis pasar, dan koordinasi tim.'],
                    ['name' => 'Pasar, kios, dan usaha ritel', 'description' => 'Lingkungan perdagangan langsung dengan interaksi pelanggan, pengelolaan stok, dan transaksi harian.'],
                ],
                'occupations' => [
                    [
                        'code' => '1221',
                        'name' => 'Manajer Penjualan dan Pemasaran',
                        'summary' => 'Profesi yang merencanakan strategi penjualan, memimpin tim, dan mengoordinasikan kegiatan pemasaran.',
                        'description' => 'Manajer penjualan menghubungkan tujuan bisnis dengan peluang pasar, kinerja tim, dan program pemasaran.',
                        'activities' => [
                            'Menyusun strategi penjualan dan rencana pemasaran berdasarkan peluang pasar.',
                            'Memimpin tim serta memantau capaian dan kualitas pelayanan pelanggan.',
                            'Menganalisis data penjualan dan mengoordinasasi program promosi.',
                        ],
                        'environment' => 'Bekerja di kantor, lapangan, atau acara bisnis dengan rapat, analisis kinerja, dan interaksi pelanggan.',
                        'alternatives' => 'Manajer produk, analis pemasaran, atau pengembang bisnis.',
                        'preferences' => ['sosial', 'analitis', 'administratif'],
                        'skills' => [
                            ['Perencanaan pemasaran', 'Bisnis', 4, 3],
                            ['Analisis penjualan', 'Kognitif', 3, 2],
                            ['Kepemimpinan tim', 'Manajerial', 4, 3],
                        ],
                        'educations' => [['EDU-S1-MANAJEMEN', 'relevant'], ['EDU-D3-PEMASARAN', 'alternative']],
                        'certifications' => ['CERT-DEMO-BISNIS'],
                        'practice' => 'studi kasus perencanaan penjualan fiktif dengan segmen pasar, target, dan program pemasaran',
                        'portfolio' => 'portofolio rencana pemasaran, papan indikator penjualan, dan rencana koordinasi tim',
                    ],
                    [
                        'code' => '5211',
                        'name' => 'Pedagang di Kios dan Los Pasar',
                        'summary' => 'Profesi yang menjalankan usaha dagang di kios atau los dengan mengatur stok, melayani pelanggan, dan mengelola transaksi.',
                        'description' => 'Pedagang pasar mengelola usaha kecil secara langsung melalui penawaran, negosiasi, pencatatan penjualan, dan pelayanan pelanggan.',
                        'activities' => [
                            'Menerima dan mengatur barang dagangan serta mengelola stok harian.',
                            'Melayani pelanggan, menjelaskan produk, dan melakukan negosiasi harga.',
                            'Mencatat transaksi serta mengatur pembelian dan pemasaran ulang barang.',
                        ],
                        'environment' => 'Bekerja di kios, los, atau pasar dengan aktivitas berdiri, interaksi pelanggan, dan perubahan permintaan harian.',
                        'alternatives' => 'Pemilik toko daring, distributor kecil, atau pengelola usaha ritel.',
                        'preferences' => ['sosial', 'praktis'],
                        'skills' => [
                            ['Pelayanan pelanggan', 'Komunikasi', 4, 3],
                            ['Negosiasi', 'Komunikasi', 3, 2],
                            ['Pengelolaan stok', 'Bisnis', 4, 3],
                        ],
                        'educations' => [['EDU-PELATIHAN-RITEL', 'relevant'], ['EDU-D3-PERDAGANGAN', 'alternative']],
                        'certifications' => ['CERT-DEMO-BISNIS'],
                        'practice' => 'simulasi satu hari berdagang fiktif dengan catatan stok, transaksi, dan keputusan pemasaran ulang',
                        'portfolio' => 'portofolio catatan stok, rekap penjualan, dan rencana pengembangan kios',
                    ],
                ],
            ],
        ];
    }
}
