<?php
/**
 * components/step_indicator.php
 * Komponen stepper 3-langkah untuk alur Konversi dan Hitung Biaya.
 */

$currentStep = $currentStep ?? 1;
$steps       = $steps       ?? ['Resep', 'Jumlah Produksi', 'Hasil'];
?>
<div class="stepper" role="list" aria-label="Langkah konversi">
    <?php foreach ($steps as $i => $label):
        $stepNum  = $i + 1;
        $isDone   = $stepNum < $currentStep;
        $isActive = $stepNum === $currentStep;
        $circleClass = $isDone
            ? 'stepper__circle stepper__circle--done'
            : ($isActive ? 'stepper__circle stepper__circle--active' : 'stepper__circle');
    ?>
        <div class="stepper__node" role="listitem">
            <div class="<?= $circleClass ?>">
                <?php if ($isDone): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                <?php else: ?>
                    <?= $stepNum ?>
                <?php endif; ?>
            </div>
            <span class="stepper__label <?= $isActive ? 'stepper__label--active' : '' ?>">
                <?= htmlspecialchars($label) ?>
            </span>
        </div>

        <?php if ($stepNum < count($steps)): ?>
            <div class="stepper__line <?= $isDone ? 'stepper__line--done' : '' ?>" aria-hidden="true"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
