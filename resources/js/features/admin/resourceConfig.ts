export interface AdminField {
    key: string;
    label: string;
    type: 'text' | 'number' | 'textarea' | 'select' | 'boolean' | 'array' | 'date';
    required?: boolean;
    options?: Array<{ value: string; label: string }>;
    optionsResource?: string;
    wide?: boolean;
    placeholder?: string;
}

export interface ResourceConfig {
    label: string;
    singular: string;
    description: string;
    fields: AdminField[];
}

const text = (key: string, label: string, required = false, wide = false): AdminField => ({ key, label, type: 'text', required, wide });
const area = (key: string, label: string, required = false, wide = false): AdminField => ({ key, label, type: 'textarea', required, wide });
const parent = (key: string, label: string, optionsResource: string): AdminField => ({ key, label, type: 'select', optionsResource, required: true });

export const resources: Record<string, ResourceConfig> = {
    categories: {
        label: 'Kategori', singular: 'Kategori', description: 'Bidang tingkat atas yang mengelompokkan profesi.',
        fields: [text('code', 'Kode', true), text('name', 'Nama', true), area('description', 'Deskripsi', false, true), text('icon', 'Nama ikon'), text('color', 'Warna'), { key: 'sort_order', label: 'Urutan', type: 'number' }],
    },
    subcategories: {
        label: 'Subkategori', singular: 'Subkategori', description: 'Pengelompokan di bawah kategori.',
        fields: [parent('category_id', 'Kategori', 'categories'), text('code', 'Kode', true), text('name', 'Nama', true), area('description', 'Deskripsi', false, true), { key: 'sort_order', label: 'Urutan', type: 'number' }],
    },
    groups: {
        label: 'Kelompok Profesi', singular: 'Kelompok Profesi', description: 'Kelompok pekerjaan di bawah subkategori.',
        fields: [parent('subcategory_id', 'Subkategori', 'subcategories'), text('code', 'Kode', true), text('name', 'Nama', true), area('description', 'Deskripsi', false, true), { key: 'sort_order', label: 'Urutan', type: 'number' }],
    },
    occupations: {
        label: 'Profesi', singular: 'Profesi', description: 'Katalog profesi beserta konten, sumber, dan status publikasi.',
        fields: [parent('group_id', 'Kelompok profesi', 'groups'), parent('source_id', 'Sumber data', 'sources'), text('code', 'Kode KBJI', true), text('name', 'Nama profesi', true), area('summary', 'Ringkasan', true, true), area('description', 'Deskripsi lengkap', true, true), { key: 'main_activities', label: 'Aktivitas utama (satu per baris)', type: 'array', wide: true }, area('responsibilities', 'Tanggung jawab', false, true), area('work_environment', 'Lingkungan kerja', false, true), area('alternative_careers', 'Jalur terkait', false, true), { key: 'work_preferences', label: 'Preferensi aktivitas (satu per baris)', type: 'array', wide: true, placeholder: 'analitis\npraktis\nsosial' }, { key: 'verified_at', label: 'Tanggal verifikasi', type: 'date' }, { key: 'is_demo', label: 'Tandai sebagai data demonstrasi', type: 'boolean' }],
    },
    skills: {
        label: 'Keterampilan', singular: 'Keterampilan', description: 'Katalog skill yang dapat dihubungkan ke profesi.',
        fields: [text('code', 'Kode', true), text('name', 'Nama', true), text('category', 'Kategori'), area('description', 'Deskripsi', false, true), text('level_framework', 'Kerangka level')],
    },
    educations: {
        label: 'Pendidikan', singular: 'Jalur Pendidikan', description: 'Program atau jalur pendidikan yang relevan dengan profesi.',
        fields: [text('code', 'Kode', true), text('name', 'Nama', true), text('education_level', 'Level pendidikan', true), text('program_type', 'Jenis program'), parent('source_id', 'Sumber data', 'sources'), area('description', 'Deskripsi', false, true)],
    },
    certifications: {
        label: 'Sertifikasi', singular: 'Sertifikasi', description: 'Sertifikasi atau lisensi yang telah dikurasi.',
        fields: [text('code', 'Kode', true), text('name', 'Nama', true), text('issuer', 'Penerbit'), { key: 'credential_type', label: 'Jenis', type: 'select', required: true, options: [{ value: 'certification', label: 'Sertifikasi' }, { value: 'license', label: 'Lisensi' }] }, parent('source_id', 'Sumber data', 'sources'), area('validity_notes', 'Catatan masa berlaku', false, true)],
    },
    industries: {
        label: 'Industri', singular: 'Industri', description: 'Acuan industri untuk mengelompokkan workplace.',
        fields: [text('code', 'Kode', true), text('name', 'Nama', true), area('description', 'Deskripsi', false, true)],
    },
    workplaces: {
        label: 'Workplace', singular: 'Workplace', description: 'Jenis lingkungan atau organisasi kerja, bukan lowongan aktif.',
        fields: [parent('industry_id', 'Industri', 'industries'), text('code', 'Kode', true), text('name', 'Nama', true), area('description', 'Deskripsi', false, true)],
    },
    sources: {
        label: 'Sumber Data', singular: 'Sumber Data', description: 'Provenance, tanggal verifikasi, dan catatan lisensi untuk setiap data.',
        fields: [text('code', 'Kode', true), text('name', 'Nama sumber', true), text('source_type', 'Jenis sumber'), text('url', 'URL'), text('publisher', 'Penerbit'), { key: 'verified_at', label: 'Tanggal verifikasi', type: 'date' }, area('notes', 'Catatan lisensi/editorial', false, true)],
    },
    roadmaps: {
        label: 'Roadmap', singular: 'Roadmap', description: 'Rencana pengembangan bertahap untuk profesi tertentu.',
        fields: [parent('occupation_id', 'Profesi', 'occupations'), text('code', 'Kode', true), text('name', 'Nama', true), area('description', 'Deskripsi', false, true)],
    },
};
