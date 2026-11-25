<?php
/**
 * login_process.php
 * Memproses form login: validasi, cek password, set session.
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /bakecost/login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');
$error    = '';

// Validasi input kosong
if (empty($email) || empty($password)) {
    $error = 'Email dan password wajib diisi.';
} else {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, nama, email, password_hash FROM pengguna WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Email atau password salah.';
    } else {
        // Set session berhasil login
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['nama'];
        $_SESSION['user_email'] = $user['email'];

        header('Location: /bakecost/pages/beranda.php');
        exit;
    }
}

// Gagal — kembali ke login dengan pesan error
$_SESSION['auth_error'] = $error;
header('Location: /bakecost/login.php');
exit;

