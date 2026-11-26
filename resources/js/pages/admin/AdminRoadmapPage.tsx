import { useEffect, useState } from 'react';
import { ArrowLeft, LoaderCircle, Plus, Save, Trash2 } from 'lucide-react';
import { Link, useParams } from 'react-router-dom';
import { Alert, Button, Field, Input, PageHeader, Spinner, Textarea } from '../../components/ui';
import { errorMessage, request } from '../../lib/api';
import type { AdminRecord, RoadmapStage } from '../../types';

interface RoadmapData extends AdminRecord { stages: RoadmapStage[] }

export function AdminRoadmapPage() {
    const { id } = useParams();
    const [roadmap, setRoadmap] = useState<RoadmapData | null>(null);
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    useEffect(() => { request<RoadmapData>('get', `/admin/roadmaps/${id}`).then(setRoadmap).catch((cause) => setError(errorMessage(cause))).finally(() => setLoading(false)); }, [id]);
    function update(index: number, patch: Partial<RoadmapStage>) { setRoadmap((current) => current ? { ...current, stages: current.stages.map((stage, stageIndex) => stageIndex === index ? { ...stage, ...patch } : stage) } : current); }
    function add() { setRoadmap((current) => current ? { ...current, stages: [...current.stages, { id: 0, code: `RM${current.id}-S${crypto.randomUUID().slice(0, 12)}`, sequence: current.stages.length + 1, title: '', objective: '', action: '', resource_title: '', resource_url: '', estimated_weeks: 2 }] } : current); }
    async function save() { if (!roadmap) return; setBusy(true); setError(null); try { await request('put', `/admin/roadmaps/${id}/stages`, { stages: roadmap.stages }); const data = await request<RoadmapData>('get', `/admin/roadmaps/${id}`); setRoadmap(data); } catch (cause) { setError(errorMessage(cause)); } finally { setBusy(false); } }
    if (loading) return <Spinner label="Memuat tahapan roadmap" />;
    if (!roadmap) return <Alert>{error ?? 'Roadmap tidak ditemukan.'}</Alert>;
    return <div><Link to="/admin/resources/roadmaps" className="mb-5 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500"><ArrowLeft size={15} />Kembali ke roadmap</Link><PageHeader eyebrow="Tahapan pengembangan" title={String(roadmap.name)} description={`Roadmap untuk ${String(roadmap.occupation?.name ?? 'profesi')}. Urutan tahap menentukan progres pengguna.`} action={<Button onClick={() => void save()} disabled={busy}>{busy ? <LoaderCircle className="animate-spin" size={16} /> : <Save size={16} />}Simpan Tahapan</Button>} />{error && <div className="mb-5"><Alert>{error}</Alert></div>}<div className="space-y-4">{roadmap.stages.map((stage, index) => <section key={`${stage.id}-${index}`} className="surface p-5"><div className="flex items-start justify-between"><h2 className="font-extrabold text-navy">Tahap {index + 1}</h2><button onClick={() => setRoadmap({ ...roadmap, stages: roadmap.stages.filter((_, stageIndex) => stageIndex !== index) })} className="text-slate-400 hover:text-danger" aria-label="Hapus tahap"><Trash2 size={16} /></button></div><div className="mt-4 grid gap-3 sm:grid-cols-2"><Field label="Kode unik"><Input value={stage.code} onChange={(event) => update(index, { code: event.target.value })} required /></Field><Field label="Urutan"><Input type="number" min={1} value={stage.sequence} onChange={(event) => update(index, { sequence: Number(event.target.value) })} required /></Field><Field label="Judul" className="sm:col-span-2"><Input value={stage.title} onChange={(event) => update(index, { title: event.target.value })} required /></Field><Field label="Tujuan"><Textarea value={stage.objective} onChange={(event) => update(index, { objective: event.target.value })} required /></Field><Field label="Aksi yang disarankan"><Textarea value={stage.action} onChange={(event) => update(index, { action: event.target.value })} required /></Field><Field label="Judul sumber"><Input value={stage.resource_title ?? ''} onChange={(event) => update(index, { resource_title: event.target.value })} /></Field><Field label="URL sumber"><Input type="url" value={stage.resource_url ?? ''} onChange={(event) => update(index, { resource_url: event.target.value })} /></Field><Field label="Estimasi minggu"><Input type="number" min={1} max={104} value={stage.estimated_weeks ?? ''} onChange={(event) => update(index, { estimated_weeks: event.target.value ? Number(event.target.value) : null })} /></Field></div></section>)}</div><Button variant="secondary" className="mt-5" onClick={add}><Plus size={15} />Tambah Tahap</Button></div>;
}
