<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Models\Plano;

/**
 * Pagina publica de vendas - unico "site institucional" do Food (nao ha
 * landing por restaurante/subdominio como no KADOSYS Igrejas, ja que
 * aqui todos os restaurantes compartilham o mesmo dominio).
 */
final class LandingController extends Controller
{
    public function index(): void
    {
        if ((new Auth($this->config))->check()) {
            $this->redirect('/dashboard');
        }

        echo $this->view('landing.home', [
            'pageTitle' => 'KADOSYS Food - Gestão completa para confeitarias e restaurantes',
            'planos' => [Plano::ESSENCIAL, Plano::PREMIUM, Plano::ENTERPRISE],
            'trialDias' => Plano::TRIAL_DIAS,
        ], 'site');
    }
}
