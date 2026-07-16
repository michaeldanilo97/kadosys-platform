<?php

use Barbearias\Models\Barbearia;
use Barbearias\Models\FilaAtendimento;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var FilaAtendimento $item
 * @var int $posicao
 * @var int $esperaEstimada
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card confirmacao-card">
        <div class="confirmacao-icone">🎟️</div>
        <h1>Você está na fila!</h1>
        <p class="subtitle">Assim que chegar sua vez, a equipe da <?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?> vai te chamar.</p>

        <div class="confirmacao-detalhes">
            <div><span>Nome</span><span><?= htmlspecialchars($item->nome, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Posição na fila</span><span><?= $posicao ?>º</span></div>
            <div><span>Espera estimada</span><span><?= $esperaEstimada > 0 ? $esperaEstimada . ' min' : 'sem espera' ?></span></div>
        </div>

        <a href="<?= $basePath ?>/fila/<?= htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8') ?>" class="btn-k btn-k-outline">Voltar</a>
    </div>
</div>
