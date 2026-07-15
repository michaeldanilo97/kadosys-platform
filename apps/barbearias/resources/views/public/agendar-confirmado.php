<?php

use Barbearias\Models\Barbearia;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var array{profissional:string, servico:string, data:string, hora:string} $confirmacao
 */
$basePath = $config['base_path'] ?? '';
$dataFormatada = (new DateTimeImmutable($confirmacao['data']))->format('d/m/Y');
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card confirmacao-card">
        <div class="confirmacao-icone">✅</div>
        <h1>Agendamento confirmado!</h1>
        <p class="subtitle">Te esperamos na <?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?>.</p>

        <div class="confirmacao-detalhes">
            <div><span>Profissional</span><span><?= htmlspecialchars($confirmacao['profissional'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Serviço</span><span><?= htmlspecialchars($confirmacao['servico'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Data</span><span><?= $dataFormatada ?></span></div>
            <div><span>Horário</span><span><?= htmlspecialchars($confirmacao['hora'], ENT_QUOTES, 'UTF-8') ?></span></div>
        </div>

        <a href="<?= $basePath ?>/agendar/<?= htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8') ?>" class="btn-k btn-k-outline">Agendar outro horário</a>
    </div>
</div>
