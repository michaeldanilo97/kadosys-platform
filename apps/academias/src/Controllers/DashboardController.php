<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Models\Academia;
use Academias\Models\AcademiaAviso;
use Academias\Models\AcademiaCheckin;
use Academias\Models\Aluno;
use Academias\Models\FinanceiroLancamento;
use Academias\Models\PlanoMatricula;
use Academias\Models\Professor;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $academia = $user !== null ? Academia::find($user->academiaId) : null;
        $academiaId = $academia?->id ?? 0;

        $hoje = date('Y-m-d');
        $resumoHoje = FinanceiroLancamento::resumoDoPeriodo($academiaId, $hoje, $hoje);

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Academias',
            'activeMenu' => 'painel',
            'user' => $user,
            'academia' => $academia,
            'totalAlunosAtivos' => Aluno::contarAtivos($academiaId),
            'totalAlunos' => Aluno::contar($academiaId),
            'totalProfessores' => Professor::contarAtivos($academiaId),
            'totalPlanosMatricula' => count(PlanoMatricula::ativos($academiaId)),
            'checkinsAgora' => count(AcademiaCheckin::presentesAgora($academiaId)),
            'receitaHoje' => $resumoHoje['receitas'],
            'rankingMes' => array_slice(AcademiaCheckin::rankingDoMes($academiaId, 5), 0, 5),
            'totalAlunosRisco' => count(Aluno::emRiscoDeEvasao($academiaId)),
            'avisoPlataforma' => AcademiaAviso::ativo(),
        ], 'dashboard');
    }
}
