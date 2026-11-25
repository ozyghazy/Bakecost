<?php
/**
 * api/bahan_baku.php
 * REST endpoint untuk manajemen bahan baku.
 *
 * GET    /api/bahan_baku.php              → list semua bahan baku milik user
 * GET    /api/bahan_baku.php?id={id}      → detail satu bahan baku
 * POST   /api/bahan_baku.php              → tambah bahan baku baru
 * PUT    /api/bahan_baku.php?id={id}      → update bahan baku
 * DELETE /api/bahan_baku.php?id={id}      → hapus bahan baku
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Hanya boleh diakses via AJAX (XMLHttpRequest)
header('Content-Type: application/json');

// Cek login — endpoint ini hanya untuk user yang sudah login
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
$db     = getDB();

// ── Helper: kirim JSON response ──
function respond(mixed $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Helper: pastikan bahan baku milik user ──
function ownsItem(PDO $db, int $id, int $userId): bool
{
    $s = $db->prepare('SELECT id FROM bahan_baku WHERE id = ? AND pengguna_id = ?');
    $s->execute([$id, $userId]);
    return (bool) $s->fetch();
}

// ────────────────────────────────────────────────────
// GET — Ambil daftar atau detail
// ────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        // Detail satu item
        $stmt = $db->prepare('SELECT id, nama, kategori, satuan, jumlah_beli, harga_beli, harga_per_satuan, updated_at FROM bahan_baku WHERE id = ? AND pengguna_id = ?');
        $stmt->execute([$id, $userId]);
        $item = $stmt->fetch();

        if (!$item) respond(['error' => 'Bahan baku tidak ditemukan.'], 404);
        respond($item);
    }

    // List semua, dengan opsional filter kategori & search
    $where  = 'WHERE pengguna_id = ?';
    $params = [$userId];

    if (!empty($_GET['kategori'])) {
        $where   .= ' AND kategori = ?';
        $params[] = $_GET['kategori'];
    }
    if (!empty($_GET['q'])) {
        $where   .= ' AND nama ILIKE ?';
        $params[] = '%' . $_GET['q'] . '%';
    }

    $stmt = $db->prepare("SELECT id, nama, kategori, satuan, jumlah_beli, harga_beli, harga_per_satuan, updated_at FROM bahan_baku $where ORDER BY nama ASC");
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

// ────────────────────────────────────────────────────
// POST — Tambah bahan baku baru
// ────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $nama        = trim($body['nama']        ?? '');
    $kategori    = trim($body['kategori']    ?? '');
    $satuan      = trim($body['satuan']      ?? 'g');
    $jumlahBeli  = (float) ($body['jumlah_beli']  ?? 0);
    $hargaBeli   = (float) ($body['harga_beli']   ?? 0);

    // Validasi wajib
    if (empty($nama))        respond(['error' => 'Nama bahan wajib diisi.'], 422);
    if ($jumlahBeli <= 0)    respond(['error' => 'Jumlah beli harus lebih dari 0.'], 422);
    if ($hargaBeli  <= 0)    respond(['error' => 'Harga beli harus lebih dari 0.'], 422);

    $satuanValid = ['g', 'kg', 'ml', 'l', 'pcs', 'butir'];
    if (!in_array($satuan, $satuanValid, true)) respond(['error' => 'Satuan tidak valid.'], 422);

    try {
        $stmt = $db->prepare('INSERT INTO bahan_baku (pengguna_id, nama, kategori, satuan, jumlah_beli, harga_beli) VALUES (?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([$userId, $nama, $kategori ?: null, $satuan, $jumlahBeli, $hargaBeli]);

        $newId = (int) $stmt->fetchColumn();

        // Ambil data yang baru masuk termasuk harga_per_satuan (kolom generated)
        $get = $db->prepare('SELECT id, nama, kategori, satuan, jumlah_beli, harga_beli, harga_per_satuan FROM bahan_baku WHERE id = ?');
        $get->execute([$newId]);

        respond(['message' => 'Bahan baku berhasil ditambahkan.', 'data' => $get->fetch()], 201);
    } catch (PDOException $e) {
        // Unique constraint violation di PostgreSQL: SQLSTATE 23505
        if ($e->getCode() === '23505') {
            respond(['error' => "Bahan baku dengan nama '$nama' sudah ada."], 409);
        }
        respond(['error' => 'Gagal menyimpan data.'], 500);
    }
}

// ────────────────────────────────────────────────────
// PUT — Update bahan baku
// ────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    if (!ownsItem($db, $id, $userId)) respond(['error' => 'Data tidak ditemukan.'], 404);

    $body = json_decode(file_get_contents('php://input'), true);

    $nama       = trim($body['nama']       ?? '');
    $kategori   = trim($body['kategori']   ?? '');
    $satuan     = trim($body['satuan']     ?? 'g');
    $jumlahBeli = (float) ($body['jumlah_beli'] ?? 0);
    $hargaBeli  = (float) ($body['harga_beli']  ?? 0);

    if (empty($nama))     respond(['error' => 'Nama bahan wajib diisi.'], 422);
    if ($jumlahBeli <= 0) respond(['error' => 'Jumlah beli harus lebih dari 0.'], 422);
    if ($hargaBeli  <= 0) respond(['error' => 'Harga beli harus lebih dari 0.'], 422);

    try {
        $stmt = $db->prepare('UPDATE bahan_baku SET nama = ?, kategori = ?, satuan = ?, jumlah_beli = ?, harga_beli = ? WHERE id = ? AND pengguna_id = ?');
        $stmt->execute([$nama, $kategori ?: null, $satuan, $jumlahBeli, $hargaBeli, $id, $userId]);

        $get = $db->prepare('SELECT id, nama, kategori, satuan, jumlah_beli, harga_beli, harga_per_satuan FROM bahan_baku WHERE id = ?');
        $get->execute([$id]);

        respond(['message' => 'Bahan baku berhasil diperbarui.', 'data' => $get->fetch()]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') {
            respond(['error' => "Nama '$nama' sudah digunakan."], 409);
        }
        respond(['error' => 'Gagal memperbarui data.'], 500);
    }
}

// ────────────────────────────────────────────────────
// DELETE — Hapus bahan baku
// ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    if (!ownsItem($db, $id, $userId)) respond(['error' => 'Data tidak ditemukan.'], 404);

    // Cek apakah bahan dipakai di resep manapun
    $cek = $db->prepare('SELECT COUNT(*) FROM resep_bahan WHERE bahan_id = ?');
    $cek->execute([$id]);
    if ((int) $cek->fetchColumn() > 0) {
        respond(['error' => 'Bahan ini masih digunakan dalam resep. Hapus dari resep terlebih dahulu.'], 409);
    }

    $stmt = $db->prepare('DELETE FROM bahan_baku WHERE id = ? AND pengguna_id = ?');
    $stmt->execute([$id, $userId]);

    respond(['message' => 'Bahan baku berhasil dihapus.']);
}

respond(['error' => 'Method tidak didukung.'], 405);

