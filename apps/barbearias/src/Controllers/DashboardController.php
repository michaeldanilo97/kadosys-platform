<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Models\Barbearia;

/**
 * Painel inicial - por enquanto so um "shell" com os modulos que serao
 * construidos nas proximas etapas (Profissionais, Servicos, Clientes,
 * Agendamentos).
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $barbearia = $user !== null ? Barbearia::find($user->barbeariaId) : null;

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Barbearias',
            'user' => $user,
            'barbearia' => $barbearia,
        ], 'dashboard');
    }
}
