<?php

use Superadmin\Core\Csrf;

/**
 * @var array $config
 * @var array $sites
 * @var string $busca
 * @var int $totalIgrejas
 * @var int $totalBarbearias
 * @var string|null $sucesso
 * @var string|null $erro
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';

$rotulosProduto = ['igrejas' => 'Igrejas', 'barbearias' => 'Barbearias'];
$rotulosStatus = [
    'ativo' => 'Ativo',
    'suspenso' => 'Suspenso',
    'pendente' => 'Pendente',
    'provisionando' => 'Provisionando',
    'erro' => 'Erro',
];
?>
<div class="page-header">
    <div>
        <h1>Sites</h1>
        <p>Todos os produtos KADOSYS em um so lugar - <?= count($sites) ?> site(s) (<?= $totalIgrejas ?> Igrejas, <?= $totalBarbearias ?> Barbearias).</p>
    </div>
    <form method="GET" action="<?= $basePath ?>/sites">
        <input
            type="search"
            name="busca"
            placeholder="Buscar por nome ou slug..."
            value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
            style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:10px 14px; color:var(--text); min-width:260px;"
        >
    </form>
</div>

<?php if (!empty($sucesso)): ?>
    <div class="flash flash-success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($erro)): ?>
    <div class="flash flash-error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
    <?php if ($sites === []): ?>
        <p class="empty-state">Nenhum site encontrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Nome</th>
                        <th>Identificador</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ultimo acesso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                        <tr>
                            <td><span class="badge badge-produto-<?= $site['produto'] ?>"><?= $rotulosProduto[$site['produto']] ?></span></td>
                            <td>
                                <?php if ($site['url']): ?>
                                    <a href="<?= htmlspecialchars($site['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($site['nome'], ENT_QUOTES, 'UTF-8') ?></a>
                                <?php else: ?>
                                    <?= htmlspecialchars($site['nome'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($site['identificador'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($site['plano'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge badge-status-<?= $site['status'] ?>"><?= $rotulosStatus[$site['status']] ?? $site['status'] ?></span></td>
                            <td><?= htmlspecialchars(substr($site['criado_em'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $site['ultimo_acesso_em'] ? htmlspecialchars(substr($site['ultimo_acesso_em'], 0, 16), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                    <?php if ($site['status'] === 'suspenso'): ?>
                                        <form method="POST" action="<?= $basePath ?>/sites/<?= $site['produto'] ?>/<?= $site['id'] ?>/reativar">
                                            <?= $csrf ?>
                                            <button type="submit" class="btn btn-sm">Reativar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= $basePath ?>/sites/<?= $site['produto'] ?>/<?= $site['id'] ?>/suspender">
                                            <?= $csrf ?>
                                            <button type="submit" class="btn btn-sm">Suspender</button>
                                        </form>
                                    <?php endif; ?>
                                    <form
                                        method="POST"
                                        action="<?= $basePath ?>/sites/<?= $site['produto'] ?>/<?= $site['id'] ?>/estender"
                                        style="display:flex; gap:4px; align-items:center;"
                                        title="Ativa o site e estende o trial/vencimento pelo numero de dias informado"
                                    >
                                        <?= $csrf ?>
                                        <input
                                            type="number"
                                            name="dias"
                                            value="30"
                                            min="1"
                                            max="365"
                                            style="width:60px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:6px 8px; color:var(--text);"
                                        >
                                        <button type="submit" class="btn btn-sm">Ativar/Estender</button>
                                    </form>
                                    <a href="<?= $basePath ?>/sites/<?= $site['produto'] ?>/<?= $site['id'] ?>/excluir" class="btn btn-sm btn-danger">Excluir</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
