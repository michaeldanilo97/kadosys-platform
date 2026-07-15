<?php

use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var Agendamento $agendamento
 * @var string $csrf
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8');
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card">
        <div class="hero-eyebrow">Reagendar</div>
        <h1><?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">Escolha uma nova data e horário pro seu atendimento.</p>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="confirmacao-detalhes" style="margin-bottom: 1.5rem;">
            <div><span>Agendado para</span><span><?= (new DateTimeImmutable($agendamento->dataHora))->format('d/m/Y H:i') ?></span></div>
            <div><span>Profissional</span><span><?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Serviço</span><span><?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?></span></div>
        </div>

        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/agendamentos/<?= $agendamento->id ?>/reagendar" data-reagendar-form>
            <?= $csrf ?>

            <div class="form-field">
                <label for="data">Nova data</label>
                <input type="date" id="data" name="data" data-campo-data value="<?= htmlspecialchars($old['data'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-field" data-bloco-horarios hidden>
                <label>Horário</label>
                <div class="horarios-grid" data-lista-horarios></div>
                <p class="form-field-hint" data-horarios-status></p>
            </div>

            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;" data-btn-confirmar disabled>Escolha um horário</button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color: var(--gray-400);">
            <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>">Voltar sem reagendar</a>
        </p>
    </div>
</div>

<script>
    (function () {
        var form = document.querySelector('[data-reagendar-form]');
        if (!form) {
            return;
        }

        var basePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;
        var slug = <?= json_encode($barbearia->slug) ?>;
        var agendamentoId = <?= json_encode($agendamento->id) ?>;
        var campoData = form.querySelector('[data-campo-data]');
        var blocoHorarios = form.querySelector('[data-bloco-horarios]');
        var listaHorarios = form.querySelector('[data-lista-horarios]');
        var statusHorarios = form.querySelector('[data-horarios-status]');
        var btnConfirmar = form.querySelector('[data-btn-confirmar]');
        var inputHoraOculto = null;

        var hoje = new Date();
        campoData.min = hoje.toISOString().slice(0, 10);

        function limparHorario() {
            if (inputHoraOculto) {
                inputHoraOculto.remove();
                inputHoraOculto = null;
            }
            btnConfirmar.disabled = true;
            btnConfirmar.textContent = 'Escolha um horário';
        }

        function buscarHorarios() {
            var data = campoData.value;

            limparHorario();

            if (!data) {
                blocoHorarios.hidden = true;

                return;
            }

            blocoHorarios.hidden = false;
            listaHorarios.innerHTML = '';
            statusHorarios.textContent = 'Buscando horários...';

            var url = basePath + '/minha-conta/' + slug + '/agendamentos/' + agendamentoId + '/reagendar/horarios?data=' + encodeURIComponent(data);

            fetch(url)
                .then(function (resposta) { return resposta.json(); })
                .then(function (dados) {
                    var horarios = dados.horarios || [];

                    if (horarios.length === 0) {
                        statusHorarios.textContent = 'Nenhum horário livre nesse dia. Tente outra data.';

                        return;
                    }

                    statusHorarios.textContent = '';

                    horarios.forEach(function (hora) {
                        var botao = document.createElement('button');
                        botao.type = 'button';
                        botao.className = 'horario-chip';
                        botao.textContent = hora;
                        botao.addEventListener('click', function () {
                            listaHorarios.querySelectorAll('.horario-chip').forEach(function (el) {
                                el.classList.remove('selecionado');
                            });
                            botao.classList.add('selecionado');

                            if (inputHoraOculto) {
                                inputHoraOculto.remove();
                            }

                            inputHoraOculto = document.createElement('input');
                            inputHoraOculto.type = 'hidden';
                            inputHoraOculto.name = 'hora';
                            inputHoraOculto.value = hora;
                            form.appendChild(inputHoraOculto);

                            btnConfirmar.disabled = false;
                            btnConfirmar.textContent = 'Confirmar novo horário';
                        });
                        listaHorarios.appendChild(botao);
                    });
                })
                .catch(function () {
                    statusHorarios.textContent = 'Não foi possível buscar os horários agora. Tente novamente.';
                });
        }

        campoData.addEventListener('change', buscarHorarios);

        if (campoData.value) {
            buscarHorarios();
        }
    })();
</script>
