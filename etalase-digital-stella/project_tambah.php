<?php
session_start();
require_once 'config/kuis_db.php';
require_once 'middleware/auth_check.php';

check_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_project = trim($_POST['nama_project'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $link_project = trim($_POST['link_project'] ?? '');
    $user_id      = $_SESSION['user_id'];

    if (empty($nama_project) || empty($deskripsi) || empty($link_project)) {
        $error = "Semua kolom input wajib diisi!";
    } elseif (!filter_var($link_project, FILTER_VALIDATE_URL)) {
        $error = "Format Link Project tidak valid (Gunakan format https://...)!";
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        $error = "Pilih gambar karya digital terlebih dahulu!";
    } else {
        $file_tmp   = $_FILES['gambar']['tmp_name'];
        $file_name  = $_FILES['gambar']['name'];
        $file_size  = $_FILES['gambar']['size'];
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        $max_size    = 2 * 1024 * 1024; // Maksimal 2MB

        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Format gambar harus berupa JPG, JPEG, PNG, atau WEBP!";
        } elseif ($file_size > $max_size) {
            $error = "Ukuran berkas gambar tidak boleh lebih dari 2MB!";
        } else {
            $new_file_name = uniqid('project_', true) . '.' . $file_ext;
            $upload_dir    = 'uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project) VALUES (:user_id, :nama_project, :deskripsi, :gambar, :link_project)");
                    $stmt->execute([
                        ':user_id'      => $user_id,
                        ':nama_project' => $nama_project,
                        ':deskripsi'    => $deskripsi,
                        ':gambar'       => $new_file_name,
                        ':link_project' => $link_project
                    ]);

                    header('Location: index.php');
                    exit();
                } catch (PDOException $e) {
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                    $error = "Terjadi kesalahan sistem: " . $e->getMessage();
                }
            } else {
                $error = "Gagal mengunggah berkas gambar ke server.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karya - Etalase Digital Stella</title>
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

        .upload-area {
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--space-8) var(--space-4);
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
        .upload-area.dragover {
            border-color: var(--primary-500);
            background: var(--primary-50);
            box-shadow: 0 0 0 3px var(--primary-100);
        }
        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .upload-icon {
            width: 48px;
            height: 48px;
            background: var(--neutral-100);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-3);
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
            max-height: 280px;
            object-fit: cover;
        }
        .preview-container .preview-label {
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

        .form-actions {
            display: flex;
            gap: var(--space-3);
            margin-top: var(--space-6);
            padding-top: var(--space-6);
            border-top: 1px solid var(--neutral-100);
        }
        .form-actions .btn-primary {
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
                <h1>Tambah Karya Digital</h1>
                <p>Unggah karya terbaikmu ke Etalase Stella</p>
            </div>
        </div>
        <div style="background: white; border: 1px solid var(--border-light); padding: 4px 10px; border-radius: 8px;">
            <img src="img/logo-telkom-schools.png" alt="Telkom Schools" style="height: 24px;">
        </div>
    </div>
</div>

<div class="form-page">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: var(--space-5);">
            <span class="alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            </span>
            <span><?= htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-card">
            <div class="form-section-title">Informasi Karya Digital</div>

            <div class="form-group">
                <label class="form-label" for="nama_project">Nama Karya Digital <span class="required">*</span></label>
                <input class="form-input" type="text" id="nama_project" name="nama_project" placeholder="Contoh: Aplikasi Pengelolaan Perpustakaan" value="<?= htmlspecialchars($_POST['nama_project'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="deskripsi">Deskripsi Karya <span class="required">*</span></label>
                <textarea class="form-input form-textarea" id="deskripsi" name="deskripsi" placeholder="Jelaskan mengenai fitur, tujuan, atau teknologi karya yang Anda buat..." required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="link_project">Link Tautan Project <span class="required">*</span></label>
                <input class="form-input" type="url" id="link_project" name="link_project" placeholder="https://github.com/username/project" value="<?= htmlspecialchars($_POST['link_project'] ?? '') ?>" required>
                <span class="form-hint">Tautan GitHub, GitLab, atau demo website langsung</span>
            </div>
        </div>

        <div class="form-card" style="margin-top: var(--space-5);">
            <div class="form-section-title">Media Tangkapan Layar</div>

            <div class="form-group">
                <label class="form-label">Unggah Pratinjau Gambar Karya <span class="required">*</span></label>
                <div class="upload-area" id="uploadArea">
                    <input type="file" id="gambar" name="gambar" accept="image/png, image/jpeg, image/jpg, image/webp" required>
                    <div class="upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    </div>
                    <p class="upload-text">Klik atau seret gambar ke area ini</p>
                    <p class="upload-hint">Format JPG, PNG, atau WEBP • Maksimal 2MB</p>
                </div>
                <div class="preview-container" id="previewContainer">
                    <span class="preview-label">Pratinjau Gambar</span>
                    <img id="imagePreview" src="" alt="Preview Gambar">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Publikasikan Karya
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
    const uploadArea = document.getElementById('uploadArea');

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

    // Drag & drop visual feedback
    ['dragenter', 'dragover'].forEach(e => {
        uploadArea.addEventListener(e, function(ev) {
            ev.preventDefault();
            uploadArea.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(e => {
        uploadArea.addEventListener(e, function(ev) {
            ev.preventDefault();
            uploadArea.classList.remove('dragover');
        });
    });
});
</script>

</body>
</html>
