<?php
/**
 * api/packaging.php — CRUD packaging milik user
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

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT id, nama, harga_per_unit FROM packaging WHERE pengguna_id = ? ORDER BY nama ASC');
    $stmt->execute([$userId]);
    respond($stmt->fetchAll());
}

if ($method === 'POST') {
    $body          = json_decode(file_get_contents('php://input'), true);
    $nama          = trim($body['nama']           ?? '');
    $hargaPerUnit  = (float) ($body['harga_per_unit'] ?? 0);

    if (empty($nama))       respond(['error' => 'Nama packaging wajib diisi.'], 422);
    if ($hargaPerUnit <= 0) respond(['error' => 'Harga harus lebih dari 0.'], 422);

    $stmt = $db->prepare('INSERT INTO packaging (pengguna_id, nama, harga_per_unit) VALUES (?, ?, ?) RETURNING id');
    $stmt->execute([$userId, $nama, $hargaPerUnit]);
    respond(['message' => 'Packaging ditambahkan.', 'id' => (int) $stmt->fetchColumn()], 201);
}

if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    $body         = json_decode(file_get_contents('php://input'), true);
    $nama         = trim($body['nama']           ?? '');
    $hargaPerUnit = (float) ($body['harga_per_unit'] ?? 0);
    if (empty($nama) || $hargaPerUnit <= 0) respond(['error' => 'Data tidak valid.'], 422);

    $stmt = $db->prepare('UPDATE packaging SET nama = ?, harga_per_unit = ? WHERE id = ? AND pengguna_id = ?');
    $stmt->execute([$nama, $hargaPerUnit, $id, $userId]);
    respond(['message' => 'Packaging diperbarui.']);
}

if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    $db->prepare('DELETE FROM packaging WHERE id = ? AND pengguna_id = ?')->execute([$id, $userId]);
    respond(['message' => 'Packaging dihapus.']);
}

respond(['error' => 'Method tidak didukung.'], 405);

