<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\User;

/**
 * Galeria da equipe (estilo rede social): nome, foto e cargo/instrumento
 * de cada usuario com acesso ao sistema - ver User::CARGOS/INSTRUMENTOS
 * e PerfilController (onde cada um edita o proprio cargo/foto).
 */
final class EquipeController extends Controller
{
    public function index(): void
    {
        echo $this->view('dashboard.equipe.index', [
            'pageTitle' => 'Equipe - KADOSYS Igrejas',
            'activeMenu' => 'equipe',
            'breadcrumb' => ['Dashboard', 'Equipe'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membrosEquipe' => User::todosAtivosParaEquipe(),
            'logoIgreja' => ConfiguracaoIgreja::atual()->logoPath,
        ], 'dashboard');
    }
}
