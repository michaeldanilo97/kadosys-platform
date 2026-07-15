<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;

/**
 * Painel inicial - numeros rapidos da barbearia e atalhos pros modulos.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $barbearia = $user !== null ? Barbearia::find($user->barbeariaId) : null;

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Barbearias',
            'activeMenu' => 'painel',
            'user' => $user,
            'barbearia' => $barbearia,
            'totalProfissionais' => $barbearia !== null ? Profissional::contarAtivos($barbearia->id) : 0,
            'totalServicos' => $barbearia !== null ? Servico::contarAtivos($barbearia->id) : 0,
            'totalClientes' => $barbearia !== null ? Cliente::contar($barbearia->id) : 0,
            'agendamentosHoje' => $barbearia !== null ? Agendamento::contarDoDia($barbearia->id, new \DateTimeImmutable()) : 0,
            'proximosAgendamentos' => $barbearia !== null ? Agendamento::proximos($barbearia->id, 5) : [],
        ], 'dashboard');
    }
}
