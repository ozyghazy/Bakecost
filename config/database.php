<?php
/**
 * database.php
 * Koneksi ke MySQL menggunakan PDO.
 * Ubah DB_HOST, DB_NAME, DB_USER, DB_PASS sesuai konfigurasi lokal kamu.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'bakecost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Kosong = default XAMPP. Ubah jika pakai password.
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan instance PDO (singleton sederhana).
 * Throws exception jika koneksi gagal.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Self-healing migration jika pengguna mengimpor bakecost.sql awal
            try {
                $pdo->exec("ALTER TABLE produksi ADD COLUMN tipe ENUM('konversi','biaya') NOT NULL DEFAULT 'biaya' AFTER harga_jual");
            } catch (Throwable $ignored) {
                // Kolom sudah ada atau tabel belum diimport, abaikan
            }
        } catch (PDOException $e) {
            // Tampilkan pesan ramah, sembunyikan detail teknis di produksi
            http_response_code(500);
            die(json_encode(['error' => 'Koneksi database gagal. Periksa konfigurasi di config/database.php']));
        }
    }

    return $pdo;
}

