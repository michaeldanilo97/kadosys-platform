<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\FidelidadeMovimento;
use Barbearias\Models\FidelidadeRecompensa;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var array<int, FidelidadeRecompensa> $recompensas
 * @var string $termoBusca
 * @var array<int, Cliente> $clientesEncontrados
 * @var Cliente|null $clienteSelecionado
 * @var array<int, FidelidadeMovimento> $historico
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$fidelidadeAtiva = $barbearia->fidelidadePontosPorReal !== null;

$tipoLabel = [
    FidelidadeMovimento::TIPO_GANHO => 'Ganho',
    FidelidadeMovimento::TIPO_RESGATE => 'Resgate',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Relacionamento</p>
            <h1 class="dashboard-title">Fidelidade</h1>
            <p class="dash-page-subtitle">Pontos por atendimento pago, trocados por recompensas cadastradas.</p>
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

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Configuração</h2>
            <span class="status-badge <?= $fidelidadeAtiva ? 'ok' : 'dim' ?>"><?= $fidelidadeAtiva ? 'Ativo' : 'Desativado' ?></span>
        </div>

        <form method="POST" action="<?= $basePath ?>/dashboard/fidelidade/configurar" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field crud-checkbox-field">
                <input type="checkbox" id="fidelidade_ativa" name="fidelidade_ativa" value="1" <?= $fidelidadeAtiva ? 'checked' : '' ?>>
                <label for="fidelidade_ativa" style="margin:0;">Programa de fidelidade ativo</label>
            </div>
            <div class="form-field">
                <label for="pontos_por_real">Pontos por real gasto</label>
                <input type="text" id="pontos_por_real" name="pontos_por_real" inputmode="decimal" value="<?= htmlspecialchars((string) ($barbearia->fidelidadePontosPorReal ?? '1'), ENT_QUOTES, 'UTF-8') ?>">
                <span class="form-field-hint">Concedido automaticamente ao registrar o pagamento de um atendimento.</span>
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Salvar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Recompensas</h2>
        </div>

        <form method="POST" action="<?= $basePath ?>/dashboard/fidelidade/recompensas" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" placeholder="Ex.: Corte grátis" required>
            </div>
            <div class="form-field">
                <label for="pontos_necessarios">Pontos necessários</label>
                <input type="number" id="pontos_necessarios" name="pontos_necessarios" min="1" required>
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-outline">+ Adicionar</button>
            </div>
        </form>

        <?php if ($recompensas === []): ?>
            <p class="crud-empty">Nenhuma recompensa cadastrada.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Pontos necessários</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recompensas as $recompensa): ?>
                            <tr>
                                <td><?= htmlspecialchars($recompensa->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $recompensa->pontosNecessarios ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/fidelidade/recompensas/<?= $recompensa->id ?>/excluir" onsubmit="return confirm('Excluir esta recompensa?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Resgatar pontos</h2>
        </div>

        <form method="GET" action="<?= $basePath ?>/dashboard/fidelidade" class="crud-search">
            <input type="text" name="busca_cliente" value="<?= htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar cliente por nome ou telefone...">
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($clientesEncontrados !== []): ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Telefone</th>
                            <th>Pontos</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesEncontrados as $encontrado): ?>
                            <tr>
                                <td><?= htmlspecialchars($encontrado->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $encontrado->telefone ? htmlspecialchars($encontrado->telefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td class="text-dim"><?= $encontrado->pontosFidelidade ?></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/fidelidade?cliente_id=<?= $encontrado->id ?>" class="btn-k btn-k-sm btn-k-outline">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($termoBusca !== ''): ?>
            <p class="crud-empty">Nenhum cliente encontrado.</p>
        <?php endif; ?>

        <?php if ($clienteSelecionado !== null): ?>
            <div class="confirmacao-detalhes" style="margin-top: 1.5rem;">
                <div><span>Cliente</span><span><?= htmlspecialchars($clienteSelecionado->nome, ENT_QUOTES, 'UTF-8') ?></span></div>
                <div><span>Saldo de pontos</span><span><strong><?= $clienteSelecionado->pontosFidelidade ?></strong></span></div>
            </div>

            <?php $recompensasAtivas = array_filter($recompensas, static fn ($r) => $r->ativo); ?>
            <?php if ($recompensasAtivas === []): ?>
                <p class="crud-empty">Nenhuma recompensa ativa cadastrada.</p>
            <?php else: ?>
                <div class="escolha-grid" style="margin-bottom: 1.5rem;">
                    <?php foreach ($recompensasAtivas as $recompensa): ?>
                        <?php $podeResgatar = $clienteSelecionado->pontosFidelidade >= $recompensa->pontosNecessarios; ?>
                        <form method="POST" action="<?= $basePath ?>/dashboard/fidelidade/resgatar" class="escolha-card" style="cursor:default;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="cliente_id" value="<?= $clienteSelecionado->id ?>">
                            <input type="hidden" name="recompensa_id" value="<?= $recompensa->id ?>">
                            <span class="nome"><?= htmlspecialchars($recompensa->nome, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="preco"><?= $recompensa->pontosNecessarios ?> pontos</span>
                            <button type="submit" class="btn-k btn-k-sm btn-k-grad" style="margin-top:0.75rem;" <?= $podeResgatar ? '' : 'disabled' ?>>
                                <?= $podeResgatar ? 'Resgatar' : 'Pontos insuficientes' ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 style="font-size: 0.95rem; margin: 0 0 0.75rem;">Histórico</h3>
            <?php if ($historico === []): ?>
                <p class="crud-empty">Nenhuma movimentação ainda.</p>
            <?php else: ?>
                <div class="crud-table-wrapper">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Pontos</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $movimento): ?>
                                <tr>
                                    <td class="text-dim"><?= (new DateTimeImmutable($movimento->createdAt))->format('d/m/Y H:i') ?></td>
                                    <td>
                                        <span class="status-badge <?= $movimento->tipo === FidelidadeMovimento::TIPO_GANHO ? 'ok' : 'dim' ?>">
                                            <?= $tipoLabel[$movimento->tipo] ?? $movimento->tipo ?>
                                        </span>
                                    </td>
                                    <td class="text-dim"><?= $movimento->tipo === FidelidadeMovimento::TIPO_GANHO ? '+' : '-' ?><?= $movimento->pontos ?></td>
                                    <td class="text-dim"><?= htmlspecialchars($movimento->descricao ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
