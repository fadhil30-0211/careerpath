import { useEffect, useState } from 'react';
import { LoaderCircle, ShieldCheck, Users } from 'lucide-react';
import { Alert, Badge, EmptyState, PageHeader, Select, Spinner, StatusBadge } from '../../components/ui';
import { useAuth } from '../../features/auth/AuthContext';
import { errorMessage, formatDate, request } from '../../lib/api';
import type { AdminRecord, Page } from '../../types';

export function AdminUsersPage() {
    const { user: current } = useAuth();
    const [items, setItems] = useState<AdminRecord[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState<number | null>(null);
    async function load() { setLoading(true); try { const data = await request<Page<AdminRecord>>('get', '/admin/users?per_page=100'); setItems(data.items); } catch (cause) { setError(errorMessage(cause)); } finally { setLoading(false); } }
    useEffect(() => { void load(); }, []);
    async function update(item: AdminRecord, patch: { role?: string; status?: string }) { setBusy(item.id); setError(null); try { await request('patch', `/admin/users/${item.id}`, patch); await load(); } catch (cause) { setError(errorMessage(cause)); } finally { setBusy(null); } }
    if (loading) return <Spinner label="Memuat pengguna" />;
    return <div><PageHeader eyebrow="Akses & keamanan" title="Pengguna" description="Kelola role dan status akun. Akun Admin yang sedang login tidak dapat ditangguhkan atau diturunkan." />{error && <div className="mb-5"><Alert>{error}</Alert></div>}{!items.length ? <EmptyState title="Belum ada pengguna" description="Akun akan muncul setelah ada yang mendaftar." /> : <div className="surface overflow-x-auto"><table className="w-full min-w-[760px] text-left"><thead className="bg-canvas text-[10px] font-bold uppercase tracking-wider text-slate-500"><tr><th className="px-5 py-3">Pengguna</th><th className="px-4 py-3">Role</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Terdaftar</th><th className="px-5 py-3 text-right">Aksi</th></tr></thead><tbody className="divide-y divide-slate-100">{items.map((item) => <tr key={item.id}><td className="px-5 py-4"><p className="text-sm font-bold text-navy">{String(item.name)}{item.id === current?.id && <span className="ml-2 text-[10px] text-teal">(Anda)</span>}</p><p className="mt-1 text-[11px] text-slate-400">{String(item.email)}</p></td><td className="px-4 py-4"><Badge tone={item.role === 'admin' ? 'blue' : 'slate'}>{item.role === 'admin' ? <ShieldCheck size={11} /> : <Users size={11} />}{item.role}</Badge></td><td className="px-4 py-4"><StatusBadge status={item.status as 'active' | 'suspended'} /></td><td className="px-4 py-4 text-[11px] text-slate-400">{formatDate(item.created_at as string)}</td><td className="px-5 py-4"><div className="flex justify-end gap-2"><Select value={String(item.role)} disabled={busy === item.id} onChange={(event) => void update(item, { role: event.target.value })} className="w-28"><option value="user">user</option><option value="admin">admin</option></Select><Select value={String(item.status)} disabled={busy === item.id || item.id === current?.id} onChange={(event) => void update(item, { status: event.target.value })} className="w-36"><option value="active">Aktif</option><option value="suspended">Ditangguhkan</option></Select>{busy === item.id && <LoaderCircle size={16} className="mt-3 animate-spin text-teal" />}</div></td></tr>)}</tbody></table></div>}</div>;
}
