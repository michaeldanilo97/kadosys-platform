<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\MercadoPagoClient;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\FaturaAcademia;
use Academias\Models\Plano;
use Academias\Models\User;

/**
 * Tela de "pagar pra continuar usando" - pra onde
 * Academias\Core\Middleware\AuthMiddleware manda uma academia em
 * teste gratis vencido, com a primeira cobranca (Pix/cartao) ainda
 * aguardando confirmacao, ou com uma fatura Pix de renovacao vencida.
 * Nao deixa trocar de plano (isso ainda nao existe no Academias) - so
 * cobra o plano que a academia ja tem.
 */
final class AssinaturaController extends Controller
{
    public function index(): void
    {
        $usuario = (new Auth($this->config))->user();
        $academia = $usuario !== null ? Academia::find($usuario->academiaId) : null;

        if ($usuario === null || $academia === null) {
            $this->redirect('/login');
        }

        $fatura = FaturaAcademia::ultimaDaAcademia($academia->id);

        // Se a ultima fatura Pix expirou (ou nao existe nenhuma ainda) e
        // a academia usa Pix, gera uma cobranca nova na hora - poupa o
        // usuario de esperar o proximo ciclo do cron so pra pagar.
        if ($academia->metodoPagamento === 'pix' && ($fatura === null || $fatura->status === FaturaAcademia::STATUS_EXPIRADA)) {
            $fatura = $this->gerarFaturaPix($academia, $usuario);
        }

        echo $this->view('dashboard.assinatura', [
            'pageTitle' => 'Assinatura - KADOSYS Academias',
            'activeMenu' => 'assinatura',
            'errors' => Session::flash('assinatura_errors') ?? [],
            'user' => $usuario,
            'academia' => $academia,
            'fatura' => ($fatura !== null && $fatura->status === FaturaAcademia::STATUS_PENDENTE) ? $fatura : null,
        ], 'dashboard');
    }

    /**
     * Endpoint de polling (JS da tela de assinatura) - so devolve o
     * status da ultima fatura Pix, nada sensivel.
     */
    public function status(): void
    {
        $usuario = (new Auth($this->config))->user();
        $fatura = $usuario !== null ? FaturaAcademia::ultimaDaAcademia($usuario->academiaId) : null;

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
        $academia = $usuario !== null ? Academia::find($usuario->academiaId) : null;

        if ($usuario === null || $academia === null) {
            $this->redirect('/login');
        }

        $fatura = $this->gerarFaturaPix($academia, $usuario);

        if ($fatura === null) {
            Session::flash('assinatura_errors', ['Não foi possível gerar a cobrança Pix agora. Tente novamente em instantes.']);
        }

        $this->redirect('/dashboard/assinatura');
    }

    /**
     * Assina (ou reassina) a cobranca recorrente por cartao (Checkout
     * Pro/preapproval) do plano que a academia ja tem - redireciona
     * pro Mercado Pago, nunca retorna em caso de sucesso.
     */
    public function assinarCartao(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('assinatura_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/assinatura');
        }

        $usuario = (new Auth($this->config))->user();
        $academia = $usuario !== null ? Academia::find($usuario->academiaId) : null;

        if ($usuario === null || $academia === null) {
            $this->redirect('/login');
        }

        $mp = new MercadoPagoClient();
        $mpConfig = require dirname(__DIR__, 2) . '/config/mercadopago.php';

        if (!$mp->configurado() || $mpConfig['app_url'] === '') {
            Session::flash('assinatura_errors', ['Pagamento com cartão indisponível no momento. Tente novamente mais tarde.']);
            $this->redirect('/dashboard/assinatura');
        }

        $referenciaExterna = 'kadosys-academias-' . $academia->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarAssinatura([
                'reason' => 'KADOSYS Academias - Plano ' . Plano::label($academia->plano),
                'amount' => Plano::valorMensal($academia->plano),
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

        Academia::atualizarMpPreapprovalId($academia->id, (string) $resposta['body']['id']);

        header('Location: ' . $resposta['body']['init_point']);
        exit;
    }

    private function gerarFaturaPix(Academia $academia, User $usuario): ?FaturaAcademia
    {
        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            return null;
        }

        $vencimento = new \DateTimeImmutable('+3 days');
        $referenciaExterna = 'kadosys-academias-' . $academia->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Academias - Plano ' . Plano::label($academia->plano),
                'amount' => Plano::valorMensal($academia->plano),
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

        $faturaId = FaturaAcademia::criar(
            $academia->id,
            $academia->plano,
            FaturaAcademia::TIPO_RENOVACAO,
            Plano::valorMensal($academia->plano),
            (string) $resposta['body']['id'],
            $qrCode,
            $qrCodeBase64 !== null ? (string) $qrCodeBase64 : null,
            $vencimento,
        );

        return FaturaAcademia::find($faturaId);
    }
}
