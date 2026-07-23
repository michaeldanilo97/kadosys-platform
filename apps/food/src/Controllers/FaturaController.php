<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\MercadoPagoClient;
use Food\Models\Restaurante;
use Food\Models\FaturaRestaurante;
use Food\Models\Plano;

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
        $restaurante = $usuario !== null ? Restaurante::find($usuario->restauranteId) : null;

        if ($usuario === null || $restaurante === null) {
            $this->redirect('/login');
        }

        $cobrancasCartao = null;

        if ($restaurante->metodoPagamento === 'cartao' && $restaurante->mpPreapprovalId !== null) {
            $cobrancasCartao = $this->buscarCobrancasCartao($restaurante->mpPreapprovalId);
        }

        echo $this->view('dashboard.faturas.index', [
            'pageTitle' => 'Faturas - KADOSYS Food',
            'activeMenu' => 'faturas',
            'user' => $usuario,
            'restaurante' => $restaurante,
            'faturasPix' => FaturaRestaurante::todasDoRestaurante($restaurante->id),
            'cobrancasCartao' => $cobrancasCartao,
            'planoLabel' => Plano::label($restaurante->plano),
            'planoValor' => Plano::valorMensal($restaurante->plano),
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
