<?php
require_once '../middleware/auth_check.php';

check_login();

if ($_SESSION['role'] === 'super_admin') {
    header('Location: users.php');
    exit();
} elseif ($_SESSION['role'] === 'admin') {
    header('Location: siswa.php');
    exit();
} else {
    header('Location: ../index.php');
    exit();
}
?>
