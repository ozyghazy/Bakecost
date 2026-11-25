<?php
/**
 * index.php — Welcome / Splash screen
 * Jika sudah login, langsung ke beranda.
 */

session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /bakecost/pages/beranda.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAKECOST — Baking Converter & Cost Calculator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container flex flex-col min-h-[100dvh] relative overflow-hidden">

    <!-- Dekorasi lingkaran pojok (sesuai desain) -->
    <div class="absolute top-0 right-0 w-28 h-28 rounded-bl-full bg-[#EAD9C8] opacity-60"></div>
    <div class="absolute top-16 left-0 w-20 h-20 rounded-br-full bg-[#EAD9C8] opacity-40"></div>
    <div class="absolute bottom-32 right-0 w-24 h-24 rounded-bl-full bg-[#EAD9C8] opacity-40"></div>

    <!-- Konten tengah -->
    <div class="flex-1 flex flex-col items-center px-8 pt-14">

        <!-- Logo & judul -->
        <div class="text-center mb-6">
            <!-- Ikon chef hat -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto mb-3 text-[#3D1F0D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5A3.5 3.5 0 018.5 3 3.5 3.5 0 005 6.5c0 1.2.61 2.27 1.54 2.9L6 18h12l-.54-8.6A3.5 3.5 0 0019 6.5 3.5 3.5 0 0015.5 3 3.5 3.5 0 0012 6.5z"/>
            </svg>
            <h1 class="text-3xl font-bold text-[#3D1F0D] tracking-wide">BAKECOST</h1>
            <p class="text-sm text-[#9E7A5A] mt-1">Baking Converter &amp; Cost Calculator</p>
        </div>

        <!-- Deskripsi -->
        <p class="text-center text-[#5A2D0C] text-sm leading-relaxed max-w-xs">
            Konversi kebutuhan bahan berdasarkan jumlah produksi dan hitung estimasi biaya bahan baku dengan mudah.
        </p>

        <!-- Ilustrasi mangkuk (emoji sebagai placeholder, bisa diganti SVG/gambar) -->
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <!-- Ilustrasi mangkuk adonan -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 160" class="w-64 h-auto mx-auto" fill="none">
                    <!-- Mangkuk -->
                    <ellipse cx="100" cy="115" rx="72" ry="14" fill="#C17A3E" opacity="0.2"/>
                    <path d="M38 80 Q38 130 100 130 Q162 130 162 80 Z" fill="#7C4A2D"/>
                    <ellipse cx="100" cy="80" rx="62" ry="16" fill="#A0633A"/>
                    <!-- Adonan dalam mangkuk -->
                    <ellipse cx="100" cy="80" rx="58" ry="12" fill="#FDE8D5"/>
                    <!-- Spatula -->
                    <rect x="120" y="30" width="6" height="60" rx="3" fill="#3D1F0D" transform="rotate(20,123,60)"/>
                    <rect x="118" y="28" width="10" height="18" rx="3" fill="#9E7A5A" transform="rotate(20,123,37)"/>
                    <!-- Telur -->
                    <ellipse cx="55" cy="120" rx="12" ry="10" fill="#F5E0CE"/>
                    <ellipse cx="55" cy="120" rx="7" ry="6" fill="#FDE8D5"/>
                    <!-- Rolling pin -->
                    <rect x="70" y="128" width="60" height="10" rx="5" fill="#C17A3E"/>
                    <rect x="68" y="127" width="8" height="12" rx="3" fill="#A0633A"/>
                    <rect x="124" y="127" width="8" height="12" rx="3" fill="#A0633A"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Tombol fixed bawah -->
    <div class="p-4 pb-8">
        <a href="/bakecost/login.php" class="btn-primary">Mulai</a>
        <p class="text-center text-[#9E7A5A] text-xs mt-3">Siap membuat kue hari ini?</p>
    </div>

</div>
</body>
</html>

