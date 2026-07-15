<?php

use Barbearias\Models\Barbearia;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var array<int, Profissional> $profissionais
 * @var array<int, Servico> $servicos
 * @var string $csrf
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card">
        <div class="hero-eyebrow">Agendamento online</div>
        <h1><?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">Escolha o profissional, o serviço e o melhor horário pra você.</p>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($profissionais === [] || $servicos === []): ?>
            <p class="crud-empty" style="padding: 1rem 0;">Essa barbearia ainda não configurou profissionais ou serviços pro agendamento online.</p>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/agendar/<?= htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8') ?>" data-agendar-form>
                <?= $csrf ?>

                <div class="form-field">
                    <label>Profissional</label>
                    <div class="escolha-grid">
                        <?php foreach ($profissionais as $profissional): ?>
                            <label class="escolha-card">
                                <input type="radio" name="profissional_id" value="<?= $profissional->id ?>" data-campo-profissional <?= (string) ($old['profissional_id'] ?? '') === (string) $profissional->id ? 'checked' : '' ?> required>
                                <span class="nome"><?= htmlspecialchars($profissional->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($profissional->especialidade): ?>
                                    <span class="desc"><?= htmlspecialchars($profissional->especialidade, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-field">
                    <label>Serviço</label>
                    <div class="escolha-grid">
                        <?php foreach ($servicos as $servico): ?>
                            <label class="escolha-card">
                                <input type="radio" name="servico_id" value="<?= $servico->id ?>" data-campo-servico <?= (string) ($old['servico_id'] ?? '') === (string) $servico->id ? 'checked' : '' ?> required>
                                <span class="nome"><?= htmlspecialchars($servico->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="preco">R$ <?= number_format($servico->preco, 2, ',', '.') ?><small> · <?= $servico->duracaoMinutos ?> min</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-field-row">
                    <div class="form-field">
                        <label for="data">Data</label>
                        <input type="date" id="data" name="data" data-campo-data value="<?= htmlspecialchars($old['data'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <div class="form-field" data-bloco-horarios hidden>
                    <label>Horário</label>
                    <div class="horarios-grid" data-lista-horarios></div>
                    <p class="form-field-hint" data-horarios-status></p>
                </div>

                <div class="form-field-row">
                    <div class="form-field">
                        <label for="nome">Seu nome</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome completo" required>
                    </div>
                    <div class="form-field">
                        <label for="telefone">Telefone (com DDD)</label>
                        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000" required>
                    </div>
                </div>

                <div class="form-field">
                    <label for="email">E-mail (opcional)</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="voce@email.com">
                </div>

                <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;" data-btn-confirmar disabled>Escolha um horário</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var form = document.querySelector('[data-agendar-form]');
        if (!form) {
            return;
        }

        var basePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;
        var slug = <?= json_encode($barbearia->slug) ?>;
        var campoData = form.querySelector('[data-campo-data]');
        var blocoHorarios = form.querySelector('[data-bloco-horarios]');
        var listaHorarios = form.querySelector('[data-lista-horarios]');
        var statusHorarios = form.querySelector('[data-horarios-status]');
        var btnConfirmar = form.querySelector('[data-btn-confirmar]');
        var horarioEscolhido = null;
        var inputHoraOculto = null;

        var hoje = new Date();
        campoData.min = hoje.toISOString().slice(0, 10);

        function profissionalSelecionado() {
            var input = form.querySelector('[data-campo-profissional]:checked');
            return input ? input.value : '';
        }

        function servicoSelecionado() {
            var input = form.querySelector('[data-campo-servico]:checked');
            return input ? input.value : '';
        }

        function limparHorario() {
            horarioEscolhido = null;
            if (inputHoraOculto) {
                inputHoraOculto.remove();
                inputHoraOculto = null;
            }
            btnConfirmar.disabled = true;
            btnConfirmar.textContent = 'Escolha um horário';
        }

        function buscarHorarios() {
            var profissionalId = profissionalSelecionado();
            var servicoId = servicoSelecionado();
            var data = campoData.value;

            limparHorario();

            if (!profissionalId || !servicoId || !data) {
                blocoHorarios.hidden = true;

                return;
            }

            blocoHorarios.hidden = false;
            listaHorarios.innerHTML = '';
            statusHorarios.textContent = 'Buscando horários...';

            var url = basePath + '/agendar/' + slug + '/horarios?profissional_id=' + encodeURIComponent(profissionalId)
                + '&servico_id=' + encodeURIComponent(servicoId) + '&data=' + encodeURIComponent(data);

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

                            horarioEscolhido = hora;

                            if (inputHoraOculto) {
                                inputHoraOculto.remove();
                            }

                            inputHoraOculto = document.createElement('input');
                            inputHoraOculto.type = 'hidden';
                            inputHoraOculto.name = 'hora';
                            inputHoraOculto.value = hora;
                            form.appendChild(inputHoraOculto);

                            btnConfirmar.disabled = false;
                            btnConfirmar.textContent = 'Confirmar agendamento';
                        });
                        listaHorarios.appendChild(botao);
                    });
                })
                .catch(function () {
                    statusHorarios.textContent = 'Não foi possível buscar os horários agora. Tente novamente.';
                });
        }

        form.querySelectorAll('[data-campo-profissional], [data-campo-servico]').forEach(function (input) {
            input.addEventListener('change', buscarHorarios);
        });
        campoData.addEventListener('change', buscarHorarios);

        if (profissionalSelecionado() && servicoSelecionado() && campoData.value) {
            buscarHorarios();
        }
    })();
</script>
