<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Models\ContaPagar;
use Food\Models\ContaReceber;
use Food\Models\FinanceiroLancamento;
use Food\Models\Ingrediente;
use Food\Models\Relatorio;
use Food\Models\Restaurante;

/**
 * Painel principal - Fase 8 traz os KPIs em tempo real do spec
 * original (receita por periodo, lucro bruto/liquido, pedidos por
 * status, ticket medio, estoque baixo, contas vencidas, clientes) +
 * o grafico de fluxo de caixa dos ultimos 6 meses (Chart.js
 * vendorizado). Toda a agregacao pesada fica em Food\Models\Relatorio,
 * reaproveitada tambem pela tela de Relatorios.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $restauranteId = $user?->restauranteId ?? 0;
        $hoje = new \DateTimeImmutable('today');

        $inicioSemana = $hoje->modify('monday this week');
        $inicioMes = $hoje->modify('first day of this month');
        $inicioAno = $hoje->modify('first day of january this year');

        $receitaHoje = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $hoje->format('Y-m-d'), $hoje->format('Y-m-d'))['receitas'];
        $receitaSemana = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $inicioSemana->format('Y-m-d'), $hoje->format('Y-m-d'))['receitas'];
        $receitaAno = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $inicioAno->format('Y-m-d'), $hoje->format('Y-m-d'))['receitas'];

        $resumoMes = Relatorio::resumoPeriodo($restauranteId, $inicioMes->format('Y-m-d'), $hoje->format('Y-m-d'));
        $vendasMes = Relatorio::vendasPeriodo($restauranteId, $inicioMes->format('Y-m-d'), $hoje->format('Y-m-d'));
        $comissaoIfoodMes = Relatorio::comissaoIfoodPeriodo($restauranteId, $inicioMes->format('Y-m-d'), $hoje->format('Y-m-d'));

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Painel - KADOSYS Food',
            'activeMenu' => 'painel',
            'user' => $user,
            'restaurante' => Restaurante::find($restauranteId),
            'receitaHoje' => $receitaHoje,
            'receitaSemana' => $receitaSemana,
            'receitaMes' => $resumoMes['receita'],
            'receitaAno' => $receitaAno,
            'resumoMes' => $resumoMes,
            'vendasMes' => $vendasMes,
            'comissaoIfoodMes' => $comissaoIfoodMes,
            'pedidosPorStatus' => Relatorio::pedidosPorStatus($restauranteId),
            'ingredientesEstoqueBaixo' => Ingrediente::comEstoqueBaixo($restauranteId),
            'totalContasPagarVencidas' => ContaPagar::totalVencidas($restauranteId),
            'totalContasReceberVencidas' => ContaReceber::totalVencidas($restauranteId),
            'clientesAtivos' => Relatorio::clientesAtivos($restauranteId),
            'clientesNovosMes' => Relatorio::clientesNovos($restauranteId, $inicioMes->format('Y-m-d'), $hoje->format('Y-m-d')),
            'produtosMaisVendidosMes' => Relatorio::produtosMaisVendidos($restauranteId, $inicioMes->format('Y-m-d'), $hoje->format('Y-m-d'), 5),
            'fluxoCaixaMensal' => Relatorio::fluxoCaixaMensal($restauranteId, 6),
        ], 'dashboard');
    }
}
