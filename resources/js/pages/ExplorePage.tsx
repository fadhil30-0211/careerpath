import { useEffect, useState } from 'react';
import { ArrowRight, BriefcaseBusiness, CheckCircle2, CircleHelp, Compass, LoaderCircle, Sparkles } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { Button, Field, Select, Spinner, TextLink } from '../components/ui';
import { useAuth } from '../features/auth/AuthContext';
import { useCatalog } from '../features/catalog/CatalogContext';
import { errorMessage, request } from '../lib/api';

export function ExplorePage() {
    const { user, update } = useAuth();
    const { categories, meta, loading } = useCatalog();
    const navigate = useNavigate();
    const [selectedCategories, setSelectedCategories] = useState<number[]>(user?.profile?.interests ?? []);
    const [activities, setActivities] = useState<string[]>(user?.profile?.work_preferences ?? []);
    const [categoryFilter, setCategoryFilter] = useState('');
    const [items, setItems] = useState<Array<{ id: number; code: string; name: string; summary: string; category: string; work_preferences: string[]; is_demo: boolean; skill_preview: string[] }>>([]);
    const [searching, setSearching] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!categories.length) return;
        const params = new URLSearchParams({ per_page: '48' });
        if (categoryFilter) params.set('category_id', categoryFilter);
        setSearching(true);
        request<{ items: typeof items }>('get', `/public/occupations?${params}`).then((result) => {
            const sorted = [...result.items].sort((a, b) => {
                const score = (item: typeof a) => item.work_preferences.filter((value) => activities.includes(value)).length * 10 + (selectedCategories.includes(categories.find((category) => category.name === item.category)?.id ?? -1) ? 5 : 0);
                return score(b) - score(a) || a.name.localeCompare(b.name, 'id');
            });
            setItems(sorted);
        }).catch((cause) => setError(errorMessage(cause))).finally(() => setSearching(false));
    }, [categories, selectedCategories, activities, categoryFilter]);

    function toggleCategory(id: number) { setSelectedCategories((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]); }
    function toggleActivity(value: string) { setActivities((current) => current.includes(value) ? current.filter((item) => item !== value) : [...current, value]); }

    async function savePreferences() {
        if (!user) { navigate('/login', { state: { from: '/explore' } }); return; }
        setSaving(true); setError(null);
        try { await update({ interests: selectedCategories, work_preferences: activities }); } catch (cause) { setError(errorMessage(cause)); } finally { setSaving(false); }
    }

    return <div className="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-14"><div className="max-w-3xl"><span className="inline-flex items-center gap-2 rounded-full bg-teal/10 px-3 py-1.5 text-xs font-bold text-teal"><Compass size={14} />Kamu Mau Jadi Apa?</span><h1 className="mt-5 text-3xl font-black tracking-tight text-navy sm:text-4xl">Mulai dari kegiatan yang membuatmu betah.</h1><p className="mt-3 text-sm leading-7 text-slate-500">Pilih satu atau beberapa bidang dan aktivitas. Preferensi mengurutkan eksplorasi, bukan membuang profesi lain secara otomatis.</p></div>
        {error && <p className="mt-5 rounded-xl bg-danger/5 p-4 text-sm text-danger">{error}</p>}
        <section className="surface mt-8 p-5 sm:p-6"><div className="flex items-center justify-between"><div><h2 className="font-extrabold text-navy">1. Pilih bidang yang menarik</h2><p className="mt-1 text-xs text-slate-500">Kamu boleh memilih lebih dari satu atau melewati tahap ini.</p></div><span className="text-xs font-bold text-teal">{selectedCategories.length} dipilih</span></div><div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{categories.map((category) => { const selected = selectedCategories.includes(category.id); return <button key={category.id} onClick={() => toggleCategory(category.id)} className={`relative rounded-xl border p-4 text-left transition duration-200 active:scale-[.99] ${selected ? 'border-teal bg-teal/5 ring-1 ring-teal/20' : 'border-slate-200 hover:border-teal/40'}`}><div className="flex items-start justify-between"><BriefcaseBusiness size={18} className={selected ? 'text-teal' : 'text-slate-400'} /><span className={`grid h-5 w-5 place-items-center rounded-full border ${selected ? 'border-teal bg-teal text-white' : 'border-slate-300'}`}>{selected && <CheckCircle2 size={13} />}</span></div><p className="mt-3 text-sm font-bold text-navy">{category.name}</p><p className="mt-1 text-[10px] text-slate-400">{category.profession_count} profesi</p></button>; })}</div></section>
        <section className="surface mt-5 p-5 sm:p-6"><h2 className="font-extrabold text-navy">2. Aktivitas seperti apa yang kamu nikmati?</h2><p className="mt-1 text-xs text-slate-500">Tidak harus yang sudah paling dikuasai.</p><div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{meta?.activities.map((activity) => { const selected = activities.includes(activity.value); return <button key={activity.value} onClick={() => toggleActivity(activity.value)} className={`rounded-xl border p-4 text-left transition duration-200 active:scale-[.99] ${selected ? 'border-sky-brand bg-sky-brand/5' : 'border-slate-200 hover:border-sky-brand/40'}`}><div className="flex items-center justify-between"><Sparkles size={17} className={selected ? 'text-sky-brand' : 'text-slate-400'} />{selected && <CheckCircle2 size={16} className="text-sky-brand" />}</div><p className="mt-3 text-sm font-bold text-navy">{activity.label}</p><p className="mt-1 text-[11px] leading-5 text-slate-500">{activity.description}</p></button>; })}</div></section>
        <div className="mt-5 flex flex-col items-start justify-between gap-4 rounded-2xl bg-navy p-5 text-white sm:flex-row sm:items-center"><div><p className="text-sm font-bold">Simpan preferensi untuk analisis berikutnya</p><p className="mt-1 text-xs text-slate-400">Preferensi dapat diubah kapan saja dari profil.</p></div><Button onClick={() => void savePreferences()} disabled={saving} variant="secondary">{saving ? <LoaderCircle className="animate-spin" size={16} /> : <CheckCircle2 size={16} />}{user ? 'Simpan Preferensi' : 'Masuk untuk Menyimpan'}</Button></div>
        <section className="mt-10"><div className="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><h2 className="text-2xl font-black tracking-tight text-navy">3. Pilih profesi untuk dikenali lebih jauh</h2><p className="mt-2 text-sm text-slate-500">Urutan awal mempertimbangkan preferensi aktivitas dan bidang.</p></div><Field label="Batasi bidang" className="w-full sm:w-56"><Select value={categoryFilter} onChange={(event) => setCategoryFilter(event.target.value)}><option value="">Semua bidang</option>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</Select></Field></div>
            {loading || searching ? <Spinner label="Menyusun pilihan untukmu" /> : <div className="grid gap-4 md:grid-cols-2">{items.map((item, index) => <article key={`${categoryFilter}-${item.id}`} className="surface hover-lift stagger-in p-5" style={{ animationDelay: `${Math.min(index, 8) * 45}ms` }}><div className="flex items-start justify-between gap-3"><div><p className="text-[10px] font-bold uppercase tracking-wider text-teal">{item.category}</p><h3 className="mt-1 text-lg font-extrabold text-navy">{item.name}</h3></div>{item.is_demo && <span className="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700">Data demo</span>}</div><p className="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{item.summary}</p><div className="mt-4 flex flex-wrap gap-1.5">{item.skill_preview.slice(0, 3).map((skill) => <span key={skill} className="rounded-md bg-slate-100 px-2 py-1 text-[10px] text-slate-600">{skill}</span>)}</div><div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4"><TextLink to={`/professions/${item.id}`}>Kenali profesi</TextLink><button onClick={() => navigate(`/matching?occupation=${item.id}&type=goal`)} className="inline-flex items-center gap-1 text-xs font-bold text-teal">Jadikan target<ArrowRight size={13} /></button></div></article>)}</div>}
            {!loading && !searching && !items.length && <div className="surface p-10 text-center text-sm text-slate-500"><CircleHelp className="mx-auto mb-3 text-slate-300" />Belum ada profesi Published pada bidang ini. Coba pilih semua bidang.</div>}
        </section>
    </div>;
}
