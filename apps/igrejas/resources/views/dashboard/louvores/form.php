<?php

/**
 * @var array $config
 * @var \Igrejas\Models\Louvor|null $louvor
 * @var array<int, array{id: int, titulo: string}> $playbacks
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $louvor !== null;

$titulo = $old['titulo'] ?? $louvor->titulo ?? '';
$letra = $old['letra'] ?? $louvor->letra ?? '';
$tomAtual = $old['tom_atual'] ?? $louvor->tomAtual ?? '';
$cifra = $old['cifra'] ?? $louvor->cifra ?? '';
$playbackId = $old['playback_id'] ?? $louvor->playbackId ?? '';
$status = $old['status'] ?? $louvor->status ?? 'ativo';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/louvores/' . $louvor->id
    : $basePath . '/dashboard/louvores';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar louvor' : 'Novo louvor' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize a letra, cifra ou tom de ' . htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados do louvor para o time consultar.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/louvores" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

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

<div class="dash-panel">
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form" enctype="multipart/form-data">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-music-note-list"></i> Dados do louvor</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Grande é o Senhor" required autofocus>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="letra">Letra</label>
                    <textarea id="letra" name="letra" rows="8" placeholder="Cole aqui a letra completa do louvor"><?= htmlspecialchars($letra, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field">
                    <label for="tom_atual">Tom atual</label>
                    <input type="text" id="tom_atual" name="tom_atual" value="<?= htmlspecialchars($tomAtual, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: G, Am, D">
                    <span class="auth-field-hint">Mudar o tom aqui registra automaticamente no histórico abaixo (quem mudou e quando).</span>
                </div>
                <?php if ($isEdit): ?>
                    <div class="crud-field">
                        <label for="tom_observacao">O que mudou nesse tom (opcional)</label>
                        <input type="text" id="tom_observacao" name="tom_observacao" placeholder="Ex.: Abaixamos meio tom pro vocalista de hoje">
                    </div>
                <?php endif; ?>
                <div class="crud-field crud-field-full">
                    <label for="cifra">Cifra</label>
                    <textarea id="cifra" name="cifra" rows="8" class="crud-field-mono" placeholder="Cole aqui a cifra (acordes sobre a letra, intro, etc.)"><?= htmlspecialchars($cifra, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field">
                    <label for="playback_id">Áudio vinculado (Playbacks)</label>
                    <select id="playback_id" name="playback_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($playbacks as $playback): ?>
                            <option value="<?= $playback['id'] ?>" <?= (string) $playbackId === (string) $playback['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($playback['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="auth-field-hint">Se o louvor já tem um áudio cadastrado em Playbacks, vincule aqui.</span>
                </div>
                <?php if ($isEdit): ?>
                    <div class="crud-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="crud-field crud-field-full">
                    <label for="anexo">Anexo da cifra (PDF ou imagem, opcional)</label>
                    <input type="file" id="anexo" name="anexo" accept=".pdf,.png,.jpg,.jpeg,.webp">
                    <?php if ($isEdit && $louvor->anexoPath !== null): ?>
                        <span class="auth-field-hint">
                            Anexo atual:
                            <a href="<?= $basePath ?>/<?= htmlspecialchars($louvor->anexoPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($louvor->anexoNomeOriginal ?? 'arquivo', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            - enviar um novo arquivo substitui o atual.
                        </span>
                        <label class="toggle-switch-field" style="margin-top: 0.5rem;">
                            <input type="checkbox" name="remover_anexo" value="1">
                            <span class="toggle-switch"></span>
                            <span class="toggle-switch-label">Remover anexo atual (sem enviar um novo)</span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/louvores" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar louvor' ?>
            </button>
        </div>
    </form>
</div>
