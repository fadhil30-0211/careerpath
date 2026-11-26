import { useEffect, useRef, useState, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react';
import { AlertCircle, ArrowRight, CheckCircle2, Compass, LoaderCircle, Search, X } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { classNames } from '../lib/api';
import type { PublicationStatus } from '../types';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & { to?: string; variant?: 'primary' | 'secondary' | 'ghost' | 'danger'; size?: 'sm' | 'md' | 'lg' };

export function Button({ to, variant = 'primary', size = 'md', className, children, ...props }: ButtonProps) {
    const styles = {
        primary: 'bg-teal text-white shadow-sm shadow-teal/20 hover:bg-teal-dark hover:shadow-md hover:shadow-teal/20',
        secondary: 'border border-slate-300 bg-white text-navy hover:border-teal hover:bg-teal/5 hover:text-teal-dark',
        ghost: 'text-slate-600 hover:bg-slate-100 hover:text-navy',
        danger: 'border border-danger/30 bg-danger/5 text-danger hover:bg-danger/10',
    }[variant];
    const sizes = { sm: 'px-3 py-2 text-xs', md: 'px-4 py-2.5 text-sm', lg: 'px-5 py-3 text-sm' }[size];
    const classes = classNames('inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition duration-200 ease-out active:scale-[.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100', styles, sizes, className);
    if (to) return <Link to={to} className={classes}>{children}</Link>;
    return <button className={classes} {...props}>{children}</button>;
}

export function Reveal({ children, className, delay = 0 }: { children: ReactNode; className?: string; delay?: number }) {
    const ref = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        const element = ref.current;
        if (!element) return;
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setVisible(true);
            return;
        }
        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                setVisible(true);
                observer.disconnect();
            }
        }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });
        observer.observe(element);
        return () => observer.disconnect();
    }, []);
    return <div ref={ref} className={classNames('reveal', visible && 'reveal-visible', className)} style={{ transitionDelay: visible ? `${delay}ms` : undefined }}>{children}</div>;
}

export function PageTransition({ children }: { children: ReactNode }) {
    const { pathname } = useLocation();
    return <div key={pathname} className="page-transition">{children}</div>;
}

export function Logo({ light = false, compact = false }: { light?: boolean; compact?: boolean }) {
    return <Link to="/" className="group inline-flex items-center gap-2.5" aria-label="CareerPath, beranda">
        <span className={classNames('grid h-9 w-9 place-items-center rounded-xl transition-transform duration-500 ease-out group-hover:-rotate-12', light ? 'bg-white/15 text-white' : 'bg-teal text-white')}><Compass size={21} strokeWidth={2.4} /></span>
        {!compact && <span className={classNames('text-lg font-extrabold tracking-tight transition-colors', light ? 'text-white' : 'text-navy group-hover:text-teal-dark')}>Career<span className={light ? 'text-teal-200' : 'text-teal'}>Path</span></span>}
    </Link>;
}

export function Field({ label, htmlFor, hint, error, children, className }: { label: string; htmlFor?: string; hint?: string; error?: string; children: ReactNode; className?: string }) {
    return <div className={className}>
        <label className="label" htmlFor={htmlFor}>{label}</label>
        {children}
        {error ? <p className="mt-1.5 text-xs font-medium text-danger">{error}</p> : hint ? <p className="mt-1.5 text-xs text-slate-500">{hint}</p> : null}
    </div>;
}

export function Input(props: InputHTMLAttributes<HTMLInputElement>) { return <input className="input" {...props} />; }
export function Textarea(props: TextareaHTMLAttributes<HTMLTextAreaElement>) { return <textarea className="input min-h-24 resize-y" {...props} />; }
export function Select(props: SelectHTMLAttributes<HTMLSelectElement>) { return <select className="input" {...props} />; }

export function Badge({ children, tone = 'slate' }: { children: ReactNode; tone?: 'teal' | 'amber' | 'red' | 'blue' | 'slate' | 'green' }) {
    const tones = {
        teal: 'bg-teal/10 text-teal-dark', amber: 'bg-warning/10 text-amber-700', red: 'bg-danger/10 text-danger',
        blue: 'bg-sky-brand/10 text-sky-brand', slate: 'bg-slate-100 text-slate-600', green: 'bg-emerald-50 text-emerald-700',
    };
    return <span className={classNames('inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold', tones[tone])}>{children}</span>;
}

export function StatusBadge({ status }: { status: PublicationStatus | 'active' | 'suspended' }) {
    const tone = status === 'published' || status === 'active' ? 'green' : status === 'review' ? 'amber' : status === 'archived' || status === 'suspended' ? 'red' : 'slate';
    const labels: Record<string, string> = { draft: 'Draft', review: 'Review', published: 'Published', archived: 'Arsip', active: 'Aktif', suspended: 'Ditangguhkan' };
    return <Badge tone={tone}>{labels[status] ?? status}</Badge>;
}

export function Alert({ children, tone = 'error' }: { children: ReactNode; tone?: 'error' | 'success' | 'info' }) {
    const tones = { error: 'border-danger/20 bg-danger/5 text-danger', success: 'border-emerald-200 bg-emerald-50 text-emerald-800', info: 'border-sky-brand/20 bg-sky-brand/5 text-navy' };
    return <div role={tone === 'error' ? 'alert' : 'status'} className={classNames('alert-enter flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm', tones[tone])}>{tone === 'success' ? <CheckCircle2 className="mt-0.5 shrink-0" size={17} /> : <AlertCircle className="mt-0.5 shrink-0" size={17} />}<div>{children}</div></div>;
}

export function Spinner({ label = 'Memuat data' }: { label?: string }) {
    return <div className="loading-enter flex min-h-48 flex-col items-center justify-center gap-3 text-sm text-slate-500" role="status"><LoaderCircle className="animate-spin text-teal" size={28} strokeWidth={1.8} /><span>{label}</span></div>;
}

export function EmptyState({ title, description, action }: { title: string; description: string; action?: ReactNode }) {
    return <div className="surface empty-enter group flex flex-col items-center px-6 py-14 text-center"><span className="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-teal/10 text-teal transition-transform duration-300 group-hover:scale-105"><Search size={23} /></span><h3 className="font-bold text-navy">{title}</h3><p className="mt-2 max-w-md text-sm leading-6 text-slate-500">{description}</p>{action && <div className="mt-5">{action}</div>}</div>;
}

export function PageHeader({ eyebrow, title, description, action }: { eyebrow?: string; title: string; description?: string; action?: ReactNode }) {
    return <div className="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>{eyebrow && <p className="mb-2 text-xs font-bold uppercase tracking-[.16em] text-teal">{eyebrow}</p>}<h1 className="text-2xl font-extrabold tracking-tight text-navy sm:text-3xl">{title}</h1>{description && <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{description}</p>}</div>
        {action && <div className="shrink-0">{action}</div>}
    </div>;
}

export function SectionHeading({ eyebrow, title, description, action }: { eyebrow?: string; title: string; description?: string; action?: ReactNode }) {
    return <div className="mb-5 flex items-end justify-between gap-4"><div>{eyebrow && <p className="mb-2 text-xs font-bold uppercase tracking-[.16em] text-teal">{eyebrow}</p>}<h2 className="text-xl font-extrabold tracking-tight text-navy">{title}</h2>{description && <p className="mt-1 text-sm text-slate-500">{description}</p>}</div>{action}</div>;
}

export function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: ReactNode }) {
    useEffect(() => {
        const listener = (event: KeyboardEvent) => { if (event.key === 'Escape') onClose(); };
        window.addEventListener('keydown', listener);
        return () => window.removeEventListener('keydown', listener);
    }, [onClose]);
    return <div className="modal-backdrop fixed inset-0 z-50 grid place-items-center bg-navy/50 p-4 backdrop-blur-sm" onMouseDown={(event) => { if (event.target === event.currentTarget) onClose(); }}>
        <div className="modal-panel max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-label={title}>
            <div className="mb-5 flex items-center justify-between"><h2 className="text-lg font-extrabold text-navy">{title}</h2><button onClick={onClose} className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-navy active:scale-95" aria-label="Tutup"><X size={19} /></button></div>
            {children}
        </div>
    </div>;
}

export function TextLink({ to, children }: { to: string; children: ReactNode }) { return <Link to={to} className="group inline-flex items-center gap-1.5 text-sm font-bold text-teal transition-colors hover:text-teal-dark">{children}<ArrowRight size={15} className="transition-transform duration-200 group-hover:translate-x-0.5" /></Link>; }
