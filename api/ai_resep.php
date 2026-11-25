<?php
/**
 * api/ai_resep.php
 * Endpoint untuk generate resep kue otomatis menggunakan AI (Google Gemini API).
 * Jika API Key belum diisi, endpoint ini menggunakan Smart Baking Generator bawaan
 * sehingga tetap bisa diuji tanpa konfigurasi awal.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error' => 'Silakan login.']); exit;
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

function respond(mixed $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') respond(['error' => 'Method tidak didukung.'], 405);

$body   = json_decode(file_get_contents('php://input'), true);
$prompt = trim($body['prompt'] ?? '');

if (empty($prompt)) {
    respond(['error' => 'Tuliskan nama atau deskripsi kue yang ingin digenerate.'], 422);
}

// Ambil bahan baku user yang tersedia
$stmt = $db->prepare('SELECT id, nama, satuan FROM bahan_baku WHERE pengguna_id = ?');
$stmt->execute([$userId]);
$userBahan = $stmt->fetchAll();

// Cek apakah ada Gemini API Key
$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

if (!empty($apiKey)) {
    // ── Call Gemini API ──
    $availableBahanStr = implode(', ', array_map(fn($b) => "{$b['nama']} (satuan: {$b['satuan']})", $userBahan));
    $systemInstruction = "Kamu adalah chef pastry profesional. Format keluaran HANYA valid JSON murni tanpa markdown, tanpa ```json. Format:
    {
       \"nama\": \"Nama Kue\",
       \"jumlah_hasil\": 10,
       \"satuan_hasil\": \"pcs\",
       \"deskripsi\": \"Deskripsi singkat cara pembuatan kue\",
       \"bahan\": [
          {\"nama\": \"Tepung terigu\", \"jumlah\": 250, \"satuan\": \"g\", \"catatan\": \"protein sedang\"}
       ]
    }";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Buatkan takaran resep baking untuk: $prompt. Bahan baku yang diutamakan bila relevan: $availableBahanStr.\n$systemInstruction"]
                ]
            ]
        ]
    ];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . $apiKey;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resData = json_decode($response, true);
        $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $rawText = preg_replace('/```(?:json)?/i', '', $rawText);
        $rawText = trim($rawText);
        $parsed = json_decode($rawText, true);
        if ($parsed && !empty($parsed['nama'])) {
            respond(['source' => 'gemini_api', 'resep' => $parsed]);
        }
    }
}

// ── Smart Baking Generator (Fallback cerdas jika API key belum diisi / offline) ──
$templateResep = [
    'nama' => ucwords($prompt),
    'jumlah_hasil' => 12,
    'satuan_hasil' => 'porsi',
    'deskripsi' => "Resep $prompt kreasi AI BAKECOST. Campur bahan kering, kocok telur & gula hingga mengembang, lalu panggang pada suhu 175°C selama 30-35 menit.",
    'bahan' => [
        ['nama' => 'Tepung terigu', 'jumlah' => 250, 'satuan' => 'g', 'catatan' => 'diayak'],
        ['nama' => 'Gula pasir', 'jumlah' => 180, 'satuan' => 'g', 'catatan' => 'butiran halus'],
        ['nama' => 'Mentega', 'jumlah' => 150, 'satuan' => 'g', 'catatan' => 'dilelehkan'],
        ['nama' => 'Telur', 'jumlah' => 4, 'satuan' => 'butir', 'catatan' => 'suhu ruang'],
        ['nama' => 'Susu cair', 'jumlah' => 60, 'satuan' => 'ml', 'catatan' => 'full cream'],
        ['nama' => 'Baking powder', 'jumlah' => 1, 'satuan' => 'g', 'catatan' => 'double acting'],
    ]
];

// Sesuaikan sedikit berdasarkan kata kunci
if (stripos($prompt, 'cokelat') !== false || stripos($prompt, 'brownies') !== false) {
    $templateResep['bahan'][] = ['nama' => 'Cokelat bubuk', 'jumlah' => 50, 'satuan' => 'g', 'catatan' => 'murni kualitas premium'];
}
if (stripos($prompt, 'keju') !== false) {
    $templateResep['bahan'][] = ['nama' => 'Keju cheddar', 'jumlah' => 100, 'satuan' => 'g', 'catatan' => 'diparut'];
}

respond([
    'source' => 'smart_template',
    'info' => 'Resep digenerate via Smart Baking Engine (Masukkan GEMINI_API_KEY di config/ai_config.php untuk koneksi langsung Google Gemini).',
    'resep' => $templateResep
]);

