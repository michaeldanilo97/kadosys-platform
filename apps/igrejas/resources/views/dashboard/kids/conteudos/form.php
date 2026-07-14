<?php

use Igrejas\Models\KidsConteudo;

/**
 * @var array $config
 * @var KidsConteudo|null $conteudo
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $conteudo !== null;

$tipo = $old['tipo'] ?? $conteudo->tipo ?? 'historia';
$titulo = $old['titulo'] ?? $conteudo->titulo ?? '';
$descricao = $old['descricao'] ?? $conteudo->descricao ?? '';
$categoria = $old['categoria'] ?? $conteudo->categoria ?? '';
$tema = $old['tema'] ?? $conteudo->tema ?? '';
$livroBiblico = $old['livro_biblico'] ?? $conteudo->livroBiblico ?? '';
$personagem = $old['personagem'] ?? $conteudo->personagem ?? '';
$dificuldade = $old['dificuldade'] ?? $conteudo->dificuldade ?? '';
$idadeMin = $old['idade_min'] ?? $conteudo->idadeMin ?? '';
$idadeMax = $old['idade_max'] ?? $conteudo->idadeMax ?? '';
$duracao = $old['duracao_minutos'] ?? $conteudo->duracaoMinutos ?? '';
$xp = $old['xp_recompensa'] ?? $conteudo->xpRecompensa ?? 10;
$moedas = $old['moedas_recompensa'] ?? $conteudo->moedasRecompensa ?? 5;
$textoConteudo = $old['texto_conteudo'] ?? $conteudo->textoConteudo ?? '';
$midiaUrl = $old['midia_url'] ?? $conteudo->midiaUrl ?? '';
$status = $old['status'] ?? $conteudo->status ?? 'publicado';
$quizPerguntas = $conteudo->quizPerguntas ?? [];

$actionUrl = $isEdit
    ? $basePath . '/dashboard/kids/conteudos/' . $conteudo->id
    : $basePath . '/dashboard/kids/conteudos';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar conteúdo' : 'Novo conteúdo' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') . '.'
                : 'Cadastre um novo conteúdo para a Biblioteca Kids da sua igreja.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids/conteudos" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-collection-play"></i> Dados básicos</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars((string) $titulo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: A Arca de Noé" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="tipo">Tipo *</label>
                    <select id="tipo" name="tipo" required>
                        <?php foreach (KidsConteudo::TIPOS as $slug => $info): ?>
                            <option value="<?= $slug ?>" <?= $tipo === $slug ? 'selected' : '' ?>>
                                <?= $info['emoji'] ?> <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="publicado" <?= $status === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                        <option value="rascunho" <?= $status === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descrição curta</label>
                    <textarea id="descricao" name="descricao" rows="2" placeholder="Resumo mostrado no card da biblioteca (opcional)"><?= htmlspecialchars((string) $descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-tags"></i> Classificação</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="categoria">Categoria</label>
                    <input type="text" id="categoria" name="categoria" value="<?= htmlspecialchars((string) $categoria, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Velho Testamento">
                </div>
                <div class="crud-field">
                    <label for="tema">Tema</label>
                    <input type="text" id="tema" name="tema" value="<?= htmlspecialchars((string) $tema, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Obediência">
                </div>
                <div class="crud-field">
                    <label for="livro_biblico">Livro bíblico</label>
                    <input type="text" id="livro_biblico" name="livro_biblico" value="<?= htmlspecialchars((string) $livroBiblico, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Gênesis">
                </div>
                <div class="crud-field">
                    <label for="personagem">Personagem</label>
                    <input type="text" id="personagem" name="personagem" value="<?= htmlspecialchars((string) $personagem, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Noé">
                </div>
                <div class="crud-field">
                    <label for="dificuldade">Dificuldade</label>
                    <select id="dificuldade" name="dificuldade">
                        <option value="">Não definida</option>
                        <option value="facil" <?= $dificuldade === 'facil' ? 'selected' : '' ?>>Fácil</option>
                        <option value="medio" <?= $dificuldade === 'medio' ? 'selected' : '' ?>>Médio</option>
                        <option value="dificil" <?= $dificuldade === 'dificil' ? 'selected' : '' ?>>Difícil</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="duracao_minutos">Duração (minutos)</label>
                    <input type="number" id="duracao_minutos" name="duracao_minutos" min="0" value="<?= htmlspecialchars((string) $duracao, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="idade_min">Idade mínima</label>
                    <input type="number" id="idade_min" name="idade_min" min="0" max="17" value="<?= htmlspecialchars((string) $idadeMin, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="idade_max">Idade máxima</label>
                    <input type="number" id="idade_max" name="idade_max" min="0" max="17" value="<?= htmlspecialchars((string) $idadeMax, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-stars"></i> Recompensa ao concluir</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="xp_recompensa">XP</label>
                    <input type="number" id="xp_recompensa" name="xp_recompensa" min="0" value="<?= htmlspecialchars((string) $xp, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="moedas_recompensa">Moedas</label>
                    <input type="number" id="moedas_recompensa" name="moedas_recompensa" min="0" value="<?= htmlspecialchars((string) $moedas, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-file-text"></i> Conteúdo e mídia</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="texto_conteudo">Texto (história, devocional, estudo, versículo...)</label>
                    <textarea id="texto_conteudo" name="texto_conteudo" rows="6" placeholder="Texto que a criança vai ler/ouvir (opcional para vídeo/áudio/colorir)"><?= htmlspecialchars((string) $textoConteudo, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="midia_url">Link externo (vídeo do YouTube, áudio, etc.)</label>
                    <input type="url" id="midia_url" name="midia_url" value="<?= htmlspecialchars((string) $midiaUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://...">
                </div>
                <div class="crud-field">
                    <label for="capa">Imagem de capa</label>
                    <input type="file" id="capa" name="capa" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="crud-field">
                    <label for="midia">Arquivo (PDF, áudio, vídeo ou imagem)</label>
                    <input type="file" id="midia" name="midia" accept=".pdf,audio/mpeg,video/mp4,image/jpeg,image/png,image/webp">
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-question-circle"></i> Perguntas do quiz</h3>
            <p class="crud-text-dim" style="margin-top: -0.4rem;">Preencha só se o tipo for "Quiz". Separe as alternativas com "|" e escolha qual é a certa.</p>
            <div class="crud-form-grid">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <?php
                    $pergunta = $quizPerguntas[$i]['pergunta'] ?? '';
                    $alternativas = isset($quizPerguntas[$i]['alternativas']) ? implode(' | ', $quizPerguntas[$i]['alternativas']) : '';
                    $correta = $quizPerguntas[$i]['correta'] ?? 0;
                    ?>
                    <div class="crud-field crud-field-full" style="border-top: 1px dashed var(--glass-border); padding-top: 0.8rem;">
                        <label>Pergunta <?= $i + 1 ?></label>
                        <input type="text" name="quiz_pergunta[]" value="<?= htmlspecialchars($pergunta, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Quem construiu a arca?">
                        <div class="crud-form-grid" style="margin-top: 0.5rem;">
                            <div class="crud-field crud-field-full">
                                <label>Alternativas (separadas por "|")</label>
                                <input type="text" name="quiz_alternativas[]" value="<?= htmlspecialchars($alternativas, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Moisés | Noé | Davi | Abraão">
                            </div>
                            <div class="crud-field">
                                <label>Alternativa certa (posição, 0 = primeira)</label>
                                <input type="number" name="quiz_correta[]" min="0" max="3" value="<?= (int) $correta ?>">
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/kids/conteudos" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar conteúdo' ?>
            </button>
        </div>
    </form>
</div>
