<?php
/**
 * Fragmento de erro 404 para uso dentro do layout do dashboard, quando o
 * usuario autenticado tenta acessar um slug de modulo que nao existe em
 * DashboardController::modules().
 */
?>

<h1 class="dash-page-title">Página não encontrada</h1>
<p class="dash-page-subtitle">O módulo solicitado não existe ou ainda não foi criado.</p>

<div class="placeholder-box">
    <div class="icon"><i class="bi bi-question-circle"></i></div>
    <h2>404</h2>
    <p>Verifique o link acessado ou utilize o menu lateral para navegar entre os módulos disponíveis.</p>
</div>
