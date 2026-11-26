import { ArrowRight, BarChart3, BookOpen, CheckCircle2, Compass, Route, Search, ShieldCheck, Sparkles, Target } from 'lucide-react';
import { Link } from 'react-router-dom';
import { OccupationCard } from '../components/OccupationCard';
import { Badge, Button, EmptyState, SectionHeading, Spinner, TextLink } from '../components/ui';
import { useCatalog } from '../features/catalog/CatalogContext';
import { useOccupationQuery } from '../features/catalog/hooks';

const features = [
    { icon: Compass, title: 'Kenali arahmu', text: 'Mulai dari minat, aktivitas favorit, dan konteks pendidikanmu.', color: 'bg-teal/10 text-teal' },
    { icon: Target, title: 'Lihat kecocokan', text: 'Dapatkan skor, alasan, cakupan data, dan area yang perlu berkembang.', color: 'bg-indigo-50 text-indigo-600' },
    { icon: Route, title: 'Bangun langkah nyata', text: 'Ikuti roadmap bertahap dan catat progres yang bisa kamu kendalikan.', color: 'bg-amber-50 text-amber-700' },
];

export function HomePage() {
    const { categories, loading, error } = useCatalog();
    const catalog = useOccupationQuery(new URLSearchParams({ per_page: '6' }));
    return <>
        <section className="relative overflow-hidden bg-white">
            <div className="absolute -right-24 -top-28 h-[420px] w-[420px] rounded-full bg-teal/5 blur-2xl" />
            <div className="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-24">
                <div className="relative">
                    <Badge tone="teal"><Sparkles size={12} />Panduan karier berbasis data</Badge>
                    <h1 className="mt-6 text-4xl font-black leading-[1.08] tracking-[-.04em] text-navy sm:text-5xl xl:text-[58px]">Temukan arah karier yang <span className="text-teal">lebih jelas.</span></h1>
                    <p className="mt-6 max-w-xl text-base leading-7 text-slate-500 sm:text-lg">CareerPath membantu kamu menjelajahi profesi, memahami kesiapan, dan menyusun langkah pengembangan—tanpa harus menebak sendiri.</p>
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row"><Button to="/explore" size="lg">Mulai Eksplorasi<ArrowRight size={17} /></Button><Button to="/professions" variant="secondary" size="lg"><Search size={16} />Lihat Semua Profesi</Button></div>
                    <div className="mt-7 flex flex-wrap gap-x-6 gap-y-3 text-xs font-medium text-slate-500"><span className="flex items-center gap-1.5"><CheckCircle2 size={15} className="text-teal" />Gratis untuk mulai</span><span className="flex items-center gap-1.5"><CheckCircle2 size={15} className="text-teal" />Tanpa data sensitif</span><span className="flex items-center gap-1.5"><CheckCircle2 size={15} className="text-teal" />Penjelasan transparan</span></div>
                </div>
                <div className="relative mx-auto w-full max-w-[500px]">
                    <div className="absolute -left-6 top-16 hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:block"><div className="flex items-center gap-3"><span className="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600"><BookOpen size={19} /></span><div><p className="text-[11px] text-slate-400">Fondasi karier</p><p className="text-sm font-bold text-navy">3 langkah berikutnya</p></div></div></div>
                    <div className="surface relative overflow-hidden p-6 sm:p-8">
                        <div className="flex items-center justify-between"><div><p className="text-xs font-bold uppercase tracking-[.14em] text-teal">Contoh tampilan analisis</p><h2 className="mt-2 text-xl font-extrabold text-navy">Analis Sistem</h2><p className="mt-1 text-xs text-slate-400">Teknologi dan Digital · KBJI 2511</p></div><span className="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-50 text-indigo-600"><BarChart3 size={23} /></span></div>
                        <div className="mt-7 rounded-2xl bg-navy p-5 text-white"><div className="flex items-end justify-between"><div><p className="text-xs text-slate-400">Kesesuaian saat ini</p><p className="mt-1 text-4xl font-black">78<span className="text-lg text-teal-200">/100</span></p></div><Badge tone="teal">Cakupan data 85%</Badge></div><div className="mt-5 h-2 overflow-hidden rounded-full bg-white/10"><div className="h-full w-[78%] rounded-full bg-teal" /></div><p className="mt-3 text-[11px] leading-5 text-slate-400">Skor membantu eksplorasi, bukan jaminan pekerjaan.</p></div>
                        <div className="mt-6 grid grid-cols-3 gap-3">{[['Keterampilan', '82'], ['Pendidikan', '90'], ['Minat', '75']].map(([label, value]) => <div key={label} className="rounded-xl bg-canvas p-3"><p className="text-[10px] text-slate-500">{label}</p><p className="mt-1 text-lg font-extrabold text-navy">{value}<span className="text-[10px] font-medium text-slate-400">%</span></p></div>)}</div>
                        <div className="mt-6 flex items-center gap-3 rounded-xl border border-teal/15 bg-teal/5 p-4"><span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-teal"><Route size={17} /></span><div><p className="text-xs font-bold text-navy">Langkah berikutnya</p><p className="mt-0.5 text-[11px] text-slate-500">Perkuat analisis kebutuhan dan komunikasi.</p></div></div>
                    </div>
                    <div className="absolute -bottom-5 -right-3 hidden items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:flex"><span className="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><ShieldCheck size={19} /></span><div><p className="text-xs font-bold text-navy">Data kamu, kendalimu</p><p className="mt-0.5 text-[10px] text-slate-400">Profil dapat diperbarui kapan saja</p></div></div>
                </div>
            </div>
        </section>
        <section className="border-y border-slate-200 bg-canvas"><div className="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-5 py-7 md:grid-cols-4 lg:px-8">{[['14', 'Bidang karier'], ['30', 'Profesi awal'], ['5', 'Faktor analisis'], ['100%', 'Alasan terbuka']].map(([value, label]) => <div key={label} className="text-center"><p className="text-2xl font-black text-navy">{value}</p><p className="mt-1 text-xs text-slate-500">{label}</p></div>)}</div></section>
        <section className="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-20"><SectionHeading eyebrow="Cara CareerPath membantu" title="Dari bingung menjadi punya rencana" description="Tiga langkah sederhana untuk memahami pilihan dan mengubahnya menjadi aksi." /><div className="grid gap-5 md:grid-cols-3">{features.map((feature, index) => <div key={feature.title} className="surface p-6"><div className="flex items-center justify-between"><span className={`grid h-12 w-12 place-items-center rounded-2xl ${feature.color}`}><feature.icon size={23} /></span><span className="text-xs font-black text-slate-300">0{index + 1}</span></div><h3 className="mt-5 text-lg font-extrabold text-navy">{feature.title}</h3><p className="mt-2 text-sm leading-6 text-slate-500">{feature.text}</p></div>)}</div></section>
        <section className="bg-white py-16 lg:py-20"><div className="mx-auto max-w-7xl px-5 lg:px-8"><SectionHeading title="Jelajahi bidang yang menarik" description="Pilih bidang untuk melihat profesi dan jalur pengembangannya." action={<TextLink to="/professions">Semua bidang</TextLink>} />
            {loading ? <Spinner /> : error ? <EmptyState title="Katalog belum tersedia" description={error} /> : <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{categories.slice(0, 6).map((category) => <Link key={category.id} to={`/professions?category=${category.id}`} className="group flex items-center justify-between rounded-2xl border border-slate-200 p-4 transition hover:border-teal/40 hover:bg-teal/5"><div className="flex items-center gap-3"><span className="grid h-11 w-11 place-items-center rounded-xl bg-canvas text-teal"><BookOpen size={19} /></span><div><p className="text-sm font-bold text-navy group-hover:text-teal-dark">{category.name}</p><p className="mt-1 text-xs text-slate-400">{category.profession_count} profesi tersedia</p></div></div><ArrowRight size={16} className="text-slate-300 group-hover:text-teal" /></Link>)}</div>}
        </div></section>
        <section className="mx-auto max-w-7xl px-5 py-16 lg:px-8"><SectionHeading eyebrow="Direktori profesi" title="Mulai dari yang ingin kamu kenal" description="Baca tanggung jawab, keterampilan, lingkungan kerja, dan roadmap-nya." action={<TextLink to="/professions">Buka direktori</TextLink>} />
            {catalog.loading ? <Spinner /> : catalog.error ? <EmptyState title="Profesi belum dapat dimuat" description={catalog.error} /> : <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{catalog.items.map((occupation) => <OccupationCard key={occupation.id} occupation={occupation} />)}</div>}
        </section>
        <section className="mx-auto max-w-7xl px-5 pb-20 lg:px-8"><div className="relative overflow-hidden rounded-3xl bg-navy px-6 py-10 text-white sm:px-10 lg:flex lg:items-center lg:justify-between"><div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-teal/20 blur-2xl" /><div className="relative"><p className="text-xs font-bold uppercase tracking-[.15em] text-teal-200">Langkah pertama tidak harus sempurna</p><h2 className="mt-3 max-w-2xl text-2xl font-extrabold sm:text-3xl">Mulai dengan apa yang kamu tahu tentang dirimu.</h2><p className="mt-3 max-w-xl text-sm leading-6 text-slate-400">Informasi yang belum diisi tetap ditandai sebagai belum diketahui, bukan dianggap nol.</p></div><Button to="/register" size="lg" className="relative mt-6 lg:mt-0">Buat Akun Gratis<ArrowRight size={17} /></Button></div></section>
    </>;
}
