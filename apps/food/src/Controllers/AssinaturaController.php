<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\MercadoPagoClient;
use Food\Core\Session;
use Food\Models\Restaurante;
use Food\Models\FaturaRestaurante;
use Food\Models\Plano;
use Food\Models\User;

/**
 * Tela de "pagar pra continuar usando" - pra onde
 * Food\Core\Middleware\AuthMiddleware manda um restaurante em
 * teste gratis vencido, com a primeira cobranca (Pix/cartao) ainda
 * aguardando confirmacao, ou com uma fatura Pix de renovacao vencida.
 * Nao deixa trocar de plano (isso ainda nao existe no Food) - so
 * cobra o plano que o restaurante ja tem.
 */
final class AssinaturaController extends Controller
{
    public function index(): void
    {
        $usuario = (new Auth($this->config))->user();
        $restaurante = $usuario !== null ? Restaurante::find($usuario->restauranteId) : null;

        if ($usuario === null || $restaurante === null) {
            $this->redirect('/login');
        }

        $fatura = FaturaRestaurante::ultimaDoRestaurante($restaurante->id);

        // Se a ultima fatura Pix expirou (ou nao existe nenhuma ainda) e
        // o restaurante usa Pix, gera uma cobranca nova na hora - poupa o
        // usuario de esperar o proximo ciclo do cron so pra pagar.
        if ($restaurante->metodoPagamento === 'pix' && ($fatura === null || $fatura->status === FaturaRestaurante::STATUS_EXPIRADA)) {
            $fatura = $this->gerarFaturaPix($restaurante, $usuario);
        }

        echo $this->view('dashboard.assinatura', [
            'pageTitle' => 'Assinatura - KADOSYS Food',
            'activeMenu' => 'assinatura',
            'errors' => Session::flash('assinatura_errors') ?? [],
            'user' => $usuario,
            'restaurante' => $restaurante,
            'fatura' => ($fatura !== null && $fatura->status === FaturaRestaurante::STATUS_PENDENTE) ? $fatura : null,
        ], 'dashboard');
    }

    /**
     * Endpoint de polling (JS da tela de assinatura) - so devolve o
     * status da ultima fatura Pix, nada sensivel.
     */
    public function status(): void
    {
        $usuario = (new Auth($this->config))->user();
        $fatura = $usuario !== null ? FaturaRestaurante::ultimaDoRestaurante($usuario->restauranteId) : null;

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
        $restaurante = $usuario !== null ? Restaurante::find($usuario->restauranteId) : null;

        if ($usuario === null || $restaurante === null) {
            $this->redirect('/login');
        }

        $fatura = $this->gerarFaturaPix($restaurante, $usuario);

        if ($fatura === null) {
            Session::flash('assinatura_errors', ['Não foi possível gerar a cobrança Pix agora. Tente novamente em instantes.']);
        }

        $this->redirect('/dashboard/assinatura');
    }

    /**
     * Assina (ou reassina) a cobranca recorrente por cartao (Checkout
     * Pro/preapproval) do plano que o restaurante ja tem - redireciona
     * pro Mercado Pago, nunca retorna em caso de sucesso.
     */
    public function assinarCartao(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('assinatura_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/assinatura');
        }

        $usuario = (new Auth($this->config))->user();
        $restaurante = $usuario !== null ? Restaurante::find($usuario->restauranteId) : null;

        if ($usuario === null || $restaurante === null) {
            $this->redirect('/login');
        }

        $mp = new MercadoPagoClient();
        $mpConfig = require dirname(__DIR__, 2) . '/config/mercadopago.php';

        if (!$mp->configurado() || $mpConfig['app_url'] === '') {
            Session::flash('assinatura_errors', ['Pagamento com cartão indisponível no momento. Tente novamente mais tarde.']);
            $this->redirect('/dashboard/assinatura');
        }

        $referenciaExterna = 'kadosys-food-' . $restaurante->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarAssinatura([
                'reason' => 'KADOSYS Food - Plano ' . Plano::label($restaurante->plano),
                'amount' => Plano::valorMensal($restaurante->plano),
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

        Restaurante::atualizarMpPreapprovalId($restaurante->id, (string) $resposta['body']['id']);

        header('Location: ' . $resposta['body']['init_point']);
        exit;
    }

    private function gerarFaturaPix(Restaurante $restaurante, User $usuario): ?FaturaRestaurante
    {
        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            return null;
        }

        $vencimento = new \DateTimeImmutable('+3 days');
        $referenciaExterna = 'kadosys-food-' . $restaurante->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Food - Plano ' . Plano::label($restaurante->plano),
                'amount' => Plano::valorMensal($restaurante->plano),
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

        $faturaId = FaturaRestaurante::criar(
            $restaurante->id,
            $restaurante->plano,
            FaturaRestaurante::TIPO_RENOVACAO,
            Plano::valorMensal($restaurante->plano),
            (string) $resposta['body']['id'],
            $qrCode,
            $qrCodeBase64 !== null ? (string) $qrCodeBase64 : null,
            $vencimento,
        );

        return FaturaRestaurante::find($faturaId);
    }
}
