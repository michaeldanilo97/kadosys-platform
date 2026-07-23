<?php

use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\FichaTreino;

/**
 * @var array $config
 * @var Academia $academia
 * @var Aluno $aluno
 * @var array<int, FichaTreino> $fichas
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($academia->slug, ENT_QUOTES, 'UTF-8');
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card" style="max-width: 560px;">
        <div class="hero-eyebrow">Meu treino</div>
        <h1>Fichas ativas</h1>
        <p class="subtitle">Escolha a ficha e marque os exercícios que você fez hoje.</p>

        <?php if ($fichas === []): ?>
            <p class="crud-empty">Você ainda não tem nenhuma ficha de treino ativa. Fale com seu professor.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top:1.25rem;">
                <?php foreach ($fichas as $ficha): ?>
                    <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>/treino/<?= $ficha->id ?>" class="glass-card kpi-card" style="text-align:left; display:block; text-decoration:none;">
                        <p class="kpi-label" style="margin:0;"><?= htmlspecialchars($ficha->nome, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($ficha->objetivo): ?>
                            <p class="form-field-hint" style="margin:0.35rem 0 0;"><?= htmlspecialchars($ficha->objetivo, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>" class="btn-k btn-k-outline" style="width:100%; margin-top: 1.5rem;">Voltar</a>
    </div>
</div>
