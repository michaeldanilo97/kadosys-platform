<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\PlanoMatricula;
use Academias\Models\Professor;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $academia = $user !== null ? Academia::find($user->academiaId) : null;
        $academiaId = $academia?->id ?? 0;

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Academias',
            'activeMenu' => 'painel',
            'user' => $user,
            'academia' => $academia,
            'totalAlunosAtivos' => Aluno::contarAtivos($academiaId),
            'totalAlunos' => Aluno::contar($academiaId),
            'totalProfessores' => Professor::contarAtivos($academiaId),
            'totalPlanosMatricula' => count(PlanoMatricula::ativos($academiaId)),
        ], 'dashboard');
    }
}
