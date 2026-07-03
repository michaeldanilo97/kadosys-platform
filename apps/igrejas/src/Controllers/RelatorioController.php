<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Models\Culto;
use Igrejas\Models\FinanceiroLancamento;
use Igrejas\Models\Grupo;
use Igrejas\Models\Membro;
use Igrejas\Models\Ministerio;
use Igrejas\Models\PatrimonioBem;

/**
 * Controller do modulo Relatorios: indicadores e relatorios
 * consolidados da igreja, agregando dados dos demais modulos por mes.
 * Somente leitura - nao ha cadastro proprio, exclusivo do plano
 * Premium (ver Plano::MODULO_MINIMO).
 */
final class RelatorioController extends Controller
{
    public function index(): void
    {
        $mes = (string) $this->request->input('mes', date('Y-m'));

        if (preg_match('/^\d{4}-\d{2}$/', $mes) !== 1) {
            $mes = date('Y-m');
        }

        $inicioDoMes = new \DateTimeImmutable($mes . '-01 00:00:00');

        echo $this->view('dashboard.relatorios.index', [
            'pageTitle' => 'Relatorios - KADOSYS Igrejas',
            'activeMenu' => 'relatorios',
            'breadcrumb' => ['Dashboard', 'Relatorios'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'mes' => $mes,
            'membrosAtivos' => Membro::countActive(),
            'novosMembrosNoMes' => Membro::countCreatedSince($inicioDoMes) - Membro::countCreatedSince($inicioDoMes->modify('+1 month')),
            'ministeriosAtivos' => Ministerio::countActive(),
            'gruposAtivos' => Grupo::countActive(),
            'cultosDoMes' => Culto::noMes($mes),
            'mediaPresencas' => Culto::mediaPresencasNoMes($mes),
            'financeiroTotais' => FinanceiroLancamento::totais(['mes' => $mes]),
            'financeiroPorCategoria' => FinanceiroLancamento::totaisPorCategoria($mes),
            'patrimonioValorTotal' => PatrimonioBem::valorTotalAtivo(),
            'patrimonioAtivos' => PatrimonioBem::countAtivos(),
        ], 'dashboard');
    }
}
