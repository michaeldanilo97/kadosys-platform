<?php

use Igrejas\Core\Csrf;

/**
 * @var array $config
 * @var string $etapa 'turma' | 'perfil' | 'pin'
 * @var array<int, array{id: int|null, nome: string, total: int}> $turmas
 * @var string $semTurmaSlug
 * @var array<int, \Igrejas\Models\KidsCrianca> $criancas
 * @var string|null $turmaParam
 * @var bool $mostrarVoltarTurmas
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var string $voltarUrl
 * @var string|null $error
 */
$basePath = $config['base_path'] ?? '';

function kids_entrar_foto(?string $fotoPath, string $nome, string $basePath): string
{
    if ($fotoPath !== null) {
        return '<img src="' . $basePath . '/' . htmlspecialchars($fotoPath, ENT_QUOTES, 'UTF-8') . '" alt="">';
    }

    return htmlspecialchars(mb_strtoupper(mb_substr($nome, 0, 1)), ENT_QUOTES, 'UTF-8');
}
?>
<div class="kids-mundo">
    <div class="kids-login">
        <?php if ($etapa === 'turma'): ?>
            <h1 class="kids-login-titulo">Qual é a sua turma? 🧑‍🤝‍🧑</h1>
            <p class="kids-login-subtitulo">Toque na sua turma pra ver sua foto.</p>

            <div class="kids-login-grade">
                <?php foreach ($turmas as $turma): ?>
                    <?php $turmaSlug = $turma['id'] === null ? $semTurmaSlug : (string) $turma['id']; ?>
                    <a href="<?= $basePath ?>/kids/entrar?turma_id=<?= urlencode($turmaSlug) ?>" class="kids-login-perfil kids-login-turma">
                        <span class="kids-login-perfil-foto"><i class="bi bi-people-fill"></i></span>
                        <span><?= htmlspecialchars($turma['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                        <small><?= $turma['total'] ?> criança<?= $turma['total'] === 1 ? '' : 's' ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php elseif ($etapa === 'perfil'): ?>
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
                        <?php
                        $linkParams = ['crianca_id' => (string) $crianca->id];
                        if ($turmaParam !== null) {
                            $linkParams['turma_id'] = $turmaParam;
                        }
                        ?>
                        <a href="<?= $basePath ?>/kids/entrar?<?= http_build_query($linkParams) ?>" class="kids-login-perfil">
                            <span class="kids-login-perfil-foto"><?= kids_entrar_foto($crianca->fotoPath, $crianca->nome, $basePath) ?></span>
                            <?= htmlspecialchars(explode(' ', $crianca->nome)[0], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($mostrarVoltarTurmas): ?>
                <p style="margin-top: 1.2rem;">
                    <a href="<?= $basePath ?>/kids/entrar" class="kids-voltar" style="display: inline-flex;">
                        <i class="bi bi-arrow-left"></i> Escolher outra turma
                    </a>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <h1 class="kids-login-titulo">Oi, <?= htmlspecialchars(explode(' ', $crianca->nome)[0], ENT_QUOTES, 'UTF-8') ?>! 👋</h1>
            <p class="kids-login-subtitulo">Digite seu PIN de 4 números.</p>

            <?php if (!empty($error)): ?>
                <div class="kids-login-erro"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="kids-login-pin-card">
                <span class="kids-login-perfil-foto" style="width: 88px; height: 88px; font-size: 2rem; margin: 0 auto; display: flex;">
                    <?= kids_entrar_foto($crianca->fotoPath, $crianca->nome, $basePath) ?>
                </span>
                <form method="POST" action="<?= $basePath ?>/kids/entrar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="crianca_id" value="<?= $crianca->id ?>">
                    <?php if (!empty($turmaParam)): ?>
                        <input type="hidden" name="turma_id" value="<?= htmlspecialchars($turmaParam, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
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
                    <a href="<?= $basePath ?>/<?= ltrim($voltarUrl, '/') ?>" class="kids-voltar" style="display: inline-flex;">
                        <i class="bi bi-arrow-left"></i> Não sou eu
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
