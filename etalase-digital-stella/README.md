# 🎨 Etalase Digital Stella — Telkom Schools

Aplikasi web portofolio dan etalase karya digital siswa berbasis **PHP Native** dan **MySQL** dengan antarmuka modern berstandar **Premium SaaS Dashboard**.

> **Hak Cipta © 2026 Faris Attaqy.**  
> *Seluruh Hak Cipta Dilindungi. Didesain & Dikembangkan untuk Telkom Schools (Stella).*

---

## 🌟 Fitur Utama

1. **Dashboard & Galeri Karya Digital**:
   - Menampilkan seluruh portofolio siswa lengkap dengan pratinjau gambar, nama project, nama pembuat, dan tautan project.
   - **Modal Detail Karya**: Popup informasi mendalam tentang deskripsi karya, tanggal unggah, serta tautan langsung ke website / GitHub.
   - **Real-Time Instant Search**: Pencarian cerdas tanpa reload halaman berdasarkan nama karya atau pembuat.

2. **Manajemen Siswa (Kelola Siswa)**:
   - *Split-layout* modern: Form pendaftaran akun siswa baru di sisi kiri, dan tabel data akun di sisi kanan.
   - Fitur **Reset Password** siswa secara langsung via modal popup.
   - Fitur **Hapus Akun Siswa** yang otomatis membersihkan file karya terkait dari server.
   - Pencarian instan akun siswa berdasarkan nama atau email.

3. **Manajemen User (Khusus Super Admin)**:
   - Mengelola seluruh akun pengguna (**Super Admin**, **Admin**, **Siswa**).
   - Filter cepat berdasarkan role dengan satu klik tombol.
   - Form pembuatan akun Admin / Super Admin baru.
   - **Proteksi Keamanan Tinggi**: Akun Super Admin terlindungi dari penghapusan.

4. **Desain UI/UX Premium**:
   - Typography modern menggunakan **Plus Jakarta Sans**.
   - Background panorama gedung sekolah Telkom Schools pada Hero Banner dan Halaman Login/Register.
   - Desain responsif optimal di desktop, laptop, tablet, dan smartphone.

---

## 🚀 Panduan Instalasi & Menjalankan di Perangkat Lain

### 1. Prasyarat Sistem
- Web Server lokal: **Laragon** (Sangat Direkomendasikan) atau **XAMPP**.
- PHP versi **7.4 / 8.0 / 8.1 / 8.2+**.
- MySQL / MariaDB.

### 2. Cara Impor Database di phpMyAdmin
1. Pastikan server Apache dan MySQL sudah berjalan (Running).
2. Buka browser dan masuk ke **phpMyAdmin**:  
   👉 `http://localhost/phpmyadmin`
3. Buat database baru bernama:  
   👉 `db_etalase_stella`
4. Pilih database `db_etalase_stella`, lalu klik tab menu **Import** di bagian atas.
5. Klik **Choose File / Pilih Berkas**, lalu pilih file:  
   👉 `database.sql` (atau `db_etalase_stella.sql`) yang ada di folder project ini.
6. Gulir ke bawah dan klik tombol **Import / Kirim / Go**.
7. Database berhasil terpasang dan siap digunakan!

---

## 🔐 Akun Login Bawaan (Default Credentials)

Gunakan akun berikut untuk masuk ke dalam sistem:

| Tingkat Akses (Role) | Alamat Email | Password | Hak Akses |
|---|---|---|---|
| 👑 **SUPER ADMIN** | `superadmin@stella.sch.id` | `password123` | Akses penuh ke seluruh sistem, Kelola User, Kelola Siswa, & Galeri Karya |
| 🛡️ **ADMIN** | `admin@stella.sch.id` | `password123` | Kelola Siswa & Galeri Karya |
| 🎓 **SISWA** | `siswa@stella.sch.id` | `password123` | Tambah, edit, dan kelola karya milik sendiri di Galeri Karya |

---

## 📁 Struktur Direktori Project

```text
etalase-digital-stella/
├── admin/
│   ├── index.php          # Redirect controller admin
│   ├── siswa.php          # Manajemen data siswa & reset password
│   └── users.php          # Manajemen user seluruh role (Super Admin)
├── assets/
│   └── css/
│       └── design-system.css # Sistem desain global & komponen UI
├── auth/
│   ├── login.php          # Halaman masuk sistem (Glassmorphism UI)
│   ├── logout.php         # Logout & pembersihan session
│   └── register.php       # Pendaftaran mandiri siswa baru
├── config/
│   └── kuis_db.php        # Konfigurasi koneksi database PDO
├── img/
│   ├── logo-telkom-schools.png # Logo resmi Telkom Schools
│   ├── IMG20261122092145.jpg   # Foto gedung Telkom Schools
│   ├── IMG20261122092147.jpg   # Foto gedung Telkom Schools
│   └── bg-telkom-optimized.jpg # Background teroptimasi
├── middleware/
│   └── auth_check.php     # Pemeriksaan login dan hak akses role
├── uploads/               # Direktori penyimpanan gambar karya
├── database.sql           # File dump database untuk phpMyAdmin
├── db_etalase_stella.sql  # Salinan file dump database
├── index.php              # Beranda & Galeri Karya Digital
├── project_tambah.php     # Formulir unggah karya digital
├── project_edit.php       # Formulir edit karya digital
├── project_hapus.php      # Skrip hapus karya & file gambar
└── README.md              # Dokumentasi & panduan project
```

---

## 📄 Lisensi & Hak Cipta
Hak Cipta © 2026 **Faris Attaqy**. Seluruh hak cipta dilindungi undang-undang.
Didedikasikan untuk **Telkom Schools** (Stella).
