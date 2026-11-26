import { useState } from 'react';
import { CheckCircle2, LoaderCircle, Save, ShieldCheck } from 'lucide-react';
import { Alert, Button, Field, Input, PageHeader, Select, Textarea } from '../components/ui';
import { useAuth } from '../features/auth/AuthContext';
import { useCatalog } from '../features/catalog/CatalogContext';
import { errorMessage, fieldErrors } from '../lib/api';
import type { UserCertification, UserExperience, UserSkill } from '../types';
import { CertificationsEditor, ExperiencesEditor, PreferencesFields, SkillsEditor } from '../features/profile/ProfileSections';

export function ProfilePage() {
    const { user, update } = useAuth();
    const { meta } = useCatalog();
    const [profile, setProfile] = useState({ display_name: user?.profile?.display_name ?? user?.name ?? '', age_range: user?.profile?.age_range ?? '', region: user?.profile?.region ?? '', education_level: user?.profile?.education_level ?? '', field_of_study: user?.profile?.field_of_study ?? '', institution: user?.profile?.institution ?? '', bio: user?.profile?.bio ?? '' });
    const [interests, setInterests] = useState<number[]>(user?.profile?.interests ?? []);
    const [activities, setActivities] = useState<string[]>(user?.profile?.work_preferences ?? []);
    const [skills, setSkills] = useState<UserSkill[]>(user?.skills ?? []);
    const [experiences, setExperiences] = useState<UserExperience[]>(user?.experiences ?? []);
    const [certifications, setCertifications] = useState<UserCertification[]>(user?.certifications ?? []);
    const [reviewed, setReviewed] = useState({ experiences: !!user?.profile?.experiences_reviewed, certifications: !!user?.profile?.certifications_reviewed });
    const [busy, setBusy] = useState(false);
    const [notice, setNotice] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const errors = fieldErrors(error);

    function set(key: keyof typeof profile, value: string) { setProfile((current) => ({ ...current, [key]: value })); }
    async function submit(event: React.FormEvent) {
        event.preventDefault(); setBusy(true); setError(null); setNotice(null);
        try { await update({ ...profile, interests, work_preferences: activities, skills, experiences, certifications, experiences_reviewed: reviewed.experiences, certifications_reviewed: reviewed.certifications }); setNotice('Profil berhasil disimpan. Analisis berikutnya akan memakai data terbaru.'); window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (cause) { setError(errorMessage(cause)); } finally { setBusy(false); }
    }

    return <form onSubmit={submit} className="mx-auto max-w-5xl"><PageHeader eyebrow="Data yang kamu kendalikan" title="Profil Karier" description="Perbarui informasi yang relevan. Semua penilaian keterampilan bersifat mandiri dan dapat diubah kapan saja." action={<Button type="submit" disabled={busy}>{busy ? <LoaderCircle className="animate-spin" size={16} /> : <Save size={16} />}Simpan Profil</Button>} />
        {notice && <div className="mb-5"><Alert tone="success">{notice}</Alert></div>}{error && <div className="mb-5"><Alert>{error}</Alert></div>}
        <div className="space-y-6"><section className="surface p-6"><div className="flex items-center gap-2"><ShieldCheck size={19} className="text-teal" /><h2 className="text-lg font-extrabold text-navy">Data dasar</h2></div><p className="mt-1 text-xs text-slate-500">Tidak perlu NIK, alamat rumah, atau informasi sensitif lainnya.</p><div className="mt-5 grid gap-4 sm:grid-cols-2"><Field label="Nama tampilan" error={errors.display_name?.[0]}><Input value={profile.display_name} onChange={(event) => set('display_name', event.target.value)} required minLength={2} /></Field><Field label="Rentang usia"><Select value={profile.age_range} onChange={(event) => set('age_range', event.target.value)}><option value="">Pilih rentang usia</option>{meta?.age_ranges.map((age) => <option key={age}>{age}</option>)}</Select></Field><Field label="Wilayah"><Input value={profile.region} onChange={(event) => set('region', event.target.value)} placeholder="Contoh: Jawa Barat" /></Field><Field label="Pendidikan terakhir"><Select value={profile.education_level} onChange={(event) => set('education_level', event.target.value)}><option value="">Belum diisi</option>{meta?.education_levels.map((level) => <option key={level}>{level}</option>)}</Select></Field><Field label="Jurusan atau bidang studi"><Input value={profile.field_of_study} onChange={(event) => set('field_of_study', event.target.value)} placeholder="Contoh: Teknik Informatika" /></Field><Field label="Institusi"><Input value={profile.institution} onChange={(event) => set('institution', event.target.value)} placeholder="Nama sekolah atau perguruan tinggi" /></Field><Field label="Tentangmu" className="sm:col-span-2"><Textarea value={profile.bio} onChange={(event) => set('bio', event.target.value)} placeholder="Ceritakan singkat minat atau tujuanmu…" maxLength={2000} /></Field></div></section>
            <section className="surface p-6"><h2 className="text-lg font-extrabold text-navy">Minat & preferensi aktivitas</h2><p className="mt-1 text-xs text-slate-500">Digunakan sebagai sinyal minat dalam analisis.</p><div className="mt-5"><PreferencesFields interests={interests} activities={activities} onChange={(nextInterests, nextActivities) => { setInterests(nextInterests); setActivities(nextActivities); }} /></div></section>
            <section className="surface p-6"><h2 className="text-lg font-extrabold text-navy">Keterampilan</h2><p className="mt-1 text-xs leading-5 text-slate-500">Jujur saat menilai level. “Belum diketahui” tidak akan dianggap nol atau otomatis dianggap belum dimiliki.</p><div className="mt-5"><SkillsEditor value={skills} onChange={setSkills} /></div>{errors.skills && <p className="mt-3 text-xs text-danger">{errors.skills[0]}</p>}</section>
            <section className="surface p-6"><h2 className="text-lg font-extrabold text-navy">Pengalaman</h2><p className="mt-1 text-xs text-slate-500">Pekerjaan, magang, proyek, organisasi, dan sukarela semuanya dapat dicantumkan.</p><div className="mt-5"><ExperiencesEditor value={experiences} onChange={setExperiences} /></div><label className="mt-4 flex items-start gap-2 text-xs leading-5 text-slate-600"><input type="checkbox" checked={reviewed.experiences} onChange={(event) => setReviewed((current) => ({ ...current, experiences: event.target.checked }))} className="mt-0.5 h-4 w-4 accent-teal" />Saya sudah memeriksa daftar pengalaman; tidak ada pengalaman relevan yang belum dicantumkan.</label></section>
            <section className="surface p-6"><h2 className="text-lg font-extrabold text-navy">Sertifikasi</h2><p className="mt-1 text-xs text-slate-500">Sertifikasi bersifat mandiri. Sistem tidak memverifikasi keaslian dokumen.</p><div className="mt-5"><CertificationsEditor value={certifications} onChange={setCertifications} /></div><label className="mt-4 flex items-start gap-2 text-xs leading-5 text-slate-600"><input type="checkbox" checked={reviewed.certifications} onChange={(event) => setReviewed((current) => ({ ...current, certifications: event.target.checked }))} className="mt-0.5 h-4 w-4 accent-teal" />Saya sudah memeriksa daftar sertifikasi; tidak ada sertifikasi relevan yang belum dicantumkan.</label></section>
            <div className="flex items-center justify-between rounded-2xl bg-white p-5 shadow-sm"><p className="flex items-center gap-2 text-xs text-slate-500"><CheckCircle2 size={15} className="text-teal" />Perubahan hanya berlaku untuk analisis baru.</p><Button type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan Semua Perubahan'}</Button></div>
        </div>
    </form>;
}
