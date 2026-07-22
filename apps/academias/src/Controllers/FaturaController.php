<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\MercadoPagoClient;
use Academias\Models\Academia;
use Academias\Models\FaturaAcademia;
use Academias\Models\Plano;

/**
 * Historico de cobranca - so leitura, sem create/update/delete. Cartao
 * nao guarda cobranca no nosso banco (o Mercado Pago debita sozinho a
 * cada ciclo, sem passar pelo nosso webhook de novo depois da primeira
 * autorizacao), entao o extrato de cartao e buscado ao vivo na API a
 * cada carregamento da pagina - por isso essa tela NAO fica atras do
 * bloqueio de trial/fatura vencida (ver rotas), senao quem esta
 * bloqueado nem conseguiria ver o motivo/pagar de novo.
 */
final class FaturaController extends Controller
{
    public function index(): void
    {
        $usuario = (new Auth($this->config))->user();
        $academia = $usuario !== null ? Academia::find($usuario->academiaId) : null;

        if ($usuario === null || $academia === null) {
            $this->redirect('/login');
        }

        $cobrancasCartao = null;

        if ($academia->metodoPagamento === 'cartao' && $academia->mpPreapprovalId !== null) {
            $cobrancasCartao = $this->buscarCobrancasCartao($academia->mpPreapprovalId);
        }

        echo $this->view('dashboard.faturas.index', [
            'pageTitle' => 'Faturas - KADOSYS Academias',
            'activeMenu' => 'faturas',
            'user' => $usuario,
            'academia' => $academia,
            'faturasPix' => FaturaAcademia::todasDaAcademia($academia->id),
            'cobrancasCartao' => $cobrancasCartao,
            'planoLabel' => Plano::label($academia->plano),
            'planoValor' => Plano::valorMensal($academia->plano),
        ], 'dashboard');
    }

    /** @return array<int, array>|null */
    private function buscarCobrancasCartao(string $preapprovalId): ?array
    {
        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            return null;
        }

        try {
            $resposta = $mp->buscarPagamentosAssinatura($preapprovalId);
        } catch (\RuntimeException) {
            return null;
        }

        if ($resposta['status'] !== 200) {
            return null;
        }

        return $resposta['body']['results'] ?? [];
    }
}
