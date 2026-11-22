<?php
require_once '../config/kuis_db.php';
require_once '../middleware/auth_check.php';

// Super Admin dan Admin memiliki akses ke Kelola Siswa
check_role(['super_admin', 'admin']);

$error = '';
$success = '';

// 1. TAMBAH SISWA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_siswa'])) {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Semua kolom input wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format alamat email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal terdiri dari 6 karakter!";
    } else {
        // Cek duplikasi email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            $error = "Email sudah terdaftar! Gunakan email lain.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'siswa')");
                $stmt->execute([
                    ':nama'     => $nama,
                    ':email'    => $email,
                    ':password' => $hashed
                ]);
                $success = "Akun siswa atas nama <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan!";
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}

// 2. RESET PASSWORD SISWA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id      = (int)($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';

    if ($user_id <= 0 || empty($new_password)) {
        $error = "Data reset password tidak lengkap!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password baru minimal terdiri dari 6 karakter!";
    } else {
        // Pastikan hanya role siswa yang di-reset lewat fitur ini
        $chk = $pdo->prepare("SELECT nama FROM users WHERE id = :id AND role = 'siswa'");
        $chk->execute([':id' => $user_id]);
        $target_user = $chk->fetch();

        if ($target_user) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $upd->execute([':password' => $hashed, ':id' => $user_id]);
            $success = "Password akun siswa <strong>" . htmlspecialchars($target_user['nama']) . "</strong> berhasil direset!";
        } else {
            $error = "Akun siswa tidak ditemukan atau tidak memiliki hak akses untuk mereset!";
        }
    }
}

// 3. HAPUS SISWA
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];

    $stmt = $pdo->prepare("SELECT nama FROM users WHERE id = :id AND role = 'siswa'");
    $stmt->execute([':id' => $hapus_id]);
    $user_to_delete = $stmt->fetch();

    if ($user_to_delete) {
        // Hapus file gambar project yang terkait terlebih dahulu
        $get_projects = $pdo->prepare("SELECT gambar FROM projects WHERE user_id = :uid");
        $get_projects->execute([':uid' => $hapus_id]);
        $user_projects = $get_projects->fetchAll();

        foreach ($user_projects as $p) {
            $file_path = '../uploads/' . $p['gambar'];
            if (!empty($p['gambar']) && file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Hapus data siswa (projects akan otomatis terhapus karena ON DELETE CASCADE)
        $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'siswa'");
        $del->execute([':id' => $hapus_id]);

        header('Location: siswa.php?msg=deleted&name=' . urlencode($user_to_delete['nama']));
        exit();
    } else {
        $error = "Akun siswa tidak dapat dihapus atau tidak ditemukan!";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $name = htmlspecialchars($_GET['name'] ?? 'Siswa');
    $success = "Akun siswa <strong>$name</strong> beserta karya miliknya berhasil dihapus!";
}

// Ambil daftar seluruh siswa
$stmt = $pdo->query("SELECT id, nama, email, role FROM users WHERE role = 'siswa' ORDER BY id DESC");
$siswa_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa - Etalase Digital Stella</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <style>
        body {
            background-color: var(--bg-body);
        }

        /* Topbar */
        .topbar {
            background: var(--neutral-950);
            color: var(--neutral-400);
            font-size: var(--text-xs);
            padding: var(--space-2) var(--space-8);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-4);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            font-size: 11.5px;
            color: var(--neutral-400);
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }
        .topbar-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        .topbar strong {
            color: var(--neutral-200);
        }
        .topbar-divider {
            width: 1px;
            height: 14px;
            background: var(--neutral-700);
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.96);
            padding: 0 var(--space-8);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: var(--z-sticky);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: 10px 0;
            text-decoration: none;
        }
        .brand-logo-container {
            height: 44px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 4px 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-xs);
        }
        .brand-logo-img {
            height: 30px;
            width: auto;
            object-fit: contain;
        }
        .brand-divider {
            width: 1.5px;
            height: 28px;
            background: var(--border-light);
        }
        .brand-title {
            font-size: var(--text-md);
            font-weight: 800;
            color: var(--neutral-900);
            line-height: 1.2;
        }
        .brand-sub {
            font-size: 11px;
            color: var(--neutral-500);
        }

        /* Navigation Links */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: var(--space-1);
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.5rem 0.875rem;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--neutral-600);
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: all var(--duration-fast);
        }
        .nav-link:hover {
            color: var(--neutral-900);
            background: var(--neutral-100);
        }
        .nav-link.active {
            color: var(--primary-700);
            background: var(--primary-50);
        }
        .nav-link svg {
            width: 16px;
            height: 16px;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        /* Page Layout */
        .main-container {
            max-width: 1320px;
            margin: var(--space-8) auto;
            padding: 0 var(--space-6);
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-6);
            flex-wrap: wrap;
            gap: var(--space-4);
        }
        .page-header h1 {
            font-size: var(--text-2xl);
            font-weight: 800;
            color: var(--neutral-900);
            letter-spacing: -0.02em;
        }
        .page-header p {
            font-size: var(--text-sm);
            color: var(--neutral-500);
            margin-top: 2px;
        }

        /* Two-Column Layout */
        .content-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: var(--space-6);
            align-items: start;
        }

        /* Left Side Card: Add Student */
        .card-form {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-2xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 80px;
        }
        .card-form-title {
            font-size: var(--text-lg);
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: var(--space-1);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        .card-form-desc {
            font-size: var(--text-xs);
            color: var(--neutral-500);
            margin-bottom: var(--space-6);
        }

        /* Right Side: Student List */
        .card-list {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-2xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-sm);
        }

        .card-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-5);
            flex-wrap: wrap;
            gap: var(--space-4);
        }
        .card-list-title-wrap {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        .card-list-title {
            font-size: var(--text-lg);
            font-weight: 700;
            color: var(--neutral-900);
        }

        /* Table Specific */
        .table-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--text-xs);
            flex-shrink: 0;
        }
        .student-meta {
            display: flex;
            flex-direction: column;
        }
        .student-name {
            font-weight: 600;
            color: var(--neutral-900);
            font-size: var(--text-sm);
        }
        .student-email {
            font-size: var(--text-xs);
            color: var(--neutral-500);
        }

        .action-cell {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        /* Mobile Hamburger */
        .mobile-nav-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--neutral-700);
            cursor: pointer;
            padding: var(--space-2);
        }

        /* Footer Hak Cipta */
        .footer {
            background: var(--neutral-950);
            padding: var(--space-10) var(--space-6) var(--space-8);
            text-align: center;
            color: var(--neutral-400);
            margin-top: var(--space-16);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .footer-inner {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-3);
        }
        .footer-logo-box {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
        }
        .footer-logo-img {
            height: 28px;
            width: auto;
        }
        .footer-copyright {
            font-size: var(--text-sm);
            color: var(--neutral-200);
            font-weight: 600;
        }
        .footer-copyright strong {
            color: #ffffff;
        }
        .footer-sub {
            font-size: var(--text-xs);
            color: var(--neutral-400);
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .card-form {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .topbar { display: none; }
            .navbar { padding: 0 var(--space-4); }
            .mobile-nav-toggle { display: block; }
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: var(--space-4);
                border-bottom: 1px solid var(--border-light);
                box-shadow: var(--shadow-lg);
            }
            .nav-menu.active { display: flex; }
            .main-container { padding: 0 var(--space-4); margin: var(--space-4) auto; }
            .search-container { max-width: 100%; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <span>🏫 <strong>Telkom Schools</strong> • Manajemen Siswa</span>
    </div>
    <div class="topbar-right">
        <div class="topbar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></span>
        </div>
        <div class="topbar-divider"></div>
        <div class="topbar-item">
            <span class="badge badge-<?= htmlspecialchars($_SESSION['role']) ?>">
                <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $_SESSION['role']))) ?>
            </span>
        </div>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">
        <!-- Logo Telkom Schools Resmi -->
        <div class="brand-logo-container">
            <img src="../img/logo-telkom-schools.png" alt="Logo Telkom Schools" class="brand-logo-img">
        </div>
        <div class="brand-divider"></div>
        <div>
            <div class="brand-title">Etalase Digital</div>
            <div class="brand-sub">Telkom Schools • Stella</div>
        </div>
    </a>

    <!-- Navigation Menu -->
    <div class="nav-menu" id="navMenu">
        <a href="../index.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            Galeri Karya
        </a>
        <a href="siswa.php" class="nav-link active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
            Kelola Siswa
        </a>
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <a href="users.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                Kelola User
            </a>
        <?php endif; ?>
    </div>

    <div class="navbar-actions">
        <a href="../auth/logout.php" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Logout
        </a>
        <button class="mobile-nav-toggle" onclick="document.getElementById('navMenu').classList.toggle('active')" aria-label="Toggle Navigation">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="main-container">
    <div class="page-header">
        <div>
            <h1>Manajemen Data Siswa</h1>
            <p>Kelola pendaftaran siswa, reset password, dan status akun platform</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: var(--space-5);">
            <span class="alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            </span>
            <span><?= $error ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="margin-bottom: var(--space-5);">
            <span class="alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
            <span><?= $success ?></span>
        </div>
    <?php endif; ?>

    <div class="content-grid">
        <!-- BAGIAN KIRI: FORM TAMBAH SISWA -->
        <section class="card-form">
            <h2 class="card-form-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary-600)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                Tambah Siswa
            </h2>
            <p class="card-form-desc">Daftarkan akun siswa baru untuk dapat mengunggah karya ke etalase</p>

            <form method="POST" action="siswa.php" autocomplete="off">
                <div class="form-group">
                    <label class="form-label" for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input class="form-input" type="text" id="nama" name="nama" placeholder="Contoh: Muhammad Fathan" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email <span class="required">*</span></label>
                    <input class="form-input" type="email" id="email" name="email" placeholder="siswa@stella.sch.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required">*</span></label>
                    <div class="password-toggle-wrapper">
                        <input class="form-input" type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePass('password', this)" title="Tampilkan/Sembunyikan Password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <span class="form-hint">Gunakan kombinasi karakter yang aman</span>
                </div>

                <button type="submit" name="tambah_siswa" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Data Siswa
                </button>
            </form>
        </section>

        <!-- BAGIAN KANAN: DAFTAR AKUN SISWA -->
        <section class="card-list">
            <div class="card-list-header">
                <div class="card-list-title-wrap">
                    <h2 class="card-list-title">Daftar Akun Siswa</h2>
                    <span class="count-pill" id="totalSiswaBadge"><?= count($siswa_list) ?> Siswa</span>
                </div>

                <!-- Real-time Search Bar -->
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                        <input type="text" id="searchSiswa" class="search-input" placeholder="Cari nama atau email siswa..." autocomplete="off">
                        <button type="button" id="clearSearchBtn" class="search-clear-btn" title="Hapus pencarian">✕</button>
                    </div>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table" id="tableSiswa">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th style="width: 180px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodySiswa">
                        <?php if (empty($siswa_list)): ?>
                            <tr id="emptyRow">
                                <td colspan="4" style="text-align: center; padding: 40px 20px; color: var(--neutral-400);">
                                    Belum ada data akun siswa yang terdaftar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($siswa_list as $s): ?>
                                <tr class="siswa-row" data-nama="<?= strtolower(htmlspecialchars($s['nama'])) ?>" data-email="<?= strtolower(htmlspecialchars($s['email'])) ?>">
                                    <td style="text-align: center; color: var(--neutral-400); font-weight: 600;" class="row-number"><?= $no++ ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: var(--space-3);">
                                            <div class="table-avatar">
                                                <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                            </div>
                                            <div class="student-meta">
                                                <span class="student-name"><?= htmlspecialchars($s['nama']) ?></span>
                                                <span class="badge badge-siswa" style="font-size: 10px; width: fit-content; padding: 1px 6px; margin-top: 2px;">Siswa</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="student-email"><?= htmlspecialchars($s['email']) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-cell" style="justify-content: center;">
                                            <!-- Tombol Reset Password -->
                                            <button type="button" class="btn-action-icon btn-reset-pass" onclick="openResetModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nama'])) ?>', '<?= htmlspecialchars(addslashes($s['email'])) ?>')" title="Reset Password Siswa">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-1.5 1.5L10 13l-4 1 1-4 7.5-7.5M19 4l1 1-1.5 1.5-1-1L19 4Z"/><path d="M7 17l-4 4"/></svg>
                                                Reset
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <a href="siswa.php?hapus=<?= $s['id'] ?>" class="btn-action-icon btn-danger-soft" onclick="return confirm('Apakah Anda yakin ingin menghapus akun siswa \'<?= htmlspecialchars(addslashes($s['nama'])) ?>\'? Seluruh karya miliknya juga akan dihapus!')" title="Hapus Akun Siswa">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Empty Search State -->
            <div id="noMatchMessage" style="display: none; text-align: center; padding: 48px 20px;">
                <div style="font-size: 36px; margin-bottom: 8px;">🔍</div>
                <h3 style="font-size: var(--text-md); color: var(--neutral-700); font-weight: 700; margin-bottom: 4px;">Siswa tidak ditemukan</h3>
                <p style="font-size: var(--text-xs); color: var(--neutral-500); margin-bottom: 12px;">Tidak ada akun siswa yang sesuai dengan kata kunci pencarian Anda.</p>
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSearch()">Bersihkan Pencarian</button>
            </div>
        </section>
    </div>
</main>

<!-- MODAL RESET PASSWORD -->
<div class="modal" id="resetPasswordModal">
    <div class="modal-backdrop" onclick="closeResetModal()"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Reset Password Siswa</h3>
            <button type="button" class="modal-close-btn" onclick="closeResetModal()">✕</button>
        </div>
        <form method="POST" action="siswa.php">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modalUserId">

                <div style="background: var(--neutral-50); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: var(--space-4); margin-bottom: var(--space-5);">
                    <div style="font-size: var(--text-xs); color: var(--neutral-500);">Target Siswa:</div>
                    <div style="font-size: var(--text-sm); font-weight: 700; color: var(--neutral-900);" id="modalUserName">-</div>
                    <div style="font-size: var(--text-xs); color: var(--neutral-600);" id="modalUserEmail">-</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">Password Baru <span class="required">*</span></label>
                    <div class="password-toggle-wrapper">
                        <input class="form-input" type="password" id="new_password" name="new_password" placeholder="Masukkan password baru (min. 6 karakter)" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePass('new_password', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <span class="form-hint">Password baru akan segera aktif dan dapat langsung digunakan login</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeResetModal()">Batal</button>
                <button type="submit" name="reset_password" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FOOTER DENGAN HAK CIPTA FARIS ATTAQY -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-logo-box">
            <img src="../img/logo-telkom-schools.png" alt="Telkom Schools" class="footer-logo-img">
        </div>
        <p class="footer-copyright">
            Hak Cipta &copy; <?= date('Y') ?> <strong>Faris Attaqy</strong>. Seluruh Hak Cipta Dilindungi.
        </p>
        <p class="footer-sub">
            Platform Etalase Digital Stella • Telkom Schools.
        </p>
    </div>
</footer>

<script>
// Toggle Password Function
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" x2="23" y1="1" y2="23"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
}

// Reset Password Modal
function openResetModal(id, nama, email) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalUserName').textContent = nama;
    document.getElementById('modalUserEmail').textContent = email;
    document.getElementById('new_password').value = '';
    document.getElementById('resetPasswordModal').classList.add('active');
}

function closeResetModal() {
    document.getElementById('resetPasswordModal').classList.remove('active');
}

// Search Filter Siswa
const searchInput = document.getElementById('searchSiswa');
const clearBtn = document.getElementById('clearSearchBtn');
const rows = document.querySelectorAll('.siswa-row');
const noMatchMsg = document.getElementById('noMatchMessage');
const tableWrapper = document.querySelector('.table-wrapper');

function applySearch() {
    const query = searchInput.value.toLowerCase().trim();
    clearBtn.classList.toggle('visible', query.length > 0);

    let matchCount = 0;
    let visibleIndex = 1;

    rows.forEach(row => {
        const nama = row.getAttribute('data-nama') || '';
        const email = row.getAttribute('data-email') || '';

        if (nama.includes(query) || email.includes(query)) {
            row.style.display = '';
            const numCell = row.querySelector('.row-number');
            if (numCell) numCell.textContent = visibleIndex++;
            matchCount++;
        } else {
            row.style.display = 'none';
        }
    });

    if (rows.length > 0) {
        if (matchCount === 0) {
            tableWrapper.style.display = 'none';
            noMatchMsg.style.display = 'block';
        } else {
            tableWrapper.style.display = 'block';
            noMatchMsg.style.display = 'none';
        }
    }
}

function clearSearch() {
    searchInput.value = '';
    applySearch();
    searchInput.focus();
}

searchInput.addEventListener('input', applySearch);
clearBtn.addEventListener('click', clearSearch);
</script>

</body>
</html>
