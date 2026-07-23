<?php

use Food\Core\Csrf;
use Food\Models\Caixa;

/**
 * @var array $config
 * @var Caixa|null $caixa
 * @var float $saldoEsperado
 * @var array<int, array{id: int, tipo: string, categoria: ?string, formaPagamento: string, valor: float, descricao: ?string, createdAt: string}> $lancamentosDoCaixa
 * @var array<int, Caixa> $historico
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Vendas</p>
            <h1 class="dashboard-title">Caixa</h1>
            <p class="dash-page-subtitle"><?= $caixa !== null ? 'Caixa aberto' : 'Nenhum caixa aberto no momento' ?></p>
        </div>
        <?php if ($caixa !== null): ?>
            <a href="<?= $basePath ?>/dashboard/pdv" class="btn-k btn-k-grad">Ir para o PDV</a>
        <?php endif; ?>
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

    <?php if ($caixa === null): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Abrir caixa</h2>
            </div>
            <form method="POST" action="<?= $basePath ?>/dashboard/caixa/abrir" class="crud-form-grid">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="valor_abertura">Valor inicial (troco)</label>
                    <input type="text" id="valor_abertura" name="valor_abertura" placeholder="0,00" value="0,00" required>
                </div>
                <div class="form-field" style="flex: 1 1 100%;">
                    <label for="observacoes">Observações (opcional)</label>
                    <input type="text" id="observacoes" name="observacoes" placeholder="Ex.: caixa aberto pela manhã">
                </div>
                <div class="form-field" style="align-self: flex-end;">
                    <button type="submit" class="btn-k btn-k-grad">Abrir caixa</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Situação do caixa</h2>
            </div>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Aberto em</th>
                            <th>Valor de abertura</th>
                            <th>Saldo esperado agora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-dim"><?= (new DateTimeImmutable($caixa->abertoEm))->format('d/m/Y H:i') ?></td>
                            <td>R$ <?= number_format($caixa->valorAbertura, 2, ',', '.') ?></td>
                            <td><strong>R$ <?= number_format($saldoEsperado, 2, ',', '.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Sangria / Suprimento</h2>
            </div>
            <div class="crud-form-grid-wrapper" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <form method="POST" action="<?= $basePath ?>/dashboard/caixa/sangria" class="crud-form-grid" data-confirm="Confirmar retirada (sangria) do caixa?">
                    <?= Csrf::field() ?>
                    <div class="form-field" style="flex: 1 1 100%;">
                        <label>Sangria (retirada de dinheiro)</label>
                    </div>
                    <div class="form-field">
                        <label for="valor_sangria">Valor</label>
                        <input type="text" id="valor_sangria" name="valor" placeholder="0,00" required>
                    </div>
                    <div class="form-field">
                        <label for="motivo_sangria">Motivo</label>
                        <input type="text" id="motivo_sangria" name="motivo" placeholder="Ex.: depósito no banco" required>
                    </div>
                    <div class="form-field" style="align-self: flex-end;">
                        <button type="submit" class="btn-k btn-k-outline">Registrar sangria</button>
                    </div>
                </form>

                <form method="POST" action="<?= $basePath ?>/dashboard/caixa/suprimento" class="crud-form-grid" data-confirm="Confirmar entrada (suprimento) no caixa?">
                    <?= Csrf::field() ?>
                    <div class="form-field" style="flex: 1 1 100%;">
                        <label>Suprimento (reforço de dinheiro)</label>
                    </div>
                    <div class="form-field">
                        <label for="valor_suprimento">Valor</label>
                        <input type="text" id="valor_suprimento" name="valor" placeholder="0,00" required>
                    </div>
                    <div class="form-field">
                        <label for="motivo_suprimento">Motivo</label>
                        <input type="text" id="motivo_suprimento" name="motivo" placeholder="Ex.: reforço de troco" required>
                    </div>
                    <div class="form-field" style="align-self: flex-end;">
                        <button type="submit" class="btn-k btn-k-grad">Registrar suprimento</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Movimentações deste caixa</h2>
            </div>
            <?php if ($lancamentosDoCaixa === []): ?>
                <p class="crud-empty">Nenhuma movimentação registrada neste caixa ainda.</p>
            <?php else: ?>
                <div class="crud-table-wrapper">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Data/hora</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Pagamento</th>
                                <th>Valor</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lancamentosDoCaixa as $lancamento): ?>
                                <tr>
                                    <td class="text-dim"><?= (new DateTimeImmutable($lancamento['createdAt']))->format('d/m/Y H:i') ?></td>
                                    <td><span class="status-badge <?= $lancamento['tipo'] === 'receita' ? 'ok' : 'danger' ?>"><?= $lancamento['tipo'] === 'receita' ? 'Entrada' : 'Saída' ?></span></td>
                                    <td class="text-dim"><?= htmlspecialchars($lancamento['categoria'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-dim"><?= htmlspecialchars($lancamento['formaPagamento'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>R$ <?= number_format($lancamento['valor'], 2, ',', '.') ?></td>
                                    <td class="text-dim"><?= htmlspecialchars($lancamento['descricao'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Fechar caixa</h2>
            </div>
            <form method="POST" action="<?= $basePath ?>/dashboard/caixa/fechar" class="crud-form-grid" data-confirm="Fechar o caixa? Confira o valor em dinheiro antes de confirmar.">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="valor_fechamento_informado">Valor contado em caixa</label>
                    <input type="text" id="valor_fechamento_informado" name="valor_fechamento_informado" placeholder="0,00" required>
                </div>
                <div class="form-field" style="flex: 1 1 100%;">
                    <label for="observacoes_fechamento">Observações (opcional)</label>
                    <input type="text" id="observacoes_fechamento" name="observacoes" placeholder="Ex.: diferença justificada por...">
                </div>
                <div class="form-field" style="align-self: flex-end;">
                    <button type="submit" class="btn-k btn-k-outline">Fechar caixa</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Histórico de caixas</h2>
        </div>
        <?php if ($historico === []): ?>
            <p class="crud-empty">Nenhum caixa foi aberto ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Aberto em</th>
                            <th>Fechado em</th>
                            <th>Status</th>
                            <th>Valor de abertura</th>
                            <th>Valor de fechamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td class="text-dim"><?= (new DateTimeImmutable($item->abertoEm))->format('d/m/Y H:i') ?></td>
                                <td class="text-dim"><?= $item->fechadoEm !== null ? (new DateTimeImmutable($item->fechadoEm))->format('d/m/Y H:i') : '-' ?></td>
                                <td><span class="status-badge <?= $item->status === Caixa::STATUS_ABERTO ? 'ok' : 'dim' ?>"><?= $item->status === Caixa::STATUS_ABERTO ? 'Aberto' : 'Fechado' ?></span></td>
                                <td>R$ <?= number_format($item->valorAbertura, 2, ',', '.') ?></td>
                                <td class="text-dim"><?= $item->valorFechamentoInformado !== null ? 'R$ ' . number_format($item->valorFechamentoInformado, 2, ',', '.') : '-' ?></td>
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
                            <a href="<?= $basePath ?>/dashboard/caixa?pagina=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
