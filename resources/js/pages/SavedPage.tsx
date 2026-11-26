import { useEffect, useState } from 'react';
import { Bookmark } from 'lucide-react';
import { OccupationCard } from '../components/OccupationCard';
import { Alert, EmptyState, PageHeader, Spinner } from '../components/ui';
import { errorMessage, formatDate, request } from '../lib/api';
import type { SavedCareer } from '../types';

export function SavedPage() {
    const [items, setItems] = useState<SavedCareer[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    useEffect(() => { request<SavedCareer[]>('get', '/me/saved-careers').then(setItems).catch((cause) => setError(errorMessage(cause))).finally(() => setLoading(false)); }, []);
    async function remove(id: number) { await request('delete', `/me/saved-careers/${id}`); setItems((current) => current.filter((item) => item.occupation_id !== id)); }
    if (loading) return <Spinner label="Memuat simpanan" />;
    return <div className="mx-auto max-w-7xl"><PageHeader eyebrow="Koleksi pilihanmu" title="Profesi Disimpan" description="Simpan profesi yang ingin dibandingkan atau dibuka kembali nanti." />{error && <Alert>{error}</Alert>}{!items.length ? <EmptyState title="Belum ada profesi disimpan" description="Gunakan tombol simpan pada kartu atau halaman detail profesi." action={<PageHeaderTitleAction />} /> : <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{items.map((item) => <div key={item.id}><p className="mb-2 px-1 text-[11px] text-slate-400">Disimpan {formatDate(item.saved_at)}</p><OccupationCard occupation={item.profession} saved onSave={() => void remove(item.occupation_id).catch((cause) => setError(errorMessage(cause)))} /></div>)}</div>}</div>;
}

function PageHeaderTitleAction() { return <a href="/professions" className="inline-flex items-center gap-2 rounded-xl bg-teal px-4 py-2.5 text-sm font-semibold text-white"><Bookmark size={15} />Jelajahi Profesi</a>; }
