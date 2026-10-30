# AIMATRIK — Public Black-box Test Cases

Seluruh skenario berstatus **NOT RUN**. Fase implementasi ini hanya melakukan validasi statis; browser dan E2E test belum dijalankan.

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
