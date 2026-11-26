import { useEffect, useState } from 'react';
import { FileClock, ShieldCheck } from 'lucide-react';
import { Badge, EmptyState, Modal, PageHeader, Spinner } from '../../components/ui';
import { errorMessage, formatDate, request } from '../../lib/api';
import type { AdminRecord, Page } from '../../types';

interface AuditLog extends AdminRecord { action: string; user: string | null; entity_type: string; entity_id: number | null; changes: unknown; ip_address: string | null; created_at: string }

export function AdminAuditPage() {
    const [logs, setLogs] = useState<AuditLog[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selected, setSelected] = useState<AuditLog | null>(null);
    useEffect(() => { request<Page<AuditLog>>('get', '/admin/audit-logs?per_page=100').then((data) => setLogs(data.items)).catch((cause) => setError(errorMessage(cause))).finally(() => setLoading(false)); }, []);
    if (loading) return <Spinner label="Memuat audit log" />;
    return <div><PageHeader eyebrow="Jejak perubahan" title="Audit Log" description="Catatan actor, aksi, entitas, dan perubahan yang dilakukan melalui panel Admin." action={<Badge tone="green"><ShieldCheck size={12} />Hanya Admin</Badge>} />{error && <p className="rounded-xl bg-danger/5 p-4 text-sm text-danger">{error}</p>}{!logs.length ? <EmptyState title="Belum ada aktivitas" description="Perubahan data dan transisi publikasi akan tercatat di sini." /> : <div className="surface overflow-x-auto"><table className="w-full min-w-[760px] text-left"><thead className="bg-canvas text-[10px] font-bold uppercase tracking-wider text-slate-500"><tr><th className="px-5 py-3">Waktu</th><th className="px-4 py-3">Actor</th><th className="px-4 py-3">Aksi</th><th className="px-4 py-3">Entitas</th><th className="px-5 py-3 text-right">Detail</th></tr></thead><tbody className="divide-y divide-slate-100">{logs.map((log) => <tr key={log.id}><td className="px-5 py-4 text-[11px] text-slate-500">{formatDate(log.created_at)}</td><td className="px-4 py-4 text-xs text-navy">{log.user || 'Sistem'}</td><td className="px-4 py-4"><Badge tone="blue">{log.action}</Badge></td><td className="px-4 py-4 text-xs text-slate-500">{log.entity_type} #{log.entity_id ?? '—'}</td><td className="px-5 py-4 text-right"><button onClick={() => setSelected(log)} className="text-xs font-bold text-teal">Lihat perubahan</button></td></tr>)}</tbody></table></div>}{selected && <Modal title={`Detail ${selected.action}`} onClose={() => setSelected(null)}><div className="space-y-4"><div className="grid gap-3 text-xs sm:grid-cols-2"><div className="rounded-xl bg-canvas p-3"><p className="text-slate-400">Actor</p><p className="mt-1 font-bold text-navy">{selected.user || 'Sistem'}</p></div><div className="rounded-xl bg-canvas p-3"><p className="text-slate-400">Alamat IP</p><p className="mt-1 font-bold text-navy">{selected.ip_address || '—'}</p></div></div><FileClock className="text-teal" size={20} /><pre className="max-h-96 overflow-auto rounded-xl bg-navy p-4 text-[11px] leading-5 text-slate-200">{JSON.stringify(selected.changes, null, 2)}</pre></div></Modal>}</div>;
}
