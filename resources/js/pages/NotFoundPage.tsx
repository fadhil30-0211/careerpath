import { ArrowLeft, Compass } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Button } from '../components/ui';

export function NotFoundPage() {
    return <div className="grid min-h-[70vh] place-items-center px-5 py-16 text-center"><div><span className="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-teal/10 text-teal"><Compass size={30} /></span><p className="mt-6 text-xs font-bold uppercase tracking-[.18em] text-teal">404 · Arah tidak ditemukan</p><h1 className="mt-3 text-3xl font-black tracking-tight text-navy">Halaman ini belum ada di peta.</h1><p className="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">Tautan mungkin berubah atau halaman sudah diarsipkan. Kembali ke beranda untuk melanjutkan eksplorasi.</p><Button to="/" className="mt-6"><ArrowLeft size={16} />Kembali ke Beranda</Button><Link to="/professions" className="ml-4 text-sm font-bold text-teal">Buka direktori</Link></div></div>;
}
