<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Models\Ingrediente;
use Food\Models\Relatorio;
use Food\Models\Restaurante;
use Food\Models\User;

/**
 * Relatorios completos (DRE, produtos mais vendidos/lucrativos,
 * clientes, estoque, fluxo de caixa) - todos os numeros vem de
 * Food\Models\Relatorio, o mesmo motor de agregacao usado no painel
 * principal, so que aqui com um seletor de periodo (mes/ano) livre em
 * vez de sempre "o mes atual".
 */
final class RelatorioController extends Controller
{
    public function index(): void
    {
        $restauranteId = $this->restauranteId();

        [$ano, $mes] = $this->periodoSelecionado();
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = (new \DateTimeImmutable($inicio))->format('Y-m-t');

        $resumo = Relatorio::resumoPeriodo($restauranteId, $inicio, $fim);
        $vendas = Relatorio::vendasPeriodo($restauranteId, $inicio, $fim);
        $comissaoIfood = Relatorio::comissaoIfoodPeriodo($restauranteId, $inicio, $fim);

        echo $this->view('dashboard.relatorios.index', [
            'pageTitle' => 'Relatórios - KADOSYS Food',
            'activeMenu' => 'relatorios',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'ano' => $ano,
            'mes' => $mes,
            'resumo' => $resumo,
            'vendas' => $vendas,
            'comissaoIfood' => $comissaoIfood,
            'produtosMaisVendidos' => Relatorio::produtosMaisVendidos($restauranteId, $inicio, $fim, 10),
            'produtosMaisLucrativos' => Relatorio::produtosMaisLucrativos($restauranteId, $inicio, $fim, 10),
            'clientesAtivos' => Relatorio::clientesAtivos($restauranteId),
            'clientesNovos' => Relatorio::clientesNovos($restauranteId, $inicio, $fim),
            'ingredientesEstoqueBaixo' => Ingrediente::comEstoqueBaixo($restauranteId),
            'fluxoCaixaMensal' => Relatorio::fluxoCaixaMensal($restauranteId, 6),
        ], 'dashboard');
    }

    /** @return array{0: int, 1: int} */
    private function periodoSelecionado(): array
    {
        $hoje = new \DateTimeImmutable('today');

        $ano = (int) $this->request->input('ano', $hoje->format('Y'));
        $mes = (int) $this->request->input('mes', $hoje->format('n'));

        if ($mes < 1 || $mes > 12) {
            $mes = (int) $hoje->format('n');
        }

        if ($ano < 2020 || $ano > 2100) {
            $ano = (int) $hoje->format('Y');
        }

        return [$ano, $mes];
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function restauranteId(): int
    {
        return $this->usuario()?->restauranteId ?? 0;
    }
}
