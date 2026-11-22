<?php
session_start();
require_once '../config/kuis_db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'siswa')");
        $stmt->execute([':nama' => $nama, ':email' => $email, ':password' => $password]);
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        $error = "Email sudah terdaftar atau terjadi kesalahan sistem.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Siswa - Etalase Digital Stella</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--primary-950);
            background-image: 
                linear-gradient(135deg, rgba(6, 78, 59, 0.88) 0%, rgba(15, 23, 42, 0.85) 60%, rgba(185, 28, 28, 0.80) 100%),
                url('../img/bg-telkom-optimized.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            position: relative;
            padding: var(--space-6) var(--space-4);
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 2;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-2xl);
            padding: var(--space-10) var(--space-8);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2);
            animation: fadeInUp var(--duration-slow) var(--ease-out);
        }

        .auth-brand {
            text-align: center;
            margin-bottom: var(--space-6);
        }
        .auth-logo-box {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: 8px 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }
        .auth-logo-img {
            height: 38px;
            width: auto;
            object-fit: contain;
        }
        .auth-brand h1 {
            font-size: var(--text-2xl);
            font-weight: 800;
            color: var(--neutral-900);
            margin-bottom: var(--space-1);
            letter-spacing: -0.02em;
        }
        .auth-brand p {
            font-size: var(--text-sm);
            color: var(--neutral-500);
        }

        .auth-form .form-group {
            margin-bottom: var(--space-5);
        }
        .auth-form .form-label {
            margin-bottom: var(--space-2);
            font-weight: 600;
        }
        .auth-form .form-input {
            padding: 0.75rem 0.875rem;
            background: #ffffff;
        }

        .auth-submit {
            width: 100%;
            padding: 0.85rem;
            font-size: var(--text-md);
            font-weight: 700;
            margin-top: var(--space-2);
            border-radius: var(--radius-lg);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--space-6);
            font-size: var(--text-sm);
            color: var(--neutral-600);
        }
        .auth-footer a {
            color: var(--primary-600);
            font-weight: 700;
        }
        .auth-footer a:hover {
            color: var(--primary-700);
            text-decoration: underline;
        }

        /* Password strength hint */
        .password-hint {
            display: flex;
            gap: var(--space-1);
            margin-top: var(--space-2);
        }
        .password-hint-bar {
            flex: 1;
            height: 3px;
            background: var(--neutral-200);
            border-radius: var(--radius-full);
            transition: background var(--duration-normal);
        }

        /* Hak Cipta Faris Attaqy di Bawah Auth */
        .auth-copyright {
            text-align: center;
            margin-top: var(--space-6);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.75);
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
        }
        .auth-copyright strong {
            color: #ffffff;
            font-weight: 700;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: var(--space-8) var(--space-5);
            }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-brand">
            <!-- Logo Telkom Schools Resmi -->
            <div class="auth-logo-box">
                <img src="../img/logo-telkom-schools.png" alt="Logo Telkom Schools" class="auth-logo-img">
            </div>
            <h1>Pendaftaran Siswa</h1>
            <p>Buat akun untuk mengunggah karya digital Anda</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger" style="margin-bottom: var(--space-5);">
                <span class="alert-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                </span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="nama">Nama Lengkap</label>
                <input class="form-input" type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap siswa" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Alamat Email</label>
                <input class="form-input" type="email" id="email" name="email" placeholder="siswa@stella.sch.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                <div class="password-hint" id="passStrength">
                    <div class="password-hint-bar"></div>
                    <div class="password-hint-bar"></div>
                    <div class="password-hint-bar"></div>
                    <div class="password-hint-bar"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-success auth-submit">Daftar Akun Baru</button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="login.php">Masuk Sekarang</a>
        </div>
    </div>

    <!-- Hak Cipta Faris Attaqy -->
    <div class="auth-copyright">
        Hak Cipta &copy; <?= date('Y') ?> <strong>Faris Attaqy</strong> • Telkom Schools. All rights reserved.
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const bars = document.querySelectorAll('.password-hint-bar');
    const val = this.value;
    let strength = 0;
    if (val.length >= 6) strength++;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
    if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) strength++;

    const colors = ['', '#ef4444', '#f59e0b', '#0ea5e9', '#10b981'];
    bars.forEach((bar, i) => {
        bar.style.background = i < strength ? colors[strength] : '#e2e8f0';
    });
});
</script>

</body>
</html>
