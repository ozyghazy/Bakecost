<?php
/**
 * register_process.php
 * Memproses form register: validasi, hash password, insert ke tabel pengguna.
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /bakecost/register.php');
    exit;
}

$nama     = trim($_POST['nama']      ?? '');
$email    = trim($_POST['email']     ?? '');
$password = trim($_POST['password']  ?? '');
$konfirmasi = trim($_POST['konfirmasi'] ?? '');
$error    = '';

// Validasi
if (empty($nama) || empty($email) || empty($password)) {
    $error = 'Semua field wajib diisi.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid.';
} elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter.';
} elseif ($password !== $konfirmasi) {
    $error = 'Konfirmasi password tidak cocok.';
} else {
    $db = getDB();

    // Cek apakah email sudah terdaftar
    $check = $db->prepare('SELECT id FROM pengguna WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        $error = 'Email sudah terdaftar. Gunakan email lain atau login.';
    } else {
        // Hash password & simpan
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $db->prepare('INSERT INTO pengguna (nama, email, password_hash, role) VALUES (?, ?, ?, \'user\')');
        $ins->execute([$nama, $email, $hash]);

        $_SESSION['auth_success'] = 'Akun berhasil dibuat! Silakan login.';
        header('Location: /bakecost/login.php');
        exit;
    }
}

// Gagal — kembali ke register
$_SESSION['auth_error'] = $error;
header('Location: /bakecost/register.php');
exit;

