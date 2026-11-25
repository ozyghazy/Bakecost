<?php
/**
 * index.php — Halaman Welcome bergaya Warm Minimalist Editorial Bakery.
 * Jika pengguna sudah login, langsung diarahkan ke beranda.
 */

session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /bakecost/pages/beranda.php');
    exit;
}

$htmlTitle = 'BakeCost — Baking Converter & Cost Calculator';
require __DIR__ . '/components/head.php';
?>
<body>
<div class="app-container flex flex-col">

    <header class="site-header">
        <div class="site-header__inner">
            <a href="/bakecost/index.php" class="brand-wordmark">BakeCost</a>
            <div class="flex items-center gap-2">
                <a href="/bakecost/login.php" class="btn-secondary btn-sm">Masuk</a>
                <a href="/bakecost/register.php" class="btn-primary btn-sm">Mulai</a>
            </div>
        </div>
    </header>

    <main class="page-content flex-1 flex flex-col gap-7">

        <!-- Editorial Hero -->
        <section class="editorial-hero">
            <div class="grid md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-7 space-y-4">
                    <p class="eyebrow">Baking Converter &amp; Cost Calculator</p>
                    <h1 class="editorial-title text-3xl md:text-5xl font-semibold text-ink leading-[1.15]">
                        Takaran presisi.<br>
                        <span class="italic font-medium text-accent">Biaya terkendali.</span>
                    </h1>
                    <p class="text-sm md:text-base text-secondary max-w-md leading-relaxed">
                        Konversi kebutuhan bahan berdasarkan target produksi dan hitung estimasi HPP usaha baking kamu dalam satu ruang kerja yang rapi.
                    </p>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="/bakecost/login.php" class="btn-primary btn-sm px-7 py-3">Mulai Sekarang</a>
                        <a href="/bakecost/register.php" class="btn-secondary btn-sm px-6 py-3">Buat Akun Baru</a>
                    </div>
                </div>

                <!-- Visual editorial + Frosted Glass Badge -->
                <div class="md:col-span-5 flex flex-col items-center justify-center relative">
                    <div class="glass-panel p-5 w-full max-w-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-[var(--border-hairline)] pb-3">
                            <div>
                                <p class="eyebrow">Contoh Batch</p>
                                <p class="font-editorial text-lg font-semibold text-ink">Fudgy Brownies</p>
                            </div>
                            <span class="badge-biaya">20 porsi</span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-muted">Pengali resep</span>
                                <span class="font-semibold text-ink tabular-nums">2× resep asli</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted">Biaya bahan baku</span>
                                <span class="font-semibold text-ink tabular-nums">Rp 51.600</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-[var(--border-hairline)]">
                                <span class="font-semibold text-secondary">Estimasi HPP / porsi</span>
                                <span class="font-bold text-accent tabular-nums">Rp 2.580</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3 Bento Feature Cards bernomor (01)-(03) -->
        <section>
            <p class="eyebrow mb-3">Fitur Utama</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <a href="/bakecost/login.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(01)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Recipe Converter</h2>
                        <p class="bento-card__desc">Skala ulang takaran gram, ml, dan butir otomatis sesuai target batch produksi.</p>
                    </div>
                </a>

                <a href="/bakecost/login.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(02)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Kalkulator HPP</h2>
                        <p class="bento-card__desc">Hitung biaya bahan, packaging, tenaga kerja, overhead, dan rekomendasi harga jual.</p>
                    </div>
                </a>

                <a href="/bakecost/login.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(03)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Bake Loss &amp; AI</h2>
                        <p class="bento-card__desc">Catat persentase penyusutan oven dan racik ide resep baru lewat asisten AI.</p>
                    </div>
                </a>
            </div>
        </section>

        <footer class="text-center pt-4 pb-2">
            <p class="text-xs text-muted">Siap membuat kue hari ini? — BakeCost</p>
        </footer>
    </main>

</div>
</body>
</html>
