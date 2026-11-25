<?php
/**
 * config/database.php
 * Koneksi ke PostgreSQL (Neon) menggunakan PDO (pdo_pgsql) dengan SSL.
 * Kredensial dibaca dari file .env di root proyek (atau environment variable server).
 */

/**
 * Muat variabel dari file .env ke $_ENV jika belum ada.
 * Parser sederhana tanpa dependency Composer.
 */
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Hilangkan kutip tunggal/ganda di awal & akhir jika ada
        if (strlen($val) >= 2 && (($val[0] === '"' && $val[-1] === '"') || ($val[0] === "'" && $val[-1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        if ($key !== '' && !isset($_ENV[$key]) && getenv($key) === false) {
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

loadEnv(__DIR__ . '/../.env');

/**
 * Ambil nilai environment variable dengan fallback default.
 */
function envVal(string $key, string $default = ''): string
{
    $val = $_ENV[$key] ?? getenv($key);
    return ($val === false || $val === null || $val === '') ? $default : (string) $val;
}

/**
 * Mengembalikan instance PDO PostgreSQL (singleton sederhana).
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host     = envVal('DB_HOST');
        $port     = envVal('DB_PORT', '5432');
        $name     = envVal('DB_NAME', 'neondb');
        $user     = envVal('DB_USER');
        $pass     = envVal('DB_PASS');
        $sslmode  = envVal('DB_SSLMODE', 'require');
        $endpoint = envVal('DB_ENDPOINT');

        if ($host === '' || $user === '') {
            http_response_code(500);
            die(json_encode(['error' => 'Konfigurasi database belum lengkap. Periksa file .env']));
        }

        // Bangun DSN PostgreSQL dengan SSL.
        // Parameter options=endpoint=... diperlukan agar libpq versi lama (<14) di XAMPP
        // tetap bisa terhubung ke Neon tanpa SNI.
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $host, $port, $name, $sslmode
        );
        if ($endpoint !== '') {
            $dsn .= ";options='endpoint=" . $endpoint . "'";
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Koneksi database PostgreSQL gagal. Periksa konfigurasi .env dan ekstensi pdo_pgsql.']));
        }
    }

    return $pdo;
}
