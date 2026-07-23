<?php

use Food\Core\Csrf;
use Food\Models\CentroCusto;
use Food\Models\ContaPagar;

/**
 * @var array $config
 * @var array<int, ContaPagar> $contas
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $statusSelecionado
 * @var array<int, CentroCusto> $centrosCusto
 * @var string|null $success
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';

$labelStatus = [
    ContaPagar::STATUS_PENDENTE => 'Pendente',
    ContaPagar::STATUS_PAGA => 'Paga',
    ContaPagar::STATUS_CANCELADA => 'Cancelada',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Financeiro</p>
            <h1 class="dashboard-title">Contas a Pagar</h1>
            <p class="dash-page-subtitle"><?= $total ?> <?= $total === 1 ? 'conta cadastrada' : 'contas cadastradas' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/financeiro" class="btn-k btn-k-outline">Voltar ao Financeiro</a>
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

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Nova conta a pagar</h2>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar" class="crud-form-grid" data-form-recorrente>
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="descricao">Descrição</label>
                <input type="text" id="descricao" name="descricao" value="<?= htmlspecialchars((string) ($old['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Aluguel, fornecedor X..." required>
            </div>
            <div class="form-field">
                <label for="categoria">Categoria</label>
                <input type="text" id="categoria" name="categoria" value="<?= htmlspecialchars((string) ($old['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Aluguel, Fornecedores...">
            </div>
            <div class="form-field">
                <label for="centro_custo_id">Centro de custo</label>
                <select id="centro_custo_id" name="centro_custo_id">
                    <option value="">Nenhum</option>
                    <?php foreach ($centrosCusto as $centro): ?>
                        <option value="<?= $centro->id ?>"><?= htmlspecialchars($centro->nome, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="valor">Valor</label>
                <input type="text" id="valor" name="valor" value="<?= htmlspecialchars((string) ($old['valor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" required>
            </div>
            <div class="form-field">
                <label for="vencimento">Vencimento</label>
                <input type="date" id="vencimento" name="vencimento" value="<?= htmlspecialchars((string) ($old['vencimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-field crud-checkbox-field">
                <input type="checkbox" id="recorrente" name="recorrente" value="1" data-campo-recorrente>
                <label for="recorrente" style="margin:0;">Despesa recorrente (repete todo mês)</label>
            </div>
            <div class="form-field" data-campo-parcela-total hidden>
                <label for="parcela_total">Repetir por quantas parcelas? (vazio = sem fim)</label>
                <input type="number" id="parcela_total" name="parcela_total" min="1" placeholder="Ex.: 12">
            </div>
            <div class="form-field" style="flex: 1 1 100%;">
                <label for="observacoes">Observações</label>
                <input type="text" id="observacoes" name="observacoes" value="<?= htmlspecialchars((string) ($old['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-field" style="align-self: flex-end;">
                <button type="submit" class="btn-k btn-k-grad">+ Cadastrar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar" class="crud-search">
            <select name="status">
                <option value="">Todos os status</option>
                <?php foreach ($labelStatus as $status => $label): ?>
                    <option value="<?= $status ?>" <?= $statusSelecionado === $status ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-k btn-k-outline">Filtrar</button>
        </form>

        <?php if ($contas === []): ?>
            <p class="crud-empty">Nenhuma conta encontrada.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contas as $conta): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($conta->descricao, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($conta->recorrente): ?><span class="status-badge dim" title="Recorrente"><i class="bi bi-arrow-repeat"></i></span><?php endif; ?>
                                </td>
                                <td class="text-dim"><?= htmlspecialchars($conta->categoria ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($conta->vencimento))->format('d/m/Y') ?></td>
                                <td>R$ <?= number_format($conta->valor, 2, ',', '.') ?></td>
                                <td>
                                    <?php if ($conta->estaVencida()): ?>
                                        <span class="status-badge danger">Vencida</span>
                                    <?php else: ?>
                                        <span class="status-badge <?= $conta->status === ContaPagar::STATUS_PAGA ? 'ok' : ($conta->status === ContaPagar::STATUS_CANCELADA ? 'dim' : 'warn') ?>"><?= $labelStatus[$conta->status] ?? $conta->status ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-col">
                                    <?php if ($conta->status === ContaPagar::STATUS_PENDENTE): ?>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar/<?= $conta->id ?>/pagar" data-confirm="Marcar esta conta como paga?">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="crud-icon-btn" title="Marcar como paga"><i class="bi bi-check-circle-fill"></i></button>
                                        </form>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar/<?= $conta->id ?>/cancelar" data-confirm="Cancelar esta conta?">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="crud-icon-btn danger" title="Cancelar"><i class="bi bi-x-circle-fill"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar/<?= $conta->id ?>/excluir" data-confirm="Excluir esta conta?">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="crud-icon-btn danger" title="Excluir"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    <?php endif; ?>
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
                            <a href="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar?pagina=<?= $p ?>&status=<?= urlencode($statusSelecionado) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
    (function () {
        var checkbox = document.querySelector('[data-campo-recorrente]');
        var campoParcelas = document.querySelector('[data-campo-parcela-total]');

        if (!checkbox || !campoParcelas) {
            return;
        }

        checkbox.addEventListener('change', function () {
            campoParcelas.hidden = !checkbox.checked;
        });
    })();
</script>
