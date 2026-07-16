<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Agendamento;
use Barbearias\Models\ComissaoPagamento;
use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var array<int, Profissional> $profissionais
 * @var array<int, array{profissionalId: int, profissionalNome: string, percentualComissao: float, quantidade: int, totalServicos: float, totalComissao: float}> $fechamento
 * @var float $totalGeral
 * @var float $totalComissoes
 * @var string $dataInicio
 * @var string $dataFim
 * @var int $profissionalId
 * @var array{profissional: Profissional, atendimentos: array<int, Agendamento>, valoresPagos: array<int, float>}|null $detalhe
 * @var ComissaoPagamento|null $comissaoPaga
 * @var bool $caixaAberto
 * @var array<int, string> $errors
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Gestão</p>
            <h1 class="dashboard-title">Comissões</h1>
            <p class="dash-page-subtitle">Fechamento por profissional, com base nos atendimentos concluídos no período.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="form-alert form-alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Faturado no período</p>
            <p class="kpi-valor"><?= $moeda($totalGeral) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Total de comissões</p>
            <p class="kpi-valor"><?= $moeda($totalComissoes) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Profissionais com atendimento</p>
            <p class="kpi-valor"><?= count($fechamento) ?></p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Fechamento do período</h2>
        </div>

        <form method="GET" action="<?= $basePath ?>/dashboard/comissoes" class="crud-form-grid">
            <div class="form-field">
                <label for="data_inicio">De</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-field">
                <label for="data_fim">Até</label>
                <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-field">
                <label for="profissional_id">Profissional</label>
                <select id="profissional_id" name="profissional_id">
                    <option value="0">Todos</option>
                    <?php foreach ($profissionais as $profissional): ?>
                        <option value="<?= $profissional->id ?>" <?= $profissionalId === $profissional->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($profissional->nome, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Filtrar</button>
            </div>
        </form>

        <?php if ($fechamento === []): ?>
            <p class="crud-empty">Nenhum atendimento concluído nesse período.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Profissional</th>
                            <th>Atendimentos</th>
                            <th>Faturado</th>
                            <th>% Comissão</th>
                            <th>A receber</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fechamento as $linha): ?>
                            <tr>
                                <td><?= htmlspecialchars($linha['profissionalNome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $linha['quantidade'] ?></td>
                                <td class="text-dim"><?= $moeda($linha['totalServicos']) ?></td>
                                <td class="text-dim"><?= number_format($linha['percentualComissao'], 2, ',', '.') ?>%</td>
                                <td><strong><?= $moeda($linha['totalComissao']) ?></strong></td>
                                <td class="actions-col">
                                    <a class="crud-icon-btn" title="Ver atendimentos" href="<?= $basePath ?>/dashboard/comissoes?data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>&profissional_id=<?= $linha['profissionalId'] ?>">🔍</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($detalhe !== null): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Atendimentos de <?= htmlspecialchars($detalhe['profissional']->nome, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>

            <?php if ($comissaoPaga !== null): ?>
                <p class="crud-empty" style="text-align:left;">
                    ✅ Comissão paga em <?= (new DateTimeImmutable((string) $comissaoPaga->createdAt))->format('d/m/Y H:i') ?>
                    (<?= $moeda($comissaoPaga->valor) ?>) -
                    <a href="<?= $basePath . '/' . $comissaoPaga->comprovantePath ?>" target="_blank" rel="noopener">ver comprovante</a>
                </p>
            <?php elseif (!empty($fechamento)): ?>
                <?php if (!$caixaAberto): ?>
                    <p class="crud-empty" style="text-align:left;">Abra o caixa (menu Financeiro) pra poder pagar a comissão.</p>
                <?php else: ?>
                    <form method="POST" action="<?= $basePath ?>/dashboard/comissoes/<?= $detalhe['profissional']->id ?>/pagar" enctype="multipart/form-data" class="crud-form-grid" style="margin-top:0.5rem;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="data_inicio" value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="data_fim" value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-field crud-field-full">
                            <label for="comprovante">Comprovante do pagamento (obrigatório - imagem ou PDF)</label>
                            <input type="file" id="comprovante" name="comprovante" accept="image/png,image/jpeg,image/webp,application/pdf" required>
                        </div>
                        <div class="crud-form-actions">
                            <button type="submit" class="btn-k btn-k-grad">💸 Pagar comissão descontando do caixa</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($detalhe['atendimentos'] === []): ?>
                <p class="crud-empty">Nenhum atendimento concluído nesse período.</p>
            <?php else: ?>
                <div class="crud-table-wrapper">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalhe['atendimentos'] as $atendimento): ?>
                                <tr>
                                    <td><?= (new DateTimeImmutable($atendimento->dataHora))->format('d/m/Y H:i') ?></td>
                                    <td><?= htmlspecialchars($atendimento->clienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-dim"><?= htmlspecialchars($atendimento->servicoNome, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-dim"><?= $moeda($detalhe['valoresPagos'][$atendimento->id] ?? $atendimento->servicoPreco) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>
