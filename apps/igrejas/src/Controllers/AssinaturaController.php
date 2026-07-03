<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\MercadoPagoClient;
use Igrejas\Core\Session;
use Igrejas\Models\Assinatura;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Plano;

/**
 * Assinatura recorrente do plano contratado via Mercado Pago (Checkout
 * Pro + Assinaturas/preapproval). O plano so muda de fato quando o
 * webhook confirma o pagamento (ver metodo webhook()) - o retorno do
 * checkout no navegador (metodo retorno()) e so uma tela de espera,
 * nunca a fonte de verdade.
 */
final class AssinaturaController extends Controller
{
    /**
     * Planos com assinatura automatica disponivel e seus valores
     * mensais. Enterprise fica de fora de proposito (e "sob consulta" -
     * negociado direto com o suporte, sem checkout automatico).
     *
     * @var array<string, float>
     */
    private const VALOR_POR_PLANO = [
        Plano::ESSENCIAL => 97.00,
        Plano::PREMIUM => 197.00,
    ];

    public function iniciar(string $plano): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('config_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if (!isset(self::VALOR_POR_PLANO[$plano])) {
            Session::flash('config_errors', ['Esse plano nao tem assinatura automatica disponivel. Para o Enterprise, fale com o suporte.']);
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
        $referenciaExterna = 'kadosys-' . $plano . '-' . bin2hex(random_bytes(6));

        try {
            $resposta = $mp->criarAssinatura([
                'reason' => 'KADOSYS Igrejas - Plano ' . Plano::label($plano),
                'amount' => self::VALOR_POR_PLANO[$plano],
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

        header('Location: ' . $resposta['body']['init_point']);
        exit;
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

    public function webhook(): void
    {
        $corpoBruto = (string) (file_get_contents('php://input') ?: '');
        $headers = $this->cabecalhosEmMinusculo();
        $xSignature = $headers['x-signature'] ?? '';
        $xRequestId = $headers['x-request-id'] ?? '';
        $dataId = $this->extrairDataId($corpoBruto);

        $mp = new MercadoPagoClient();

        if (!$mp->validarAssinaturaWebhook($xSignature, $xRequestId, $dataId)) {
            Assinatura::registrarEvento(null, 'webhook_assinatura_invalida', $corpoBruto);
            http_response_code(401);

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
        $assinatura = Assinatura::buscarPorPreapprovalId($dataId);

        if ($assinatura === null) {
            Assinatura::registrarEvento(null, 'webhook_assinatura_desconhecida', $corpoBruto);
            http_response_code(200);

            return;
        }

        $statusMp = (string) ($preapproval['status'] ?? '');
        $statusInterno = match ($statusMp) {
            'authorized' => 'autorizada',
            'paused' => 'pausada',
            'cancelled' => 'cancelada',
            default => 'pendente',
        };

        Assinatura::atualizarStatus($assinatura->id, $statusInterno);
        Assinatura::registrarEvento($assinatura->id, 'webhook_status_' . $statusInterno, $corpoBruto);

        if ($statusInterno === 'autorizada') {
            ConfiguracaoIgreja::atualizarPlano($assinatura->plano);
        }

        http_response_code(200);
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
}
