<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\KidsCrianca> $filhos
 * @var string|null $success
 * @var array $errors
 * @var array{id: int, pin: string}|null $pinGerado
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids.css?v=<?= View::assetVersion('assets/css/kids.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Meus filhos</h1>
        <p class="dash-page-subtitle">Gerencie o PIN de acesso dos seus filhos à Biblioteca Kids.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/perfil" class="btn-k btn-k-ghost"><i class="bi bi-arrow-left"></i> Meu perfil</a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($errors !== []): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($filhos === []): ?>
    <div class="dash-panel">
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-emoji-smile"></i></div>
            <h2>Nenhum filho vinculado ao seu cadastro</h2>
            <p>
                Se você tem um filho no ministério infantil, peça para a equipe da igreja vincular
                seu cadastro de Membro como responsável dele em Kids &gt; Crianças.
            </p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($filhos as $crianca): ?>
        <div class="dash-panel" style="margin-bottom: 1.4rem;">
            <div class="kids-perfil-header" style="margin-bottom: 0;">
                <div class="kids-perfil-foto">
                    <?php if ($crianca->fotoPath !== null): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <span class="kids-crianca-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($crianca->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div class="kids-perfil-info">
                    <div class="kids-perfil-nome-linha">
                        <h2><?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <p class="crud-text-dim">
                        <?= $crianca->turmaNome !== null ? htmlspecialchars($crianca->turmaNome, ENT_QUOTES, 'UTF-8') : 'Sem turma definida' ?>
                        <?= $crianca->idade() !== null ? ' &middot; ' . $crianca->idade() . ' anos' : '' ?>
                    </p>
                    <div class="kids-perfil-stats">
                        <span title="Experiência"><i class="bi bi-star-fill"></i> <?= $crianca->xp ?> XP</span>
                        <span title="Moedas"><i class="bi bi-coin"></i> <?= $crianca->moedas ?> moedas</span>
                        <span title="Sequência de presença"><i class="bi bi-fire"></i> <?= $crianca->sequenciaDias ?> de sequência</span>
                    </div>
                </div>
            </div>

            <?php if ($pinGerado !== null && $pinGerado['id'] === $crianca->id): ?>
                <div class="kids-codigo-banner" style="margin-top: 1.2rem;">
                    <div class="kids-codigo-banner-icone"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div class="kids-codigo-banner-titulo">PIN de <?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="kids-codigo-banner-codigo"><?= htmlspecialchars($pinGerado['pin'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p>Anote este código - ele não será mostrado novamente. É com ele que seu filho entra sozinho em <a href="<?= $basePath ?>/kids/entrar">/kids/entrar</a>.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="crud-form-actions" style="justify-content: flex-start; margin-top: 1.2rem;">
                <form method="POST" action="<?= $basePath ?>/dashboard/kids/meus-filhos/<?= $crianca->id ?>/pin">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn-k btn-k-grad">
                        <i class="bi bi-key"></i> <?= $crianca->temPin() ? 'Gerar novo PIN' : 'Gerar PIN de acesso' ?>
                    </button>
                </form>
                <?php if ($crianca->temPin()): ?>
                    <form
                        method="POST"
                        action="<?= $basePath ?>/dashboard/kids/meus-filhos/<?= $crianca->id ?>/pin/remover"
                        data-confirm="Remover o PIN de <?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?>? Ela não conseguirá mais entrar sozinha."
                    >
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn-k btn-k-ghost">
                            <i class="bi bi-key"></i> Remover PIN
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
