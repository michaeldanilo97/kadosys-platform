<?php

use Academias\Core\Csrf;
use Academias\Models\Caixa;
use Academias\Models\FinanceiroLancamento;

/**
 * @var array $config
 * @var Caixa|null $caixa
 * @var array<int, FinanceiroLancamento> $lancamentosDoCaixa
 * @var array{receitas: float, despesas: float, saldo: float} $resumoDia
 * @var array{receitas: float, despesas: float, saldo: float} $resumoMes
 * @var array<int, FinanceiroLancamento> $lancamentos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $tipo
 * @var array<int, string> $formasPagamento
 * @var string|null $success
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';

$labelFormaPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao_credito' => 'Cartão de crédito',
    'cartao_debito' => 'Cartão de débito',
    'outro' => 'Outro',
];

$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');

$totalCaixaReceitas = 0.0;
$totalCaixaDespesas = 0.0;

foreach ($lancamentosDoCaixa as $lancamentoCaixa) {
    if ($lancamentoCaixa->tipo === FinanceiroLancamento::TIPO_RECEITA) {
        $totalCaixaReceitas += $lancamentoCaixa->valor;
    } else {
        $totalCaixaDespesas += $lancamentoCaixa->valor;
    }
}

$valorEsperadoCaixa = $caixa !== null ? $caixa->valorAbertura + $totalCaixaReceitas - $totalCaixaDespesas : 0.0;
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Gestão</p>
            <h1 class="dashboard-title">Financeiro</h1>
            <p class="dash-page-subtitle">Caixa, receitas e despesas da academia.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receitas hoje</p>
            <p class="kpi-valor"><?= $moeda($resumoDia['receitas']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Despesas hoje</p>
            <p class="kpi-valor"><?= $moeda($resumoDia['despesas']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Saldo do mês</p>
            <p class="kpi-valor"><?= $moeda($resumoMes['saldo']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receitas do mês</p>
            <p class="kpi-valor"><?= $moeda($resumoMes['receitas']) ?></p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Caixa</h2>
            <?php if ($caixa !== null): ?>
                <span class="status-badge ok">Aberto desde <?= (new DateTimeImmutable($caixa->abertoEm))->format('d/m/Y H:i') ?></span>
            <?php else: ?>
                <span class="status-badge dim">Fechado</span>
            <?php endif; ?>
        </div>

        <?php if ($caixa === null): ?>
            <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/caixa/abrir" class="crud-form-grid">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="valor_abertura">Valor de abertura (R$)</label>
                    <input type="text" id="valor_abertura" name="valor_abertura" inputmode="decimal" value="0,00" required>
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes_abertura">Observações (opcional)</label>
                    <input type="text" id="observacoes_abertura" name="observacoes" placeholder="Ex.: troco inicial">
                </div>
                <div class="crud-form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn-k btn-k-grad">Abrir caixa</button>
                </div>
            </form>
        <?php else: ?>
            <div class="confirmacao-detalhes" style="margin-bottom: 1rem;">
                <div><span>Valor de abertura</span><span><?= $moeda($caixa->valorAbertura) ?></span></div>
                <div><span>Receitas no caixa</span><span><?= $moeda($totalCaixaReceitas) ?></span></div>
                <div><span>Despesas no caixa</span><span><?= $moeda($totalCaixaDespesas) ?></span></div>
                <div><span>Valor esperado</span><span><?= $moeda($valorEsperadoCaixa) ?></span></div>
            </div>

            <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/caixa/fechar" class="crud-form-grid" onsubmit="return confirm('Fechar o caixa? Essa ação não pode ser desfeita.');">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="valor_fechamento_informado">Valor contado ao fechar (R$)</label>
                    <input type="text" id="valor_fechamento_informado" name="valor_fechamento_informado" inputmode="decimal" value="<?= htmlspecialchars(number_format($valorEsperadoCaixa, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes_fechamento">Observações (opcional)</label>
                    <input type="text" id="observacoes_fechamento" name="observacoes" placeholder="Ex.: diferença de troco">
                </div>
                <div class="crud-form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn-k btn-k-outline">Fechar caixa</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Novo lançamento</h2>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/lancamentos" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="receita" <?= ($old['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>>Receita</option>
                    <option value="despesa" <?= ($old['tipo'] ?? '') === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                </select>
            </div>
            <div class="form-field">
                <label for="categoria">Categoria</label>
                <input type="text" id="categoria" name="categoria" list="categorias-sugeridas" value="<?= htmlspecialchars($old['categoria'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Produtos, Aluguel..." required>
                <datalist id="categorias-sugeridas">
                    <option value="Mensalidade">
                    <option value="Aluguel">
                    <option value="Equipamentos">
                    <option value="Material">
                    <option value="Contas fixas">
                    <option value="Marketing">
                    <option value="Outros">
                </datalist>
            </div>
            <div class="form-field">
                <label for="aluno_id">Aluno (opcional)</label>
                <input type="number" id="aluno_id" name="aluno_id" min="1" value="<?= htmlspecialchars($old['aluno_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="ID do aluno, se for mensalidade">
            </div>
            <div class="form-field">
                <label for="forma_pagamento">Forma de pagamento</label>
                <select id="forma_pagamento" name="forma_pagamento" required>
                    <?php foreach ($formasPagamento as $forma): ?>
                        <option value="<?= $forma ?>" <?= ($old['forma_pagamento'] ?? '') === $forma ? 'selected' : '' ?>>
                            <?= $labelFormaPagamento[$forma] ?? $forma ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="valor">Valor (R$)</label>
                <input type="text" id="valor" name="valor" inputmode="decimal" value="<?= htmlspecialchars($old['valor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" required>
            </div>
            <div class="form-field">
                <label for="data_lancamento">Data</label>
                <input type="date" id="data_lancamento" name="data_lancamento" value="<?= htmlspecialchars($old['data_lancamento'] ?? (new DateTimeImmutable())->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-field crud-field-full">
                <label for="descricao">Descrição (opcional)</label>
                <input type="text" id="descricao" name="descricao" value="<?= htmlspecialchars($old['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="crud-form-actions" style="grid-column: 1 / -1;">
                <button type="submit" class="btn-k btn-k-grad">Registrar lançamento</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Lançamentos</h2>
        </div>

        <form method="GET" action="<?= $basePath ?>/dashboard/financeiro" class="crud-search">
            <select name="tipo" onchange="this.form.submit()" style="padding:0.65rem 1rem; border-radius:10px; border:1px solid var(--glass-border); background:var(--input-bg); color:var(--text);">
                <option value="">Todos</option>
                <option value="receita" <?= $tipo === 'receita' ? 'selected' : '' ?>>Receitas</option>
                <option value="despesa" <?= $tipo === 'despesa' ? 'selected' : '' ?>>Despesas</option>
            </select>
        </form>

        <?php if ($lancamentos === []): ?>
            <p class="crud-empty">Nenhum lançamento encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Forma</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lancamentos as $lancamento): ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($lancamento->dataLancamento))->format('d/m/Y') ?></td>
                                <td>
                                    <span class="status-badge <?= $lancamento->tipo === 'receita' ? 'ok' : 'danger' ?>">
                                        <?= $lancamento->tipo === 'receita' ? 'Receita' : 'Despesa' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($lancamento->categoria, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $labelFormaPagamento[$lancamento->formaPagamento] ?? $lancamento->formaPagamento ?></td>
                                <td class="text-dim"><?= $moeda($lancamento->valor) ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/lancamentos/<?= $lancamento->id ?>/excluir" onsubmit="return confirm('Excluir este lançamento?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($lastPage > 1): ?>
                <div class="crud-pagination">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="atual"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $basePath ?>/dashboard/financeiro?pagina=<?= $p ?>&tipo=<?= urlencode($tipo) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
