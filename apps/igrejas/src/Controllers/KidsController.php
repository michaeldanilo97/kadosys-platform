<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Models\KidsCheckin;
use Igrejas\Models\KidsConteudo;
use Igrejas\Models\KidsCrianca;
use Igrejas\Models\KidsTurma;

/**
 * Controller da página inicial do módulo KADOSYS Kids: painel com os
 * atalhos pras 3 telas operacionais da Fase 1 (Turmas, Crianças,
 * Check-in) e um resumo do dia. As telas do modo "criança" (histórias,
 * jogos, avatares etc, ver o brief completo do módulo) ficam para as
 * próximas fases - aqui é o lado da equipe/professores.
 */
final class KidsController extends Controller
{
    public function index(): void
    {
        echo $this->view('dashboard.kids.index', [
            'pageTitle' => 'KADOSYS Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'turmasAtivas' => KidsTurma::countAtivas(),
            'criancasAtivas' => KidsCrianca::countAtivas(),
            'checkinsHoje' => KidsCheckin::totalHoje(),
            'presentesAgora' => count(KidsCheckin::abertosHoje()),
            'conteudosPublicados' => KidsConteudo::countPublicados(),
        ], 'dashboard');
    }
}
