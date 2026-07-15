<?php

use Barbearias\Models\Cliente;

/**
 * @var array $config
 * @var array<int, Cliente> $aniversariantes
 * @var array<int, array{cliente: Cliente, ultimaVisita: string}> $inativos
 * @var int $dias
 */
$basePath = $config['base_path'] ?? '';

$mesesLabel = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
    7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
$mesAtual = $mesesLabel[(int) (new DateTimeImmutable())->format('n')];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Relacionamento</p>
            <h1 class="dashboard-title">CRM</h1>
            <p class="dash-page-subtitle">Quem vale a pena chamar - sem envio automático, é você quem entra em contato.</p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Aniversariantes de <?= $mesAtual ?></h2>
        </div>

        <?php if ($aniversariantes === []): ?>
            <p class="crud-empty">Nenhum cliente com aniversário cadastrado nesse mês.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Aniversário</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aniversariantes as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (new DateTimeImmutable($cliente->dataNascimento))->format('d/m') ?></td>
                                <td class="text-dim"><?= $cliente->telefone ? htmlspecialchars($cliente->telefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td class="text-dim"><?= $cliente->email ? htmlspecialchars($cliente->email, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Clientes inativos</h2>
        </div>

        <form method="GET" action="<?= $basePath ?>/dashboard/crm" class="crud-search">
            <label for="dias" style="align-self:center; font-size:0.85rem; color:var(--gray-400);">Sem vir há mais de</label>
            <select id="dias" name="dias" onchange="this.form.submit()" style="padding:0.65rem 1rem; border-radius:10px; border:1px solid var(--glass-border); background:var(--input-bg); color:var(--text);">
                <option value="30" <?= $dias === 30 ? 'selected' : '' ?>>30 dias</option>
                <option value="60" <?= $dias === 60 ? 'selected' : '' ?>>60 dias</option>
                <option value="90" <?= $dias === 90 ? 'selected' : '' ?>>90 dias</option>
                <option value="180" <?= $dias === 180 ? 'selected' : '' ?>>180 dias</option>
            </select>
        </form>

        <?php if ($inativos === []): ?>
            <p class="crud-empty">Nenhum cliente inativo com esse critério.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Última visita</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inativos as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['cliente']->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($item['ultimaVisita']))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= $item['cliente']->telefone ? htmlspecialchars($item['cliente']->telefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td class="text-dim"><?= $item['cliente']->email ? htmlspecialchars($item['cliente']->email, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
