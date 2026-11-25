<?php
/**
 * step_indicator.php
 * Komponen stepper 3-langkah untuk alur Konversi dan Hitung Biaya.
 * Variabel yang harus di-set sebelum include:
 *   $currentStep - nomor langkah aktif: 1, 2, atau 3
 *   $steps       - array label per step (opsional, ada default)
 */

$currentStep = $currentStep ?? 1;
$steps = $steps ?? ['Resep', 'Jumlah Produksi', 'Hasil'];
?>
<div class="flex items-center gap-0 mb-6 px-1">
    <?php foreach ($steps as $i => $label):
        $stepNum  = $i + 1;
        $isDone   = $stepNum < $currentStep;
        $isActive = $stepNum === $currentStep;
    ?>
        <!-- Lingkaran step -->
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                <?= $isDone   ? 'bg-[#7C4A2D] text-white' :
                   ($isActive ? 'bg-[#7C4A2D] text-white' :
                                'bg-[#DDD0C4] text-[#9E7A5A]') ?>">
                <?php if ($isDone): ?>
                    <!-- Centang jika sudah selesai -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                <?php else: ?>
                    <?= $stepNum ?>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 font-medium <?= $isActive ? 'text-[#7C4A2D]' : 'text-[#9E7A5A]' ?>">
                <?= htmlspecialchars($label) ?>
            </span>
        </div>

        <!-- Garis penghubung (tidak setelah step terakhir) -->
        <?php if ($stepNum < count($steps)): ?>
            <div class="flex-1 h-[2px] mb-4 <?= $isDone ? 'bg-[#7C4A2D]' : 'bg-[#DDD0C4]' ?>"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

