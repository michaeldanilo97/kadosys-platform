<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;

/**
 * Controller responsavel pela Landing Page publica do produto.
 */
final class LandingController extends Controller
{
    public function index(): void
    {
        echo $this->view('landing.home', [
            'pageTitle' => 'KADOSYS Igrejas - Gestao completa para sua igreja',
        ], 'landing');
    }
}
