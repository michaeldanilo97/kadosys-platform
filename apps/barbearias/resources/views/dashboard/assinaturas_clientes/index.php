<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\AssinaturaCliente;
use Barbearias\Models\AssinaturaPlano;
use Barbearias\Models\Cliente;

/**
 * @var array $config
 * @var array<int, AssinaturaPlano> $planos
 * @var string $termoBusca
 * @var array<int, Cliente> $clientesEncontrados
 * @var array<int, array{assinatura: AssinaturaCliente, usados: int, inicioCiclo: DateTimeImmutable}> $assinaturasAtivas
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Relacionamento</p>
            <h1 class="dashboard-title">Assinaturas</h1>
            <p class="dash-page-subtitle">Pacotes pré-pagos de atendimentos por mês - a mensalidade é cobrada fora do sistema, o app só controla o consumo.</p>
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
            <h2>Planos</h2>
        </div>

        <form method="POST" action="<?= $basePath ?>/dashboard/assinaturas-clientes/planos" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" placeholder="Ex.: Plano Mensal" required>
            </div>
            <div class="form-field">
                <label for="preco">Preço mensal (R$)</label>
                <input type="text" id="preco" name="preco" inputmode="decimal" placeholder="120,00" required>
            </div>
            <div class="form-field">
                <label for="atendimentos_por_mes">Atendimentos por mês</label>
                <input type="number" id="atendimentos_por_mes" name="atendimentos_por_mes" min="1" required>
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-outline">+ Adicionar</button>
            </div>
        </form>

        <?php if ($planos === []): ?>
            <p class="crud-empty">Nenhum plano cadastrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Preço mensal</th>
                            <th>Atendimentos/mês</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planos as $plano): ?>
                            <tr>
                                <td><?= htmlspecialchars($plano->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $moeda($plano->preco) ?></td>
                                <td class="text-dim"><?= $plano->atendimentosPorMes ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/assinaturas-clientes/planos/<?= $plano->id ?>/excluir" onsubmit="return confirm('Excluir este plano?');">
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
            <h2>Assinar cliente</h2>
        </div>

        <form method="GET" action="<?= $basePath ?>/dashboard/assinaturas-clientes" class="crud-search">
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
                            <th>Plano</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesEncontrados as $encontrado): ?>
                            <tr>
                                <td><?= htmlspecialchars($encontrado->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $encontrado->telefone ? htmlspecialchars($encontrado->telefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td>
                                    <?php if ($planos === []): ?>
                                        <span class="text-dim">Cadastre um plano primeiro</span>
                                    <?php else: ?>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/assinaturas-clientes/assinar" style="display:flex; gap:0.4rem;">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="cliente_id" value="<?= $encontrado->id ?>">
                                            <select name="plano_id" style="padding:0.4rem 0.5rem; border-radius:8px; border:1px solid var(--glass-border); background:var(--input-bg); color:var(--text);">
                                                <?php foreach ($planos as $plano): ?>
                                                    <option value="<?= $plano->id ?>"><?= htmlspecialchars($plano->nome, ENT_QUOTES, 'UTF-8') ?> (<?= $moeda($plano->preco) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-k btn-k-sm btn-k-grad">Assinar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($termoBusca !== ''): ?>
            <p class="crud-empty">Nenhum cliente encontrado.</p>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Assinantes ativos</h2>
        </div>

        <?php if ($assinaturasAtivas === []): ?>
            <p class="crud-empty">Nenhuma assinatura ativa.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Plano</th>
                            <th>Ciclo atual</th>
                            <th>Uso no ciclo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assinaturasAtivas as $linha): ?>
                            <?php $assinatura = $linha['assinatura']; ?>
                            <tr>
                                <td><?= htmlspecialchars($assinatura->clienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($assinatura->planoNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">desde <?= $linha['inicioCiclo']->format('d/m/Y') ?></td>
                                <td>
                                    <span class="status-badge <?= $linha['usados'] >= $assinatura->planoAtendimentosPorMes ? 'danger' : 'ok' ?>">
                                        <?= $linha['usados'] ?>/<?= $assinatura->planoAtendimentosPorMes ?>
                                    </span>
                                </td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/assinaturas-clientes/<?= $assinatura->id ?>/cancelar" onsubmit="return confirm('Cancelar a assinatura de <?= htmlspecialchars(addslashes($assinatura->clienteNome), ENT_QUOTES, 'UTF-8') ?>?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Cancelar">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
