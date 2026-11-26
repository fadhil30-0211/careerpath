import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { Activity, ArrowLeft, BookOpenCheck, FileClock, FolderHeart, LayoutDashboard, LogOut, Menu, Settings2, Users } from 'lucide-react';
import { useAuth } from '../../features/auth/AuthContext';
import { Logo, PageTransition } from '../ui';

const links = [
    ['/admin', 'Ringkasan', LayoutDashboard], ['/admin/resources/occupations', 'Profesi', BookOpenCheck],
    ['/admin/resources/categories', 'Taksonomi', Settings2], ['/admin/resources/skills', 'Keterampilan', Activity],
    ['/admin/resources/workplaces', 'Workplace', FolderHeart], ['/admin/resources/sources', 'Sumber Data', BookOpenCheck],
    ['/admin/reviews', 'Review Konten', FileClock], ['/admin/users', 'Pengguna', Users],
] as const;

export function AdminLayout() {
    const { user, logout } = useAuth();
    const location = useLocation();
    const [open, setOpen] = useState(false);
    useEffect(() => setOpen(false), [location.pathname]);
    useEffect(() => {
        if (!open) return;
        const closeOnEscape = (event: KeyboardEvent) => { if (event.key === 'Escape') setOpen(false); };
        window.addEventListener('keydown', closeOnEscape);
        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [open]);
    return <div className="min-h-screen bg-canvas lg:pl-[248px]">
        <aside id="admin-navigation" className={`fixed inset-y-0 left-0 z-50 flex w-[248px] flex-col overflow-y-auto bg-navy p-5 transition-transform duration-300 ease-out lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
            <div className="px-2"><Logo light /></div>
            <div className="mt-8 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Administrasi</div>
            <nav className="mt-3 space-y-1" aria-label="Navigasi Admin">{links.map(([to, label, Icon]) => <NavLink key={to} to={to} end={to === '/admin'} onClick={() => setOpen(false)} className={({ isActive }) => `nav-link ${isActive ? 'nav-link-active' : ''}`}><Icon size={18} />{label}</NavLink>)}</nav>
            <div className="mt-auto space-y-3 border-t border-white/10 pt-4"><NavLink to="/dashboard" onClick={() => setOpen(false)} className="nav-link"><ArrowLeft size={17} />Kembali ke Pengguna</NavLink><button onClick={() => void logout()} className="nav-link w-full"><LogOut size={17} />Keluar</button><div className="px-3 pt-2 text-xs text-slate-500">{user?.name} · Admin</div></div>
        </aside>
        <button className={`fixed inset-0 z-40 bg-navy/40 backdrop-blur-[2px] transition-opacity duration-300 lg:hidden ${open ? 'opacity-100' : 'pointer-events-none opacity-0'}`} onClick={() => setOpen(false)} aria-label="Tutup navigasi" aria-hidden={!open} tabIndex={open ? 0 : -1} />
        <div className="min-w-0">
            <header className="sticky top-0 z-30 flex h-[72px] items-center justify-between border-b border-slate-200/80 bg-white/95 px-5 backdrop-blur lg:px-8">
                <button className="grid h-11 w-11 place-items-center rounded-xl text-navy transition hover:bg-canvas active:scale-95 lg:hidden" onClick={() => setOpen(true)} aria-label="Buka navigasi" aria-controls="admin-navigation" aria-expanded={open}><Menu /></button>
                <div className="hidden items-center gap-2 text-xs text-slate-500 lg:flex"><span className="h-2 w-2 rounded-full bg-emerald-500" />Sesi admin aktif · Data katalog tersimpan</div>
                <div className="ml-auto flex items-center gap-3"><div className="hidden text-right sm:block"><p className="text-sm font-bold text-navy">{user?.name}</p><p className="text-xs text-slate-500">Administrator</p></div><span className="grid h-10 w-10 place-items-center rounded-full bg-teal/10 text-sm font-extrabold text-teal">{(user?.name || 'AD').slice(0, 2).toUpperCase()}</span></div>
            </header>
            <main className="mx-auto max-w-[1500px] p-5 lg:p-8"><PageTransition><Outlet /></PageTransition></main>
        </div>
    </div>;
}
