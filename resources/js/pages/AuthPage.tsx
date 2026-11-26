import { useState } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ArrowRight, Eye, EyeOff, LoaderCircle, LockKeyhole, Mail, ShieldCheck, UserRound } from 'lucide-react';
import { useAuth } from '../features/auth/AuthContext';
import { errorMessage, fieldErrors } from '../lib/api';
import { Alert, Badge, Button, Field, Input, Logo } from '../components/ui';

export function AuthPage({ mode }: { mode: 'login' | 'register' }) {
    const { user, login, register } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [remember, setRemember] = useState(true);
    const [show, setShow] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const errors = fieldErrors(error);
    const isRegister = mode === 'register';
    if (user) return <Navigate to={user.role === 'admin' ? '/admin' : user.profile?.onboarding_completed ? '/dashboard' : '/onboarding'} replace />;

    async function submit(event: React.FormEvent) {
        event.preventDefault();
        setBusy(true);
        setError(null);
        try {
            const data = isRegister ? await register(name, email, password, confirmation) : await login(email, password, remember);
            const from = (location.state as { from?: string } | null)?.from;
            navigate(data.role === 'admin' ? '/admin' : from || (data.profile?.onboarding_completed ? '/dashboard' : '/onboarding'), { replace: true });
        } catch (cause) {
            setError(errorMessage(cause));
        } finally {
            setBusy(false);
        }
    }

    return <div className="grid min-h-screen lg:grid-cols-[1fr_1.05fr]">
        <div className="auth-panel relative hidden overflow-hidden bg-navy p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div className="absolute -right-32 -top-32 h-[500px] w-[500px] rounded-full border-[80px] border-teal/10" /><div className="absolute -bottom-20 left-20 h-64 w-64 rounded-full bg-sky-brand/10 blur-3xl" />
            <Logo light />
            <div className="relative max-w-lg"><Badge tone="teal">Eksplorasi dengan makna</Badge><h1 className="mt-6 text-4xl font-black leading-tight tracking-tight">Karier bukan satu jawaban. Ini ruang untuk mencarinya.</h1><p className="mt-5 text-base leading-7 text-slate-400">Bangun pemahaman tentang dirimu, cocokkan dengan karakter profesi, lalu ambil langkah yang masuk akal untukmu.</p><div className="mt-9 space-y-4">{['Skor disertai alasan dan cakupan data', 'Keterampilan yang belum diisi tidak dianggap nol', 'Persyaratan wajib ditampilkan terpisah'].map((text) => <div key={text} className="flex items-center gap-3 text-sm text-slate-300"><span className="grid h-7 w-7 place-items-center rounded-full bg-teal/20 text-teal-200"><ShieldCheck size={15} /></span>{text}</div>)}</div></div>
            <p className="relative text-xs text-slate-500">CareerPath membantu pengambilan keputusan, bukan menggantikannya.</p>
        </div>
        <div className="auth-panel flex items-center justify-center bg-canvas px-5 py-10">
            <div className="w-full max-w-md"><div className="mb-8 lg:hidden"><Logo /></div><p className="text-xs font-bold uppercase tracking-[.16em] text-teal">{isRegister ? 'Mulai perjalananmu' : 'Selamat datang kembali'}</p><h2 className="mt-2 text-3xl font-black tracking-tight text-navy">{isRegister ? 'Buat akun CareerPath' : 'Masuk ke akunmu'}</h2><p className="mt-2 text-sm leading-6 text-slate-500">{isRegister ? 'Cukup nama, email, dan kata sandi. Tidak perlu NIK atau data sensitif.' : 'Lanjutkan eksplorasi dan lihat perkembangan profil kariermu.'}</p>
                <form onSubmit={submit} className="surface mt-7 space-y-4 p-6">
                    {error && <Alert>{error}</Alert>}
                    {isRegister && <Field label="Nama lengkap" htmlFor="name" error={errors.name?.[0]}><div className="relative"><UserRound className="absolute left-3.5 top-3.5 text-slate-400" size={17} /><Input id="name" value={name} onChange={(event) => setName(event.target.value)} placeholder="Nama panggilanmu" className="pl-10" autoComplete="name" required minLength={2} /></div></Field>}
                    <Field label="Alamat email" htmlFor="email" error={errors.email?.[0]}><div className="relative"><Mail className="absolute left-3.5 top-3.5 text-slate-400" size={17} /><Input id="email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="nama@email.com" className="pl-10" autoComplete="email" required /></div></Field>
                    <Field label="Kata sandi" htmlFor="password" error={errors.password?.[0]} hint={isRegister ? 'Minimal 8 karakter.' : undefined}><div className="relative"><LockKeyhole className="absolute left-3.5 top-3.5 text-slate-400" size={17} /><Input id="password" type={show ? 'text' : 'password'} value={password} onChange={(event) => setPassword(event.target.value)} placeholder="••••••••" className="px-10" autoComplete={isRegister ? 'new-password' : 'current-password'} required minLength={isRegister ? 8 : undefined} /><button type="button" onClick={() => setShow(!show)} className="absolute right-3.5 top-3.5 rounded-md text-slate-400 transition hover:text-navy active:scale-90" aria-label={show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}>{show ? <EyeOff size={17} /> : <Eye size={17} />}</button></div></Field>
                    {isRegister && <Field label="Ulangi kata sandi" htmlFor="confirmation" error={errors.password_confirmation?.[0]}><Input id="confirmation" type={show ? 'text' : 'password'} value={confirmation} onChange={(event) => setConfirmation(event.target.value)} className="px-3.5" autoComplete="new-password" required minLength={8} /></Field>}
                    {!isRegister && <label className="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" checked={remember} onChange={(event) => setRemember(event.target.checked)} className="h-4 w-4 accent-teal" />Ingat saya di perangkat ini</label>}
                    <Button type="submit" size="lg" className="group w-full" disabled={busy}>{busy && <LoaderCircle className="animate-spin" size={16} />}{busy ? 'Memproses…' : isRegister ? 'Buat Akun' : 'Masuk'}{!busy && <ArrowRight size={16} className="transition-transform duration-200 group-hover:translate-x-0.5" />}</Button>
                </form>
                {!isRegister && <div className="mt-5 rounded-xl border border-dashed border-slate-300 p-4"><p className="text-xs font-bold text-navy">Mencoba prototipe lokal?</p><p className="mt-1 text-[11px] leading-5 text-slate-500">Isi otomatis akun demo. Kredensial ini hanya untuk pengembangan lokal.</p><div className="mt-3 flex gap-2"><button onClick={() => { setEmail('user@careerpath.local'); setPassword('UserCareer#2026'); }} className="rounded-lg bg-white px-3 py-1.5 text-[11px] font-bold text-teal shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md active:scale-95">Akun Pengguna</button><button onClick={() => { setEmail('admin@careerpath.local'); setPassword('CareerPath#2026'); }} className="rounded-lg bg-white px-3 py-1.5 text-[11px] font-bold text-navy shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md active:scale-95">Akun Admin</button></div></div>}
                <p className="mt-6 text-center text-sm text-slate-500">{isRegister ? 'Sudah punya akun?' : 'Belum punya akun?'} <Link to={isRegister ? '/login' : '/register'} className="font-bold text-teal hover:text-teal-dark">{isRegister ? 'Masuk di sini' : 'Daftar gratis'}</Link></p>
            </div>
        </div>
    </div>;
}
