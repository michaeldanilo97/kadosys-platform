<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Models\Restaurante;

/**
 * Painel principal - nesta Fase 1 (esqueleto + billing) ainda so mostra
 * as boas-vindas, ja que Produtos/Ficha Tecnica/Estoque/Pedidos/PDV
 * entram nas fases seguintes. Os KPIs em tempo real do spec original
 * (receita, pedidos, estoque baixo etc) sao adicionados aqui conforme
 * cada modulo correspondente for construido.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $restaurante = $user !== null ? Restaurante::find($user->restauranteId) : null;

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Food',
            'activeMenu' => 'painel',
            'user' => $user,
            'restaurante' => $restaurante,
        ], 'dashboard');
    }
}
