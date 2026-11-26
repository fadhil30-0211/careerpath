import { useEffect, useState } from 'react';
import { Link, NavLink, Navigate, Outlet, useLocation } from 'react-router-dom';
import { LayoutDashboard, LogOut, Menu, Sparkles, X } from 'lucide-react';
import { useAuth } from '../../features/auth/AuthContext';
import { Button, Logo, PageTransition, Spinner } from '../ui';

export function RequireAuth() {
    const { user, loading } = useAuth();
    const location = useLocation();
    if (loading) return <Spinner label="Memeriksa sesi" />;
    if (!user) return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />;
    return <Outlet />;
}

export function RequireAdmin() {
    const { user, loading } = useAuth();
    if (loading) return <Spinner label="Memeriksa akses Admin" />;
    if (!user) return <Navigate to="/login" replace />;
    if (user.role !== 'admin') return <Navigate to="/dashboard" replace />;
    return <Outlet />;
}

export function PublicLayout() {
    const { user } = useAuth();
    const location = useLocation();
    const [open, setOpen] = useState(false);
    useEffect(() => setOpen(false), [location.pathname]);
    const links = [
        ['/professions', 'Direktori Profesi'], ['/explore', 'Kamu Mau Jadi Apa?'], ['/how-it-works', 'Cara Kerja'],
    ];
    return <div className="min-h-screen">
        <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div className="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-5 lg:px-8">
                <Logo />
                <nav className="hidden items-center gap-7 md:flex" aria-label="Navigasi utama">
                    {links.map(([to, label]) => <NavLink key={to} to={to} className={({ isActive }) => `text-sm font-semibold transition hover:text-teal ${isActive ? 'text-teal' : 'text-slate-600'}`}>{label}</NavLink>)}
                </nav>
                <div className="hidden items-center gap-3 md:flex">
                    {user ? <Button to={user.role === 'admin' ? '/admin' : '/dashboard'} size="sm">{user.role === 'admin' ? 'Panel Admin' : 'Dasbor Saya'}</Button> : <><Button to="/login" variant="ghost" size="sm">Masuk</Button><Button to="/register" size="sm">Mulai Gratis</Button></>}
                </div>
                <button className="rounded-xl p-2 text-navy transition hover:bg-canvas active:scale-95 md:hidden" onClick={() => setOpen(!open)} aria-label={open ? 'Tutup menu' : 'Buka menu'} aria-expanded={open}><span className={`block transition-transform duration-300 ${open ? 'rotate-90' : ''}`}>{open ? <X /> : <Menu />}</span></button>
            </div>
            <div className={`mobile-menu md:hidden ${open ? 'mobile-menu-open' : ''}`} aria-hidden={!open} inert={!open}>
                <div className="mobile-menu-inner">
                    <nav className="space-y-1 border-t border-slate-100 bg-white p-4">
                        {links.map(([to, label]) => <Link key={to} to={to} onClick={() => setOpen(false)} className="block rounded-xl px-3 py-3 text-sm font-semibold text-navy transition hover:bg-canvas active:bg-teal/5">{label}</Link>)}
                        <Link to={user ? (user.role === 'admin' ? '/admin' : '/dashboard') : '/login'} onClick={() => setOpen(false)} className="mt-3 block rounded-xl bg-teal px-3 py-3 text-center text-sm font-bold text-white transition hover:bg-teal-dark active:scale-[.99]">{user ? (user.role === 'admin' ? 'Panel Admin' : 'Dasbor Saya') : 'Masuk ke CareerPath'}</Link>
                    </nav>
                </div>
            </div>
        </header>
        <main><PageTransition><Outlet /></PageTransition></main>
        <footer className="mt-20 bg-navy text-slate-300">
            <div className="mx-auto grid max-w-7xl gap-10 px-5 py-12 md:grid-cols-[1.4fr_1fr_1fr] lg:px-8">
                <div><Logo light /><p className="mt-5 max-w-sm text-sm leading-6 text-slate-400">Membantu kamu memahami pilihan karier, kesiapan, dan langkah pengembangan dengan data yang transparan.</p></div>
                <div><h3 className="mb-4 text-sm font-bold text-white">Jelajahi</h3><div className="space-y-3 text-sm"><Link to="/professions" className="block hover:text-white">Direktori profesi</Link><Link to="/explore" className="block hover:text-white">Eksplorasi karier</Link><Link to="/matching" className="block hover:text-white">Pencocokan karier</Link></div></div>
                <div><h3 className="mb-4 text-sm font-bold text-white">Batasan layanan</h3><p className="text-sm leading-6 text-slate-400">Bukan portal lowongan, tes psikologi formal, atau jaminan diterima bekerja. Data demonstrasi ditandai dengan jelas.</p></div>
            </div>
            <div className="border-t border-white/10 py-5 text-center text-xs text-slate-500">CareerPath V2.0 · Prototipe edukasi karier · 2026</div>
        </footer>
    </div>;
}

const userLinks = [
    ['/dashboard', 'Dasbor', LayoutDashboard], ['/explore', 'Eksplorasi Karier', Sparkles], ['/matching', 'Career Matching'],
    ['/development', 'Pengembangan'], ['/saved', 'Profesi Disimpan'], ['/history', 'Riwayat Analisis'], ['/roadmaps', 'Roadmap Saya'], ['/profile', 'Profil'],
] as const;

export function AppLayout() {
    const { user, logout } = useAuth();
    const location = useLocation();
    const [open, setOpen] = useState(false);
    const [leaving, setLeaving] = useState(false);
    useEffect(() => setOpen(false), [location.pathname]);
    return <div className="min-h-screen bg-canvas lg:pl-[248px]">
        <aside className={`fixed inset-y-0 left-0 z-50 flex w-[248px] flex-col bg-navy p-5 transition-transform duration-300 ease-out lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
            <div className="px-2"><Logo light /></div>
            <div className="mt-8 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Ruang kariermu</div>
            <nav className="mt-3 space-y-1" aria-label="Navigasi pengguna">{userLinks.map(([to, label, Icon]) => <NavLink key={to} to={to} onClick={() => setOpen(false)} className={({ isActive }) => `nav-link ${isActive ? 'nav-link-active' : ''}`}>{Icon ? <Icon size={18} /> : null}{label}</NavLink>)}</nav>
            <div className="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4"><p className="text-xs font-bold text-white">Langkah kecil, arah lebih jelas.</p><p className="mt-2 text-xs leading-5 text-slate-400">Lengkapi profilmu untuk analisis yang lebih relevan.</p><Link to="/profile" onClick={() => setOpen(false)} className="mt-3 inline-flex items-center gap-1 text-xs font-bold text-teal-200">Lengkapi profil →</Link></div>
            <button disabled={leaving} onClick={async () => { setLeaving(true); try { await logout(); } finally { setLeaving(false); } }} className="mt-4 flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white disabled:opacity-50"><LogOut size={18} />{leaving ? 'Keluar…' : 'Keluar'}</button>
        </aside>
        <button className={`fixed inset-0 z-40 bg-navy/40 backdrop-blur-[2px] transition-opacity duration-300 lg:hidden ${open ? 'opacity-100' : 'pointer-events-none opacity-0'}`} onClick={() => setOpen(false)} aria-label="Tutup navigasi" aria-hidden={!open} tabIndex={open ? 0 : -1} />
        <div className="min-w-0">
            <header className="sticky top-0 z-30 flex h-[72px] items-center justify-between border-b border-slate-200/80 bg-white/95 px-5 backdrop-blur lg:px-8">
                <button className="rounded-xl p-2 text-navy transition hover:bg-canvas active:scale-95 lg:hidden" onClick={() => setOpen(true)} aria-label="Buka navigasi"><Menu /></button>
                <div className="hidden items-center gap-2 text-xs text-slate-500 lg:flex"><span className="h-2 w-2 rounded-full bg-emerald-500" />Sesi aktif · Data profil tersimpan</div>
                <div className="ml-auto flex items-center gap-3"><div className="hidden text-right sm:block"><p className="text-sm font-bold text-navy">{user?.profile?.display_name || user?.name}</p><p className="text-xs text-slate-500">Akun pengguna</p></div><span className="grid h-10 w-10 place-items-center rounded-full bg-teal/10 text-sm font-extrabold text-teal">{(user?.profile?.display_name || user?.name || 'CP').slice(0, 2).toUpperCase()}</span></div>
            </header>
            <main className="mx-auto max-w-[1440px] p-5 lg:p-8"><PageTransition><Outlet /></PageTransition></main>
        </div>
    </div>;
}
