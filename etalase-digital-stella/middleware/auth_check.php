<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        // Tentukan path ke login.php berdasarkan lokasi file yang memanggil
        if (file_exists('auth/login.php')) {
            $login_url = 'auth/login.php';
        } elseif (file_exists('../auth/login.php')) {
            $login_url = '../auth/login.php';
        } else {
            $login_url = '/etalase-digital-stella/auth/login.php';
        }
        header("Location: $login_url");
        exit();
    }
}

function check_role($roles = []) {
    check_login();
    if (!in_array($_SESSION['role'], $roles)) {
        // Redirect atau pesan akses ditolak
        if (file_exists('index.php')) {
            $home_url = 'index.php';
        } elseif (file_exists('../index.php')) {
            $home_url = '../index.php';
        } else {
            $home_url = '/etalase-digital-stella/index.php';
        }
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Akses Ditolak</title><link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700&display=swap'><style>body{font-family:'Plus Jakarta Sans',sans-serif;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}.box{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:32px;text-align:center;max-width:420px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);}.btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#ef4444;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;}</style></head><body><div class='box'><div style='font-size:48px;margin-bottom:12px;'>🚫</div><h2 style='margin:0 0 8px 0;'>Akses Ditolak</h2><p style='color:#94a3b8;margin:0 0 16px 0;font-size:14px;'>Anda tidak memiliki izin untuk mengakses halaman ini.</p><a href='$home_url' class='btn'>Kembali ke Beranda</a></div></body></html>";
        exit();
    }
}
?>
