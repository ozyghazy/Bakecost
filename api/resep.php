<?php
/**
 * api/resep.php
 * REST endpoint untuk manajemen resep & bahan resep.
 *
 * GET    /api/resep.php                        → list semua resep milik user
 * GET    /api/resep.php?id={id}                → detail resep (+ bahan jika with_bahan=1)
 * POST   /api/resep.php                        → tambah resep baru (+ bahan)
 * PUT    /api/resep.php?id={id}                → update resep (+ bahan)
 * DELETE /api/resep.php?id={id}                → hapus resep
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu.']);
    exit;
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

/** Cek kepemilikan resep */
function ownsResep(PDO $db, int $id, int $userId): bool
{
    $s = $db->prepare('SELECT id FROM resep WHERE id = ? AND pengguna_id = ?');
    $s->execute([$id, $userId]);
    return (bool) $s->fetch();
}

// ────────────────────────────────────────────────────
// GET
// ────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        // Detail resep
        $stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil, deskripsi, dibuat_via_ai, created_at FROM resep WHERE id = ? AND pengguna_id = ?');
        $stmt->execute([$id, $userId]);
        $resep = $stmt->fetch();
        if (!$resep) respond(['error' => 'Resep tidak ditemukan.'], 404);

        // Sertakan daftar bahan jika diminta
        if (!empty($_GET['with_bahan'])) {
            $bahanStmt = $db->prepare(
                'SELECT rb.id, rb.bahan_id, b.nama AS bahan_nama, b.satuan, b.harga_per_satuan,
                        rb.jumlah, rb.catatan
                 FROM resep_bahan rb
                 JOIN bahan_baku b ON b.id = rb.bahan_id
                 WHERE rb.resep_id = ?
                 ORDER BY b.nama ASC'
            );
            $bahanStmt->execute([$id]);
            $resep['bahan'] = $bahanStmt->fetchAll();
        }

        respond($resep);
    }

    // List semua resep
    $where  = 'WHERE r.pengguna_id = ?';
    $params = [$userId];

    if (!empty($_GET['q'])) {
        $where   .= ' AND r.nama ILIKE ?';
        $params[] = '%' . $_GET['q'] . '%';
    }

    $stmt = $db->prepare(
        "SELECT r.id, r.nama, r.jumlah_hasil, r.satuan_hasil, r.deskripsi, r.dibuat_via_ai, r.created_at,
                COUNT(rb.id) AS jumlah_bahan
         FROM resep r
         LEFT JOIN resep_bahan rb ON rb.resep_id = r.id
         $where
         GROUP BY r.id
         ORDER BY r.created_at DESC"
    );
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

// ────────────────────────────────────────────────────
// POST — Tambah resep baru
// ────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $nama        = trim($body['nama']         ?? '');
    $jumlahHasil = (float) ($body['jumlah_hasil'] ?? 0);
    $satuanHasil = trim($body['satuan_hasil'] ?? 'pcs');
    $deskripsi   = trim($body['deskripsi']    ?? '');
    $viaiAI      = (bool) ($body['dibuat_via_ai'] ?? false);
    $bahanList   = $body['bahan'] ?? [];

    if (empty($nama))        respond(['error' => 'Nama resep wajib diisi.'], 422);
    if ($jumlahHasil <= 0)   respond(['error' => 'Jumlah hasil harus lebih dari 0.'], 422);
    if (empty($bahanList))   respond(['error' => 'Resep harus memiliki minimal 1 bahan.'], 422);

    $db->beginTransaction();
    try {
        // Insert resep (RETURNING id untuk PostgreSQL + boolean 'true'/'false')
        $ins = $db->prepare('INSERT INTO resep (pengguna_id, nama, jumlah_hasil, satuan_hasil, deskripsi, dibuat_via_ai) VALUES (?, ?, ?, ?, ?, ?) RETURNING id');
        $ins->execute([$userId, $nama, $jumlahHasil, $satuanHasil, $deskripsi ?: null, $viaiAI ? 'true' : 'false']);
        $resepId = (int) $ins->fetchColumn();

        // Insert bahan resep
        $insBahan = $db->prepare('INSERT INTO resep_bahan (resep_id, bahan_id, jumlah, catatan) VALUES (?, ?, ?, ?)');
        foreach ($bahanList as $b) {
            $bahanId = (int) ($b['bahan_id'] ?? 0);
            $jumlah  = (float) ($b['jumlah'] ?? 0);
            $catatan = trim($b['catatan'] ?? '');

            if ($bahanId <= 0 || $jumlah <= 0) continue; // skip bahan tidak valid

            // Pastikan bahan milik user ini
            $cek = $db->prepare('SELECT id FROM bahan_baku WHERE id = ? AND pengguna_id = ?');
            $cek->execute([$bahanId, $userId]);
            if (!$cek->fetch()) continue;

            $insBahan->execute([$resepId, $bahanId, $jumlah, $catatan ?: null]);
        }

        $db->commit();
        respond(['message' => 'Resep berhasil disimpan.', 'id' => $resepId], 201);
    } catch (Throwable $e) {
        $db->rollBack();
        respond(['error' => 'Gagal menyimpan resep: ' . $e->getMessage()], 500);
    }
}

// ────────────────────────────────────────────────────
// PUT — Update resep
// ────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    if (!ownsResep($db, $id, $userId)) respond(['error' => 'Resep tidak ditemukan.'], 404);

    $body = json_decode(file_get_contents('php://input'), true);

    $nama        = trim($body['nama']         ?? '');
    $jumlahHasil = (float) ($body['jumlah_hasil'] ?? 0);
    $satuanHasil = trim($body['satuan_hasil'] ?? 'pcs');
    $deskripsi   = trim($body['deskripsi']    ?? '');
    $bahanList   = $body['bahan'] ?? [];

    if (empty($nama))      respond(['error' => 'Nama resep wajib diisi.'], 422);
    if ($jumlahHasil <= 0) respond(['error' => 'Jumlah hasil harus lebih dari 0.'], 422);
    if (empty($bahanList)) respond(['error' => 'Resep harus memiliki minimal 1 bahan.'], 422);

    $db->beginTransaction();
    try {
        // Update header resep
        $upd = $db->prepare('UPDATE resep SET nama = ?, jumlah_hasil = ?, satuan_hasil = ?, deskripsi = ? WHERE id = ? AND pengguna_id = ?');
        $upd->execute([$nama, $jumlahHasil, $satuanHasil, $deskripsi ?: null, $id, $userId]);

        // Hapus bahan lama → insert ulang
        $db->prepare('DELETE FROM resep_bahan WHERE resep_id = ?')->execute([$id]);

        $insBahan = $db->prepare('INSERT INTO resep_bahan (resep_id, bahan_id, jumlah, catatan) VALUES (?, ?, ?, ?)');
        foreach ($bahanList as $b) {
            $bahanId = (int) ($b['bahan_id'] ?? 0);
            $jumlah  = (float) ($b['jumlah'] ?? 0);
            $catatan = trim($b['catatan'] ?? '');
            if ($bahanId <= 0 || $jumlah <= 0) continue;

            $cek = $db->prepare('SELECT id FROM bahan_baku WHERE id = ? AND pengguna_id = ?');
            $cek->execute([$bahanId, $userId]);
            if (!$cek->fetch()) continue;

            $insBahan->execute([$id, $bahanId, $jumlah, $catatan ?: null]);
        }

        $db->commit();
        respond(['message' => 'Resep berhasil diperbarui.']);
    } catch (Throwable $e) {
        $db->rollBack();
        respond(['error' => 'Gagal memperbarui resep.'], 500);
    }
}

// ────────────────────────────────────────────────────
// DELETE — Hapus resep
// ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    if (!ownsResep($db, $id, $userId)) respond(['error' => 'Resep tidak ditemukan.'], 404);

    // resep_bahan terhapus otomatis via ON DELETE CASCADE
    $db->prepare('DELETE FROM resep WHERE id = ? AND pengguna_id = ?')->execute([$id, $userId]);
    respond(['message' => 'Resep berhasil dihapus.']);
}

respond(['error' => 'Method tidak didukung.'], 405);

