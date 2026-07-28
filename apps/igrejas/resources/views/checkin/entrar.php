<?php

/**
 * @var array $config
 * @var string|null $nomeIgreja
 * @var string|null $logoPath
 * @var string $token
 * @var array<int, \Igrejas\Models\Culto> $cultosHoje
 * @var string|null $error
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $logoPath ? $basePath . '/' . $logoPath : null;
?>

<div class="avisos-publico-shell checkin-shell">
    <header class="avisos-publico-header">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="avisos-publico-logo">
        <?php endif; ?>
        <h1><?= htmlspecialchars($nomeIgreja ?? 'Nossa igreja', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Check-in</p>
    </header>

    <main>
        <?php if (!empty($error)): ?>
            <div class="avisos-publico-card" style="border-color: rgba(248,113,113,0.4); color: var(--danger, #f87171);">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($cultosHoje === []): ?>
            <div class="avisos-publico-vazio">
                <i class="bi bi-calendar-x"></i>
                <p>Nenhum culto agendado para hoje.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/checkin/<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" id="checkin-form">
                <?= $csrf ?>
                <input type="hidden" name="membro_id" id="checkin-membro-id" value="">

                <?php if (count($cultosHoje) === 1): ?>
                    <input type="hidden" name="culto_id" value="<?= $cultosHoje[0]->id ?>">
                    <p class="checkin-culto-atual">
                        Culto de hoje: <strong><?= htmlspecialchars($cultosHoje[0]->titulo, ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($cultosHoje[0]->hora): ?>
                            às <?= substr($cultosHoje[0]->hora, 0, 5) ?>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="checkin-culto-atual">Qual culto de hoje?</p>
                    <div class="checkin-cultos-opcoes">
                        <?php foreach ($cultosHoje as $i => $culto): ?>
                            <label class="checkin-culto-opcao">
                                <input type="radio" name="culto_id" value="<?= $culto->id ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                                <span>
                                    <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($culto->hora): ?>
                                        <small><?= substr($culto->hora, 0, 5) ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="checkin-busca">
                    <label for="checkin-busca-input">Digite seu nome</label>
                    <input type="text" id="checkin-busca-input" autocomplete="off" placeholder="Pelo menos 2 letras..." required>
                    <div id="checkin-busca-resultados" class="checkin-busca-resultados" hidden></div>
                </div>

                <button type="submit" class="btn-k btn-k-grad checkin-confirmar-btn" id="checkin-confirmar-btn" disabled style="width: 100%; justify-content: center; margin-top: 1rem;">
                    <i class="bi bi-check-circle"></i> Confirmar presença
                </button>
            </form>
        <?php endif; ?>
    </main>

    <footer class="avisos-publico-footer">
        <span>KADOSYS Igrejas</span>
    </footer>
</div>

<script>
(function () {
    var input = document.getElementById('checkin-busca-input');
    var resultados = document.getElementById('checkin-busca-resultados');
    var membroIdField = document.getElementById('checkin-membro-id');
    var confirmarBtn = document.getElementById('checkin-confirmar-btn');

    if (!input || !resultados) {
        return;
    }

    var basePath = <?= json_encode($basePath) ?>;
    var token = <?= json_encode($token) ?>;
    var timer = null;

    function limparSelecao() {
        membroIdField.value = '';
        confirmarBtn.disabled = true;
    }

    function mostrarResultados(itens) {
        resultados.innerHTML = '';

        if (itens.length === 0) {
            resultados.hidden = true;

            return;
        }

        itens.forEach(function (membro) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'checkin-busca-item';
            item.textContent = membro.nome;
            item.addEventListener('click', function () {
                membroIdField.value = membro.id;
                input.value = membro.nome;
                resultados.hidden = true;
                confirmarBtn.disabled = false;
            });
            resultados.appendChild(item);
        });

        resultados.hidden = false;
    }

    input.addEventListener('input', function () {
        limparSelecao();

        var termo = input.value.trim();

        if (timer) {
            clearTimeout(timer);
        }

        if (termo.length < 2) {
            resultados.hidden = true;

            return;
        }

        timer = setTimeout(function () {
            fetch(basePath + '/checkin/' + token + '/buscar?q=' + encodeURIComponent(termo))
                .then(function (resp) { return resp.json(); })
                .then(function (data) { mostrarResultados(data.resultados || []); })
                .catch(function () { resultados.hidden = true; });
        }, 250);
    });
})();
</script>
