<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Models\Academia;

/**
 * Painel inicial. Nesta Fase 1 (esqueleto + billing) ainda nao ha
 * Alunos/Professores pra resumir aqui - os cartoes de numeros entram
 * junto com o CRUD backbone (Fase 2).
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $academia = $user !== null ? Academia::find($user->academiaId) : null;

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Academias',
            'activeMenu' => 'painel',
            'user' => $user,
            'academia' => $academia,
        ], 'dashboard');
    }
}
