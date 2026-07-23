<?php

/**
 * @var array $config
 * @var array $site
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$rotulosProduto = ['igrejas' => 'Igreja', 'barbearias' => 'Barbearia', 'food' => 'Restaurante'];
?>
<div class="page-header">
    <div>
        <h1>Excluir site</h1>
        <p>Essa acao e permanente e nao pode ser desfeita.</p>
    </div>
</div>

<div class="card" style="max-width:560px;">
    <p>
        Voce esta prestes a excluir a <?= $rotulosProduto[$site['produto']] ?>
        <strong><?= htmlspecialchars($site['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
        (<?= htmlspecialchars($site['identificador'], ENT_QUOTES, 'UTF-8') ?>).
    </p>
    <p style="color:var(--text-dim); font-size:0.88rem;">
        <?php if ($site['produto'] === 'igrejas'): ?>
            O banco de dados e o usuario MySQL dessa igreja no cPanel serao excluidos, junto com o registro
            central. O subdominio precisa ser removido manualmente depois (essa hospedagem nao permite excluir
            subdominio via API).
        <?php elseif ($site['produto'] === 'barbearias'): ?>
            Todos os dados dessa barbearia (agendamentos, clientes, financeiro, produtos etc.) serao excluidos
            em cascata, sem possibilidade de recuperacao.
        <?php else: ?>
            Todos os dados desse restaurante (pedidos, produtos, estoque, financeiro etc.) serao excluidos em
            cascata, sem possibilidade de recuperacao.
        <?php endif; ?>
    </p>

    <form method="POST" action="<?= $basePath ?>/sites/<?= $site['produto'] ?>/<?= $site['id'] ?>/excluir">
        <?= $csrf ?>
        <div class="field">
            <label for="confirmacao_nome">
                Para confirmar, digite o nome exato: <strong><?= htmlspecialchars($site['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
            </label>
            <input type="text" id="confirmacao_nome" name="confirmacao_nome" autocomplete="off" required autofocus>
        </div>
        <div style="display:flex; gap:10px; margin-top:16px;">
            <button type="submit" class="btn btn-danger">Excluir definitivamente</button>
            <a href="<?= $basePath ?>/sites" class="btn">Cancelar</a>
        </div>
    </form>
</div>
