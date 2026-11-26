import { useState } from 'react';
import { AlertTriangle, ArrowRight, Check, CheckCircle2, ChevronDown, CircleHelp, Info, LockKeyhole, Sparkles, Target, TrendingUp } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { Analysis, GapStatus, MandatoryRequirement } from '../../types';
import { Badge } from '../../components/ui';
import { classNames } from '../../lib/api';

const gapTone: Record<GapStatus, 'green' | 'amber' | 'red' | 'slate'> = { aligned: 'green', needs_improvement: 'amber', not_yet_possessed: 'red', unknown: 'slate' };
const factorLabels: Record<string, string> = { education: 'Pendidikan', skills: 'Keterampilan', experience: 'Pengalaman', certifications: 'Sertifikasi', interests: 'Minat & preferensi' };

export function ScoreRing({ score }: { score: number | null }) {
    const value = score ?? 0;
    return <div className="relative grid h-28 w-28 shrink-0 place-items-center rounded-full" style={{ background: `conic-gradient(#0f8b8d ${value * 3.6}deg, #e8eef2 0deg)` }}><div className="grid h-[88px] w-[88px] place-items-center rounded-full bg-white text-center"><div><p className="text-2xl font-black text-navy">{score === null ? '—' : Math.round(score)}</p><p className="text-[9px] font-medium text-slate-400">dari 100</p></div></div></div>;
}

export function MandatoryList({ items }: { items: MandatoryRequirement[] }) {
    if (!items.length) return <div className="rounded-xl border border-dashed border-slate-200 p-4 text-xs leading-5 text-slate-500">Tidak ada persyaratan wajib terkurasi untuk profesi ini. Tetap periksa regulasi dan persyaratan resmi sebelum melamar.</div>;
    return <div className="space-y-2">{items.map((item) => <div key={`${item.type}-${item.id}`} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 px-4 py-3"><div className="flex items-center gap-2.5"><LockKeyhole size={15} className="text-slate-400" /><div><p className="text-xs font-bold text-navy">{item.name}</p><p className="mt-0.5 text-[10px] uppercase tracking-wide text-slate-400">Persyaratan wajib · {item.type}</p></div></div><Badge tone={item.status === 'satisfied' ? 'green' : item.status === 'not_met' ? 'red' : 'slate'}>{item.status === 'satisfied' && <Check size={11} />}{item.status === 'not_met' && <AlertTriangle size={11} />}{item.status === 'unknown' && <CircleHelp size={11} />}{item.status_label}</Badge></div>)}</div>;
}

export function AnalysisDetails({ analysis }: { analysis: Analysis }) {
    return <div className="space-y-5">
        <div className="rounded-2xl border border-teal/15 bg-teal/5 p-5"><div className="flex items-start gap-3"><Sparkles size={19} className="mt-0.5 shrink-0 text-teal" /><div><h3 className="text-sm font-extrabold text-navy">Mengapa profesi ini muncul?</h3><ul className="mt-3 space-y-2">{analysis.reasons.map((reason) => <li key={reason} className="flex items-start gap-2 text-xs leading-5 text-slate-600"><CheckCircle2 size={13} className="mt-0.5 shrink-0 text-teal" />{reason}</li>)}</ul></div></div></div>
        <section><h3 className="mb-3 flex items-center gap-2 text-sm font-extrabold text-navy"><LockKeyhole size={16} className="text-danger" />Persyaratan wajib</h3><MandatoryList items={analysis.mandatory_requirements} /></section>
        <section><h3 className="mb-3 text-sm font-extrabold text-navy">Rincian skill gap</h3><div className="space-y-2">{analysis.skill_gaps.map((gap) => <div key={gap.skill_id} className="rounded-xl border border-slate-200 p-4"><div className="flex flex-wrap items-start justify-between gap-2"><div><p className="text-xs font-bold text-navy">{gap.name}</p><p className="mt-1 text-[10px] text-slate-400">Levelmu {gap.user_level ?? 'belum diketahui'} · Target {gap.target_level}/5</p></div><Badge tone={gapTone[gap.status]}>{gap.status_label}</Badge></div><div className="mt-3 flex gap-1">{[1, 2, 3, 4, 5].map((level) => <span key={level} className={classNames('h-1.5 flex-1 rounded-full', level <= (gap.target_level ?? 0) ? 'bg-teal' : 'bg-slate-100')} />)}</div><p className="mt-3 text-[11px] leading-5 text-slate-500">{gap.action}</p></div>)}</div></section>
        <section><h3 className="mb-3 text-sm font-extrabold text-navy">Komponen skor</h3><div className="space-y-3">{analysis.breakdown.map((factor) => <div key={factor.factor}><div className="flex items-center justify-between text-xs"><span className="font-medium text-slate-600">{factorLabels[factor.factor] ?? factor.factor} <span className="text-slate-400">· bobot {factor.weight}%</span></span><span className="font-bold text-navy">{factor.known ? `${Math.round((factor.value ?? 0) * 100)}%` : 'Belum diketahui'}</span></div><div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100"><div className={classNames('h-full rounded-full', factor.known ? 'bg-sky-brand' : 'bg-slate-200')} style={{ width: `${factor.known ? (factor.value ?? 0) * 100 : 100}%` }} /></div></div>)}</div></section>
        {!!analysis.unknown_fields.length && <div className="rounded-xl bg-amber-50 p-4"><p className="flex items-center gap-2 text-xs font-bold text-amber-800"><Info size={14} />Informasi yang belum lengkap</p><ul className="mt-2 list-inside list-disc space-y-1 text-[11px] leading-5 text-amber-800">{analysis.unknown_fields.map((field) => <li key={field}>{field}</li>)}</ul><p className="mt-2 text-[10px] leading-5 text-amber-700">Informasi ini tidak dianggap nol. Lengkapi profil untuk meningkatkan cakupan analisis.</p></div>}
    </div>;
}

export function AnalysisResultCard({ analysis, defaultOpen = false }: { analysis: Analysis & { rank?: number; id?: number }; defaultOpen?: boolean }) {
    const [open, setOpen] = useState(defaultOpen);
    const readiness = analysis.readiness ?? analysis.profession.readiness;
    return <article className="surface overflow-hidden">
        <button onClick={() => setOpen(!open)} className="flex w-full items-center gap-4 p-5 text-left sm:gap-6" aria-expanded={open}>
            {analysis.rank && <span className="hidden w-6 text-center text-sm font-black text-slate-300 sm:block">#{analysis.rank}</span>}
            <ScoreRing score={analysis.score} />
            <div className="min-w-0 flex-1"><div className="flex flex-wrap items-center gap-2"><h2 className="text-lg font-extrabold text-navy">{analysis.profession.name}</h2>{analysis.profession.is_demo && <Badge tone="amber">Data demo</Badge>}</div><p className="mt-1 text-xs text-slate-400">{analysis.profession.category} · KBJI {analysis.profession.code}</p><div className="mt-3 flex flex-wrap gap-2"><Badge tone={readiness?.status === 'ready_to_explore' ? 'green' : readiness?.status === 'needs_development' ? 'amber' : 'slate'}><Target size={11} />{readiness?.label ?? 'Perlu data tambahan'}</Badge><span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">Cakupan data {Math.round(analysis.coverage)}%</span></div></div>
            <ChevronDown size={20} className={classNames('shrink-0 text-slate-400 transition', open && 'rotate-180')} />
        </button>
        <div className="border-t border-slate-100 px-5 py-4"><div className="mb-4 h-1.5 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-teal" style={{ width: `${analysis.coverage}%` }} /></div><div className="flex flex-wrap items-center justify-between gap-3"><p className="text-[11px] text-slate-500">Skor keselarasan, bukan probabilitas diterima kerja.</p><div className="flex gap-2"><Link to={`/professions/${analysis.profession.id}`} className="text-xs font-bold text-slate-500 hover:text-navy">Detail profesi</Link><Link to={`/development?occupation=${analysis.profession.id}`} className="inline-flex items-center gap-1 text-xs font-bold text-teal">Rencana pengembangan<ArrowRight size={12} /></Link></div></div></div>
        {open && <div className="border-t border-slate-100 bg-canvas/50 p-5"><AnalysisDetails analysis={analysis} /></div>}
    </article>;
}

export function AnalysisDisclaimer() {
    return <div className="flex items-start gap-3 rounded-2xl border border-sky-brand/15 bg-sky-brand/5 p-4"><TrendingUp size={18} className="mt-0.5 shrink-0 text-sky-brand" /><p className="text-xs leading-5 text-slate-600"><strong className="text-navy">Cara membaca hasil.</strong> Skor membantu membandingkan keselarasan profil dengan data katalog. Hasil tidak menentukan nilai dirimu, bukan tes psikologi, dan bukan jaminan pekerjaan. Pertimbangkan pengalaman, kondisi pasar, serta percakapan dengan profesional.</p></div>;
}
