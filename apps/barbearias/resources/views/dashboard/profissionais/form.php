<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\PortfolioFoto;
use Barbearias\Models\Profissional;
use Barbearias\Models\Unidade;

/**
 * @var array $config
 * @var Profissional|null $profissional
 * @var array<int, Unidade> $unidades
 * @var array<int, int> $unidadesAtuais
 * @var array<int, PortfolioFoto> $portfolio
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $profissional !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/profissionais/' . $profissional->id : $basePath . '/dashboard/profissionais';

$diasSemana = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 0 => 'Dom'];
$diasAtuais = $old['dias_atendimento'] ?? $profissional->diasAtendimento ?? [];
$horarioInicioAtual = $old['horario_inicio'] ?? ($profissional !== null ? substr((string) $profissional->horarioInicio, 0, 5) : '09:00');
$horarioFimAtual = $old['horario_fim'] ?? ($profissional !== null ? substr((string) $profissional->horarioFim, 0, 5) : '18:00');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Equipe</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar profissional' : 'Novo profissional' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/profissionais" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $profissional->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" value="<?= htmlspecialchars($old['especialidade'] ?? $profissional->especialidade ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Corte masculino, barba">
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $profissional->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $profissional->email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="profissional@email.com">
                </div>
                <div class="form-field">
                    <label for="foto">Foto</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                    <span class="form-field-hint">PNG, JPG ou WEBP - até 5MB.</span>
                    <?php if ($isEdit && $profissional->fotoPath): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($profissional->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual" class="foto-preview">
                    <?php endif; ?>
                </div>

                <div class="form-field crud-field-full">
                    <label>Dias de atendimento</label>
                    <div class="dias-semana-grid">
                        <?php foreach ($diasSemana as $numero => $label): ?>
                            <label class="dia-semana-chip">
                                <input type="checkbox" name="dias_atendimento[]" value="<?= $numero ?>" <?= in_array($numero, $diasAtuais, true) ? 'checked' : '' ?>>
                                <span><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <span class="form-field-hint">Usado pra calcular os horários disponíveis no agendamento público.</span>
                </div>
                <div class="form-field">
                    <label for="horario_inicio">Início do expediente</label>
                    <input type="time" id="horario_inicio" name="horario_inicio" value="<?= htmlspecialchars($horarioInicioAtual, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="horario_fim">Fim do expediente</label>
                    <input type="time" id="horario_fim" name="horario_fim" value="<?= htmlspecialchars($horarioFimAtual, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <?php if ($unidades !== []): ?>
                    <?php $unidadesMarcadas = $old['unidades'] ?? $unidadesAtuais; ?>
                    <div class="form-field crud-field-full">
                        <label>Atende em</label>
                        <div class="dias-semana-grid">
                            <?php foreach ($unidades as $unidade): ?>
                                <label class="dia-semana-chip">
                                    <input type="checkbox" name="unidades[]" value="<?= $unidade->id ?>" <?= in_array($unidade->id, $unidadesMarcadas, true) || in_array((string) $unidade->id, $unidadesMarcadas, true) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($unidade->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="form-field-hint">Em quais unidades esse profissional atende - usado pra filtrar quem aparece no agendamento público.</span>
                    </div>
                <?php endif; ?>

                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $profissional->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Profissional ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/profissionais" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>

    <?php if ($isEdit): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Portfólio</h2>
            </div>
            <p class="form-field-hint" style="margin: -0.5rem 0 1rem;">Fotos de trabalhos desse profissional, mostradas na página pública de agendamento.</p>

            <?php if ($portfolio !== []): ?>
                <div class="escolha-grid" style="margin-bottom: 1.5rem;">
                    <?php foreach ($portfolio as $foto): ?>
                        <div class="crud-person" style="flex-direction: column; align-items: stretch;">
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($foto->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($foto->legenda ?? 'Foto do portfólio', ENT_QUOTES, 'UTF-8') ?>" class="foto-preview" style="width:100%; height:140px; object-fit:cover;">
                            <?php if ($foto->legenda): ?>
                                <p class="form-field-hint" style="margin: 0.4rem 0 0;"><?= htmlspecialchars($foto->legenda, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <form method="POST" action="<?= $basePath ?>/dashboard/portfolio/<?= $foto->id ?>/excluir" onsubmit="return confirm('Remover esta foto do portfólio?');" style="margin-top: 0.5rem;">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn-k btn-k-outline btn-k-sm" style="width:100%;">Remover</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= $basePath ?>/dashboard/profissionais/<?= $profissional->id ?>/portfolio" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <div class="crud-form-grid">
                    <div class="form-field">
                        <label for="portfolio_foto">Adicionar foto</label>
                        <input type="file" id="portfolio_foto" name="foto" accept="image/png,image/jpeg,image/webp" required>
                        <span class="form-field-hint">PNG, JPG ou WEBP - até 5MB.</span>
                    </div>
                    <div class="form-field">
                        <label for="legenda">Legenda (opcional)</label>
                        <input type="text" id="legenda" name="legenda" placeholder="Ex.: Degradê + barba">
                    </div>
                </div>
                <div class="crud-form-actions">
                    <button type="submit" class="btn-k btn-k-outline">Adicionar ao portfólio</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>
