import { useState } from 'react';
import { ArrowLeft, ArrowRight, Check, GraduationCap, LoaderCircle, Sparkles, Target } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { Alert, Button, Field, Input, Logo, Select } from '../components/ui';
import { useAuth } from '../features/auth/AuthContext';
import { PreferencesFields, SkillsEditor } from '../features/profile/ProfileSections';
import { useCatalog } from '../features/catalog/CatalogContext';
import { errorMessage } from '../lib/api';
import type { UserSkill } from '../types';

export function OnboardingPage() {
    const { user, update } = useAuth();
    const { meta } = useCatalog();
    const navigate = useNavigate();
    const [step, setStep] = useState(1);
    const [displayName, setDisplayName] = useState(user?.profile?.display_name ?? user?.name ?? '');
    const [education, setEducation] = useState(user?.profile?.education_level ?? '');
    const [field, setField] = useState(user?.profile?.field_of_study ?? '');
    const [interests, setInterests] = useState<number[]>(user?.profile?.interests ?? []);
    const [activities, setActivities] = useState<string[]>(user?.profile?.work_preferences ?? []);
    const [skills, setSkills] = useState<UserSkill[]>(user?.skills ?? []);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function finish() {
        setBusy(true); setError(null);
        try { await update({ display_name: displayName, education_level: education || null, field_of_study: field || null, interests, work_preferences: activities, skills, experiences_reviewed: true, certifications_reviewed: true, onboarding_completed: true }); navigate('/dashboard'); } catch (cause) { setError(errorMessage(cause)); } finally { setBusy(false); }
    }

    return <div className="min-h-screen bg-canvas"><header className="border-b border-slate-200 bg-white px-5 py-4"><div className="mx-auto flex max-w-4xl items-center justify-between"><Logo /><span className="text-xs text-slate-400">Langkah {step} dari 3</span></div></header><main className="mx-auto max-w-4xl px-5 py-10"><div className="mb-8 flex gap-2">{[1, 2, 3].map((item) => <div key={item} className={`h-1.5 flex-1 rounded-full ${step >= item ? 'bg-teal' : 'bg-slate-200'}`} />)}</div>
        {step === 1 && <section className="surface p-6 sm:p-8"><span className="grid h-12 w-12 place-items-center rounded-2xl bg-teal/10 text-teal"><GraduationCap size={23} /></span><h1 className="mt-5 text-2xl font-black tracking-tight text-navy sm:text-3xl">Kenalan dulu, yuk.</h1><p className="mt-2 text-sm leading-6 text-slate-500">Data dasar membantu sistem memahami konteksmu. Isi yang belum pasti boleh dikosongkan.</p><div className="mt-7 grid gap-4 sm:grid-cols-2"><Field label="Nama panggilan"><Input value={displayName} onChange={(event) => setDisplayName(event.target.value)} required minLength={2} /></Field><Field label="Pendidikan terakhir"><Select value={education} onChange={(event) => setEducation(event.target.value)}><option value="">Belum diisi</option>{meta?.education_levels.map((level) => <option key={level}>{level}</option>)}</Select></Field><Field label="Jurusan atau bidang studi" className="sm:col-span-2"><Input value={field} onChange={(event) => setField(event.target.value)} placeholder="Contoh: IPA, Teknik, Ekonomi, atau belum memilih" /></Field></div><div className="mt-8 flex justify-end"><Button onClick={() => setStep(2)}>Lanjut<ArrowRight size={16} /></Button></div></section>}
        {step === 2 && <section className="surface p-6 sm:p-8"><span className="grid h-12 w-12 place-items-center rounded-2xl bg-sky-brand/10 text-sky-brand"><Target size={23} /></span><h1 className="mt-5 text-2xl font-black tracking-tight text-navy sm:text-3xl">Apa yang membuatmu penasaran?</h1><p className="mt-2 text-sm leading-6 text-slate-500">Tidak harus punya jawaban pasti. Pilihan ini akan menjadi sinyal minat, bukan pembatas mutlak.</p><div className="mt-7"><PreferencesFields interests={interests} activities={activities} onChange={(nextInterests, nextActivities) => { setInterests(nextInterests); setActivities(nextActivities); }} /></div><div className="mt-8 flex justify-between"><Button variant="ghost" onClick={() => setStep(1)}><ArrowLeft size={16} />Kembali</Button><Button onClick={() => setStep(3)}>Lanjut<ArrowRight size={16} /></Button></div></section>}
        {step === 3 && <section className="surface p-6 sm:p-8"><span className="grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-600"><Sparkles size={23} /></span><h1 className="mt-5 text-2xl font-black tracking-tight text-navy sm:text-3xl">Ceritakan yang sudah kamu ketahui.</h1><p className="mt-2 text-sm leading-6 text-slate-500">Tambahkan beberapa keterampilan yang relevan. Kamu boleh melewati bagian ini dan melengkapinya nanti.</p><div className="mt-7"><SkillsEditor value={skills} onChange={setSkills} /></div>{error && <div className="mt-5"><Alert>{error}</Alert></div>}<div className="mt-8 flex flex-col-reverse justify-between gap-3 sm:flex-row"><Button variant="ghost" onClick={() => setStep(2)}><ArrowLeft size={16} />Kembali</Button><Button onClick={() => void finish()} disabled={busy}>{busy ? <LoaderCircle className="animate-spin" size={16} /> : <Check size={16} />}{busy ? 'Menyimpan profil…' : 'Selesai & Lihat Dasbor'}</Button></div><p className="mt-5 text-center text-[11px] leading-5 text-slate-400">Dengan melanjutkan, pengalaman dan sertifikasi ditandai sudah diperiksa. Keduanya masih dapat diubah di halaman Profil.</p></section>}
    </main></div>;
}
