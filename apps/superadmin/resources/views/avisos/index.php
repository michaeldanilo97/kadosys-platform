<?php

/**
 * @var array $config
 * @var \Superadmin\Models\AvisoIgreja|null $avisoIgreja
 * @var \Superadmin\Models\AvisoBarbearia|null $avisoBarbearia
 * @var \Superadmin\Models\AvisoFood|null $avisoFood
 * @var array $historicoIgreja
 * @var array $historicoBarbearia
 * @var array $historicoFood
 * @var string|null $sucesso
 * @var string|null $erro
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$formatarData = static fn (string $data): string => (new DateTimeImmutable($data))->format('d/m/Y H:i');
?>
<div class="page-header">
    <div>
        <h1>Avisos</h1>
        <p>Publique um aviso no sino de notificações de Igrejas, Barbearias, Food ou todos.</p>
    </div>
</div>

<?php if (!empty($sucesso)): ?>
    <div class="flash flash-success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($erro)): ?>
    <div class="flash flash-error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card" style="max-width:640px; margin-bottom:24px;">
    <form method="POST" action="<?= $basePath ?>/avisos">
        <?= $csrf ?>
        <div class="field">
            <label for="mensagem">Mensagem</label>
            <textarea id="mensagem" name="mensagem" rows="3" required
                style="width:100%; background:var(--bg-2); border:1px solid var(--border); border-radius:10px; padding:11px 14px; color:var(--text); font-size:0.95rem; font-family:inherit; resize:vertical;"
                placeholder="Ex.: Manutenção programada no sábado às 22h."></textarea>
        </div>
        <div class="field">
            <label>Público</label>
            <div style="display:flex; gap:16px; margin-top:4px;">
                <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; color:var(--text); font-weight:400;">
                    <input type="radio" name="publico" value="todos" checked> Todos
                </label>
                <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; color:var(--text); font-weight:400;">
                    <input type="radio" name="publico" value="igrejas"> Somente Igrejas
                </label>
                <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; color:var(--text); font-weight:400;">
                    <input type="radio" name="publico" value="barbearias"> Somente Barbearias
                </label>
                <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; color:var(--text); font-weight:400;">
                    <input type="radio" name="publico" value="food"> Somente Food
                </label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:auto; margin-top:12px; padding-left:24px; padding-right:24px;">Publicar aviso</button>
    </form>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px;">
    <div class="card">
        <h2 style="font-size:1rem; margin:0 0 4px;"><span class="badge badge-produto-igrejas">Igrejas</span></h2>
        <?php if ($avisoIgreja === null): ?>
            <p class="empty-state" style="padding:20px 0;">Nenhum aviso ativo.</p>
        <?php else: ?>
            <p style="font-size:0.9rem; margin:12px 0 4px;"><?= htmlspecialchars($avisoIgreja->mensagem, ENT_QUOTES, 'UTF-8') ?></p>
            <p style="font-size:0.78rem; color:var(--text-dim); margin:0 0 12px;">Publicado em <?= $formatarData($avisoIgreja->createdAt) ?></p>
            <form method="POST" action="<?= $basePath ?>/avisos/igrejas/<?= $avisoIgreja->id ?>/encerrar">
                <?= $csrf ?>
                <button type="submit" class="btn btn-sm">Encerrar</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-size:1rem; margin:0 0 4px;"><span class="badge badge-produto-barbearias">Barbearias</span></h2>
        <?php if ($avisoBarbearia === null): ?>
            <p class="empty-state" style="padding:20px 0;">Nenhum aviso ativo.</p>
        <?php else: ?>
            <p style="font-size:0.9rem; margin:12px 0 4px;"><?= htmlspecialchars($avisoBarbearia->mensagem, ENT_QUOTES, 'UTF-8') ?></p>
            <p style="font-size:0.78rem; color:var(--text-dim); margin:0 0 12px;">Publicado em <?= $formatarData($avisoBarbearia->createdAt) ?></p>
            <form method="POST" action="<?= $basePath ?>/avisos/barbearias/<?= $avisoBarbearia->id ?>/encerrar">
                <?= $csrf ?>
                <button type="submit" class="btn btn-sm">Encerrar</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-size:1rem; margin:0 0 4px;"><span class="badge badge-produto-food">Food</span></h2>
        <?php if ($avisoFood === null): ?>
            <p class="empty-state" style="padding:20px 0;">Nenhum aviso ativo.</p>
        <?php else: ?>
            <p style="font-size:0.9rem; margin:12px 0 4px;"><?= htmlspecialchars($avisoFood->mensagem, ENT_QUOTES, 'UTF-8') ?></p>
            <p style="font-size:0.78rem; color:var(--text-dim); margin:0 0 12px;">Publicado em <?= $formatarData($avisoFood->createdAt) ?></p>
            <form method="POST" action="<?= $basePath ?>/avisos/food/<?= $avisoFood->id ?>/encerrar">
                <?= $csrf ?>
                <button type="submit" class="btn btn-sm">Encerrar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($historicoIgreja !== [] || $historicoBarbearia !== [] || $historicoFood !== []): ?>
    <div class="card" style="margin-top:20px;">
        <h2 style="font-size:1rem; margin:0 0 12px;">Histórico recente</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Mensagem</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historicoIgreja as $item): ?>
                        <tr>
                            <td><span class="badge badge-produto-igrejas">Igrejas</span></td>
                            <td style="white-space:normal; max-width:400px;"><?= htmlspecialchars($item->mensagem, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $item->ativo ? '<span class="badge badge-status-ativo">Ativo</span>' : '<span class="badge badge-status-suspenso">Encerrado</span>' ?></td>
                            <td><?= $formatarData($item->createdAt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($historicoBarbearia as $item): ?>
                        <tr>
                            <td><span class="badge badge-produto-barbearias">Barbearias</span></td>
                            <td style="white-space:normal; max-width:400px;"><?= htmlspecialchars($item->mensagem, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $item->ativo ? '<span class="badge badge-status-ativo">Ativo</span>' : '<span class="badge badge-status-suspenso">Encerrado</span>' ?></td>
                            <td><?= $formatarData($item->createdAt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($historicoFood as $item): ?>
                        <tr>
                            <td><span class="badge badge-produto-food">Food</span></td>
                            <td style="white-space:normal; max-width:400px;"><?= htmlspecialchars($item->mensagem, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $item->ativo ? '<span class="badge badge-status-ativo">Ativo</span>' : '<span class="badge badge-status-suspenso">Encerrado</span>' ?></td>
                            <td><?= $formatarData($item->createdAt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
