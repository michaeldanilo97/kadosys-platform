<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Session;
use Food\Models\ContaPagar;
use Food\Models\ContaReceber;
use Food\Models\FinanceiroLancamento;
use Food\Models\Restaurante;
use Food\Models\User;

final class FinanceiroController extends Controller
{
    private const POR_PAGINA = 20;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $tipo = (string) $this->request->input('tipo', '');

        $hoje = new \DateTimeImmutable();
        $resumoDia = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $hoje->format('Y-m-d'), $hoje->format('Y-m-d'));
        $resumoMes = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $hoje->format('Y-m-01'), $hoje->format('Y-m-t'));

        $resultado = FinanceiroLancamento::paginate($restauranteId, $page, self::POR_PAGINA, $tipo);

        echo $this->view('dashboard.financeiro.index', [
            'pageTitle' => 'Financeiro - KADOSYS Food',
            'activeMenu' => 'financeiro',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'resumoDia' => $resumoDia,
            'resumoMes' => $resumoMes,
            'totalContasPagarVencidas' => ContaPagar::totalVencidas($restauranteId),
            'totalContasReceberVencidas' => ContaReceber::totalVencidas($restauranteId),
            'proximasContasPagar' => ContaPagar::proximasPendentes($restauranteId, 5),
            'proximasContasReceber' => ContaReceber::proximasPendentes($restauranteId, 5),
            'lancamentos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'tipo' => $tipo,
            'success' => Session::flash('financeiro_success'),
            'errors' => Session::flash('financeiro_errors') ?? [],
        ], 'dashboard');
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
