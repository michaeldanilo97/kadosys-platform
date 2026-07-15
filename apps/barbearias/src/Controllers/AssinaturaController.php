<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\MercadoPagoClient;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FaturaBarbearia;
use Barbearias\Models\Plano;
use Barbearias\Models\User;

/**
 * Tela de "pagar pra continuar usando" - pra onde
 * Barbearias\Core\Middleware\AuthMiddleware manda uma barbearia em
 * teste gratis vencido, com a primeira cobranca (Pix/cartao) ainda
 * aguardando confirmacao, ou com uma fatura Pix de renovacao vencida.
 * Nao deixa trocar de plano (isso ainda nao existe no Barbearias) - so
 * cobra o plano que a barbearia ja tem.
 */
final class AssinaturaController extends Controller
{
    public function index(): void
    {
        $usuario = (new Auth($this->config))->user();
        $barbearia = $usuario !== null ? Barbearia::find($usuario->barbeariaId) : null;

        if ($usuario === null || $barbearia === null) {
            $this->redirect('/login');
        }

        $fatura = FaturaBarbearia::ultimaDaBarbearia($barbearia->id);

        // Se a ultima fatura Pix expirou (ou nao existe nenhuma ainda) e
        // a barbearia usa Pix, gera uma cobranca nova na hora - poupa o
        // usuario de esperar o proximo ciclo do cron so pra pagar.
        if ($barbearia->metodoPagamento === 'pix' && ($fatura === null || $fatura->status === FaturaBarbearia::STATUS_EXPIRADA)) {
            $fatura = $this->gerarFaturaPix($barbearia, $usuario);
        }

        echo $this->view('dashboard.assinatura', [
            'pageTitle' => 'Assinatura - KADOSYS Barbearias',
            'activeMenu' => 'assinatura',
            'errors' => Session::flash('assinatura_errors') ?? [],
            'user' => $usuario,
            'barbearia' => $barbearia,
            'fatura' => ($fatura !== null && $fatura->status === FaturaBarbearia::STATUS_PENDENTE) ? $fatura : null,
        ], 'dashboard');
    }

    /**
     * Endpoint de polling (JS da tela de assinatura) - so devolve o
     * status da ultima fatura Pix, nada sensivel.
     */
    public function status(): void
    {
        $usuario = (new Auth($this->config))->user();
        $fatura = $usuario !== null ? FaturaBarbearia::ultimaDaBarbearia($usuario->barbeariaId) : null;

        $this->jsonResponse([
            'status' => $fatura?->status ?? 'desconhecido',
        ]);
    }

    /**
     * Gera uma cobranca Pix nova sob demanda (o botao "Gerar novo QR
     * Code" da tela de assinatura, pra quem deixou a anterior vencer).
     */
    public function gerarPix(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('assinatura_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/assinatura');
        }

        $usuario = (new Auth($this->config))->user();
        $barbearia = $usuario !== null ? Barbearia::find($usuario->barbeariaId) : null;

        if ($usuario === null || $barbearia === null) {
            $this->redirect('/login');
        }

        $fatura = $this->gerarFaturaPix($barbearia, $usuario);

        if ($fatura === null) {
            Session::flash('assinatura_errors', ['Não foi possível gerar a cobrança Pix agora. Tente novamente em instantes.']);
        }

        $this->redirect('/dashboard/assinatura');
    }

    /**
     * Assina (ou reassina) a cobranca recorrente por cartao (Checkout
     * Pro/preapproval) do plano que a barbearia ja tem - redireciona
     * pro Mercado Pago, nunca retorna em caso de sucesso.
     */
    public function assinarCartao(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('assinatura_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/assinatura');
        }

        $usuario = (new Auth($this->config))->user();
        $barbearia = $usuario !== null ? Barbearia::find($usuario->barbeariaId) : null;

        if ($usuario === null || $barbearia === null) {
            $this->redirect('/login');
        }

        $mp = new MercadoPagoClient();
        $mpConfig = require dirname(__DIR__, 2) . '/config/mercadopago.php';

        if (!$mp->configurado() || $mpConfig['app_url'] === '') {
            Session::flash('assinatura_errors', ['Pagamento com cartão indisponível no momento. Tente novamente mais tarde.']);
            $this->redirect('/dashboard/assinatura');
        }

        $referenciaExterna = 'kadosys-barbearias-' . $barbearia->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarAssinatura([
                'reason' => 'KADOSYS Barbearias - Plano ' . Plano::label($barbearia->plano),
                'amount' => Plano::valorMensal($barbearia->plano),
                'payerEmail' => $usuario->email,
                'backUrl' => $mpConfig['app_url'] . $this->url('/dashboard/assinatura'),
                'externalReference' => $referenciaExterna,
            ]);
        } catch (\RuntimeException) {
            Session::flash('assinatura_errors', ['Não foi possível iniciar o pagamento agora. Tente novamente em instantes.']);
            $this->redirect('/dashboard/assinatura');
        }

        if ($resposta['status'] >= 300 || !isset($resposta['body']['init_point'], $resposta['body']['id'])) {
            Session::flash('assinatura_errors', ['O Mercado Pago recusou a assinatura. Tente novamente.']);
            $this->redirect('/dashboard/assinatura');
        }

        Barbearia::atualizarMpPreapprovalId($barbearia->id, (string) $resposta['body']['id']);

        header('Location: ' . $resposta['body']['init_point']);
        exit;
    }

    private function gerarFaturaPix(Barbearia $barbearia, User $usuario): ?FaturaBarbearia
    {
        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            return null;
        }

        $vencimento = new \DateTimeImmutable('+3 days');
        $referenciaExterna = 'kadosys-barbearias-' . $barbearia->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Barbearias - Plano ' . Plano::label($barbearia->plano),
                'amount' => Plano::valorMensal($barbearia->plano),
                'payerEmail' => $usuario->email,
                'externalReference' => $referenciaExterna,
                'expiraEm' => $vencimento,
            ]);
        } catch (\RuntimeException) {
            return null;
        }

        $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
        $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

        if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
            return null;
        }

        $faturaId = FaturaBarbearia::criar(
            $barbearia->id,
            $barbearia->plano,
            FaturaBarbearia::TIPO_RENOVACAO,
            Plano::valorMensal($barbearia->plano),
            (string) $resposta['body']['id'],
            $qrCode,
            $qrCodeBase64 !== null ? (string) $qrCodeBase64 : null,
            $vencimento,
        );

        return FaturaBarbearia::find($faturaId);
    }
}
