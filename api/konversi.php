<?php
/**
 * api/konversi.php
 * Menghitung konversi bahan tanpa menyimpan ke DB.
 *
 * POST /api/konversi.php
 * Body: { resep_id: int, jumlah_produksi: float }
 * Response: { resep, faktor, bahan: [...], biaya_total }
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

function respond(mixed $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') respond(['error' => 'Method tidak didukung.'], 405);

$body         = json_decode(file_get_contents('php://input'), true);
$resepId      = (int) ($body['resep_id']       ?? 0);
$jumlahTarget = (float) ($body['jumlah_produksi'] ?? 0);

if (!$resepId || $jumlahTarget <= 0) {
    respond(['error' => 'resep_id dan jumlah_produksi wajib diisi.'], 422);
}

$db = getDB();

// Ambil header resep (pastikan milik user)
$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$resepId, $userId]);
$resep = $stmt->fetch();

if (!$resep) respond(['error' => 'Resep tidak ditemukan.'], 404);

// Hitung faktor konversi
$faktor = $jumlahTarget / $resep['jumlah_hasil'];

// Ambil bahan + kalkulasi
$bahanStmt = $db->prepare(
    'SELECT rb.bahan_id, b.nama AS bahan_nama, b.satuan, b.harga_per_satuan,
            rb.jumlah AS jumlah_asli, rb.catatan
     FROM resep_bahan rb
     JOIN bahan_baku b ON b.id = rb.bahan_id
     WHERE rb.resep_id = ?
     ORDER BY b.nama ASC'
);
$bahanStmt->execute([$resepId]);
$bahanList = $bahanStmt->fetchAll();

$biayaTotal = 0;
$bahanKonversi = [];

foreach ($bahanList as $b) {
    $jumlahKonversi = $b['jumlah_asli'] * $faktor;
    $biayaBahan     = $jumlahKonversi * $b['harga_per_satuan'];
    $biayaTotal    += $biayaBahan;

    $bahanKonversi[] = [
        'bahan_id'        => $b['bahan_id'],
        'bahan_nama'      => $b['bahan_nama'],
        'satuan'          => $b['satuan'],
        'harga_per_satuan'=> (float) $b['harga_per_satuan'],
        'jumlah_asli'     => (float) $b['jumlah_asli'],
        'jumlah_konversi' => round($jumlahKonversi, 3),
        'biaya'           => round($biayaBahan, 2),
        'catatan'         => $b['catatan'],
    ];
}

respond([
    'resep' => [
        'id'           => $resep['id'],
        'nama'         => $resep['nama'],
        'jumlah_asli'  => (float) $resep['jumlah_hasil'],
        'satuan_hasil' => $resep['satuan_hasil'],
    ],
    'jumlah_produksi' => $jumlahTarget,
    'faktor'          => round($faktor, 4),
    'bahan'           => $bahanKonversi,
    'biaya_total'     => round($biayaTotal, 2),
]);

