# CareerPath

CareerPath adalah website eksplorasi karier berbahasa Indonesia. Pengguna dapat mengenal profesi, melengkapi profil, menjalankan analisis kecocokan yang deterministik, menyimpan profesi, melihat riwayat analisis, dan menyusun roadmap pengembangan. Admin memiliki panel editorial untuk mengelola katalog dan alur publikasi konten.

Proyek ini merupakan MVP/prototipe. Nama dan kode profesi merujuk KBJI 2026 dari BPS, sedangkan deskripsi, skill, pendidikan, sertifikasi, industri, tempat kerja, dan roadmap adalah konten editorial demonstrasi. Skor kecocokan bukan probabilitas diterima kerja.

## Fitur utama

- Eksplorasi profesi dan kategori karier tanpa perlu login.
- Pendaftaran, login, logout, dan sesi berbasis cookie Sanctum.
- Profil pengguna, minat, preferensi kerja, skill, pengalaman, dan sertifikasi.
- Career Matching dengan skor keselaruhan, cakupan data, alasan, skill gap, dan persyaratan wajib.
- Skill yang belum diisi tetap berstatus `unknown`, bukan dianggap nol.
- Profesi tersimpan, riwayat analisis yang tidak dapat diubah, dan roadmap pribadi.
- Panel Admin untuk katalog, persyaratan profesi, roadmap, review, pengguna, dan audit log.
- Alur publikasi: `Draft → Review → Published → Archived`.
- Hanya konten berstatus `Published` yang tampil di publik dan dipakai pada matching.

## Teknologi

- Laravel 13
- PHP 8.3 atau lebih baru
- React 19 + TypeScript
- Vite 8 + Tailwind CSS 4
- Laravel Sanctum
- MySQL 8 untuk pengembangan di PC, atau SQLite untuk Termux/Redmi
- PHPUnit untuk pengujian backend

## Cara paling cepat: buka dari Redmi tanpa memasang server di HP

Gunakan cara ini jika CareerPath sudah berjalan di PC/Laragon. Redmi hanya perlu Chrome dan terhubung ke Wi-Fi yang sama dengan PC.

### 1. Siapkan aset build di PC

Jalankan dari folder proyek:

```bash
npm ci
npm run build
```

Hentikan Vite development server dengan menekan `Ctrl+C` di terminal tempat `npm run dev` berjalan. Jika file `public/hot` masih ada, ubah namanya menjadi `public/hot.disabled`. Tanpa langkah ini, browser Redmi dapat mencoba membuka alamat Vite lokal PC dan halaman tampil kosong.

### 2. Cari alamat IPv4 PC

Di Command Prompt atau PowerShell Windows:

```powershell
ipconfig
```

Catat alamat `IPv4 Address`, misalnya `192.168.1.10`. Alamat ini bisa berubah setelah router atau PC dimulai ulang.

### 3. Sesuaikan `.env` di PC

Ganti alamat contoh dengan IPv4 PC Anda:

```dotenv
APP_URL=http://192.168.1.10:8000

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,127.0.0.1:8123,localhost:8123,127.0.0.1:8000,localhost:8000,192.168.1.10:8000
```

Penting: alamat IP PC harus ditambahkan ke `SANCTUM_STATEFUL_DOMAINS`. Jika tidak, login dari Redmi dapat menampilkan error `Session store not set on request`.

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

### 4. Izinkan port 8000 di Windows

Buka PowerShell sebagai Administrator, lalu jalankan:

```powershell
New-NetFirewallRule -DisplayName "CareerPath 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow -Profile Private
```

Aturan ini hanya untuk jaringan Private/rumah. Jangan membuka port ini ke jaringan publik.

### 5. Jalankan server yang dapat diakses Redmi

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Biarkan terminal tetap terbuka. MySQL/Laragon juga harus tetap berjalan.

### 6. Buka website di Redmi

1. Hubungkan Redmi ke Wi-Fi yang sama dengan PC.
2. Buka Chrome.
3. Masuk ke `http://192.168.1.10:8000/login`, sesuaikan IP-nya.
4. Klik **Akun Admin** atau **Akun Pengguna** untuk mengisi form otomatis.
5. Tekan **Masuk**.

Jangan membuka `127.0.0.1` dari Redmi untuk metode ini, karena alamat tersebut menunjuk ke HP itu sendiri, bukan ke PC.

Jika halaman tidak terbuka, periksa apakah PC dan Redmi memakai Wi-Fi yang sama, apakah PC menyala, apakah aturan firewall sudah dibuat, dan apakah router memakai fitur isolasi perangkat/AP isolation.

## Instalasi lengkap di Redmi dengan Termux

Gunakan cara ini jika website akan dijalankan langsung di HP tanpa PC. Panduan ini memakai SQLite agar tidak perlu memasang MySQL di Android.

Langkah Termux belum diuji pada perangkat Redmi fisik. Migrasi, seed, dan struktur database SQLite sudah diverifikasi terpisah di lingkungan pengembangan.

### 1. Instal Termux

Instal Termux dari sumber resmi:

- F-Droid: <https://f-droid.org/packages/com.termux/>
- GitHub Releases: <https://github.com/termux/termux-app/releases>

Hindari versi Termux lama dari Play Store karena beberapa fitur dan paketnya sudah tidak didukung.

### 2. Siapkan proyek di Redmi

Buat ZIP dari **isi folder proyek** `careerpath`, bukan dari folder induknya, sehingga file `artisan` berada di akar ZIP.

Kecualikan file/folder berikut saat membuat ZIP:

```text
.env
vendor/
node_modules/
storage/logs/*
storage/framework/cache/data/*
storage/framework/sessions/*
storage/framework/views/*
public/hot
```

Jangan menghapus struktur folder `storage/framework` dan `bootstrap/cache`; cukup keluarkan isi runtime-nya. Folder `public/build/` boleh disertakan. Jika disertakan dan masih berisi `manifest.json`, proses build frontend di HP dapat dilewati.

Salin ZIP ke folder `Download` Redmi. Lalu buka Termux dan jalankan:

```bash
pkg update
pkg upgrade -y
pkg install php composer nodejs git unzip -y
termux-setup-storage
```

Izinkan akses penyimpanan ketika Android meminta izin. Setelah itu:

```bash
cd ~
cp ~/storage/shared/Download/careerpath.zip .
unzip careerpath.zip -d careerpath
cd careerpath
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
```

Pastikan perintah `ls` menampilkan `artisan`, `composer.json`, dan `package.json`. Jika ZIP membuat satu folder tambahan, masuk ke folder tersebut terlebih dahulu.

### 3. Buat konfigurasi lokal

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Di editor `nano`, sesuaikan bagian berikut:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
DB_DATABASE=/data/data/com.termux/files/home/careerpath/database/database.sqlite

SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

Nilai `DB_HOST`, `DB_USERNAME`, dan `DB_PASSWORD` tidak dipakai ketika `DB_CONNECTION=sqlite`.

Simpan perubahan di `nano` dengan menekan `Ctrl+O`, lalu `Enter`, dan keluar dengan `Ctrl+X`.

### 4. Pasang dependensi dan siapkan database

```bash
composer install --no-dev --optimize-autoloader
touch database/database.sqlite
php artisan migrate --seed --force
```

Perintah ini membuat database SQLite baru di dalam folder proyek dan mengisi katalog serta akun demo. Jangan menjalankannya pada database yang berisi data yang ingin dipertahankan.

### 5. Build frontend jika belum tersedia

Jika `public/build/manifest.json` sudah ada, lewati langkah ini. Jika belum:

```bash
npm ci
npm run build
```

Vite 8 membutuhkan Node.js 22.12 atau lebih baru, sedangkan beberapa dependensi opsional pada proyek ini membutuhkan Node.js 22.13. Gunakan Node.js 22.13 atau lebih baru. Paket `nodejs` dari repositori Termux yang diperbarui seharusnya sudah memenuhi kebutuhan tersebut.

### 6. Jalankan website

```bash
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

Buka Chrome di Redmi dan masuk ke:

```text
http://127.0.0.1:8000
```

Biarkan sesi Termux tetap terbuka. Jika Android menghentikan Termux karena penghematan baterai, buka:

`Pengaturan Android → Aplikasi → Termux → Baterai → Tidak dibatasi`

Pada notifikasi Termux terdapat opsi **Acquire wakelock** untuk membantu mencegah HP tertidur saat server digunakan.

### 7. Menjalankan kembali di lain waktu

Buka Termux, lalu jalankan:

```bash
cd ~/careerpath
php artisan serve --host=127.0.0.1 --port=8000
```

Tidak perlu mengulang migrasi atau seed. Data akun, profil, analisis, profesi tersimpan, dan roadmap tersimpan di `database/database.sqlite`.

Untuk mencadangkan data SQLite:

```bash
cp database/database.sqlite ~/storage/shared/Download/careerpath-backup.sqlite
```

Jangan membagikan file backup tersebut karena berisi data akun dan profil pengguna.

### Menambahkan shortcut ke layar utama Redmi

1. Buka alamat CareerPath di Chrome.
2. Tekan menu tiga titik.
3. Pilih **Tambahkan ke layar utama** atau **Install app** jika tersedia.
4. Beri nama `CareerPath`.

Ini membuat shortcut, bukan aplikasi offline. Server PC/Termux tetap harus berjalan setiap kali website dibuka.

## Instalasi di PC/Laragon

### 1. Buat database baru

Buat database kosong dengan nama `careerpath_dev` melalui HeidiSQL/phpMyAdmin. Gunakan database baru ini dan jangan mengubah database lain yang sudah berisi data.

Jika database belum ada, SQL berikut boleh dijalankan pada server MySQL lokal:

```sql
CREATE DATABASE careerpath_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Konfigurasi proyek

Jalankan dari folder proyek. Di Command Prompt Windows:

```bat
copy .env.example .env
composer install
php artisan key:generate
```

Jika `.env` sudah ada, jangan menimpanya. Sesuaikan nilai berikut:

```dotenv
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careerpath_dev
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,127.0.0.1:8123,localhost:8123,127.0.0.1:8000,localhost:8000
```

Isi `DB_PASSWORD` sesuai MySQL lokal Anda. Pada instalasi Laragon bawaan, pengguna sering kali `root` tanpa password. Jangan membagikan file `.env` karena berisi `APP_KEY` dan konfigurasi rahasia.

### 3. Migrasi, seed, dan build

```bash
php artisan migrate
php artisan db:seed
npm ci
npm run build
```

Seeder menyediakan 30 profesi, 90 skill, 53 program pendidikan, 11 sertifikasi, dan 2 akun demo. Seeder dapat dijalankan ulang, tetapi password akun demo akan dikembalikan ke nilai bawaan.

Jangan gunakan `php artisan migrate:fresh` pada database yang berisi data yang ingin dipertahankan karena perintah itu menghapus seluruh tabel.

### 4. Jalankan server

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000`. Untuk pengembangan frontend dengan hot reload, jalankan juga `npm run dev`; proxy Vite pada proyek ini diarahkan ke port backend `8123`, sehingga backend development sebaiknya dijalankan dengan:

```bash
php artisan serve --host=127.0.0.1 --port=8123
```

## Akun demo lokal

Akun berikut hanya untuk pengembangan lokal:

| Peran | Email | Password | Halaman setelah login |
| --- | --- | --- | --- |
| Admin | `admin@careerpath.local` | `CareerPath#2026` | `/admin` |
| Pengguna | `user@careerpath.local` | `UserCareer#2026` | `/dashboard` |

Di halaman `/login` juga tersedia tombol **Akun Admin** dan **Akun Pengguna** yang mengisi email serta password secara otomatis.

Jangan memakai akun demo pada website publik. Ganti password dan hapus kebutuhan akan akun demo sebelum deployment. Jika README ini akan dipublikasikan bersama source code, pertimbangkan untuk menghapus tabel kredensial demo dari dokumentasi publik.

## Panduan penggunaan

### Pengunjung

- `/` — halaman utama dan gambaran website.
- `/professions` — direktori profesi.
- `/professions/{id}` — detail profesi, aktivitas, skill, pendidikan, sertifikasi, dan tempat kerja.
- `/explore` — eksplorasi berdasarkan kategori.
- `/how-it-works` — penjelasan metode analisis.

### Pengguna terdaftar

1. Buka `/register` untuk membuat akun, atau login melalui `/login`. Password akun baru minimal 8 karakter dan harus diketik ulang pada kolom konfirmasi.
2. Lengkapi data di `/profile` dan `/onboarding`.
3. Buka `/matching` untuk menjalankan analisis.
4. Periksa skor, cakupan data, alasan, skill yang belum diketahui, skill gap, dan persyaratan wajib.
5. Simpan profesi yang menarik melalui `/saved`.
6. Buka `/development` untuk melihat jalur pendidikan dan sertifikasi.
7. Mulai roadmap melalui `/roadmaps` dan perbarui progres setiap tahap.
8. Gunakan `/history` untuk melihat hasil analisis sebelumnya. Riwayat menyimpan snapshot input, katalog, kebijakan, hasil, dan versi algoritme.

Skor keselaruhan bukan prediksi kelulusan atau penerimaan kerja. Persyaratan wajib dievaluasi terpisah dari skor relevansi, dan data yang belum diisi tidak dianggap sebagai skill level nol.

### Admin

1. Login dengan akun Admin, lalu buka `/admin`.
2. Kelola taksonomi dan katalog melalui menu **Profesi**, **Taksonomi**, **Keterampilan**, **Workplace**, dan **Sumber Data**.
3. Untuk menerbitkan konten, ubah status dari `Draft` ke `Review`, kemudian setujui menjadi `Published`.
4. Gunakan **Arsip** untuk menyembunyikan konten tanpa menghapusnya.
5. Atur persyaratan wajib/rekomendasi profesi dan tahap roadmap melalui menu terkait.
6. Pantau keputusan editorial di **Review Konten**, status akun di **Pengguna**, dan jejak perubahan di audit log.

Konten berstatus `Draft`, `Review`, atau `Archived` tidak boleh dipakai sebagai katalog publik atau input matching.

## Pemecahan masalah

### `Session store not set on request`

Penyebabnya adalah host dan port yang dibuka browser belum masuk ke `SANCTUM_STATEFUL_DOMAINS`.

Contoh:

- `127.0.0.1:8000` → tambahkan `127.0.0.1:8000`.
- `192.168.1.10:8000` → tambahkan `192.168.1.10:8000`.

Setelah mengubah `.env`, jalankan `php artisan config:clear`, hentikan dan jalankan ulang server, lalu refresh halaman login.

### Error 419 atau halaman kedaluwarsa

Pastikan cookie browser aktif, membuka website melalui satu alamat yang konsisten, dan menekan tombol login setelah halaman selesai dimuat. Tutup halaman lalu buka kembali jika token CSRF kedaluwarsa.

### Halaman kosong atau `Vite manifest not found`

Jalankan `npm run build` dan pastikan `public/build/manifest.json` ada. Jika ingin memakai hasil build, hentikan `npm run dev` serta nonaktifkan `public/hot` dengan mengubah namanya menjadi `public/hot.disabled`.

### Redmi tidak dapat membuka alamat IP PC

Periksa Wi-Fi, IPv4 PC, status server, firewall, dan isolasi perangkat pada router. Server PC harus dijalankan dengan `--host=0.0.0.0`, bukan hanya `127.0.0.1`.

### `SQLSTATE ... Connection refused`

Pastikan MySQL/Laragon berjalan dan konfigurasi database pada `.env` sudah benar. Pada Termux, pastikan `DB_CONNECTION=sqlite` dan file `database/database.sqlite` sudah dibuat.

### Terlalu banyak percobaan login

Tunggu sekitar satu menit. Pembatasan login adalah fitur keamanan untuk mencegah percobaan berulang.

### Server Termux berhenti sendiri

Matikan pembatasan baterai untuk Termux, aktifkan **Acquire wakelock**, dan jangan menutup sesi Termux. Android dapat menghentikan proses background untuk menghemat daya.

## Perintah pengembangan dan pengujian

```bash
npx tsc --noEmit
npm run build
php artisan test
```

Pengujian backend menggunakan database MySQL khusus `careerpath_test` sesuai `phpunit.xml`, bukan database development. Sesuaikan kredensial database test pada lingkungan lokal jika diperlukan. Test suite tidak diperlukan untuk menjalankan website harian.

## Keamanan sebelum publikasi

- Ganti password akun demo dan nonaktifkan akun yang tidak diperlukan.
- Jangan membagikan `.env`, `APP_KEY`, password database, atau backup berisi data pengguna.
- Gunakan HTTPS dan domain yang benar saat deployment.
- Set `APP_ENV=production` dan `APP_DEBUG=false` pada server publik.
- Gunakan kredensial database dengan hak akses terbatas, bukan akun administratif jika tidak diperlukan.
- Lakukan backup database secara rutin.
- Validasi ahli dan izin data sebelum memperlakukan katalog demo sebagai konten produksi.
