<?php
/**
 * api/bake_loss.php
 * REST endpoint kalkulasi & riwayat bake loss (penyusutan adonan panggang).
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error' => 'Silakan login.']); exit;
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
$db     = getDB();

function respond(mixed $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// GET — list bake loss
if ($method === 'GET') {
    $resepId = isset($_GET['resep_id']) ? (int) $_GET['resep_id'] : null;
    $where = 'r.pengguna_id = ?';
    $params = [$userId];

    if ($resepId) {
        $where .= ' AND bl.resep_id = ?';
        $params[] = $resepId;
    }

    $stmt = $db->prepare("
        SELECT bl.id, bl.resep_id, r.nama AS resep_nama,
               bl.berat_sebelum, bl.berat_sesudah, bl.persen_loss,
               bl.catatan, bl.tanggal
        FROM bake_loss bl
        JOIN resep r ON r.id = bl.resep_id
        WHERE $where
        ORDER BY bl.tanggal DESC, bl.id DESC
    ");
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

// POST — simpan data bake loss
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $resepId      = (int)   ($body['resep_id']      ?? 0);
    $beratSebelum = (float) ($body['berat_sebelum'] ?? 0);
    $beratSesudah = (float) ($body['berat_sesudah'] ?? 0);
    $catatan      = trim($body['catatan'] ?? '');
    $tanggal      = !empty($body['tanggal']) ? $body['tanggal'] : date('Y-m-d');

    if (!$resepId) respond(['error' => 'Pilih resep terlebih dahulu.'], 422);
    if ($beratSebelum <= 0 || $beratSesudah <= 0) {
        respond(['error' => 'Berat sebelum dan sesudah harus lebih dari 0 gram.'], 422);
    }
    if ($beratSesudah > $beratSebelum) {
        respond(['error' => 'Berat sesudah dipanggang tidak boleh lebih besar dari berat sebelum.'], 422);
    }

    // Pastikan resep milik user
    $cek = $db->prepare('SELECT id FROM resep WHERE id = ? AND pengguna_id = ?');
    $cek->execute([$resepId, $userId]);
    if (!$cek->fetch()) respond(['error' => 'Resep tidak valid.'], 404);

    $stmt = $db->prepare('
        INSERT INTO bake_loss (resep_id, berat_sebelum, berat_sesudah, catatan, tanggal)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([$resepId, $beratSebelum, $beratSesudah, $catatan ?: null, $tanggal]);

    $newId = (int) $stmt->fetchColumn();
    $get = $db->prepare('SELECT * FROM bake_loss WHERE id = ?');
    $get->execute([$newId]);
    respond(['message' => 'Data bake loss berhasil disimpan.', 'data' => $get->fetch()], 201);
}

// DELETE — hapus bake loss
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    $stmt = $db->prepare('
        DELETE FROM bake_loss bl
        USING resep r
        WHERE bl.resep_id = r.id AND bl.id = ? AND r.pengguna_id = ?
    ');
    $stmt->execute([$id, $userId]);
    respond(['message' => 'Data berhasil dihapus.']);
}

respond(['error' => 'Method tidak didukung.'], 405);

