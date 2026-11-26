import { useEffect, useState } from 'react';
import { FileClock } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Badge, EmptyState, PageHeader, Spinner } from '../../components/ui';
import { errorMessage, formatDate, request } from '../../lib/api';
import type { Page } from '../../types';

interface Review { id: number; decision: string; notes: string | null; reviewer: string | null; entity_type: string; entity_id: number; entity_name: string | null; status: string; created_at: string }

export function AdminReviewsPage() {
    const [reviews, setReviews] = useState<Review[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    useEffect(() => { request<Page<Review>>('get', '/admin/reviews').then((data) => setReviews(data.items)).catch((cause) => setError(errorMessage(cause))).finally(() => setLoading(false)); }, []);
    if (loading) return <Spinner label="Memuat review konten" />;
    return <div><PageHeader eyebrow="Alur editorial" title="Review Konten" description="Riwayat keputusan review. Aksi publish atau kembalikan dilakukan dari daftar data terkait." />{error && <p className="rounded-xl bg-danger/5 p-4 text-sm text-danger">{error}</p>}{!reviews.length ? <EmptyState title="Belum ada review" description="Ajukan data Draft menjadi Review untuk mulai mencatat keputusan editorial." action={<Link to="/admin/resources/occupations" className="font-bold text-teal">Kelola profesi →</Link>} /> : <div className="space-y-4">{reviews.map((review) => <article key={review.id} className="surface p-5"><div className="flex flex-wrap items-start justify-between gap-4"><div><div className="flex flex-wrap items-center gap-2"><Badge tone={review.decision === 'approved' ? 'green' : review.decision === 'submitted' ? 'amber' : review.decision === 'returned' ? 'red' : 'slate'}>{review.decision}</Badge><span className="text-[11px] text-slate-400">{review.entity_type} #{review.entity_id}</span></div><h2 className="mt-3 font-extrabold text-navy">{review.entity_name || 'Data tidak ditemukan'}</h2><p className="mt-1 text-xs text-slate-500">Status saat ini: {review.status} · Reviewer: {review.reviewer || 'Sistem'}</p></div><span className="text-xs text-slate-400">{formatDate(review.created_at)}</span></div>{review.notes && <div className="mt-4 rounded-xl bg-canvas p-4 text-xs leading-6 text-slate-600"><FileClock size={14} className="mr-2 inline text-teal" />{review.notes}</div>}</article>)}</div>}</div>;
}
