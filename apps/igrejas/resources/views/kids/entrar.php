<?php

use Igrejas\Core\Csrf;

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\KidsCrianca> $criancas
 * @var int $criancaSelecionadaId
 * @var string|null $error
 */
$basePath = $config['base_path'] ?? '';
$criancaSelecionada = null;
foreach ($criancas as $c) {
    if ($c->id === $criancaSelecionadaId) {
        $criancaSelecionada = $c;
        break;
    }
}
?>
<div class="kids-mundo">
    <div class="kids-login">
        <?php if ($criancaSelecionada === null): ?>
            <h1 class="kids-login-titulo">Quem é você? 🙋</h1>
            <p class="kids-login-subtitulo">Toque na sua foto pra entrar.</p>

            <?php if (!empty($error)): ?>
                <div class="kids-login-erro"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($criancas === []): ?>
                <div class="kids-login-vazio">
                    Nenhum perfil com PIN configurado ainda. Peça para a equipe da igreja gerar o seu.
                </div>
            <?php else: ?>
                <div class="kids-login-grade">
                    <?php foreach ($criancas as $crianca): ?>
                        <a href="<?= $basePath ?>/kids/entrar?crianca_id=<?= $crianca->id ?>" class="kids-login-perfil">
                            <span class="kids-login-perfil-foto">
                                <?php if ($crianca->fotoPath !== null): ?>
                                    <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                <?php else: ?>
                                    <?= htmlspecialchars(mb_strtoupper(mb_substr($crianca->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </span>
                            <?= htmlspecialchars(explode(' ', $crianca->nome)[0], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h1 class="kids-login-titulo">Oi, <?= htmlspecialchars(explode(' ', $criancaSelecionada->nome)[0], ENT_QUOTES, 'UTF-8') ?>! 👋</h1>
            <p class="kids-login-subtitulo">Digite seu PIN de 4 números.</p>

            <?php if (!empty($error)): ?>
                <div class="kids-login-erro"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="kids-login-pin-card">
                <span class="kids-login-perfil-foto" style="width: 88px; height: 88px; font-size: 2rem; margin: 0 auto; display: flex;">
                    <?php if ($criancaSelecionada->fotoPath !== null): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($criancaSelecionada->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($criancaSelecionada->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <form method="POST" action="<?= $basePath ?>/kids/entrar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="crianca_id" value="<?= $criancaSelecionada->id ?>">
                    <input
                        type="password"
                        name="pin"
                        class="kids-login-pin-input"
                        inputmode="numeric"
                        pattern="[0-9]{4}"
                        maxlength="4"
                        placeholder="••••"
                        autofocus
                        required
                    >
                    <button type="submit" class="kids-btn-concluir" style="width: 100%; justify-content: center;">
                        Entrar
                    </button>
                </form>
                <p style="margin-top: 1rem;">
                    <a href="<?= $basePath ?>/kids/entrar" class="kids-voltar" style="display: inline-flex;">
                        <i class="bi bi-arrow-left"></i> Não sou eu
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
