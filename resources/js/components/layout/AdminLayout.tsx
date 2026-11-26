import { NavLink, Outlet } from 'react-router-dom';
import { Activity, ArrowLeft, BookOpenCheck, FileClock, FolderHeart, LayoutDashboard, LogOut, Settings2, Users } from 'lucide-react';
import { useAuth } from '../../features/auth/AuthContext';
import { Logo } from '../ui';

const links = [
    ['/admin', 'Ringkasan', LayoutDashboard], ['/admin/resources/occupations', 'Profesi', BookOpenCheck],
    ['/admin/resources/categories', 'Taksonomi', Settings2], ['/admin/resources/skills', 'Keterampilan', Activity],
    ['/admin/resources/workplaces', 'Workplace', FolderHeart], ['/admin/resources/sources', 'Sumber Data', BookOpenCheck],
    ['/admin/reviews', 'Review Konten', FileClock], ['/admin/users', 'Pengguna', Users],
] as const;

export function AdminLayout() {
    const { user, logout } = useAuth();
    return <div className="min-h-screen lg:grid lg:grid-cols-[248px_1fr]">
        <aside className="flex min-h-screen flex-col bg-navy p-5 lg:sticky lg:top-0 lg:h-screen">
            <div className="px-2"><Logo light /></div>
            <div className="mt-8 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Administrasi</div>
            <nav className="mt-3 space-y-1" aria-label="Navigasi Admin">{links.map(([to, label, Icon]) => <NavLink key={to} to={to} end={to === '/admin'} className={({ isActive }) => `nav-link ${isActive ? 'nav-link-active' : ''}`}><Icon size={18} />{label}</NavLink>)}</nav>
            <div className="mt-auto space-y-3 border-t border-white/10 pt-4"><NavLink to="/dashboard" className="nav-link"><ArrowLeft size={17} />Kembali ke Pengguna</NavLink><button onClick={() => void logout()} className="nav-link w-full"><LogOut size={17} />Keluar</button><div className="px-3 pt-2 text-xs text-slate-500">{user?.name} · Admin</div></div>
        </aside>
        <div className="min-w-0 bg-canvas"><main className="mx-auto max-w-[1500px] p-5 lg:p-8"><Outlet /></main></div>
    </div>;
}
