<?php

declare(strict_types=1);

namespace Kadosys\Apps\Igrejas\Controllers;

use Kadosys\Core\Http\Controller;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;

/**
 * DashboardController
 *
 * Controller responsavel pelo painel administrativo principal
 * do aplicativo "igrejas". Apenas estrutura visual nesta v1,
 * sem regras de negocio.
 */
final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('dashboard.index', [
            'authUserName' => $this->auth()->user()['name'] ?? 'Usuario',
        ]);
    }
}
