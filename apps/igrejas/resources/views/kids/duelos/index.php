<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array<int, array{id: int, criadorNome: string, conteudoTitulo: string}> $pendentes
 * @var array<int, array{id: int, oponenteNome: string, conteudoTitulo: string}> $emAndamento
 * @var array<int, \Igrejas\Models\KidsCrianca> $amigos
 * @var array<int, \Igrejas\Models\KidsConteudo> $quizzes
 * @var string $csrfToken
 * @var string|null $erro
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>🎮 Jogar com Amigo</h1>
            <p>Desafie alguém da sua igreja pra um duelo de quiz!</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php if ($erro): ?>
        <div class="kids-login-erro">
            <span><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if ($pendentes !== []): ?>
        <h2 class="kids-secao-titulo">📩 Convites pendentes</h2>
        <div class="kids-duelo-lista">
            <?php foreach ($pendentes as $convite): ?>
                <div class="kids-duelo-card">
                    <span class="kids-duelo-texto">
                        <strong><?= htmlspecialchars($convite['criadorNome'], ENT_QUOTES, 'UTF-8') ?></strong>
                        te chamou pra um duelo de "<?= htmlspecialchars($convite['conteudoTitulo'], ENT_QUOTES, 'UTF-8') ?>"!
                    </span>
                    <div class="kids-duelo-acoes">
                        <form method="POST" action="<?= $basePath ?>/kids/duelos/<?= $convite['id'] ?>/aceitar">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="kids-btn-concluir" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Aceitar</button>
                        </form>
                        <form method="POST" action="<?= $basePath ?>/kids/duelos/<?= $convite['id'] ?>/recusar">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="kids-voltar" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Recusar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($emAndamento !== []): ?>
        <h2 class="kids-secao-titulo">⏳ Em andamento</h2>
        <div class="kids-duelo-lista">
            <?php foreach ($emAndamento as $duelo): ?>
                <a href="<?= $basePath ?>/kids/duelos/<?= $duelo['id'] ?>" class="kids-duelo-card kids-duelo-card-link">
                    <span class="kids-duelo-texto">
                        Duelo com <strong><?= htmlspecialchars($duelo['oponenteNome'], ENT_QUOTES, 'UTF-8') ?></strong>
                        - "<?= htmlspecialchars($duelo['conteudoTitulo'], ENT_QUOTES, 'UTF-8') ?>"
                    </span>
                    <span class="kids-item-selo-loja">Continuar ▶</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="kids-secao-titulo">⚔️ Desafiar um amigo</h2>
    <?php if ($amigos === []): ?>
        <div class="kids-vazio">Ainda não há outras crianças com acesso pra desafiar. Peça pra equipe cadastrar o PIN de mais amigos!</div>
    <?php elseif ($quizzes === []): ?>
        <div class="kids-vazio">Ainda não há nenhum quiz publicado pra duelar.</div>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/kids/duelos" class="kids-duelo-form">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <label class="kids-duelo-label">
                Quem?
                <select name="convidado_id" required>
                    <?php foreach ($amigos as $amigo): ?>
                        <option value="<?= $amigo->id ?>"><?= htmlspecialchars($amigo->nome, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="kids-duelo-label">
                Qual quiz?
                <select name="conteudo_id" required>
                    <?php foreach ($quizzes as $quiz): ?>
                        <option value="<?= $quiz->id ?>"><?= htmlspecialchars($quiz->titulo, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit" class="kids-btn-concluir"><i class="bi bi-controller"></i> Desafiar!</button>
        </form>
    <?php endif; ?>
</div>
