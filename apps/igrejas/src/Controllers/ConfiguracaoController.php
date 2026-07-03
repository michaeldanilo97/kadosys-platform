<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\MercadoPagoClient;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Assinatura;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\FaturaPix;
use Igrejas\Models\Plano;

/**
 * Controller de Configuracoes gerais da igreja.
 *
 * Nesta etapa cobre apenas a logo usada no fadeout da projecao de video
 * (modulo Projecao/Telao). Demais preferencias serao adicionadas conforme
 * o modulo Configuracoes crescer.
 */
final class ConfiguracaoController extends Controller
{
    private const UPLOAD_DIR = 'uploads/logo';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    private const TAMANHO_MAXIMO = 5 * 1024 * 1024;

    public function index(): void
    {
        echo $this->view('dashboard.configuracoes.index', [
            'pageTitle' => 'Configuracoes - KADOSYS Igrejas',
            'activeMenu' => 'configuracoes',
            'breadcrumb' => ['Dashboard', 'Configuracoes'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'configuracao' => ConfiguracaoIgreja::atual(),
            'assinatura' => Assinatura::ultima(),
            'pagamentoConfigurado' => (new MercadoPagoClient())->configurado(),
            // Pix so esta disponivel pra igrejas provisionadas
            // automaticamente (com registro central de tenant) - a fatura
            // Pix mensal depende desse registro pra o webhook (que roda
            // sempre na instalacao central) saber a quem pertence cada
            // pagamento. A instalacao original continua so com cartao.
            'pixDisponivel' => TenantResolver::atual() !== null,
            'success' => Session::flash('config_success'),
            'errors' => Session::flash('config_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function atualizarPlano(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('config_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $plano = (string) $this->request->input('plano', '');

        if (!isset(Plano::LABELS[$plano])) {
            Session::flash('config_errors', ['Plano invalido.']);
            $this->redirect('/dashboard/configuracoes');
        }

        ConfiguracaoIgreja::atualizarPlano($plano);

        Session::flash('config_success', 'Plano atualizado para ' . Plano::label($plano) . '.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Tela mostrada quando a fatura Pix de renovacao mensal vence sem
     * pagamento (ver AuthMiddleware::bloquearSeFaturaPixVencida) - exibe
     * o QR code pra pagar. Se a ultima fatura ja estiver "expirada" (o
     * cron so gera uma nova uma vez por dia), gera uma cobranca nova na
     * hora pra nao deixar o admin esperando.
     */
    public function faturaVencida(): void
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null || $tenant->metodoPagamento !== 'pix') {
            $this->redirect('/dashboard');
        }

        $fatura = FaturaPix::ultimaDoTenant($tenant->id);

        if ($fatura !== null && ($fatura->status === 'paga' || $fatura->status === 'cancelada')) {
            $this->redirect('/dashboard');
        }

        if ($fatura === null || $fatura->status === 'expirada') {
            $fatura = $this->gerarNovaFaturaPix($tenant, $fatura);
        }

        echo $this->view('dashboard.fatura-vencida', [
            'pageTitle' => 'Fatura pendente - KADOSYS Igrejas',
            'activeMenu' => 'fatura-vencida',
            'breadcrumb' => ['Dashboard', 'Fatura pendente'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'fatura' => $fatura,
        ], 'dashboard');
    }

    /**
     * Endpoint de polling (JS da tela de fatura vencida) - so devolve o
     * status da ultima fatura, nada sensivel.
     */
    public function faturaVencidaStatus(): void
    {
        $tenant = TenantResolver::atual();
        $fatura = $tenant !== null ? FaturaPix::ultimaDoTenant($tenant->id) : null;

        $this->jsonResponse([
            'status' => $fatura?->status ?? 'desconhecido',
        ]);
    }

    /**
     * Gera uma cobranca Pix nova pra fatura de renovacao, sob demanda
     * (fora do horario do cron). Devolve a fatura anterior sem quebrar a
     * tela se o Mercado Pago nao estiver configurado ou recusar a
     * cobranca - o cron tenta de novo no proximo ciclo de qualquer jeito.
     */
    private function gerarNovaFaturaPix(\Igrejas\Models\Tenant $tenant, ?FaturaPix $anterior): ?FaturaPix
    {
        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            return $anterior;
        }

        $valor = Plano::VALOR_MENSAL[$tenant->plano] ?? null;

        if ($valor === null) {
            return $anterior;
        }

        $usuario = (new Auth($this->config))->user();
        $vencimento = new \DateTimeImmutable('+3 days');
        $referencia = 'kadosys-renovacao-' . $tenant->id . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Igrejas - Renovacao ' . Plano::label($tenant->plano),
                'amount' => $valor,
                'payerEmail' => $usuario?->email ?? '',
                'externalReference' => $referencia,
                'expiraEm' => $vencimento,
            ]);
        } catch (\RuntimeException) {
            return $anterior;
        }

        $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
        $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

        if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
            return $anterior;
        }

        FaturaPix::criar(
            $tenant->id,
            $tenant->plano,
            $valor,
            (string) $resposta['body']['id'],
            $qrCode,
            (string) $qrCodeBase64,
            $vencimento,
        );

        return FaturaPix::buscarPorPaymentId((string) $resposta['body']['id']);
    }

    /**
     * Tela mostrada quando o teste gratis de 7 dias vence sem a igreja
     * escolher um plano pago (ver AuthMiddleware::bloquearSeTrialExpirado)
     * - so um aviso com CTA pra Configuracoes, onde ela ja pode assinar
     * via cartao ou Pix normalmente (essas rotas continuam liberadas
     * mesmo com o trial vencido).
     */
    public function trialExpirado(): void
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null || $tenant->metodoPagamento !== 'trial') {
            $this->redirect('/dashboard');
        }

        echo $this->view('dashboard.trial-expirado', [
            'pageTitle' => 'Teste gratis encerrado - KADOSYS Igrejas',
            'activeMenu' => 'trial-expirado',
            'breadcrumb' => ['Dashboard', 'Teste gratis encerrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'tenant' => $tenant,
        ], 'dashboard');
    }

    public function atualizarLogo(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('config_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $arquivo = $this->request->file('logo');

        if ($arquivo === null) {
            Session::flash('config_errors', ['Selecione um arquivo de imagem.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('config_errors', ['Falha no envio do arquivo. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            Session::flash('config_errors', ['A imagem deve ter no maximo 5MB.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('config_errors', ['Formato invalido. Envie PNG, JPG, WEBP ou SVG.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('config_errors', ['Nao foi possivel salvar a imagem no servidor.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $this->removerArquivosLogo($destinoDir);

        $nomeArquivo = 'logo.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('config_errors', ['Nao foi possivel salvar a imagem no servidor.']);
            $this->redirect('/dashboard/configuracoes');
        }

        ConfiguracaoIgreja::atualizarLogo(self::UPLOAD_DIR . '/' . $nomeArquivo);

        Session::flash('config_success', 'Logo atualizada com sucesso.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function removerLogo(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $this->removerArquivosLogo(dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR);
            ConfiguracaoIgreja::removerLogo();
            Session::flash('config_success', 'Logo removida.');
        }

        $this->redirect('/dashboard/configuracoes');
    }

    private function removerArquivosLogo(string $destinoDir): void
    {
        foreach (glob($destinoDir . '/logo.*') ?: [] as $arquivoAntigo) {
            unlink($arquivoAntigo);
        }
    }
}
