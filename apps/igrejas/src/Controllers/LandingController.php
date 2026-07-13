<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\TenantResolver;

/**
 * Controller responsavel pela Landing Page publica do produto.
 */
final class LandingController extends Controller
{
    public function index(): void
    {
        // No subdominio de uma igreja ja provisionada (ex.:
        // igrejaabc.kadosys.com.br), a raiz "/" nao faz sentido mostrar
        // o site institucional de vendas - vai direto pro login daquela
        // igreja. So a instalacao central (kadosys.com.br) mostra a
        // landing page normalmente.
        if (TenantResolver::atual() !== null) {
            $this->redirect('/login');
        }

        echo $this->view('landing.home', [
            'pageTitle' => 'KADOSYS Igrejas - Gestão completa para sua igreja',
        ], 'landing');
    }
}
