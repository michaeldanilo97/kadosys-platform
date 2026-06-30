<?php
/**
 * View: dashboard.index
 *
 * Pagina inicial do painel administrativo. Apenas estrutura visual,
 * sem funcionalidades de negocio, conforme especificacao da v1.
 */

$title = 'Dashboard';
$breadcrumb = [
    ['label' => 'Dashboard'],
];

ob_start();
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary mb-1 small">Membros</p>
                        <h3 class="mb-0">0</h3>
                    </div>
                    <span class="badge bg-primary-subtle text-primary-emphasis rounded-circle p-2">
                        <i class="bi bi-people fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary mb-1 small">Eventos</p>
                        <h3 class="mb-0">0</h3>
                    </div>
                    <span class="badge bg-success-subtle text-success-emphasis rounded-circle p-2">
                        <i class="bi bi-calendar-event fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary mb-1 small">Financeiro</p>
                        <h3 class="mb-0">R$ 0,00</h3>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-circle p-2">
                        <i class="bi bi-cash-coin fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary mb-1 small">Aplicativo ativo</p>
                        <h3 class="mb-0 text-capitalize"><?= htmlspecialchars(config('app.active_app')) ?></h3>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-circle p-2">
                        <i class="bi bi-grid-1x2 fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h5 class="card-title mb-3">Bem-vindo a Kadosys Platform</h5>
        <p class="text-secondary mb-0">
            Esta e a infraestrutura base do painel administrativo. Os modulos de
            negocio (Membros, Eventos, Financeiro, Escalas, Biblia, Louvores, etc)
            serao implementados em versoes futuras, utilizando exatamente este
            mesmo Core compartilhado.
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';
