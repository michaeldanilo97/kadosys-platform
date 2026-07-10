<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\CpanelUapiClient;
use Igrejas\Core\Csrf;
use Igrejas\Core\MercadoPagoClient;
use Igrejas\Core\Provisionador;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Assinatura;
use Igrejas\Models\AssinaturaTenant;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\FaturaPix;
use Igrejas\Models\Plano;
use Igrejas\Models\Provisionamento;
use Igrejas\Models\Tenant;

/**
 * Assinatura recorrente do plano contratado via Mercado Pago (Checkout
 * Pro + Assinaturas/preapproval). O plano so muda de fato quando o
 * webhook confirma o pagamento (ver metodo webhook()) - o retorno do
 * checkout no navegador (metodo retorno()) e so uma tela de espera,
 * nunca a fonte de verdade.
 */
final class AssinaturaController extends Controller
{
    public function iniciar(string $plano): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('config_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if (!isset(Plano::VALOR_MENSAL[$plano])) {
            Session::flash('config_errors', ['Esse plano nao tem assinatura automatica disponivel.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            Session::flash('config_errors', ['Pagamento online ainda nao foi configurado neste servidor. Fale com o suporte.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $mpConfig = require dirname(__DIR__, 2) . '/config/mercadopago.php';

        if ($mpConfig['app_url'] === '') {
            Session::flash('config_errors', ['A variavel de ambiente APP_URL nao foi configurada no servidor (necessaria para o retorno do pagamento).']);
            $this->redirect('/dashboard/configuracoes');
        }

        $usuario = (new Auth($this->config))->user();
        $metodoPagamento = (string) $this->request->input('metodo_pagamento', 'cartao');
        $tenantAtual = TenantResolver::atual();

        // Upgrade/downgrade proporcional ao ciclo ja pago - so faz
        // sentido pra quem ja tem um plano pago em andamento (nao
        // trial, que e gratis, e nao a primeira assinatura, que nao tem
        // ciclo anterior pra aproveitar). Fora isso, cai no fluxo de
        // sempre logo abaixo (cobranca cheia, assinatura nova).
        if (
            $tenantAtual !== null
            && $tenantAtual->metodoPagamento !== 'trial'
            && $tenantAtual->proximoVencimento !== null
            && $plano !== $tenantAtual->plano
            && isset(Plano::VALOR_MENSAL[$tenantAtual->plano])
        ) {
            $vencimentoCiclo = new \DateTimeImmutable($tenantAtual->proximoVencimento);
            $hoje = new \DateTimeImmutable('today');
            $diasRestantes = $hoje < $vencimentoCiclo ? $hoje->diff($vencimentoCiclo)->days : 0;

            if ($diasRestantes > 0) {
                $valorAtual = Plano::VALOR_MENSAL[$tenantAtual->plano];
                $valorNovo = Plano::VALOR_MENSAL[$plano];

                if ($valorNovo < $valorAtual) {
                    $this->agendarDowngrade($tenantAtual, $plano, $vencimentoCiclo);
                }

                if ($valorNovo > $valorAtual) {
                    $valorProporcional = round(($valorNovo - $valorAtual) * $diasRestantes / 30, 2);

                    $this->iniciarUpgradeProporcional($mp, $tenantAtual, $plano, $valorProporcional, $usuario?->email ?? '');
                }
            }
        }

        if ($metodoPagamento === 'pix') {
            $this->iniciarPix($mp, $plano, $usuario?->email ?? '');
        }

        $referenciaExterna = 'kadosys-' . $plano . '-' . bin2hex(random_bytes(6));

        try {
            $resposta = $mp->criarAssinatura([
                'reason' => 'KADOSYS Igrejas - Plano ' . Plano::label($plano),
                'amount' => Plano::VALOR_MENSAL[$plano],
                'payerEmail' => $usuario?->email ?? '',
                'backUrl' => $mpConfig['app_url'] . $this->url('/dashboard/assinatura/retorno'),
                'externalReference' => $referenciaExterna,
            ]);
        } catch (\RuntimeException $exception) {
            Assinatura::registrarEvento(null, 'erro_criar_assinatura', $exception->getMessage());
            Session::flash('config_errors', ['Nao foi possivel iniciar o pagamento agora. Tente novamente em instantes.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($resposta['status'] >= 300 || !isset($resposta['body']['init_point'], $resposta['body']['id'])) {
            Assinatura::registrarEvento(null, 'erro_criar_assinatura', json_encode($resposta, JSON_UNESCAPED_UNICODE));
            Session::flash('config_errors', ['O Mercado Pago recusou a criacao da assinatura. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $assinaturaId = Assinatura::criar($plano, (string) $resposta['body']['id'], $usuario?->email ?? '');
        Assinatura::registrarEvento($assinaturaId, 'assinatura_criada', json_encode($resposta['body'], JSON_UNESCAPED_UNICODE));

        if ($tenantAtual !== null) {
            // O registro acima (Assinatura::criar) fica gravado no banco
            // ISOLADO deste tenant - o webhook do Mercado Pago roda
            // sempre na instalacao central e nunca enxergaria esse
            // registro sozinho. AssinaturaTenant e a ponte central que
            // permite ao webhook achar de qual tenant e essa assinatura
            // e aplicar a troca de plano no banco certo (ver
            // AssinaturaController::webhook()/atualizarPlanoNoBancoDoTenant()).
            AssinaturaTenant::registrar($tenantAtual->id, $plano, (string) $resposta['body']['id']);

            // So marca o metodo de pagamento AQUI (antes do webhook
            // confirmar) quando o trial ja estava vencido e bloqueando o
            // acesso - o objetivo e liberar quem ja estava bloqueado assim
            // que ela parte pra um pagamento de verdade, sem esperar o
            // webhook. Com trial ainda valido, a troca so e efetivada
            // quando o pagamento realmente e confirmado (ver
            // processarAssinaturaTenant()) - do contrario, um clique em
            // "Assinar" so pra abrir a tela do Mercado Pago (sem nunca
            // completar o pagamento) ja derrubava o trial em andamento.
            if ($this->trialVencido($tenantAtual)) {
                Tenant::atualizarMetodoPagamento($tenantAtual->id, 'cartao');
            }

            // Uma assinatura nova por cima de qualquer coisa anterior
            // (trial, primeira assinatura) substitui um downgrade que
            // porventura estivesse agendado - nao faz sentido manter
            // agendado.
            Tenant::cancelarTrocaAgendada($tenantAtual->id);
        }

        header('Location: ' . $resposta['body']['init_point']);
        exit;
    }

    /**
     * Equivalente a iniciar(), mas pra Pix: em vez de redirecionar pro
     * Checkout Pro (que so aceita cartao), gera uma cobranca avulsa e
     * redireciona pra tela de QR code (reaproveitada de
     * ConfiguracaoController::faturaVencida). So disponivel pra tenants
     * provisionados automaticamente (ver TenantResolver) - sem isso nao
     * ha onde registrar a fatura Pix (ver Igrejas\Models\FaturaPix).
     */
    private function iniciarPix(MercadoPagoClient $mp, string $plano, string $payerEmail): never
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null) {
            Session::flash('config_errors', ['Pagamento via Pix nao esta disponivel pra esta conta. Use cartao.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $vencimento = new \DateTimeImmutable('+3 days');
        $referenciaExterna = 'kadosys-renovacao-' . $tenant->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Igrejas - Plano ' . Plano::label($plano),
                'amount' => Plano::VALOR_MENSAL[$plano],
                'payerEmail' => $payerEmail,
                'externalReference' => $referenciaExterna,
                'expiraEm' => $vencimento,
            ]);
        } catch (\RuntimeException $exception) {
            Assinatura::registrarEvento(null, 'erro_criar_pagamento_pix', $exception->getMessage());
            Session::flash('config_errors', ['Nao foi possivel gerar a cobranca Pix agora. Tente novamente em instantes.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
        $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

        if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
            Assinatura::registrarEvento(null, 'erro_criar_pagamento_pix', json_encode($resposta, JSON_UNESCAPED_UNICODE));
            Session::flash('config_errors', ['O Mercado Pago recusou a cobranca Pix. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        FaturaPix::criar(
            $tenant->id,
            $plano,
            Plano::VALOR_MENSAL[$plano],
            (string) $resposta['body']['id'],
            $qrCode,
            (string) $qrCodeBase64,
            $vencimento,
        );

        // Mesmo raciocinio de iniciar(): so marca o metodo de pagamento
        // ja aqui (antes da confirmacao) se o trial ja estava vencido -
        // senao gerar o QR code so pra visualizar ja perdia o trial em
        // andamento antes do Pix cair de verdade (ver processarFaturaPix()).
        if ($this->trialVencido($tenant)) {
            Tenant::atualizarMetodoPagamento($tenant->id, 'pix');
        }
        Tenant::cancelarTrocaAgendada($tenant->id);

        $this->redirect('/dashboard/fatura-vencida');
    }

    /**
     * Mesma regra de Igrejas\Core\Middleware\AuthMiddleware::trialExpirado()
     * (duplicada de proposito - sao camadas diferentes: aquela decide se
     * BLOQUEIA acesso, esta decide se PODE marcar o metodo de pagamento
     * antes da confirmacao do pagamento).
     */
    private function trialVencido(Tenant $tenant): bool
    {
        if ($tenant->metodoPagamento !== 'trial' || $tenant->trialExpiraEm === null) {
            return false;
        }

        return new \DateTimeImmutable() > new \DateTimeImmutable($tenant->trialExpiraEm);
    }

    /**
     * Downgrade (plano mais barato que o atual): nao cobra nada agora -
     * a igreja ja pagou pelo ciclo atual, entao mantem o plano/modulos
     * de hoje ate o ciclo vencer (proximo_vencimento). So entao a troca
     * e aplicada de fato (ver cron/aplicar_trocas_agendadas.php).
     */
    private function agendarDowngrade(Tenant $tenant, string $novoPlano, \DateTimeImmutable $vencimentoCiclo): never
    {
        Tenant::agendarTrocaPlano($tenant->id, $novoPlano);

        Session::flash('config_success', sprintf(
            'Seu plano vai mudar de %s para %s a partir de %s (fim do ciclo ja pago) - ate la, os modulos do plano %s continuam liberados normalmente.',
            Plano::label($tenant->plano),
            Plano::label($novoPlano),
            $vencimentoCiclo->format('d/m/Y'),
            Plano::label($tenant->plano)
        ));
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Upgrade (plano mais caro que o atual): em vez de cobrar o valor
     * cheio de novo (o que faria a igreja pagar o mes que ja tinha
     * pago), gera uma cobranca avulsa via Pix so da diferenca
     * proporcional aos dias que faltam pro ciclo atual vencer. Sempre
     * via Pix (mesmo pra quem assina por cartao) porque o Checkout Pro
     * nao tem como cobrar um valor avulso numa assinatura recorrente ja
     * ativa sem cancela-la. O plano so muda de fato quando o webhook
     * confirma o pagamento (ver processarFaturaPix()), que tambem
     * atualiza o valor da assinatura de cartao pro proximo ciclo, se
     * for o caso.
     */
    private function iniciarUpgradeProporcional(
        MercadoPagoClient $mp,
        Tenant $tenant,
        string $novoPlano,
        float $valorProporcional,
        string $payerEmail,
    ): never {
        Tenant::cancelarTrocaAgendada($tenant->id);

        $vencimento = new \DateTimeImmutable('+3 days');
        $referenciaExterna = 'kadosys-upgrade-' . $tenant->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Igrejas - Upgrade proporcional para ' . Plano::label($novoPlano),
                'amount' => $valorProporcional,
                'payerEmail' => $payerEmail,
                'externalReference' => $referenciaExterna,
                'expiraEm' => $vencimento,
            ]);
        } catch (\RuntimeException $exception) {
            Assinatura::registrarEvento(null, 'erro_criar_pagamento_pix_upgrade', $exception->getMessage());
            Session::flash('config_errors', ['Nao foi possivel gerar a cobranca do upgrade agora. Tente novamente em instantes.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
        $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

        if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
            Assinatura::registrarEvento(null, 'erro_criar_pagamento_pix_upgrade', json_encode($resposta, JSON_UNESCAPED_UNICODE));
            Session::flash('config_errors', ['O Mercado Pago recusou a cobranca do upgrade. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        FaturaPix::criar(
            $tenant->id,
            $novoPlano,
            $valorProporcional,
            (string) $resposta['body']['id'],
            $qrCode,
            (string) $qrCodeBase64,
            $vencimento,
            FaturaPix::TIPO_UPGRADE_PROPORCIONAL,
        );

        $this->redirect('/dashboard/configuracoes/upgrade-pendente');
    }

    /**
     * Pagina de retorno do Checkout Pro. Nunca atualiza o plano por
     * aqui - o navegador do usuario nao e uma fonte confiavel pra
     * confirmar pagamento, so o webhook (assinado pelo Mercado Pago) e.
     */
    public function retorno(): void
    {
        Session::flash('config_success', 'Pagamento em processamento no Mercado Pago. Assim que for aprovado, o plano e liberado automaticamente aqui em Configuracoes.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Tela de QR code da cobranca avulsa de upgrade proporcional (ver
     * iniciarUpgradeProporcional()) - diferente de /dashboard/fatura-vencida,
     * deixa claro que o acesso NAO esta bloqueado (a igreja ja pagou o
     * ciclo atual, isso e so um complemento pra liberar o plano maior
     * antes do fim do ciclo).
     */
    public function upgradePendente(): void
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null) {
            $this->redirect('/dashboard');
        }

        $fatura = FaturaPix::ultimaDoTenant($tenant->id);

        if ($fatura === null || $fatura->tipo !== FaturaPix::TIPO_UPGRADE_PROPORCIONAL || $fatura->status !== 'pendente') {
            $this->redirect('/dashboard/configuracoes');
        }

        echo $this->view('dashboard.configuracoes.upgrade-pendente', [
            'pageTitle' => 'Upgrade pendente - KADOSYS Igrejas',
            'activeMenu' => 'configuracoes',
            'breadcrumb' => ['Dashboard', 'Configuracoes', 'Upgrade pendente'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'fatura' => $fatura,
        ], 'dashboard');
    }

    /**
     * Endpoint de polling (reusa o mesmo JS de /dashboard/fatura-vencida).
     */
    public function upgradePendenteStatus(): void
    {
        $tenant = TenantResolver::atual();
        $fatura = $tenant !== null ? FaturaPix::ultimaDoTenant($tenant->id) : null;

        $this->jsonResponse([
            'status' => $fatura?->status ?? 'desconhecido',
        ]);
    }

    public function webhook(): void
    {
        $corpoBruto = (string) (file_get_contents('php://input') ?: '');
        $headers = $this->cabecalhosEmMinusculo();
        $xSignature = $headers['x-signature'] ?? '';
        $xRequestId = $headers['x-request-id'] ?? '';
        $dataId = $this->extrairDataId($corpoBruto);
        $tipo = $this->extrairTipo($corpoBruto);

        $mp = new MercadoPagoClient();

        if (!$mp->validarAssinaturaWebhook($xSignature, $xRequestId, $dataId)) {
            Assinatura::registrarEvento(null, 'webhook_assinatura_invalida', $corpoBruto);
            http_response_code(401);

            return;
        }

        // Notificacao de pagamento avulso (Pix) - cadastro novo ou fatura
        // de renovacao mensal. Fluxo totalmente separado do preapproval
        // (cartao) abaixo, porque "data.id" aqui e um id de pagamento, nao
        // de assinatura.
        if ($tipo === 'payment') {
            $this->processarNotificacaoPagamento($mp, $dataId, $corpoBruto);

            return;
        }

        // Nunca confia no corpo da notificacao pra decidir o status -
        // sempre busca o estado autoritativo direto na API, usando o id
        // ja validado pela assinatura.
        try {
            $resposta = $mp->buscarAssinatura($dataId);
        } catch (\RuntimeException $exception) {
            // Falha de rede/comunicacao com o Mercado Pago: responde erro
            // (nao 200) de proposito, para o Mercado Pago tentar reenviar
            // esta notificacao mais tarde em vez de considerar entregue.
            Assinatura::registrarEvento(null, 'webhook_erro_comunicacao', $exception->getMessage());
            http_response_code(502);

            return;
        }

        if ($resposta['status'] !== 200) {
            // Nao era uma notificacao de assinatura (preapproval) - por
            // exemplo, pode ser o aviso de um pagamento avulso da
            // cobranca recorrente. O que importa pra liberar o plano e
            // o status da assinatura em si, entao ignora sem erro.
            Assinatura::registrarEvento(null, 'webhook_ignorado', $corpoBruto);
            http_response_code(200);

            return;
        }

        $preapproval = $resposta['body'];
        $statusMp = (string) ($preapproval['status'] ?? '');
        $statusInterno = match ($statusMp) {
            'authorized' => 'autorizada',
            'paused' => 'pausada',
            'cancelled' => 'cancelada',
            default => 'pendente',
        };

        // Tres origens possiveis pro mesmo preapproval_id: a propria
        // instalacao original trocando de plano (Assinatura, banco local
        // - so bate se essa assinatura tiver sido criada sem nenhum
        // tenant resolvido, ver Configuracoes), uma igreja de SUBDOMINIO
        // ja existente trocando de plano (AssinaturaTenant, ponte
        // central - ver AssinaturaController::iniciar()), ou um cadastro
        // publico novo (Provisionamento) - so uma delas vai bater.
        $assinatura = Assinatura::buscarPorPreapprovalId($dataId);

        if ($assinatura !== null) {
            Assinatura::atualizarStatus($assinatura->id, $statusInterno);
            Assinatura::registrarEvento($assinatura->id, 'webhook_status_' . $statusInterno, $corpoBruto);

            if ($statusInterno === 'autorizada') {
                ConfiguracaoIgreja::atualizarPlano($assinatura->plano);
            }

            http_response_code(200);

            return;
        }

        $assinaturaTenant = AssinaturaTenant::buscarPorPreapprovalId($dataId);

        if ($assinaturaTenant !== null) {
            $this->processarAssinaturaTenant($assinaturaTenant, $statusInterno, $corpoBruto);
            http_response_code(200);

            return;
        }

        $provisionamento = Provisionamento::buscarPorPreapprovalId($dataId);

        if ($provisionamento !== null) {
            $this->processarProvisionamento($provisionamento, $statusInterno, $corpoBruto);
            http_response_code(200);

            return;
        }

        Assinatura::registrarEvento(null, 'webhook_assinatura_desconhecida', $corpoBruto);
        http_response_code(200);
    }

    /**
     * So dispara o provisionamento (criar banco, subdominio, etc.) na
     * primeira vez que a assinatura e autorizada - o Mercado Pago pode
     * reenviar a mesma notificacao mais de uma vez, e
     * Provisionamento::reivindicarProcessamento() garante (de forma
     * atomica no banco) que isso so acontece uma unica vez mesmo se duas
     * entregas chegarem quase juntas.
     */
    private function processarProvisionamento(Provisionamento $provisionamento, string $statusInterno, string $corpoBruto): void
    {
        Assinatura::registrarEvento(null, 'webhook_provisionamento_' . $statusInterno, $corpoBruto);

        if ($statusInterno !== 'autorizada') {
            return;
        }

        if (!Provisionamento::reivindicarProcessamento($provisionamento->id)) {
            return;
        }

        (new Provisionador(new CpanelUapiClient()))->provisionar($provisionamento);
    }

    /**
     * Notificacao de pagamento avulso (topic/type "payment"). Cobre os
     * dois casos de cobranca Pix do sistema: o pagamento unico de um
     * cadastro publico novo (Provisionamento) e a fatura de renovacao
     * mensal de um tenant ja ativo (FaturaPix) - so um dos dois vai bater
     * pra um dado id de pagamento.
     */
    private function processarNotificacaoPagamento(MercadoPagoClient $mp, string $paymentId, string $corpoBruto): void
    {
        try {
            $resposta = $mp->buscarPagamento($paymentId);
        } catch (\RuntimeException $exception) {
            Assinatura::registrarEvento(null, 'webhook_pagamento_erro_comunicacao', $exception->getMessage());
            http_response_code(502);

            return;
        }

        if ($resposta['status'] !== 200) {
            Assinatura::registrarEvento(null, 'webhook_pagamento_ignorado', $corpoBruto);
            http_response_code(200);

            return;
        }

        $pagamento = $resposta['body'];
        $statusPagamento = (string) ($pagamento['status'] ?? '');
        $statusInterno = match ($statusPagamento) {
            'approved' => 'autorizada',
            'cancelled', 'rejected', 'refunded', 'charged_back' => 'cancelada',
            default => 'pendente',
        };

        $provisionamento = Provisionamento::buscarPorPaymentId($paymentId);

        if ($provisionamento !== null) {
            $this->processarProvisionamento($provisionamento, $statusInterno, $corpoBruto);
            http_response_code(200);

            return;
        }

        $fatura = FaturaPix::buscarPorPaymentId($paymentId);

        if ($fatura !== null) {
            $this->processarFaturaPix($mp, $fatura, $statusInterno, $corpoBruto);
            http_response_code(200);

            return;
        }

        Assinatura::registrarEvento(null, 'webhook_pagamento_desconhecido', $corpoBruto);
        http_response_code(200);
    }

    /**
     * Confirma a fatura de renovacao mensal (ver cron/gerar_faturas_pix.php)
     * assim que o Pix cai - o que libera o acesso da igreja de volta (ver
     * tela de Configuracoes) e o proximo ciclo fica por conta do cron.
     */
    private function processarFaturaPix(MercadoPagoClient $mp, FaturaPix $fatura, string $statusInterno, string $corpoBruto): void
    {
        Assinatura::registrarEvento(null, 'webhook_fatura_pix_' . $statusInterno, $corpoBruto);

        if ($statusInterno !== 'autorizada' || $fatura->status !== 'pendente') {
            return;
        }

        FaturaPix::marcarPaga($fatura->id);

        // A fatura carrega o plano que ela cobre (pode ser uma troca de
        // plano via Pix, nao so a renovacao do mesmo plano - ver
        // AssinaturaController::iniciarPix). Propaga pros dois lugares:
        // o registro central (usado pelo cron pra saber o valor da
        // proxima cobranca) e o banco isolado do proprio tenant (fonte
        // de verdade pro PlanoMiddleware liberar os modulos).
        Tenant::atualizarPlano($fatura->tenantId, $fatura->plano);
        $this->atualizarPlanoNoBancoDoTenant($fatura->tenantId, $fatura->plano);

        if ($fatura->tipo === FaturaPix::TIPO_RENOVACAO) {
            // Renovacao normal: esta fatura cobre ate o seu proprio
            // vencimento - vira o novo fim de ciclo. So agora (Pix
            // confirmado de verdade) e que o metodo de pagamento passa a
            // ser "pix" - ver o comentario em iniciarPix() sobre nao
            // marcar isso antes da confirmacao.
            Tenant::atualizarMetodoPagamento($fatura->tenantId, 'pix');
            Tenant::atualizarProximoVencimento($fatura->tenantId, new \DateTimeImmutable($fatura->vencimento));
        } else {
            // Upgrade proporcional: so cobriu a diferenca dos dias que
            // faltavam no ciclo atual - o fim do ciclo (proximo_vencimento)
            // nao muda. Se a igreja normalmente paga por cartao, o valor
            // da assinatura recorrente precisa subir pro valor cheio do
            // novo plano a partir do proximo ciclo (o Pix so cobriu o
            // upgrade deste mes).
            $assinaturaCartao = AssinaturaTenant::ativaDoTenant($fatura->tenantId);

            if ($assinaturaCartao !== null && isset(Plano::VALOR_MENSAL[$fatura->plano])) {
                try {
                    $mp->atualizarAssinatura($assinaturaCartao->mpPreapprovalId, Plano::VALOR_MENSAL[$fatura->plano]);
                } catch (\RuntimeException $exception) {
                    Assinatura::registrarEvento(null, 'erro_atualizar_valor_assinatura_' . $fatura->tenantId, $exception->getMessage());
                }
            }
        }
    }

    /**
     * Confirma a troca de plano por CARTAO (Checkout Pro/preapproval) de
     * uma igreja de SUBDOMINIO ja existente - equivalente a
     * processarFaturaPix(), mas pro cartao em vez do Pix (ver
     * AssinaturaTenant pra entender por que essa ponte central e
     * necessaria).
     */
    private function processarAssinaturaTenant(AssinaturaTenant $assinaturaTenant, string $statusInterno, string $corpoBruto): void
    {
        Assinatura::registrarEvento(null, 'webhook_assinatura_tenant_' . $statusInterno, $corpoBruto);

        AssinaturaTenant::atualizarStatus($assinaturaTenant->id, $statusInterno);

        if ($statusInterno !== 'autorizada') {
            return;
        }

        Tenant::atualizarPlano($assinaturaTenant->tenantId, $assinaturaTenant->plano);
        // So agora (cartao confirmado de verdade pelo webhook) e que o
        // metodo de pagamento passa a ser "cartao" - ver o comentario em
        // iniciar() sobre nao marcar isso antes da confirmacao.
        Tenant::atualizarMetodoPagamento($assinaturaTenant->tenantId, 'cartao');
        $this->atualizarPlanoNoBancoDoTenant($assinaturaTenant->tenantId, $assinaturaTenant->plano);

        // O Mercado Pago nao expoe um jeito simples de sabermos a data
        // exata do proximo debito recorrente por aqui - usa +30 dias
        // como aproximacao do ciclo mensal, so pra ter uma data de
        // referencia pra calcular upgrade proporcional/aplicar downgrade
        // agendado (ver iniciar() e cron/aplicar_trocas_agendadas.php).
        Tenant::atualizarProximoVencimento($assinaturaTenant->tenantId, new \DateTimeImmutable('+30 days'));
    }

    /**
     * O webhook roda sempre na instalacao central (o Mercado Pago chama
     * uma unica URL fixa, nunca o subdominio de um tenant especifico) -
     * entao Database::connection() aqui NUNCA e o banco isolado do
     * tenant certo. Por isso conecta direto, com as credenciais gravadas
     * no registro central (mesmo padrao do Provisionador).
     */
    private function atualizarPlanoNoBancoDoTenant(int $tenantId, string $plano): void
    {
        $tenant = Tenant::buscarPorId($tenantId);

        if ($tenant === null) {
            return;
        }

        $dbConfig = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['driver'],
            $dbConfig['host'],
            $dbConfig['port'],
            $tenant->dbName,
            $dbConfig['charset']
        );

        try {
            $pdo = new \PDO($dsn, $tenant->dbUser, $tenant->dbPassword, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->prepare(
                'INSERT INTO configuracoes_igreja (id, plano) VALUES (1, :plano)
                 ON DUPLICATE KEY UPDATE plano = VALUES(plano)'
            )->execute(['plano' => $plano]);
        } catch (\Throwable $exception) {
            Assinatura::registrarEvento(null, 'erro_atualizar_plano_tenant_' . $tenantId, $exception->getMessage());
        }
    }

    /** @return array<string, string> */
    private function cabecalhosEmMinusculo(): array
    {
        $headers = [];

        foreach (getallheaders() ?: [] as $chave => $valor) {
            $headers[strtolower($chave)] = $valor;
        }

        return $headers;
    }

    /**
     * O parametro "data.id" da URL de notificacao vem com um ponto
     * literal no nome - o PHP converte pontos em nomes de parametro pra
     * underscore ao popular $_GET, entao precisa ser lido direto da
     * query string bruta. Cai pro corpo (JSON) como reforco, caso a
     * query string nao traga o parametro.
     */
    private function extrairDataId(string $corpoBruto): string
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';

        if (preg_match('/(?:^|&)data\.id=([^&]+)/', $query, $matches) === 1) {
            return urldecode($matches[1]);
        }

        $corpo = json_decode($corpoBruto, true);

        return (string) ($corpo['data']['id'] ?? '');
    }

    /**
     * O tipo da notificacao ("payment" ou "subscription_preapproval")
     * vem tanto na query string ("type"/"topic", dependendo da versao do
     * webhook) quanto, as vezes, no corpo. Sem ponto no nome, entao
     * $_GET funciona normalmente aqui (diferente de "data.id").
     */
    private function extrairTipo(string $corpoBruto): string
    {
        $tipo = (string) ($_GET['type'] ?? $_GET['topic'] ?? '');

        if ($tipo !== '') {
            return $tipo;
        }

        $corpo = json_decode($corpoBruto, true);

        return (string) ($corpo['type'] ?? $corpo['topic'] ?? '');
    }
}
