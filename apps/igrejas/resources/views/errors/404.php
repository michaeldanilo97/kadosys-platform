<?php
/**
 * Fragmento de erro 404 para uso dentro do layout do dashboard, quando o
 * usuario autenticado tenta acessar um slug de modulo que nao existe em
 * DashboardController::modules().
 */
?>

<h1 class="dash-page-title">Pagina nao encontrada</h1>
<p class="dash-page-subtitle">O modulo solicitado nao existe ou ainda nao foi criado.</p>

<div class="placeholder-box">
    <div class="icon"><i class="bi bi-question-circle"></i></div>
    <h2>404</h2>
    <p>Verifique o link acessado ou utilize o menu lateral para navegar entre os modulos disponiveis.</p>
</div>
