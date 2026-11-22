<?php
require_once 'config/kuis_db.php';
require_once 'middleware/auth_check.php';

check_login();

// Ambil semua karya beserta nama pembuat
$stmt = $pdo->query("SELECT projects.*, users.nama AS pembuat 
                     FROM projects 
                     JOIN users ON projects.user_id = users.id 
                     ORDER BY projects.created_at DESC");
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etalase Digital Stella - Portofolio Siswa Telkom Schools</title>
    <link rel="stylesheet" href="assets/css/design-system.css">
    <style>
        /* ===== TOPBAR ===== */
        .topbar {
            background: var(--neutral-950);
            color: var(--neutral-400);
            font-size: var(--text-xs);
            padding: var(--space-2) var(--space-8);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-4);
            letter-spacing: 0.01em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            font-size: 11.5px;
            color: var(--neutral-400);
        }
        .topbar-left strong {
            color: var(--neutral-200);
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
        .topbar-item svg {
            width: 14px;
            height: 14px;
            opacity: 0.7;
        }
        .topbar strong {
            color: var(--neutral-200);
            font-weight: 600;
        }
        .topbar-divider {
            width: 1px;
            height: 14px;
            background: var(--neutral-700);
        }

        /* ===== NAVBAR ===== */
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
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: 10px 0;
            text-decoration: none;
        }
        .navbar-brand:hover { color: inherit; }

        /* Container Logo Telkom Schools */
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
            transition: transform var(--duration-fast), box-shadow var(--duration-fast);
        }
        .brand-logo-container:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        .brand-logo-img {
            height: 30px;
            width: auto;
            object-fit: contain;
        }

        .brand-divider {
            width: 1.5px;
            height: 32px;
            background: var(--border-light);
            border-radius: 1px;
        }
        .brand-title {
            font-size: var(--text-md);
            font-weight: 800;
            color: var(--neutral-900);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }
        .brand-sub {
            font-size: 11px;
            color: var(--neutral-500);
            font-weight: 500;
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
            gap: var(--space-2);
        }

        /* Mobile hamburger */
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: var(--space-2);
            color: var(--neutral-600);
        }

        /* ===== HERO SECTION DENGAN BACKGROUND GEDUNG SEKOLAH ===== */
        .hero {
            position: relative;
            background-color: var(--primary-950);
            background-image: 
                linear-gradient(135deg, rgba(25, 2, 2, 0.90) 0%, rgba(120, 15, 15, 0.85) 45%, rgba(185, 28, 28, 0.78) 100%),
                url('img/bg-telkom-optimized.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            color: var(--neutral-0);
            text-align: center;
            padding: var(--space-20) var(--space-6) var(--space-20);
            overflow: hidden;
            box-shadow: inset 0 -10px 30px rgba(0, 0, 0, 0.25);
        }

        .hero-pattern {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 15% 50%, rgba(255,255,255,0.06) 0%, transparent 40%),
                radial-gradient(circle at 85% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .hero-glow {
            position: absolute;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 450px;
            background: radial-gradient(ellipse, rgba(239, 68, 68, 0.35) 0%, transparent 70%);
            pointer-events: none;
            filter: blur(50px);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 680px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-full);
            padding: 6px 18px;
            font-size: var(--text-xs);
            font-weight: 700;
            margin-bottom: var(--space-6);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: var(--accent-amber);
            border-radius: 50%;
            animation: pulse 2s ease infinite;
            box-shadow: 0 0 10px var(--accent-amber);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.4; transform: scale(0.85); }
        }

        .hero h1 {
            font-size: var(--text-5xl);
            font-weight: 800;
            color: var(--neutral-0);
            margin-bottom: var(--space-4);
            letter-spacing: -0.025em;
            line-height: 1.15;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        .hero h1 .accent {
            background: linear-gradient(135deg, #ffd54f 0%, #ffca28 50%, #ffa000 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: var(--text-md);
            color: rgba(255, 255, 255, 0.88);
            max-width: 540px;
            margin: 0 auto var(--space-6);
            line-height: var(--leading-relaxed);
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
        }

        .hero-copyright-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            background: rgba(0, 0, 0, 0.35);
            padding: 4px 14px;
            border-radius: var(--radius-full);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* ===== WAVE ===== */
        .wave {
            margin-top: -1px;
            line-height: 0;
            position: relative;
            z-index: 1;
        }
        .wave svg { display: block; }

        /* ===== CONTENT AREA ===== */
        .content-area {
            max-width: 1280px;
            margin: 0 auto;
            padding: var(--space-8) var(--space-6) var(--space-16);
        }

        /* Search & Filter Header */
        .gallery-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-8);
            flex-wrap: wrap;
            gap: var(--space-4);
            background: var(--bg-surface);
            padding: var(--space-4) var(--space-6);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
        }
        .gallery-toolbar-left {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        .gallery-toolbar h2 {
            font-size: var(--text-xl);
            font-weight: 800;
            color: var(--neutral-900);
        }

        /* ===== PROJECT GRID ===== */
        .project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-6);
        }

        .project-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all var(--duration-normal) var(--ease-out);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .project-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
            border-color: var(--neutral-300);
        }

        .project-card-img-wrapper {
            position: relative;
            overflow: hidden;
            height: 205px;
            background: var(--neutral-100);
        }
        .project-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--duration-slow) var(--ease-out);
        }
        .project-card:hover .project-card-img {
            transform: scale(1.05);
        }

        .project-card-body {
            padding: var(--space-5);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .project-card-title {
            font-size: var(--text-md);
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: var(--space-1);
            line-height: var(--leading-tight);
        }

        .project-card-author {
            font-size: var(--text-xs);
            color: var(--neutral-500);
            margin-bottom: var(--space-3);
            display: flex;
            align-items: center;
            gap: var(--space-1);
        }
        .project-card-author strong {
            color: var(--primary-600);
            font-weight: 600;
        }

        .project-card-desc {
            font-size: var(--text-sm);
            color: var(--neutral-600);
            line-height: var(--leading-relaxed);
            flex: 1;
            margin-bottom: var(--space-4);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .project-card-buttons {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-2);
            margin-bottom: var(--space-3);
            flex-wrap: wrap;
        }

        .project-card-actions {
            display: flex;
            gap: var(--space-2);
            padding-top: var(--space-3);
            border-top: 1px solid var(--neutral-100);
        }

        /* ===== FOOTER DENGAN HAK CIPTA FARIS ATTAQY ===== */
        .footer {
            background: var(--neutral-950);
            padding: var(--space-12) var(--space-6) var(--space-8);
            text-align: center;
            color: var(--neutral-400);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .footer-inner {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-4);
        }
        .footer-logo-box {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            margin-bottom: var(--space-2);
        }
        .footer-logo-img {
            height: 34px;
            width: auto;
        }
        .footer-brand-text {
            font-size: var(--text-lg);
            font-weight: 800;
            color: var(--neutral-100);
        }
        .footer-brand-text span {
            color: var(--primary-500);
        }
        .footer-divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-500), transparent);
            margin: var(--space-2) 0;
        }
        .footer-copyright {
            font-size: var(--text-sm);
            color: var(--neutral-200);
            font-weight: 600;
        }
        .footer-copyright strong {
            color: #ffffff;
            font-weight: 700;
        }
        .footer-sub {
            font-size: var(--text-xs);
            color: var(--neutral-400);
            line-height: var(--leading-relaxed);
            max-width: 520px;
        }

        @media (max-width: 768px) {
            .topbar { display: none; }
            .navbar { padding: 0 var(--space-4); }
            .nav-toggle { display: block; }
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
            .gallery-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-container { max-width: 100%; }
            .hero { padding: var(--space-12) var(--space-4) var(--space-16); }
            .hero h1 { font-size: var(--text-3xl); }
            .content-area { padding: var(--space-6) var(--space-4); }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <span>🏫 <strong>Telkom Schools</strong> • Etalase Digital Stella</span>
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
    <a href="index.php" class="navbar-brand">
        <!-- Logo Telkom Schools Resmi -->
        <div class="brand-logo-container">
            <img src="img/logo-telkom-schools.png" alt="Logo Telkom Schools" class="brand-logo-img">
        </div>
        <div class="brand-divider"></div>
        <div>
            <div class="brand-title">Etalase Digital</div>
            <div class="brand-sub">Telkom Schools • Stella</div>
        </div>
    </a>

    <!-- Navigation Menu (Super Admin / Admin / Siswa) -->
    <div class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            Galeri Karya
        </a>
        <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin'): ?>
            <a href="admin/siswa.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
                Kelola Siswa
            </a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <a href="admin/users.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                Kelola User
            </a>
        <?php endif; ?>
    </div>

    <div class="navbar-actions">
        <a href="project_tambah.php" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            Tambah Karya
        </a>
        <a href="auth/logout.php" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Logout
        </a>
        <button class="nav-toggle" onclick="document.getElementById('navMenu').classList.toggle('active')" aria-label="Toggle Navigation">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>
    </div>
</nav>

<!-- HERO BANNER DENGAN BACKGROUND GEDUNG SEKOLAH -->
<section class="hero">
    <div class="hero-pattern"></div>
    <div class="hero-grid"></div>
    <div class="hero-glow"></div>
    <div class="hero-content animate-fade-in-up">
        <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            Telkom Schools • Stella
        </div>
        <h1>Etalase Digital <span class="accent">Stella</span></h1>
        <p>Eksplorasi portofolio dan hasil karya digital terbaik karya para siswa berbakat Telkom Schools.</p>
        
        <!-- Hak Cipta Faris Attaqy di Hero -->
        <div class="hero-copyright-tag">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9.354a4 4 0 1 0 0 5.292"/></svg>
            <span>Hak Cipta: <strong>Faris Attaqy</strong></span>
        </div>
    </div>
</section>
<div class="wave">
    <svg viewBox="0 0 1440 56" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" height="56">
        <path d="M0,32 C240,56 480,8 720,32 C960,56 1200,8 1440,32 L1440,56 L0,56 Z" fill="var(--bg-body)"/>
    </svg>
</div>

<!-- CONTENT AREA -->
<main class="content-area">
    <!-- Gallery Toolbar: Title, Count, & Search Bar -->
    <div class="gallery-toolbar">
        <div class="gallery-toolbar-left">
            <h2>Galeri Karya Digital</h2>
            <span class="count-pill" id="projectCountBadge"><?= count($projects) ?> Karya</span>
        </div>

        <!-- Real-Time Search Bar -->
        <div class="search-container">
            <div class="search-input-wrapper">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                <input type="text" id="searchProject" class="search-input" placeholder="Cari nama project atau pembuat..." autocomplete="off">
                <button type="button" id="clearSearchProject" class="search-clear-btn" title="Hapus pencarian">✕</button>
            </div>
        </div>
    </div>

    <?php if (empty($projects)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </div>
            <p class="empty-state-title">Belum ada karya</p>
            <p class="empty-state-desc">Jadilah yang pertama mengunggah karya digitalmu dan tunjukkan kreativitasmu!</p>
            <a href="project_tambah.php" class="btn btn-primary" style="margin-top: var(--space-5);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                Tambah Karya Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="project-grid" id="projectGrid">
            <?php foreach($projects as $p): ?>
                <div class="project-card animate-fade-in-up card-item" 
                     data-title="<?= strtolower(htmlspecialchars($p['nama_project'])) ?>" 
                     data-author="<?= strtolower(htmlspecialchars($p['pembuat'])) ?>"
                     data-id="<?= $p['id'] ?>">
                    
                    <div class="project-card-img-wrapper">
                        <img class="project-card-img" src="uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama_project']) ?>" loading="lazy">
                    </div>
                    <div class="project-card-body">
                        <h3 class="project-card-title"><?= htmlspecialchars($p['nama_project']) ?></h3>
                        <p class="project-card-author">
                            Oleh <strong><?= htmlspecialchars($p['pembuat']) ?></strong>
                        </p>
                        <p class="project-card-desc"><?= htmlspecialchars($p['deskripsi']) ?></p>

                        <!-- Card Action Buttons -->
                        <div class="project-card-buttons">
                            <!-- Tombol Detail Modal -->
                            <button type="button" class="btn btn-secondary btn-xs" onclick='openProjectDetail(<?= json_encode($p) ?>)'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                Detail Karya
                            </button>

                            <!-- Link ke Website / Repository -->
                            <a href="<?= htmlspecialchars($p['link_project']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-xs">
                                <span>Lihat Project</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                            </a>
                        </div>

                        <!-- Hak Akses Edit/Hapus: Owner / Admin / Super Admin -->
                        <?php if($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $p['user_id']): ?>
                            <div class="project-card-actions">
                                <a href="project_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning-soft btn-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    Edit
                                </a>
                                <a href="project_hapus.php?id=<?= $p['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus karya ini?')" class="btn btn-danger-soft btn-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    Hapus
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty Search State -->
        <div id="noMatchProjects" style="display: none; text-align: center; padding: 60px 20px;">
            <div style="font-size: 42px; margin-bottom: 12px;">🎨</div>
            <h3 style="font-size: var(--text-lg); color: var(--neutral-800); font-weight: 700; margin-bottom: 4px;">Karya tidak ditemukan</h3>
            <p style="font-size: var(--text-sm); color: var(--neutral-500); margin-bottom: 16px;">Tidak ada karya yang sesuai dengan kata kunci pencarian Anda.</p>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearProjectSearch()">Reset Pencarian</button>
        </div>
    <?php endif; ?>
</main>

<!-- MODAL DETAIL PROJECT -->
<div class="modal" id="projectDetailModal">
    <div class="modal-backdrop" onclick="closeProjectDetail()"></div>
    <div class="modal-container" style="max-width: 640px;">
        <div class="modal-header">
            <h3 class="modal-title" id="modalDetailTitle">Detail Karya Digital</h3>
            <button type="button" class="modal-close-btn" onclick="closeProjectDetail()">✕</button>
        </div>
        <div class="modal-body">
            <img src="" alt="Thumbnail Karya" id="modalDetailImg" class="modal-detail-img">

            <div class="modal-detail-meta">
                <span class="badge badge-primary" id="modalDetailAuthorBadge">Oleh Siswa</span>
                <span style="font-size: var(--text-xs); color: var(--neutral-500);" id="modalDetailDate">Diunggah: -</span>
            </div>

            <div style="font-size: var(--text-xs); font-weight: 700; color: var(--neutral-500); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Deskripsi Karya:</div>
            <div class="modal-detail-desc" id="modalDetailDesc">
                -
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeProjectDetail()">Tutup</button>
            <a href="#" target="_blank" rel="noopener" id="modalDetailLink" class="btn btn-primary btn-sm">
                <span>Kunjungi Tautan Project</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
            </a>
        </div>
    </div>
</div>

<!-- FOOTER RESMI DENGAN HAK CIPTA FARIS ATTAQY -->
<footer class="footer">
    <div class="footer-inner">
        <!-- Logo Telkom Schools di Footer -->
        <div class="footer-logo-box">
            <img src="img/logo-telkom-schools.png" alt="Telkom Schools Logo" class="footer-logo-img">
        </div>
        
        <div class="footer-brand-text"><span>Etalase Digital</span> Stella</div>
        <div class="footer-divider"></div>
        
        <p class="footer-copyright">
            Hak Cipta &copy; <?= date('Y') ?> <strong>Faris Attaqy</strong>. Seluruh Hak Cipta Dilindungi.
        </p>
        <p class="footer-sub">
            Platform Etalase Karya Digital dikembangkan dengan bangga oleh <strong>Faris Attaqy</strong> untuk <strong>Telkom Schools</strong> (Stella).
        </p>
    </div>
</footer>

<script>
// Modal Detail Project
function openProjectDetail(project) {
    document.getElementById('modalDetailTitle').textContent = project.nama_project;
    document.getElementById('modalDetailImg').src = 'uploads/' + project.gambar;
    document.getElementById('modalDetailAuthorBadge').textContent = 'Oleh: ' + project.pembuat;
    document.getElementById('modalDetailDesc').textContent = project.deskripsi;
    document.getElementById('modalDetailLink').href = project.link_project;

    if (project.created_at) {
        document.getElementById('modalDetailDate').textContent = 'Diunggah: ' + project.created_at;
    } else {
        document.getElementById('modalDetailDate').textContent = '';
    }

    document.getElementById('projectDetailModal').classList.add('active');
}

function closeProjectDetail() {
    document.getElementById('projectDetailModal').classList.remove('active');
}

// Real-Time Search Projects
const searchProjectInput = document.getElementById('searchProject');
const clearSearchProjectBtn = document.getElementById('clearSearchProject');
const projectCards = document.querySelectorAll('.card-item');
const projectGrid = document.getElementById('projectGrid');
const noMatchProjects = document.getElementById('noMatchProjects');
const projectCountBadge = document.getElementById('projectCountBadge');

function applyProjectSearch() {
    const query = searchProjectInput.value.toLowerCase().trim();
    clearSearchProjectBtn.classList.toggle('visible', query.length > 0);

    let matchCount = 0;

    projectCards.forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const author = card.getAttribute('data-author') || '';

        if (title.includes(query) || author.includes(query)) {
            card.style.display = 'flex';
            matchCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (projectCountBadge) {
        projectCountBadge.textContent = matchCount + ' Karya';
    }

    if (projectCards.length > 0) {
        if (matchCount === 0) {
            projectGrid.style.display = 'none';
            noMatchProjects.style.display = 'block';
        } else {
            projectGrid.style.display = 'grid';
            noMatchProjects.style.display = 'none';
        }
    }
}

function clearProjectSearch() {
    searchProjectInput.value = '';
    applyProjectSearch();
    searchProjectInput.focus();
}

if (searchProjectInput) {
    searchProjectInput.addEventListener('input', applyProjectSearch);
}
if (clearSearchProjectBtn) {
    clearSearchProjectBtn.addEventListener('click', clearProjectSearch);
}
</script>

</body>
</html>
