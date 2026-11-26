import { ArrowUpRight, Bookmark, BriefcaseBusiness, Check, GraduationCap, MapPin, Sparkles } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { OccupationCard } from '../types';
import { Badge, Button } from './ui';

const categoryStyles: Record<string, { icon: typeof BriefcaseBusiness; color: string }> = {
    'Kesehatan dan Medis': { icon: Check, color: 'bg-rose-50 text-rose-600' },
    'Teknologi dan Digital': { icon: Sparkles, color: 'bg-indigo-50 text-indigo-600' },
    'Pendidikan': { icon: GraduationCap, color: 'bg-amber-50 text-amber-700' },
    'Bisnis dan Keuangan': { icon: BriefcaseBusiness, color: 'bg-emerald-50 text-emerald-700' },
};

export function OccupationCard({ occupation, saved = false, onSave }: { occupation: OccupationCard; saved?: boolean; onSave?: () => void }) {
    const style = categoryStyles[occupation.category ?? ''] ?? { icon: BriefcaseBusiness, color: 'bg-teal/10 text-teal' };
    const Icon = style.icon;
    return <article className="surface group flex h-full flex-col overflow-hidden p-5 transition duration-200 hover:-translate-y-0.5 hover:border-teal/30 hover:shadow-[0_16px_35px_rgba(24,50,75,.09)]">
        <div className="flex items-start justify-between gap-3">
            <span className={`grid h-11 w-11 place-items-center rounded-xl ${style.color}`}><Icon size={21} /></span>
            <div className="flex items-center gap-2">{occupation.is_demo && <Badge tone="amber">Data demo</Badge>}{onSave && <button onClick={onSave} className={`rounded-lg p-2 transition ${saved ? 'bg-teal/10 text-teal' : 'text-slate-400 hover:bg-slate-100 hover:text-teal'}`} aria-label={saved ? 'Hapus dari simpanan' : 'Simpan profesi'}><Bookmark size={17} fill={saved ? 'currentColor' : 'none'} /></button>}</div>
        </div>
        <p className="mt-5 text-[11px] font-bold uppercase tracking-[.12em] text-teal">{occupation.category}</p>
        <h3 className="mt-1.5 text-lg font-extrabold leading-6 text-navy group-hover:text-teal-dark">{occupation.name}</h3>
        <p className="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{occupation.summary}</p>
        <div className="mt-4 flex flex-wrap gap-1.5">{occupation.skill_preview.slice(0, 3).map((skill) => <span key={skill} className="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-600">{skill}</span>)}</div>
        <div className="mt-auto flex items-center justify-between border-t border-slate-100 pt-4">
            <span className="flex items-center gap-1 text-[11px] font-medium text-slate-400"><MapPin size={12} />KBJI {occupation.code}</span>
            <Link to={`/professions/${occupation.id}`} className="inline-flex items-center gap-1 text-xs font-bold text-teal hover:text-teal-dark">Lihat detail<ArrowUpRight size={14} /></Link>
        </div>
    </article>;
}

export function SaveButton({ saved, onClick, busy = false }: { saved: boolean; onClick: () => void; busy?: boolean }) {
    return <Button variant={saved ? 'secondary' : 'primary'} onClick={onClick} disabled={busy}><Bookmark size={16} fill={saved ? 'currentColor' : 'none'} />{saved ? 'Tersimpan' : 'Simpan Profesi'}</Button>;
}
