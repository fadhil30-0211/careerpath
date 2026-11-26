import { useDeferredValue, useState } from 'react';
import { ChevronLeft, ChevronRight, Filter, Search, X } from 'lucide-react';
import { OccupationCard } from '../components/OccupationCard';
import { Button, EmptyState, Field, Input, PageHeader, Select, Spinner } from '../components/ui';
import { useCatalog } from '../features/catalog/CatalogContext';
import { useOccupationQuery } from '../features/catalog/hooks';
import type { GroupNode, SubcategoryNode } from '../types';

export function ProfessionsPage() {
    const { categories } = useCatalog();
    const [filters, setFilters] = useState({ search: '', category: '', subcategory: '', group: '', activity: '' });
    const [page, setPage] = useState(1);
    const deferredSearch = useDeferredValue(filters.search);
    const params = new URLSearchParams();
    if (deferredSearch) params.set('search', deferredSearch);
    if (filters.category) params.set('category_id', filters.category);
    if (filters.subcategory) params.set('subcategory_id', filters.subcategory);
    if (filters.group) params.set('group_id', filters.group);
    if (filters.activity) params.set('activity', filters.activity);
    params.set('per_page', '12');
    params.set('page', String(page));
    const { items, meta, loading, error, reload } = useOccupationQuery(params);
    const { meta: catalogMeta } = useCatalog();
    const category = categories.find((item) => String(item.id) === filters.category);
    const subcategories: SubcategoryNode[] = category?.subcategories ?? [];
    const groups: GroupNode[] = subcategories.flatMap((item) => item.groups);
    const activeCount = [filters.category, filters.subcategory, filters.group, filters.activity].filter(Boolean).length;

    function change(key: keyof typeof filters, value: string) {
        setFilters((current) => key === 'category' ? { ...current, category: value, subcategory: '', group: '' } : key === 'subcategory' ? { ...current, subcategory: value, group: '' } : { ...current, [key]: value });
        setPage(1);
    }

    return <div className="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
        <PageHeader eyebrow="Direktori profesi" title="Kenali profesi, sebelum memilih jalur" description="Telusuri berdasarkan bidang, kelompok pekerjaan, atau jenis aktivitas. Semua profesi hanya ditampilkan setelah melewati review dan publikasi." />
        <div className="surface mb-7 p-5">
            <div className="relative"><Search className="absolute left-3.5 top-3.5 text-slate-400" size={18} /><Input value={filters.search} onChange={(event) => change('search', event.target.value)} placeholder="Cari nama profesi atau keterampilan…" className="pl-10" aria-label="Cari profesi" />{filters.search && <button onClick={() => change('search', '')} className="absolute right-3 top-3 rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-navy active:scale-90" aria-label="Hapus pencarian"><X size={17} /></button>}</div>
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Field label="Bidang"><Select value={filters.category} onChange={(event) => change('category', event.target.value)}><option value="">Semua bidang</option>{categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select></Field>
                <Field label="Subkategori"><Select value={filters.subcategory} onChange={(event) => change('subcategory', event.target.value)} disabled={!filters.category}><option value="">Semua subkategori</option>{subcategories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select></Field>
                <Field label="Kelompok profesi"><Select value={filters.group} onChange={(event) => change('group', event.target.value)} disabled={!filters.subcategory}><option value="">Semua kelompok</option>{groups.filter((item) => subcategories.some((subcategory) => String(subcategory.id) === filters.subcategory && subcategory.groups.some((group) => group.id === item.id))).map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select></Field>
                <Field label="Jenis aktivitas"><Select value={filters.activity} onChange={(event) => change('activity', event.target.value)}><option value="">Semua aktivitas</option>{catalogMeta?.activities.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</Select></Field>
            </div>
            <div className="mt-4 flex items-center justify-between"><p className="flex items-center gap-1.5 text-xs text-slate-500"><Filter size={13} />{activeCount ? `${activeCount} filter aktif` : 'Belum ada filter tambahan'}</p>{activeCount > 0 && <button onClick={() => { setFilters({ search: '', category: '', subcategory: '', group: '', activity: '' }); setPage(1); }} className="text-xs font-bold text-teal">Hapus semua filter</button>}</div>
        </div>
        <div className="mb-5 flex items-center justify-between"><p className="text-sm text-slate-500">{loading ? 'Mencari profesi…' : <><strong className="text-navy">{meta.total}</strong> profesi ditemukan</>}</p><span className="hidden text-xs text-slate-400 sm:block">Diperbarui sesuai data Published</span></div>
        {loading ? <Spinner label="Memuat direktori" /> : error ? <EmptyState title="Direktori tidak dapat dimuat" description={error} action={<Button onClick={reload}>Coba Lagi</Button>} /> : items.length === 0 ? <EmptyState title="Profesi tidak ditemukan" description="Coba kata kunci lain atau hapus filter untuk melihat pilihan yang lebih luas." action={<Button variant="secondary" onClick={() => { setFilters({ search: '', category: '', subcategory: '', group: '', activity: '' }); setPage(1); }}>Hapus Filter</Button>} /> : <><div key={params.toString()} className="results-enter grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{items.map((occupation, index) => <OccupationCard key={occupation.id} occupation={occupation} index={index} />)}</div>{meta.last_page > 1 && <div className="mt-8 flex items-center justify-center gap-3"><Button variant="secondary" onClick={() => setPage((value) => value - 1)} disabled={page <= 1}><ChevronLeft size={16} />Sebelumnya</Button><span className="text-sm text-slate-500">Halaman {meta.current_page} dari {meta.last_page}</span><Button variant="secondary" onClick={() => setPage((value) => value + 1)} disabled={page >= meta.last_page}>Berikutnya<ChevronRight size={16} /></Button></div>}</>}
    </div>;
}
