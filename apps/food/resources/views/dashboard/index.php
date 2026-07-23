<?php

use Food\Models\User;

/**
 * @var array $config
 * @var User|null $user
 * @var \Food\Models\Restaurante|null $restaurante
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Bem-vindo ao KADOSYS Food</h2>
        </div>
        <p>
            Sua conta foi criada com sucesso. Os módulos de Produtos, Ficha Técnica,
            Estoque, Fornecedores, Compras, Caixa (PDV), Pedidos, Produção,
            Clientes, Financeiro, Precificação Inteligente e Relatórios chegam nas
            próximas atualizações - acompanhe as novidades por aqui.
        </p>
        <p>
            Por enquanto, confira sua <a href="<?= $basePath ?>/dashboard/faturas">fatura de assinatura</a>
            e mantenha seus dados de acesso em segurança.
        </p>
    </div>
</main>
