import { useEffect, useState } from 'react';
import { Info } from 'lucide-react';
import { Badge, Button, EmptyState, Modal, PageHeader, Spinner } from '../components/ui';
import { AnalysisResultCard } from '../features/matching/AnalysisResults';
import { errorMessage, formatDate, request } from '../lib/api';
import type { MatchingRun } from '../types';

export function HistoryPage() {
    const [runs, setRuns] = useState<MatchingRun[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selected, setSelected] = useState<MatchingRun | null>(null);
    useEffect(() => { request<{ items: MatchingRun[] }>('get', '/me/analysis-history').then((data) => setRuns(data.items)).catch((cause) => setError(errorMessage(cause))).finally(() => setLoading(false)); }, []);
    if (loading) return <Spinner label="Memuat riwayat" />;
    return <div className="mx-auto max-w-5xl"><PageHeader eyebrow="Snapshot yang tidak berubah" title="Riwayat Analisis" description="Setiap hasil memakai input dan versi katalog saat analisis dibuat. Perubahan profil tidak menimpa riwayat." />{error && <p className="rounded-xl bg-danger/5 p-4 text-sm text-danger">{error}</p>}{!runs.length ? <EmptyState title="Belum ada riwayat" description="Analisis pertamamu akan tersimpan di sini beserta input dan hasilnya." action={<Button to="/matching">Jalankan Analisis</Button>} /> : <div className="space-y-4">{runs.map((run) => <article key={run.id} className="surface p-5"><div className="flex flex-wrap items-start justify-between gap-4"><div><div className="flex flex-wrap items-center gap-2"><Badge tone={run.type === 'goal' ? 'blue' : 'teal'}>{run.type === 'goal' ? 'Analisis Target' : 'Career Matching'}</Badge><span className="text-xs text-slate-400">{formatDate(run.created_at)}</span></div><h2 className="mt-3 text-lg font-extrabold text-navy">{run.results[0]?.profession.name ?? 'Tidak ada hasil'}</h2><p className="mt-1 text-xs text-slate-500">{run.results.length} hasil · {run.candidate_count} kandidat · {run.algorithm_version}</p></div><div className="flex gap-2"><Button variant="secondary" size="sm" onClick={() => setSelected(run)}><Info size={14} />Input Snapshot</Button><Button to={`/matching/${run.id}`} size="sm">Buka Hasil</Button></div></div>{run.results[0] && <div className="mt-4"><AnalysisResultCard analysis={run.results[0]} /></div>}</article>)}</div>}
        {selected && <Modal title={`Input analisis #${selected.id}`} onClose={() => setSelected(null)}><div className="space-y-5"><div className="rounded-xl bg-canvas p-4 text-xs leading-6 text-slate-600"><p><strong className="text-navy">Pendidikan:</strong> {selected.input_snapshot.education_level || 'Belum diisi'}</p><p><strong className="text-navy">Jurusan:</strong> {selected.input_snapshot.field_of_study || 'Belum diisi'}</p><p><strong className="text-navy">Skill tercatat:</strong> {selected.input_snapshot.skills.length}</p><p><strong className="text-navy">Pengalaman:</strong> {selected.input_snapshot.experiences.length}</p><p><strong className="text-navy">Sertifikasi:</strong> {selected.input_snapshot.certifications.length}</p></div><div><h3 className="text-sm font-extrabold text-navy">Rincian input</h3><pre className="mt-3 max-h-80 overflow-auto rounded-xl bg-navy p-4 text-[11px] leading-5 text-slate-200">{JSON.stringify(selected.input_snapshot, null, 2)}</pre></div><div className="flex justify-end"><Button variant="secondary" onClick={() => setSelected(null)}>Tutup</Button></div></div></Modal>}
    </div>;
}
