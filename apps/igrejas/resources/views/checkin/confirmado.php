<?php

/**
 * @var array $config
 * @var \Igrejas\Models\Membro $membro
 * @var \Igrejas\Models\Culto $culto
 * @var bool $jaConfirmado
 */
?>

<div class="avisos-publico-shell checkin-shell">
    <main class="checkin-confirmado">
        <div class="checkin-confirmado-icone">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h1><?= $jaConfirmado ? 'Presença já confirmada' : 'Presença confirmada!' ?></h1>
        <p>
            <?= $jaConfirmado ? 'Você já tinha confirmado presença' : 'Bem-vindo(a),' ?>
            <strong><?= htmlspecialchars(explode(' ', $membro->nome)[0], ENT_QUOTES, 'UTF-8') ?></strong>
            no culto <strong><?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>
    </main>

    <footer class="avisos-publico-footer">
        <span>KADOSYS Igrejas</span>
    </footer>
</div>
