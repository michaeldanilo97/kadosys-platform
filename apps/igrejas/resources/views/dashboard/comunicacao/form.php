<?php
/**
 * @var array $config
 * @var \Igrejas\Models\ComunicacaoAviso|null $aviso
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $aviso !== null;

$titulo = $old['titulo'] ?? $aviso->titulo ?? '';
$conteudo = $old['conteudo'] ?? $aviso->conteudo ?? '';
$publicoAlvo = $old['publico_alvo'] ?? $aviso->publicoAlvo ?? 'todos';
$status = $old['status'] ?? $aviso->status ?? 'rascunho';
$dataPublicacao = $old['data_publicacao'] ?? $aviso->dataPublicacao ?? '';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/comunicacao/' . $aviso->id
    : $basePath . '/dashboard/comunicacao';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar aviso' : 'Novo aviso' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize o aviso ' . htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para publicar um novo aviso ou comunicado.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/comunicacao" class="btn-k btn-k-ghost">
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
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-megaphone"></i> Dados do aviso</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="titulo">Titulo *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Mudanca no horario do culto de quarta" required autofocus>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="conteudo">Conteudo *</label>
                    <textarea id="conteudo" name="conteudo" rows="5" placeholder="Escreva o aviso ou comunicado" required><?= htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field">
                    <label for="publico_alvo">Publico</label>
                    <select id="publico_alvo" name="publico_alvo">
                        <option value="todos" <?= $publicoAlvo === 'todos' ? 'selected' : '' ?>>Todos os membros</option>
                        <option value="lideranca" <?= $publicoAlvo === 'lideranca' ? 'selected' : '' ?>>So lideranca</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="rascunho" <?= $status === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="publicado" <?= $status === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                        <option value="arquivado" <?= $status === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="data_publicacao">Data de publicacao</label>
                    <input type="date" id="data_publicacao" name="data_publicacao" value="<?= htmlspecialchars($dataPublicacao, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="auth-field-hint">Se deixar em branco e publicar, usa a data de hoje.</span>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/comunicacao" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar aviso' ?>
            </button>
        </div>
    </form>
</div>
