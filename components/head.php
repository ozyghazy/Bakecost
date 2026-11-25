<?php
/**
 * components/head.php
 * Head HTML bersama untuk semua halaman BakeCost.
 * Memuat Google Fonts (Playfair Display & Plus Jakarta Sans),
 * Tailwind CSS CDN, dan stylesheet utama.
 */

$htmlTitle = $htmlTitle ?? 'BakeCost — Baking Converter & Cost Calculator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($htmlTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css?v=2">
</head>
