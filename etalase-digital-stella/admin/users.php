<?php
require_once '../config/kuis_db.php';
require_once '../middleware/auth_check.php';

// Memastikan hanya Super Admin yang bisa mengakses halaman ini
check_role(['super_admin']);

$error = '';
$success = '';

// 1. TAMBAH ADMIN BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_admin'])) {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? 'admin');

    // Validasi role yang diizinkan untuk ditambah
    if (!in_array($role, ['admin', 'super_admin'])) {
        $role = 'admin';
    }

    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Semua kolom input wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal terdiri dari 6 karakter!";
    } else {
        // Cek duplikasi email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);

        if ($check->rowCount() > 0) {
            $error = "Alamat email sudah terdaftar dalam sistem!";
        } else {
            $pass_hash = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :pass, :role)");
                $stmt->execute([
                    ':nama'  => $nama,
                    ':email' => $email,
                    ':pass'  => $pass_hash,
                    ':role'  => $role
                ]);
                $role_title = $role === 'super_admin' ? 'Super Admin' : 'Admin';
                $success = "Akun <strong>$role_title</strong> atas nama <strong>" . htmlspecialchars($nama) . "</strong> berhasil dibuat!";
            } catch (PDOException $e) {
                $error = "Gagal menyimpan akun: " . $e->getMessage();
            }
        }
    }
}

// 2. HAPUS USER
if (isset($_GET['hapus'])) {
    $id_to_delete = (int)$_GET['hapus'];

    // Cek target user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $id_to_delete]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        $error = "Akun pengguna tidak ditemukan!";
    } elseif ($target_user['role'] === 'super_admin') {
        // Proteksi Mutlak: Super Admin tidak boleh dihapus
        $error = "Proteksi Keamanan: Akun dengan role Super Admin tidak dapat dihapus!";
    } elseif ($target_user['id'] == $_SESSION['user_id']) {
        // Proteksi: Tidak dapat menghapus diri sendiri
        $error = "Anda tidak dapat menghapus akun Anda sendiri saat sedang login!";
    } else {
        // Hapus file gambar project yang terkait jika ada
        $get_projects = $pdo->prepare("SELECT gambar FROM projects WHERE user_id = :uid");
        $get_projects->execute([':uid' => $id_to_delete]);
        $user_projects = $get_projects->fetchAll();

        foreach ($user_projects as $p) {
            $file_path = '../uploads/' . $p['gambar'];
            if (!empty($p['gambar']) && file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Hapus user dari database
        $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $del->execute([':id' => $id_to_delete]);

        header('Location: users.php?msg=deleted&name=' . urlencode($target_user['nama']));
        exit();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $name = htmlspecialchars($_GET['name'] ?? 'Pengguna');
    $success = "Akun <strong>$name</strong> berhasil dihapus dari sistem!";
}

// Ambil seluruh akun user
$users = $pdo->query("SELECT * FROM users ORDER BY 
    CASE role 
        WHEN 'super_admin' THEN 1 
        WHEN 'admin' THEN 2 
        WHEN 'siswa' THEN 3 
        ELSE 4 
    END ASC, id DESC")->fetchAll();

// Hitung total per role
$total_all = count($users);
$total_super = 0;
$total_admin = 0;
$total_siswa = 0;
foreach ($users as $u) {
    if ($u['role'] === 'super_admin') $total_super++;
    elseif ($u['role'] === 'admin') $total_admin++;
    elseif ($u['role'] === 'siswa') $total_siswa++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Super Admin</title>
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

        /* Navigation Menu */
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

        /* Container */
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

        /* Layout Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: var(--space-6);
            align-items: start;
        }

        /* Form Card */
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

        /* User List Card */
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
            margin-bottom: var(--space-4);
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

        /* Filter & Search Bar Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-5);
            flex-wrap: wrap;
            gap: var(--space-3);
        }

        /* Avatar per role */
        .table-avatar-super_admin {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--primary-600), var(--primary-800));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--text-xs);
            flex-shrink: 0;
        }
        .table-avatar-admin {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--text-xs);
            flex-shrink: 0;
        }
        .table-avatar-siswa {
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

        .user-meta {
            display: flex;
            flex-direction: column;
        }
        .user-name {
            font-weight: 600;
            color: var(--neutral-900);
            font-size: var(--text-sm);
        }
        .user-email {
            font-size: var(--text-xs);
            color: var(--neutral-500);
        }

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
            .toolbar { flex-direction: column; align-items: stretch; }
            .search-container { max-width: 100%; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <span>🏫 <strong>Telkom Schools</strong> • Manajemen Akun Sistem</span>
    </div>
    <div class="topbar-right">
        <div class="topbar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></span>
        </div>
        <div class="topbar-divider"></div>
        <div class="topbar-item">
            <span class="badge badge-super_admin">SUPER ADMIN</span>
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
        <a href="siswa.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
            Kelola Siswa
        </a>
        <a href="users.php" class="nav-link active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
            Kelola User
        </a>
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
            <h1>Manajemen User & Hak Akses</h1>
            <p>Kelola seluruh akun pengguna, tambahkan administrator, dan pantau hak akses sistem</p>
        </div>
        <div style="display: flex; gap: var(--space-2);">
            <span class="badge badge-super_admin"><?= $total_super ?> Super Admin</span>
            <span class="badge badge-admin"><?= $total_admin ?> Admin</span>
            <span class="badge badge-siswa"><?= $total_siswa ?> Siswa</span>
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
        <!-- BAGIAN KIRI: FORM TAMBAH ADMIN -->
        <section class="card-form">
            <h2 class="card-form-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary-600)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                Tambah Admin
            </h2>
            <p class="card-form-desc">Buat akun pengelola dengan hak akses admin untuk membantu pengelolaan platform</p>

            <form method="POST" action="users.php" autocomplete="off">
                <div class="form-group">
                    <label class="form-label" for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input class="form-input" type="text" id="nama" name="nama" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email <span class="required">*</span></label>
                    <input class="form-input" type="email" id="email" name="email" placeholder="admin@stella.sch.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required">*</span></label>
                    <div class="password-toggle-wrapper">
                        <input class="form-input" type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePass('password', this)" title="Tampilkan/Sembunyikan Password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Tingkat Hak Akses (Role)</label>
                    <select class="form-select" id="role" name="role">
                        <option value="admin" selected>ADMIN (Kelola Siswa & Karya)</option>
                        <option value="super_admin">SUPER ADMIN (Akses Penuh Seluruh Sistem)</option>
                    </select>
                </div>

                <button type="submit" name="tambah_admin" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                    Tambah Akun Admin
                </button>
            </form>
        </section>

        <!-- BAGIAN KANAN: DAFTAR USER & FILTER -->
        <section class="card-list">
            <div class="card-list-header">
                <div class="card-list-title-wrap">
                    <h2 class="card-list-title">Seluruh Akun Terdaftar</h2>
                    <span class="count-pill" id="userCountBadge"><?= $total_all ?> User</span>
                </div>
            </div>

            <!-- Toolbar: Filter Role Tabs & Search Bar -->
            <div class="toolbar">
                <!-- Filter Pills -->
                <div class="filter-tabs" id="roleFilterTabs">
                    <button type="button" class="filter-pill active" data-role="all">Semua (<?= $total_all ?>)</button>
                    <button type="button" class="filter-pill" data-role="super_admin">Super Admin (<?= $total_super ?>)</button>
                    <button type="button" class="filter-pill" data-role="admin">Admin (<?= $total_admin ?>)</button>
                    <button type="button" class="filter-pill" data-role="siswa">Siswa (<?= $total_siswa ?>)</button>
                </div>

                <!-- Real-time Search Bar -->
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                        <input type="text" id="searchUser" class="search-input" placeholder="Cari nama atau email user..." autocomplete="off">
                        <button type="button" id="clearUserSearch" class="search-clear-btn" title="Hapus pencarian">✕</button>
                    </div>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table" id="tableUser">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th style="width: 140px; text-align: center;">Role</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyUser">
                        <?php $no = 1; foreach ($users as $u): ?>
                            <tr class="user-row" data-role="<?= $u['role'] ?>" data-nama="<?= strtolower(htmlspecialchars($u['nama'])) ?>" data-email="<?= strtolower(htmlspecialchars($u['email'])) ?>">
                                <td style="text-align: center; color: var(--neutral-400); font-weight: 600;" class="user-row-number"><?= $no++ ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                                        <div class="table-avatar-<?= $u['role'] ?>">
                                            <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                        </div>
                                        <div class="user-meta">
                                            <span class="user-name">
                                                <?= htmlspecialchars($u['nama']) ?>
                                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                    <span style="font-size: 11px; color: var(--primary-600); font-weight: 700;">(Anda)</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($u['role'] === 'super_admin'): ?>
                                        <span class="badge badge-super_admin">SUPER ADMIN</span>
                                    <?php elseif ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-admin">ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge badge-siswa">SISWA</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($u['role'] === 'super_admin'): ?>
                                        <span style="font-size: var(--text-xs); color: var(--neutral-400); font-style: italic; display: inline-flex; align-items: center; gap: 3px;" title="Akun Super Admin dilindungi oleh sistem">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                                            Dilindungi
                                        </span>
                                    <?php elseif ($u['id'] == $_SESSION['user_id']): ?>
                                        <span style="font-size: var(--text-xs); color: var(--neutral-400); font-style: italic;">
                                            Sedang Login
                                        </span>
                                    <?php else: ?>
                                        <a href="users.php?hapus=<?= $u['id'] ?>" class="btn-action-icon btn-danger-soft" onclick="return confirm('Apakah Anda yakin ingin menghapus akun \'<?= htmlspecialchars(addslashes($u['nama'])) ?>\' (<?= strtoupper($u['role']) ?>)? Tindakan ini tidak dapat dibatalkan!')" title="Hapus Akun Pengguna">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            Hapus
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Empty Search / Filter State -->
            <div id="noMatchMessage" style="display: none; text-align: center; padding: 48px 20px;">
                <div style="font-size: 36px; margin-bottom: 8px;">🔍</div>
                <h3 style="font-size: var(--text-md); color: var(--neutral-700); font-weight: 700; margin-bottom: 4px;">User tidak ditemukan</h3>
                <p style="font-size: var(--text-xs); color: var(--neutral-500); margin-bottom: 12px;">Tidak ada akun yang sesuai dengan filter role atau kata kunci pencarian Anda.</p>
                <button type="button" class="btn btn-secondary btn-sm" onclick="resetAllFilters()">Reset Filter & Pencarian</button>
            </div>
        </section>
    </div>
</main>

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
// Toggle Password
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" x2="23" y1="1" y2="23"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
}

// Real-time Search & Role Filter
const searchInput = document.getElementById('searchUser');
const clearBtn = document.getElementById('clearUserSearch');
const filterPills = document.querySelectorAll('.filter-pill');
const userRows = document.querySelectorAll('.user-row');
const tableWrapper = document.querySelector('.table-wrapper');
const noMatchMsg = document.getElementById('noMatchMessage');
const countBadge = document.getElementById('userCountBadge');

let activeRole = 'all';

function filterUsers() {
    const query = searchInput.value.toLowerCase().trim();
    clearBtn.classList.toggle('visible', query.length > 0);

    let matchCount = 0;
    let visibleIndex = 1;

    userRows.forEach(row => {
        const rowRole = row.getAttribute('data-role');
        const nama = row.getAttribute('data-nama') || '';
        const email = row.getAttribute('data-email') || '';

        const roleMatches = (activeRole === 'all' || rowRole === activeRole);
        const searchMatches = (nama.includes(query) || email.includes(query));

        if (roleMatches && searchMatches) {
            row.style.display = '';
            const numCell = row.querySelector('.user-row-number');
            if (numCell) numCell.textContent = visibleIndex++;
            matchCount++;
        } else {
            row.style.display = 'none';
        }
    });

    countBadge.textContent = matchCount + ' User';

    if (matchCount === 0) {
        tableWrapper.style.display = 'none';
        noMatchMsg.style.display = 'block';
    } else {
        tableWrapper.style.display = 'block';
        noMatchMsg.style.display = 'none';
    }
}

// Filter Role Pill Click
filterPills.forEach(pill => {
    pill.addEventListener('click', function() {
        filterPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeRole = this.getAttribute('data-role');
        filterUsers();
    });
});

// Search Input Listener
searchInput.addEventListener('input', filterUsers);

// Clear Search
clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    filterUsers();
    searchInput.focus();
});

function resetAllFilters() {
    searchInput.value = '';
    activeRole = 'all';
    filterPills.forEach(p => p.classList.toggle('active', p.getAttribute('data-role') === 'all'));
    filterUsers();
}
</script>

</body>
</html>
