<?php
/**
 * auth_guard.php
 * Sertakan file ini di setiap halaman yang butuh login.
 * Jika session tidak aktif, user diarahkan ke halaman login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /bakecost/login.php');
    exit;
}

/**
 * Mengembalikan ID pengguna yang sedang login.
 */
function currentUserId(): int
{
    return (int) $_SESSION['user_id'];
}

/**
 * Mengembalikan nama pengguna yang sedang login.
 */
function currentUserName(): string
{
    return htmlspecialchars($_SESSION['user_name'] ?? 'Pengguna');
}

/**
 * Mengembalikan email pengguna yang sedang login.
 */
function currentUserEmail(): string
{
    return htmlspecialchars($_SESSION['user_email'] ?? '');
}

