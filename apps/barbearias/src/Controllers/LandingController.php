<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Models\Plano;

/**
 * Pagina publica de vendas - unico "site institucional" do Barbearias
 * (nao ha landing por igreja/subdominio como no KADOSYS Igrejas, ja
 * que aqui todas as barbearias compartilham o mesmo dominio).
 */
final class LandingController extends Controller
{
    public function index(): void
    {
        if ((new Auth($this->config))->check()) {
            $this->redirect('/dashboard');
        }

        echo $this->view('landing.home', [
            'pageTitle' => 'KADOSYS Barbearias - Gestão completa para sua barbearia',
            'planos' => [Plano::ESSENCIAL, Plano::PREMIUM, Plano::ENTERPRISE],
            'trialDias' => Plano::TRIAL_DIAS,
        ], 'site');
    }
}
