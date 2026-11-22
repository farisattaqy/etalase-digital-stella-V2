<?php
require_once 'config/kuis_db.php';
require_once 'middleware/auth_check.php';

check_login();

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit(); }

// Fetch data project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if (!$project) { die("Data karya tidak ditemukan."); }

// Pengecekan Otorisasi
if ($_SESSION['role'] === 'siswa' && $project['user_id'] != $_SESSION['user_id']) {
    die("Akses Ditolak: Anda tidak dapat mengedit karya milik siswa lain.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_project = trim($_POST['nama_project']);
    $deskripsi    = trim($_POST['deskripsi']);
    $link_project = trim($_POST['link_project']);
    $gambar_nama  = $project['gambar'];

    // Jika user mengunggah gambar baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid('project_', true) . '.' . $ext;
        
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], 'uploads/' . $new_name)) {
            if (file_exists('uploads/' . $project['gambar'])) {
                unlink('uploads/' . $project['gambar']); // Hapus gambar lama
            }
            $gambar_nama = $new_name;
        }
    }

    $stmt = $pdo->prepare("UPDATE projects SET nama_project = :nama, deskripsi = :desk, gambar = :img, link_project = :link WHERE id = :id");
    $stmt->execute([':nama' => $nama_project, ':desk' => $deskripsi, ':img' => $gambar_nama, ':link' => $link_project, ':id' => $id]);
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karya - Etalase Digital Stella</title>
    <link rel="stylesheet" href="assets/css/design-system.css">
    <style>
        body {
            background: var(--bg-body);
        }

        .page-header {
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid var(--border-light);
            padding: var(--space-4) var(--space-6);
            position: sticky;
            top: 0;
            z-index: var(--z-sticky);
            backdrop-filter: blur(12px);
        }
        .page-header-inner {
            max-width: 760px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-4);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }
        .page-header h1 {
            font-size: var(--text-lg);
            font-weight: 800;
        }
        .page-header p {
            font-size: var(--text-xs);
            color: var(--neutral-500);
        }

        .form-page {
            max-width: 760px;
            margin: var(--space-8) auto;
            padding: 0 var(--space-6);
            animation: fadeInUp var(--duration-slow) var(--ease-out);
        }

        .form-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-sm);
        }

        .form-section-title {
            font-size: var(--text-sm);
            font-weight: 700;
            color: var(--neutral-700);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: var(--space-5);
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .current-image {
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border-light);
            margin-bottom: var(--space-4);
            position: relative;
        }
        .current-image img {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
        }
        .current-image .image-label {
            position: absolute;
            top: var(--space-3);
            left: var(--space-3);
            background: rgba(0,0,0,0.6);
            color: white;
            font-size: var(--text-xs);
            font-weight: 600;
            padding: 2px 10px;
            border-radius: var(--radius-full);
            backdrop-filter: blur(4px);
        }

        .upload-area {
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--space-6) var(--space-4);
            text-align: center;
            cursor: pointer;
            transition: all var(--duration-normal) var(--ease-out);
            background: var(--neutral-25);
            position: relative;
        }
        .upload-area:hover {
            border-color: var(--primary-300);
            background: var(--primary-50);
        }
        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .upload-icon {
            width: 40px;
            height: 40px;
            background: var(--neutral-100);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-2);
            color: var(--neutral-400);
        }
        .upload-text {
            font-size: var(--text-sm);
            color: var(--neutral-600);
            font-weight: 500;
        }
        .upload-hint {
            font-size: var(--text-xs);
            color: var(--neutral-400);
            margin-top: var(--space-1);
        }

        .preview-container {
            margin-top: var(--space-4);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: none;
            border: 1px solid var(--border-light);
            position: relative;
        }
        .preview-container.active {
            display: block;
        }
        .preview-container img {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
        }
        .preview-container .preview-label {
            position: absolute;
            top: var(--space-3);
            left: var(--space-3);
            background: rgba(16,185,129,0.85);
            color: white;
            font-size: var(--text-xs);
            font-weight: 600;
            padding: 2px 10px;
            border-radius: var(--radius-full);
        }

        .form-actions {
            display: flex;
            gap: var(--space-3);
            margin-top: var(--space-6);
            padding-top: var(--space-6);
            border-top: 1px solid var(--neutral-100);
        }
        .form-actions .btn-warning {
            flex: 1;
        }

        /* Footer */
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

        @media (max-width: 768px) {
            .form-card { padding: var(--space-5); }
            .form-page { margin: var(--space-4) auto; padding: 0 var(--space-4); }
            .page-header { padding: var(--space-3) var(--space-4); }
        }
    </style>
</head>
<body>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-inner">
        <div class="header-left">
            <a href="index.php" class="btn btn-secondary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
            <div>
                <h1>Edit Karya Digital</h1>
                <p>Perbarui informasi karya digital portofolio</p>
            </div>
        </div>
        <div style="background: white; border: 1px solid var(--border-light); padding: 4px 10px; border-radius: 8px;">
            <img src="img/logo-telkom-schools.png" alt="Telkom Schools" style="height: 24px;">
        </div>
    </div>
</div>

<div class="form-page">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-card">
            <div class="form-section-title">Informasi Karya</div>

            <div class="form-group">
                <label class="form-label" for="nama_project">Nama Karya Digital <span class="required">*</span></label>
                <input class="form-input" type="text" id="nama_project" name="nama_project" value="<?= htmlspecialchars($project['nama_project']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="deskripsi">Deskripsi Karya <span class="required">*</span></label>
                <textarea class="form-input form-textarea" id="deskripsi" name="deskripsi" required><?= htmlspecialchars($project['deskripsi']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="link_project">Link Tautan Project <span class="required">*</span></label>
                <input class="form-input" type="url" id="link_project" name="link_project" value="<?= htmlspecialchars($project['link_project']) ?>" required>
                <span class="form-hint">Tautan GitHub, GitLab, atau demo website live</span>
            </div>
        </div>

        <div class="form-card" style="margin-top: var(--space-5);">
            <div class="form-section-title">Media Tangkapan Layar</div>

            <div class="form-group">
                <label class="form-label">Gambar Saat Ini</label>
                <div class="current-image">
                    <span class="image-label">Gambar Aktif</span>
                    <img src="uploads/<?= htmlspecialchars($project['gambar']) ?>" alt="Gambar saat ini">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ganti Gambar (Opsional)</label>
                <div class="upload-area">
                    <input type="file" id="gambar" name="gambar" accept="image/*">
                    <div class="upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    </div>
                    <p class="upload-text">Klik untuk mengunggah gambar pengganti</p>
                    <p class="upload-hint">Kosongkan kolom ini bila tidak ingin mengganti gambar</p>
                </div>
                <div class="preview-container" id="previewContainer">
                    <span class="preview-label">Gambar Baru</span>
                    <img id="imagePreview" src="" alt="Preview">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-warning btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<!-- FOOTER DENGAN HAK CIPTA FARIS ATTAQY -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-logo-box">
            <img src="img/logo-telkom-schools.png" alt="Telkom Schools" class="footer-logo-img">
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
document.addEventListener('DOMContentLoaded', function() {
    const inputGambar = document.getElementById('gambar');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');

    inputGambar.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.addEventListener('load', function() {
                imagePreview.setAttribute('src', this.result);
                previewContainer.classList.add('active');
            });
            reader.readAsDataURL(file);
        } else {
            imagePreview.setAttribute('src', '');
            previewContainer.classList.remove('active');
        }
    });
});
</script>

</body>
</html>
