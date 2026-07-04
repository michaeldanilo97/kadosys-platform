<?php

/**
 * @var array $config
 * @var \Igrejas\Models\ConfiguracaoIgreja $configuracao
 * @var \Igrejas\Models\Assinatura|null $assinatura
 * @var bool $pagamentoConfigurado
 * @var bool $pixDisponivel
 * @var \Igrejas\Models\Tenant|null $tenant
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';

// Durante o teste gratis nenhum plano foi de fato contratado ainda (ver
// mesma logica em configuracoes/index.php).
$emTrial = $tenant !== null && $tenant->metodoPagamento === 'trial';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Plano contratado</h1>
        <p class="dash-page-subtitle">Consulta do plano e da assinatura da igreja.</p>
    </div>
</div>

<?= \Igrejas\Core\View::render('dashboard.configuracoes._plano_panel', [
    'configuracao' => $configuracao,
    'emTrial' => $emTrial,
    'tenant' => $tenant,
    'assinatura' => $assinatura,
    'pagamentoConfigurado' => $pagamentoConfigurado,
    'pixDisponivel' => $pixDisponivel,
    'csrf' => $csrf,
    'basePath' => $basePath,
    'permiteAlterarPlano' => false,
]) ?>
