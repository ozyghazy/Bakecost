<?php
/**
 * api/hpp.php
 * REST endpoint: hitung & simpan HPP, list riwayat produksi.
 *
 * POST /api/hpp.php → hitung + simpan ke tabel produksi
 * GET  /api/hpp.php → list riwayat produksi milik user
 * GET  /api/hpp.php?id={id} → detail satu produksi
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login.']);
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

// ────────────────────────────────────────────────────
// GET — List riwayat atau detail satu produksi
// ────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare(
            'SELECT p.*, r.nama AS resep_nama, r.satuan_hasil
             FROM produksi p
             JOIN resep r ON r.id = p.resep_id
             WHERE p.id = ? AND r.pengguna_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        if (!$row) respond(['error' => 'Data tidak ditemukan.'], 404);

        // Detail packaging yang dipakai
        $pkgStmt = $db->prepare(
            'SELECT pp.jumlah, pk.nama, pk.harga_per_unit
             FROM produksi_packaging pp
             JOIN packaging pk ON pk.id = pp.packaging_id
             WHERE pp.produksi_id = ?'
        );
        $pkgStmt->execute([$id]);
        $row['packaging'] = $pkgStmt->fetchAll();

        respond($row);
    }

    // List semua, dengan filter tipe (konversi / biaya / semua)
    $tipe   = $_GET['tipe'] ?? 'semua';
    $where  = 'r.pengguna_id = ?';
    $params = [$userId];

    if ($tipe === 'konversi') { $where .= " AND p.tipe = 'konversi'"; }
    if ($tipe === 'biaya')    { $where .= " AND p.tipe = 'biaya'"; }

    $stmt = $db->prepare(
        "SELECT p.id, p.resep_id, r.nama AS resep_nama, r.satuan_hasil,
                p.jumlah_produksi, p.biaya_bahan, p.biaya_packaging,
                p.biaya_tenaga_kerja, p.biaya_overhead,
                p.hpp_total, p.hpp_per_unit, p.margin_persen, p.harga_jual,
                p.tipe, p.created_at
         FROM produksi p
         JOIN resep r ON r.id = p.resep_id
         WHERE $where
         ORDER BY p.created_at DESC"
    );
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

// ────────────────────────────────────────────────────
// POST — Hitung + simpan HPP
// ────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $resepId       = (int)   ($body['resep_id']           ?? 0);
    $jumlahProd    = (float) ($body['jumlah_produksi']     ?? 0);
    $biayaTenaga   = (float) ($body['biaya_tenaga_kerja']  ?? 0);
    $biayaOverhead = (float) ($body['biaya_overhead']      ?? 0);
    $marginPersen  = isset($body['margin_persen']) ? (float) $body['margin_persen'] : null;
    $packagingIds  = $body['packaging_ids'] ?? [];  // array of {id, jumlah}
    $tipe          = in_array($body['tipe'] ?? '', ['konversi','biaya']) ? $body['tipe'] : 'biaya';
    $biayaBahanOverride = isset($body['biaya_bahan']) ? (float) $body['biaya_bahan'] : null;

    if (!$resepId || $jumlahProd <= 0) respond(['error' => 'resep_id dan jumlah_produksi wajib diisi.'], 422);

    // Cek resep milik user
    $stmtR = $db->prepare('SELECT id, jumlah_hasil FROM resep WHERE id = ? AND pengguna_id = ?');
    $stmtR->execute([$resepId, $userId]);
    $resep = $stmtR->fetch();
    if (!$resep) respond(['error' => 'Resep tidak ditemukan.'], 404);

    $faktor = $jumlahProd / $resep['jumlah_hasil'];

    // Hitung biaya bahan dari DB (kecuali jika di-override dari frontend)
    if ($biayaBahanOverride !== null) {
        $biayaBahan = $biayaBahanOverride;
    } else {
        $bahanStmt = $db->prepare(
            'SELECT SUM(rb.jumlah * b.harga_per_satuan) AS total
             FROM resep_bahan rb
             JOIN bahan_baku b ON b.id = rb.bahan_id
             WHERE rb.resep_id = ?'
        );
        $bahanStmt->execute([$resepId]);
        $biayaBahan = (float) ($bahanStmt->fetchColumn() ?? 0) * $faktor;
    }

    // Hitung biaya packaging
    $biayaPackaging = 0;
    $validPackaging = [];

    foreach ($packagingIds as $pkg) {
        $pkgId  = (int)   ($pkg['id']     ?? 0);
        $jumlah = (int)   ($pkg['jumlah'] ?? 0);
        if (!$pkgId || $jumlah <= 0) continue;

        $stmtPkg = $db->prepare('SELECT id, harga_per_unit FROM packaging WHERE id = ? AND pengguna_id = ?');
        $stmtPkg->execute([$pkgId, $userId]);
        $pkgRow = $stmtPkg->fetch();
        if (!$pkgRow) continue;

        $biayaPackaging += $jumlah * $pkgRow['harga_per_unit'];
        $validPackaging[] = ['id' => $pkgId, 'jumlah' => $jumlah];
    }

    // Hitung HPP total & per unit
    $hppTotal  = $biayaBahan + $biayaPackaging + $biayaTenaga + $biayaOverhead;
    $hppPerUnit = $jumlahProd > 0 ? $hppTotal / $jumlahProd : 0;
    $hargaJual  = $marginPersen !== null ? $hppPerUnit * (1 + $marginPersen / 100) : null;

    $db->beginTransaction();
    try {
        $ins = $db->prepare(
            'INSERT INTO produksi (resep_id, jumlah_produksi, faktor_konversi,
             biaya_bahan, biaya_packaging, biaya_tenaga_kerja, biaya_overhead,
             hpp_total, hpp_per_unit, margin_persen, harga_jual, tipe)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             RETURNING id'
        );
        $ins->execute([
            $resepId, $jumlahProd, $faktor,
            round($biayaBahan, 2), round($biayaPackaging, 2),
            round($biayaTenaga, 2), round($biayaOverhead, 2),
            round($hppTotal, 2), round($hppPerUnit, 2),
            $marginPersen, $hargaJual ? round($hargaJual, 2) : null,
            $tipe,
        ]);
        $produksiId = (int) $ins->fetchColumn();

        // Simpan packaging yang dipakai
        if (!empty($validPackaging)) {
            $insPkg = $db->prepare('INSERT INTO produksi_packaging (produksi_id, packaging_id, jumlah) VALUES (?, ?, ?)');
            foreach ($validPackaging as $vp) {
                $insPkg->execute([$produksiId, $vp['id'], $vp['jumlah']]);
            }
        }

        $db->commit();
        respond([
            'message'     => 'Perhitungan HPP berhasil disimpan.',
            'id'          => $produksiId,
            'hpp_total'   => round($hppTotal, 2),
            'hpp_per_unit'=> round($hppPerUnit, 2),
            'harga_jual'  => $hargaJual ? round($hargaJual, 2) : null,
        ], 201);
    } catch (Throwable $e) {
        $db->rollBack();
        respond(['error' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
    }
}

// DELETE — hapus satu record produksi
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID diperlukan.'], 400);
    $stmt = $db->prepare(
        'DELETE FROM produksi p USING resep r WHERE p.resep_id = r.id AND p.id = ? AND r.pengguna_id = ?'
    );
    $stmt->execute([$id, $userId]);
    respond(['message' => 'Data dihapus.']);
}

respond(['error' => 'Method tidak didukung.'], 405);

