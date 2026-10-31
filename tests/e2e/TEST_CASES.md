# AIMATRIK — Public Black-box Test Cases

Phase 3 berstatus **NOT RUN**: hanya validasi statis pada implementasi ini. Pengguna melaporkan full suite Phase 2 **11 PASS, 0 FAIL, 0 SKIP (3 menit)**. Status Phase 1 dipertahankan sesuai pencatatan sebelumnya.

| ID | Test Case | Preconditions | Steps | Expected Result | Actual Result | Status | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
| BB-01 | Landing page | Laravel lokal berjalan; Chromium terpasang | Buka `/`; periksa branding, navbar, section utama dan `asset-model.png` | HTTP 200; AIMATRIK tampil; section tersedia; gambar termuat dengan naturalWidth > 0; tidak ada JavaScript page error | Belum dijalankan | NOT RUN | `public.spec.js`: BB-01; bukti runtime belum tersedia |
| BB-02 | Domain accordion | Landing page dapat dibuka; Bootstrap CDN dapat diakses | Periksa jumlah/ID/target; buka lalu tutup keenam accordion | Tepat 6 item; ID/target unik; aria sesuai; setiap panel terbuka/tertutup; tidak ada JavaScript page error | Belum dijalankan | NOT RUN | `public.spec.js`: BB-02; bukti runtime belum tersedia |
| BB-03 | Sustainability | Landing page dapat dibuka | Buka accordion Keberlanjutan (SA); baca tujuan, kapabilitas dan praktik | Tujuan sesuai requirement; tepat 2 kapabilitas SA-1/SA-2 dan 4 praktik SA-1.1/SA-1.2/SA-2.1/SA-2.2; seluruh item terlihat | Belum dijalankan | NOT RUN | `public.spec.js`: BB-03; bukti runtime belum tersedia |
| BB-04 | Responsive layout (manual) | Laravel lokal berjalan; desktop dan perangkat/viewport mobile tersedia | Ikuti checklist manual di bawah | Navbar, gambar framework dan accordion terbaca/berfungsi; tidak ada overflow horizontal atau konten penting terpotong | Belum dijalankan | NOT RUN | Isi ukuran viewport dan screenshot saat pemeriksaan manual |
| BB-05 | Autentikasi | Laravel dan layanan session/cache/database siap; akun testing aktif sudah ada untuk login valid; env `PW_ADMIN_EMAIL` dan `PW_ADMIN_PASSWORD` tersedia untuk login valid/logout | Buka `/login`; kirim credential invalid acak; pada context terpisah login valid; buka dropdown akun dan logout; akses `/admin/home` kembali | Login tersedia; invalid menampilkan kegagalan autentikasi; valid menuju dashboard; logout menuju beranda/login dan akses admin kembali menuju login. Tanpa credential env, hanya login valid/logout di-skip dengan alasan jelas | Belum dijalankan | NOT RUN | `auth.spec.js`: tiga skenario BB-05; bukti runtime belum tersedia |
| BB-06 | Protected routes | Context baru tanpa login | Buka `/admin/home`, `/admin/daftar-penilaian`, `/admin/master-data/domain` secara terpisah | Tiap route menuju form login atau menolak dengan HTTP 401/403; HTTP 404/500 tidak dianggap lulus | Belum dijalankan | NOT RUN | `auth.spec.js`: tiga skenario BB-06; bukti runtime belum tersedia |

## BB-04 — Checklist manual

- [ ] Desktop (misalnya 1440 × 900): navbar dan section utama tertata sesuai desain existing.
- [ ] Mobile (misalnya 390 × 844): navbar ringkas tampil; menu dapat dibuka/ditutup dan link dapat dipakai.
- [ ] Desktop dan mobile: gambar framework original termuat, proporsional, dan tidak terpotong.
- [ ] Desktop dan mobile: keenam accordion dapat dibuka/ditutup; seluruh konten Sustainability dapat dibaca.
- [ ] Desktop dan mobile: tidak ada horizontal overflow atau konten penting terpotong.

## Menjalankan secara lokal setelah fase implementasi

Gunakan server Laravel yang sudah berjalan; konfigurasi tidak menjalankan server secara otomatis. Default base URL: `http://127.0.0.1:8000`; opsional override melalui `PW_BASE_URL` menggunakan host yang memang diizinkan aplikasi. Jangan mengubah allowlist untuk menjalankan test.

```powershell
npm.cmd ci --ignore-scripts
npx.cmd playwright install chromium
npm.cmd run test:e2e
```

Sediakan `PW_ADMIN_EMAIL` dan `PW_ADMIN_PASSWORD` melalui environment terminal menggunakan akun testing aktif yang sudah ada. Jangan menaruh credential di source atau instrumen ini. Suite tidak membuat akun/fixture, tidak membuka assessment, dan tidak melakukan CRUD admin. Login/logout serta login invalid menggunakan mekanisme session/cache existing.

Chromium berjalan headless dengan 1 worker, tanpa retry/video. Reporter berupa list. Screenshot hanya saat gagal; trace hanya dipertahankan saat gagal. Artefak berada di `node_modules/.cache/playwright-results/`, yang sudah diabaikan Git. Screenshot/trace dapat memuat data akun; simpan lokal dan jangan membagikannya tanpa pemeriksaan.

Catat hasil aktual setelah eksekusi: PASS/FAIL/SKIP, alasan skip, dan lokasi artefak kegagalan pada kolom Evidence. Jangan mengubah status menjadi PASS berdasarkan validasi statis saja. CDN Bootstrap dan aset eksternal existing memerlukan koneksi yang tersedia agar interaksi halaman dapat diuji.

## Phase 2 — Admin dan assessment

Config Phase 1 tetap memilih `public.spec.js` dan `auth.spec.js`. Config Phase 2 terpisah memilih dua spec berikut. Hasil Phase 2 berasal dari laporan full suite pengguna: **11 PASS, 0 FAIL, 0 SKIP (3 menit)**; suite tidak dijalankan ulang pada implementasi Phase 3.

Phase 2 memakai satu worker, nol retry, dan batas 120 detik per kasus karena CRUD hierarki mencakup beberapa form/navigasi; assertion tetap memakai auto-waiting Playwright.

Evidence kegagalan Phase 2 berada di `node_modules/.cache/playwright-phase2-results/`, terpisah dari artefak Phase 1. Simpan screenshot/trace secara lokal karena dapat memuat data akun testing.

| ID | Test Case | Preconditions | Steps | Expected Result | Actual Result | Status | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
| BB-07 | Role authorization | Guard E2E lolos; fixture Super Admin dan Admin A siap | Guest membuka master data; Super Admin membuka domain dan seluruh level hierarki; Admin A membuka URL yang sama | Guest menuju login; Super Admin HTTP 200; Admin A HTTP 403 pada setiap level | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `admin.spec.js`: BB-07; berdasarkan laporan pengguna |
| BB-08 | Domain CRUD | Super Admin E2E; domain dummy kasus ini belum ada | Buat domain inactive; edit; coba kode duplikat; toggle aktif/nonaktif; hapus lewat konfirmasi UI | Satu record tampil; edit tersimpan; duplikat ditolak; status berubah; dummy hilang | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `admin.spec.js`: BB-08; berdasarkan laporan pengguna |
| BB-09 | Hierarchy CRUD | Domain dummy inactive khusus BB-09 | Lewati navigasi parent; buat/edit/hapus satu kapabilitas, bidang praktik, dan praktik inactive; periksa hidden parent IDs dan kunjungi ulang level parent | Parent-child sesuai; perubahan tampil pada parent yang benar; penghapusan dari level terbawah berhasil | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `admin.spec.js`: BB-09; berdasarkan laporan pengguna |
| BB-10 | Create assessment | Admin A dan satu hierarki dasar aktif E2E | Buat assessment lewat modal; refresh daftar; buka assessment; definisikan target untuk menampilkan dua praktik | Assessment muncul pada akun A; satu bidang dan dua praktik terkloning; tampilan tetap tersedia setelah refresh | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `assessment.spec.js`: BB-10; berdasarkan laporan pengguna |
| BB-11 | Answers and target | Draft milik A, target belum ditentukan | Buka bidang; pilih target level 0 melalui Select2; isi dua praktik level 0; simpan; refresh | Target 0 dan jawaban F tersimpan pada UI dan record fixture | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `assessment.spec.js`: BB-11; berdasarkan laporan pengguna |
| BB-12 | Supporting files | Draft A dengan target/jawaban baseline; PDF dummy dari setup | Upload PDF melalui form; preview daftar dan GET PDF; hapus; upload teks dummy berekstensi exe | PDF termuat; hapus menghilangkan record/file; invalid tidak tersimpan pada record maupun disk testing | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `assessment.spec.js`: BB-12; berdasarkan laporan pengguna |
| BB-13 | Publish/unpublish | Draft A baseline | Publish UI; periksa disabled/absent controls; kirim mutasi langsung jawaban/upload, target, delete file dan metadata; bandingkan snapshot; unpublish; edit/simpan/refresh | Published tidak berubah; penolakan bukan karena CSRF; unpublish mengaktifkan edit dan jawaban N tersimpan | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `assessment.spec.js`: BB-13; berdasarkan laporan pengguna |
| BB-14 | Assessment ownership | A/B masing-masing memiliki assessment; B memiliki sentinel PDF | B memverifikasi data awal; A membuka URL B lalu memalsukan assessment/field/practice IDs untuk load/delete/upload/jawaban/target lewat HTTP Laravel; bandingkan snapshot B; login B kembali | A ditolak; isi jawaban, target, metadata dan hash/daftar file B tetap sama | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `assessment.spec.js`: BB-14; berdasarkan laporan pengguna |
| BB-16 | Empty assessment | Assessment A tanpa pertanyaan, dibuat oleh fixture | Buka URL; periksa empty state; kembali ke daftar; buka lagi | HTTP 200; empty state; tanpa form jawaban; navigasi tidak error | Full suite pengguna: 11 PASS, 0 FAIL, 0 SKIP (3 menit) | PASS | `assessment.spec.js`: BB-16; berdasarkan laporan pengguna |

### Persiapan PostgreSQL dan environment

Gunakan database baru **khusus** bernama `aimatrik_e2e`, bukan salinan database pengguna. Gunakan role tersendiri yang hanya diberi hak atas database E2E. Contoh perintah administrator PostgreSQL berikut dijalankan manual; sesuaikan host/akun administrator lokal:

```powershell
createuser --username=postgres --pwprompt aimatrik_e2e_user
createdb --username=postgres --owner=aimatrik_e2e_user aimatrik_e2e
Copy-Item tests/e2e/testing.env.example .env.testing
```

Jangan mengganti atau menyalin `.env` development. Isi `.env.testing` lokal yang sudah diabaikan Git. Jangan commit file tersebut. Isi nilai credential dengan editor lokal; quote nilai yang mengandung spasi atau `#`. Node 20.6+ diperlukan untuk `--env-file` (Playwright yang terpasang memerlukan Node 20+).

| Variable | Keperluan |
| --- | --- |
| `APP_ENV=testing`, `PW_E2E=1` | Opt-in wajib untuk fixture dan test mutasi |
| `DB_CONNECTION=pgsql`, `DB_DATABASE=aimatrik_e2e` | Nama dan driver harus persis; identitas `current_database()` juga diperiksa |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi PostgreSQL khusus testing; jangan gunakan role aplikasi development/production |
| `DB_URL` | Harus kosong, sehingga tidak menimpa database yang diperiksa |
| `APP_KEY` | Key acak baru khusus E2E; jangan menyalin key development |
| `APP_URL`, `PW_BASE_URL` | Origin yang sama dengan router manual; default Phase 2 `http://127.0.0.1:8001` |
| `APP_FORCE_HTTPS=false` | Server testing lokal memakai HTTP |
| `PW_SUPER_EMAIL`, `PW_SUPER_PASSWORD` | Super Admin fixture |
| `PW_ADMIN_A_EMAIL`, `PW_ADMIN_A_PASSWORD` | Admin A fixture |
| `PW_ADMIN_B_EMAIL`, `PW_ADMIN_B_PASSWORD` | Admin B fixture |
| `PW_PHP` (opsional) | Lokasi executable PHP jika `php` tidak ada di PATH |

Ketiga email harus berbeda dan memakai suffix `@aimatrik-e2e.invalid`; password berasal dari environment dan minimal 12 karakter. `PW_ADMIN_EMAIL`/`PW_ADMIN_PASSWORD` Phase 1 tidak diganti. Playwright Phase 2 membaca variable dari `.env.testing` melalui command Node di bawah, bukan menulis credential ke test atau manifest.

Untuk menghasilkan key baru tanpa mencetaknya, command berikut hanya memperbarui `.env.testing`:

```powershell
node --input-type=module -e "import {readFileSync,writeFileSync} from 'node:fs'; import {randomBytes} from 'node:crypto'; const p='.env.testing'; writeFileSync(p,readFileSync(p,'utf8').replace(/^APP_KEY=.*$/m,'APP_KEY=base64:'+randomBytes(32).toString('base64')));"
php tests/e2e/fixtures.php migrate
php tests/e2e/fixtures.php setup
```

`migrate` adalah operasi **manual eksplisit** dengan migration existing pada database yang lolos guard; `setup` tidak menjalankan migrasi, reset, atau seeder aplikasi. Setup menolak database yang sudah memiliki user/domain/assessment, sehingga tidak mengubah akun existing. Ia membuat 3 akun, 1 domain aktif, 1 kapabilitas, 1 bidang praktik, 2 praktik level 0, PDF dummy kecil, dan manifest ID/penanda tanpa credential. Tidak ada `migrate:fresh` atau pengosongan database otomatis.

### Server Laravel testing dan menjalankan Phase 2

**Blocker port existing:** `AppConst::ALLOW_HOST` menerima `127.0.0.1:8000`, bukan `127.0.0.1:8001`. Kode aplikasi/allowlist tidak diubah oleh test. Default config tetap 8001; preflight akan menandainya SKIP jika request ditolak. Agar dapat berjalan dengan source saat ini, hentikan server development secara manual, lalu set **kedua** `APP_URL` dan `PW_BASE_URL` di `.env.testing` menjadi `http://127.0.0.1:8000`, dan jalankan router testing di port 8000. Jangan arahkan test mutasi ke server development yang masih berjalan.

Terminal server (sesuaikan port jika memakai override di atas):

```powershell
php -S 127.0.0.1:8001 -t public tests/e2e/server.php
```

Terminal test, dari root repository:

```powershell
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase2.config.js
# Satu kasus saja, bila diperlukan:
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase2.config.js --grep BB-14
```

Tidak ada server orchestration. Router manual di luar `public/` memeriksa environment/config/identitas database sebelum menangani request Laravel normal. Ia menambahkan header identitas fixture hanya pada responsnya; tidak ada endpoint testing baru pada aplikasi production. Preflight `/login` harus HTTP 200 dengan identitas manifest yang cocok **sebelum** prepare/login/mutasi. CLI database yang benar saja tidak cukup jika browser ternyata mengarah ke server yang salah. Server normal tanpa attestation ditolak/skipped; credential kosong di-skip dengan alasan yang jelas. Kegagalan guard/fixture menghentikan test, bukan dianggap PASS. Cookie, session, cache, compiled view, dan disk lampiran testing terpisah; router tidak melayani junction storage development.

### Rerun, inspeksi dan cleanup yang aman

`beforeEach` menjalankan `prepare BB-xx` setelah attestation. Ia hanya mengganti assessment dengan nama kasus dan owner fixture yang tepat, atau domain dummy khusus BB-08/09. Kasus tidak bergantung pada hasil kasus lain. BB-16 dibuat tanpa pertanyaan, tanpa menonaktifkan domain. Fixture dan file dummy boleh ditinggalkan untuk investigasi setelah test gagal. Jalankan satu suite saja pada satu waktu.

```powershell
php tests/e2e/fixtures.php inspect BB-14
# Setelah selesai: hentikan router manual, kemudian bersihkan fixture milik run ini saja.
php tests/e2e/fixtures.php cleanup
```

Cleanup memakai guard database yang sama; scope dibatasi manifest run, owner, prefix nama/kode dan ID anaknya. Ia menghapus asset/file milik assessment fixture, assessment fixture, domain/hierarkinya, dan tiga akun fixture. Ia menolak referensi di luar fixture, tidak melakukan cleanup massal/reset, dan tidak menghapus data berdasarkan ID request dari browser. Manifest/PDF dihapus setelah cleanup; session/cache testing yang tersisa hanyalah artefak lokal. Jika manifest hilang atau setup terputus, hentikan proses dan periksa database E2E secara manual; jangan mencoba reset database lain.

Snapshot read-only Eloquent dipakai hanya untuk ID fixture dan pembandingan persistence/hash; tindakan yang diuji tetap melalui UI atau HTTP route Laravel dengan cookie/CSRF. Case BB-13/14 memeriksa pesan penolakan existing dan data yang tetap sama; HTTP 419 atau 500 tak dikenal tidak dihitung sebagai keberhasilan authorization. Select2 dioperasikan lewat widget terlihat, tanpa force click atau manipulasi DOM. Phase 2 telah dilaporkan lulus oleh pengguna. Phase 3 belum dijalankan; jika aplikasi bermasalah, catat FAIL dan evidence, jangan ubah fitur untuk memaksa PASS.

## Phase 3 — Recap, user, profile/security dan lifecycle

Empat test baru **NOT RUN**. Tidak ada browser, fixture CLI, cleanup, atau mutasi database yang dijalankan selama implementasi ini. Config Phase 3 hanya memilih `phase3.spec.js`, mewarisi satu worker, nol retry, Chromium headless, timeout 120 detik, screenshot hanya saat gagal dan trace retained saat gagal dari konfigurasi existing. Output: `node_modules/.cache/playwright-phase3-results/`.

| ID | Test Case | Preconditions | Steps | Expected Result | Actual Result | Status | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
| BB-15 | Assessment recap & print | Guard/attestation existing; assessment A aktif draft, 1 domain/kapabilitas/bidang, 2 praktik Level 0 dengan jawaban F dan target 0 | Tolak recap/detail/print saat draft; ubah menjadi inactive+published melalui HTTP Laravel dan periksa 404; unpublish, aktifkan, publish; periksa summary, detail, HTML print; login B dan periksa 404 | Hanya active+published milik A mendapat 200; domain/nama/count sesuai; nilai saat ini dan target tetap 0.0, termasuk minimum domain/institusi; detail merefleksikan hasil F (Baik); print menampilkan dua praktik Fully Achieved, `#print-area`, judul, ringkasan dan tombol cetak; data tidak berubah | Belum dijalankan; source memiliki indikasi defect target nol | NOT RUN | `phase3.spec.js`: BB-15; artefak runtime belum tersedia |
| BB-17 | User management | Super Admin; identitas dummy email run khusus BB-17 | Create Admin aktif melalui modal/Select2; inspect; edit nama dengan password kosong; refresh; submit email duplikat; tolak delete aktif; toggle inactive; hapus; satu akses URL langsung sebagai Admin A | Satu identitas dummy; role/status/nama tersimpan dan password tetap sama; duplikat tidak menambah record; aktif gagal dihapus dengan alasan yang benar; inactive dapat dihapus; Admin A HTTP 403 | Belum dijalankan | NOT RUN | `phase3.spec.js`: BB-17 |
| BB-18 | Profile & security | Admin disposable BB-18 terpisah; credential awal dari env existing | Login disposable; edit nama, refresh dan inspect; PATCH nama kosong; PATCH confirmation mismatch dan email duplikat; submit email/password valid lewat UI; inspect boolean hash; periksa logout, login baru, logout UI dan tolak password lama | Nama tersimpan; invalid HTTP 422 dengan error field yang benar dan data tetap sama; email/password benar-benar berubah; sesi lama berakhir; credential baru bekerja dan password lama ditolak | Belum dijalankan; confirmed source defect / pending runtime verification pada SecurityController | NOT RUN | `phase3.spec.js`: BB-18; artefak runtime belum tersedia |
| BB-19 | Assessment lifecycle | Draft active khusus BB-19 milik A | Edit nama/deskripsi melalui modal; inspect dan refresh; tolak delete aktif; ubah checkbox inactive lewat modal; delete melalui konfirmasi UI; refresh dan inspect withTrashed | Metadata lengkap tetap benar meski judul kartu dipotong; aktif ditolak dihapus; inactive tersimpan; kartu/link ID hilang; record masih ada dengan deleted_at terisi | Belum dijalankan | NOT RUN | `phase3.spec.js`: BB-19 |

### Menjalankan Phase 3

Gunakan database, `.env.testing`, manifest dan router testing yang sudah dipakai Phase 2. **Tidak perlu setup/migrate/reset ulang.** Gunakan origin `APP_URL`/`PW_BASE_URL` existing yang telah lolos attestation; tidak ada server orchestration atau perubahan allowlist.

```powershell
# Full Phase 3
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase3.config.js
# Masing-masing kasus
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase3.config.js --grep BB-15
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase3.config.js --grep BB-17
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase3.config.js --grep BB-18
node --env-file=.env.testing node_modules/@playwright/test/cli.js --config=playwright.phase3.config.js --grep BB-19
```

Tidak ada variable credential baru. BB-17 memakai `PW_ADMIN_A_PASSWORD` untuk dummy; BB-18 memakai nilai yang sama sebagai password awal disposable. Password pengganti BB-18 berasal dari nilai env tersebut ditambah suffix `-BB-18`, sehingga selalu berbeda tanpa hardcode password. Fixture hanya mengembalikan `passwordMatchesInitial`/`passwordMatchesReplacement`; tidak mengeluarkan password, hash, cookie atau token. Jangan mengunggah trace/screenshot tanpa pemeriksaan: seperti Phase 2, artefak Playwright dapat merekam input credential testing.

### Fixture, inspect dan cleanup

`prepare` berjalan otomatis sesudah preflight/attestation existing. BB-15 dan BB-19 hanya mengganti assessment dengan prefix kasus dan akun manifest yang sesuai, termasuk sisa soft-deleted. BB-15 mengisi hierarki dasar, target 0, dua jawaban F dan counter 1 domain terjawab. BB-19 dimulai tanpa pertanyaan karena hanya menguji metadata/lifecycle; inspect menerima dua nama yang diizinkan (awal/Updated), memakai owner manifest dan melaporkan `deleted_at` secara read-only.

BB-17 tidak menambahkan akun pada prepare: akun dibuat melalui UI. Identitas ditemukan lewat email `bb-17-<run>@aimatrik-e2e.invalid` dan nama prefix kasus yang tepat. BB-18 dibuat oleh prepare dengan username stabil `<prefix>-BB-18`, email `bb-18-<run>@aimatrik-e2e.invalid`, dan hanya mengizinkan email perubahan `bb-18-updated-<run>@aimatrik-e2e.invalid`. Kedua akun wajib Admin non-primary, berbeda dari tiga ID utama manifest. Ambiguitas atau penanda yang tidak sesuai menyebabkan fail closed. Perubahan nama/email/password disposable setelah kegagalan dipulihkan pada prepare berikutnya dengan menghapus lalu membuat ulang hanya akun disposable yang terverifikasi.

Cleanup user menolak assessment (termasuk soft-deleted), asset profil, session database, atau password-reset token yang tidak diharapkan; tidak melakukan cascade terhadap relasi tersebut. Tidak ada upload foto pada test ini. Tiga akun utama tidak diubah oleh test. Command cleanup existing juga membersihkan disposable terverifikasi; jangan menjalankannya ketika suite/server masih digunakan.

```powershell
# Opsional: metadata read-only, tanpa password/hash
php tests/e2e/fixtures.php inspect BB-15
php tests/e2e/fixtures.php inspect BB-17
php tests/e2e/fixtures.php inspect BB-18
php tests/e2e/fixtures.php inspect BB-19
# Setelah selesai dan router dihentikan, cleanup seluruh fixture run terverifikasi:
php tests/e2e/fixtures.php cleanup
```

### Defect yang harus tetap terlihat

- BB-15: `pluck('levelTarget')->filter()` pada controller membuang angka 0. Ekspektasi summary/print tetap **0.0**, bukan `-`. Soft assertions memungkinkan kedua halaman dan pemeriksaan owner tetap berjalan, tetapi setiap mismatch tetap membuat test **FAIL**.
- BB-18: `SecurityController::update()` tidak memanggil service `updateUserSecurity()` sebelum commit. Ini **confirmed source defect / pending runtime verification**. Test tetap menuntut email/password baru tersimpan, sesi berakhir, login baru berhasil dan password lama ditolak. Tidak ada skip/expected failure untuk defect ini; kegagalan login baru dapat menghentikan langkah lanjutan secara normal. Controller tidak diubah.
- Detail recap existing menampilkan agregat bidang praktik (level/target/gap/performa); jawaban individual F diperiksa pada fixture dan HTML print. Status BB-19 diubah melalui checkbox modal edit karena kartu existing tidak menawarkan tombol set-status terpisah.
