<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Models\Plano;

/**
 * Pagina publica de vendas - unico "site institucional" do Academias
 * (nao ha landing por igreja/subdominio como no KADOSYS Igrejas, ja
 * que aqui todas as academias compartilham o mesmo dominio).
 */
final class LandingController extends Controller
{
    public function index(): void
    {
        if ((new Auth($this->config))->check()) {
            $this->redirect('/dashboard');
        }

        echo $this->view('landing.home', [
            'pageTitle' => 'KADOSYS Academias - Gestão completa para sua academia',
            'planos' => [Plano::ESSENCIAL, Plano::PREMIUM, Plano::ENTERPRISE],
            'trialDias' => Plano::TRIAL_DIAS,
        ], 'site');
    }
}
