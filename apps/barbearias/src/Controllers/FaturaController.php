<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\MercadoPagoClient;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FaturaBarbearia;
use Barbearias\Models\Plano;

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
        $barbearia = $usuario !== null ? Barbearia::find($usuario->barbeariaId) : null;

        if ($usuario === null || $barbearia === null) {
            $this->redirect('/login');
        }

        $cobrancasCartao = null;

        if ($barbearia->metodoPagamento === 'cartao' && $barbearia->mpPreapprovalId !== null) {
            $cobrancasCartao = $this->buscarCobrancasCartao($barbearia->mpPreapprovalId);
        }

        echo $this->view('dashboard.faturas.index', [
            'pageTitle' => 'Faturas - KADOSYS Barbearias',
            'activeMenu' => 'faturas',
            'user' => $usuario,
            'barbearia' => $barbearia,
            'faturasPix' => FaturaBarbearia::todasDaBarbearia($barbearia->id),
            'cobrancasCartao' => $cobrancasCartao,
            'planoLabel' => Plano::label($barbearia->plano),
            'planoValor' => Plano::valorMensal($barbearia->plano),
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
