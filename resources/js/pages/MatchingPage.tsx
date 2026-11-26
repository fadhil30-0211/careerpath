import { useEffect, useState } from 'react';
import { ArrowRight, BriefcaseBusiness, CheckCircle2, CircleAlert, GraduationCap, History, LoaderCircle, Route, Sparkles, Target } from 'lucide-react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { Alert, Badge, Button, PageHeader, Spinner } from '../components/ui';
import { useAuth } from '../features/auth/AuthContext';
import { AnalysisResultCard } from '../features/matching/AnalysisResults';
import { errorMessage, request } from '../lib/api';
import type { MatchingRun, OccupationDetail } from '../types';

export function MatchingPage() {
    const { user } = useAuth();
    const [params] = useSearchParams();
    const occupationId = params.get('occupation');
    const [type, setType] = useState<'matching' | 'goal'>(params.get('type') === 'goal' ? 'goal' : 'matching');
    const [profession, setProfession] = useState<OccupationDetail | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const navigate = useNavigate();

    useEffect(() => { if (occupationId) request<OccupationDetail>('get', `/public/occupations/${occupationId}`).then(setProfession).catch((cause) => setError(errorMessage(cause))); }, [occupationId]);

    async function run() {
        setBusy(true); setError(null);
        try { const result = await request<MatchingRun>('post', '/career-matching/runs', { type, occupation_id: type === 'goal' ? Number(occupationId) : null }); navigate(`/matching/${result.id}`); } catch (cause) { setError(errorMessage(cause)); } finally { setBusy(false); }
    }

    const filled = [user?.profile?.education_level, user?.profile?.interests?.length, user?.skills?.length, user?.profile?.experiences_reviewed, user?.profile?.certifications_reviewed].filter(Boolean).length;
    return <div className="mx-auto max-w-5xl"><PageHeader eyebrow="Kamu Bisa Jadi Apa?" title="Career Matching" description="Bandingkan profilmu dengan profesi Published. Kamu tetap mendapat penjelasan meskipun sebagian informasi belum lengkap." />
        {error && <div className="mb-5"><Alert>{error}</Alert></div>}
        <div className="grid gap-6 lg:grid-cols-[1fr_340px]"><section className="surface p-6"><h2 className="text-lg font-extrabold text-navy">Pilih jenis analisis</h2><div className="mt-5 grid gap-3 sm:grid-cols-2"><button onClick={() => setType('matching')} className={`rounded-2xl border p-5 text-left transition ${type === 'matching' ? 'border-teal bg-teal/5 ring-1 ring-teal/20' : 'border-slate-200 hover:border-teal/30'}`}><span className={`grid h-10 w-10 place-items-center rounded-xl ${type === 'matching' ? 'bg-teal text-white' : 'bg-slate-100 text-slate-400'}`}><Sparkles size={19} /></span><h3 className="mt-4 text-sm font-extrabold text-navy">Temukan berbagai kecocokan</h3><p className="mt-2 text-xs leading-5 text-slate-500">Bandingkan profil dengan seluruh katalog Published dan lihat 10 hasil teratas.</p></button><button onClick={() => setType('goal')} disabled={!occupationId} className={`rounded-2xl border p-5 text-left transition disabled:cursor-not-allowed disabled:opacity-50 ${type === 'goal' ? 'border-teal bg-teal/5 ring-1 ring-teal/20' : 'border-slate-200 hover:border-teal/30'}`}><span className={`grid h-10 w-10 place-items-center rounded-xl ${type === 'goal' ? 'bg-teal text-white' : 'bg-slate-100 text-slate-400'}`}><Target size={19} /></span><h3 className="mt-4 text-sm font-extrabold text-navy">Analisis satu target</h3><p className="mt-2 text-xs leading-5 text-slate-500">{profession ? `Fokus pada ${profession.name}.` : 'Pilih profesi dari direktori atau halaman eksplorasi.'}</p></button></div>
            {profession && <div className="mt-5 flex items-center gap-3 rounded-xl bg-canvas p-4"><BriefcaseBusiness size={20} className="text-teal" /><div><p className="text-sm font-bold text-navy">{profession.name}</p><p className="mt-0.5 text-xs text-slate-500">{profession.category} · KBJI {profession.code}</p></div></div>}
            <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4"><p className="flex items-center gap-2 text-xs font-bold text-amber-800"><CircleAlert size={14} />Sebelum menjalankan</p><p className="mt-2 text-[11px] leading-5 text-amber-800">Periksa profil dan tingkat skillmu. Input yang belum dikonfirmasi akan ditandai belum diketahui dan mengurangi cakupan analisis, bukan dinilai nol.</p></div>
            <Button onClick={() => void run()} size="lg" className="mt-6 w-full" disabled={busy || (type === 'goal' && !occupationId)}>{busy ? <LoaderCircle className="animate-spin" size={17} /> : <Sparkles size={17} />}{busy ? 'Menganalisis profil…' : 'Jalankan Analisis'}</Button>
        </section><aside className="space-y-5"><section className="surface p-5"><div className="flex items-center justify-between"><h3 className="text-sm font-extrabold text-navy">Kelengkapan profil</h3><Badge tone={filled >= 4 ? 'green' : 'amber'}>{filled}/5 area</Badge></div><div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-teal" style={{ width: `${filled / 5 * 100}%` }} /></div><div className="mt-4 space-y-3">{[{ label: 'Pendidikan & jurusan', value: !!user?.profile?.education_level, icon: GraduationCap }, { label: 'Minat & preferensi', value: !!user?.profile?.interests?.length, icon: Target }, { label: 'Tingkat keterampilan', value: !!user?.skills?.length, icon: CheckCircle2 }, { label: 'Pengalaman dikonfirmasi', value: !!user?.profile?.experiences_reviewed, icon: BriefcaseBusiness }, { label: 'Sertifikasi dikonfirmasi', value: !!user?.profile?.certifications_reviewed, icon: History }].map((item) => <div key={item.label} className="flex items-center gap-2.5 text-xs text-slate-600"><item.icon size={15} className={item.value ? 'text-teal' : 'text-slate-300'} /><span className="flex-1">{item.label}</span>{item.value && <CheckCircle2 size={13} className="text-teal" />}</div>)}</div><Link to="/profile" className="mt-5 inline-flex items-center gap-1 text-xs font-bold text-teal">Perbarui profil<ArrowRight size={13} /></Link></section><section className="rounded-2xl bg-navy p-5 text-white"><h3 className="text-sm font-extrabold">Setelah analisis</h3><ul className="mt-4 space-y-3 text-xs leading-5 text-slate-300"><li className="flex gap-2"><span className="text-teal-200">01</span>Baca alasan dan komponen skor.</li><li className="flex gap-2"><span className="text-teal-200">02</span>Periksa persyaratan wajib secara terpisah.</li><li className="flex gap-2"><span className="text-teal-200">03</span>Pilih skill yang ingin dikembangkan.</li><li className="flex gap-2"><span className="text-teal-200">04</span>Simpan profesi atau mulai roadmap.</li></ul><div className="mt-5 flex items-center gap-2 border-t border-white/10 pt-4 text-[10px] text-slate-400"><Route size={14} />Hasil disimpan sebagai snapshot riwayat.</div></section></aside></div>
    </div>;
}

export function MatchingResultPage() {
    const { id } = useParams();
    const [run, setRun] = useState<MatchingRun | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    useEffect(() => { request<MatchingRun>('get', `/career-matching/runs/${id}`).then(setRun).catch((cause) => setError(errorMessage(cause))).finally(() => setLoading(false)); }, [id]);
    if (loading) return <Spinner label="Memuat hasil analisis" />;
    if (error || !run) return <Alert>{error ?? 'Hasil tidak ditemukan.'}</Alert>;
    return <div className="mx-auto max-w-5xl"><PageHeader eyebrow="Hasil analisis" title={run.type === 'goal' ? 'Kesiapan menuju profesi target' : 'Rekomendasi karier untukmu'} description={`${run.results.length} hasil dari ${run.candidate_count} profesi Published · Algoritma ${run.algorithm_version}`} action={<Button to="/matching" variant="secondary">Analisis Baru</Button>} /><Alert tone="info">Hasil ini disimpan sebagai snapshot. Perubahan profil atau katalog tidak mengubah riwayat analisis ini.</Alert><div className="mt-6 space-y-4">{run.results.map((result, index) => <AnalysisResultCard key={result.id} analysis={result} defaultOpen={index === 0} />)}</div>{!run.results.length && <div className="surface mt-6 p-10 text-center text-sm text-slate-500">Belum ada profesi Published yang dapat dianalisis.</div>}</div>;
}
