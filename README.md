# 🏫 Absen Santri Digital (v2.0)

[![PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892bf.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Sistem Absensi Digital berbasis QR Code yang modern, responsif, dan terintegrasi dengan WhatsApp Gateway. Dirancang khusus untuk mempermudah manajemen kehadiran di Pesantren, Sekolah, atau Instansi Pendidikan lainnya.

---

## ✨ Fitur Unggulan

### 📊 Dashboard & Analytics Modern
- Tampilan Dashboard premium dengan **Chart.js** (7-day trend).
- Ringkasan data (Total Santri, Kehadiran Hari Ini) dengan desain *glassmorphism*.
- Support font modern (Outfit & Inter).

### 📸 Scanner QR Code Canggih
- **Centralized Overlay**: Hasil scan (Berhasil/Gagal) muncul tepat di atas kamera, tidak perlu scroll.
- **Auto-Pause Camera**: Kamera otomatis jeda setelah scan berhasil untuk menghemat baterai.
- **Manual Resume**: Tombol "Scan Lagi" untuk melanjutkan absensi berikutnya.
- **Scrolling Announcement**: Pengumuman berjalan yang dapat diubah dari panel admin.

### 📲 Integrasi WhatsApp Gateway (MPWA)
- Notifikasi real-time ke Wali Murid saat santri melakukan scan.
- Laporan urutan kehadiran real-time ke nomor Admin/Guru.
- **WA Tester Utility**: Halaman khusus untuk menguji koneksi API gateway secara mandiri.
- **Robust Formatting**: Pembersihan nomor HP otomatis (membersihkan spasi/strip dan konversi ke format 62).

### 📝 Manajemen Data & Laporan
- Kelola data Santri/Murid lengkap dengan foto dan generator QR Code otomatis.
- Laporan kehadiran harian.
- **Rekap Bulanan**: Hitung otomatis jumlah Hadir, Sakit, Izin, dan Alpa per bulan.
- **Export CSV**: Download data santri untuk kebutuhan backup atau pengolahan data lain.
- **Print Version**: Cetak laporan kehadiran dengan tata letak profesional (siap tanda tangan).

---

## 🚀 Cara Instalasi (Web Hosting)

Sistem ini sudah dilengkapi dengan **Installation Wizard** untuk memudahkan setup tanpa harus masuk ke phpMyAdmin secara manual.

1. **Upload File**: Unggah semua file project ke dalam folder `public_html` atau subfolder di hosting Anda.
2. **Setup Database**: Buat Database MySQL baru melalui cPanel/Panel Hosting Anda.
3. **Jalankan Installer**: Buka link berikut di browser:
   ```text
   https://domain-anda.com/install.php
   ```
4. **Isi Konfigurasi**: Masukkan detail host, username, password, dan nama database yang baru saja Anda buat.
5. **Selesai**: Klik tombol install. Sistem akan membuat tabel secara otomatis dan mengarahkan Anda ke halaman login.
6. **⚠️ Keamanan**: Setelah instalasi berhasil, pastikan untuk **MENGHAPUS** file `install.php` dari server Anda.

---

## 🛠️ Persyaratan Sistem
- PHP 7.4 atau versi lebih tinggi.
- MySQL / MariaDB.
- Extension `cURL` aktif (untuk WhatsApp Gateway).
- Koneksi Internet (untuk memuat library Bootstrap & Chart.js).

---

## 👨‍💻 Pengembang & Dukungan

Project ini dikembangkan dan dikelola oleh:

**ALIJAYA-NET**  
📞 WhatsApp: **081947215703**  
🌐 Website: [alijaya.net](https://alijaya.net)

Jika Anda menemukan kendala atau membutuhkan custom fitur, jangan ragu untuk menghubungi kontak di atas.

---

> [!TIP]
> **Donasi & Support**: Jika aplikasi ini bermanfaat, dukung terus pengembang agar dapat merilis update fitur menarik lainnya di masa depan.

© 2026 Alijaya-Net. All Rights Reserved.
