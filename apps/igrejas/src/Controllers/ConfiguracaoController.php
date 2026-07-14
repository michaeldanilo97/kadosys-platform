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
use Igrejas\Models\BibliaLivro;
use Igrejas\Models\BibliaVersao;
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
        $configuracao = ConfiguracaoIgreja::atual();

        echo $this->view('dashboard.configuracoes.index', [
            'pageTitle' => 'Configurações - KADOSYS Igrejas',
            'activeMenu' => 'configuracoes',
            'breadcrumb' => ['Dashboard', 'Configurações'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'configuracao' => $configuracao,
            // Link publico da pagina de doacao, pronto pra compartilhar
            // (WhatsApp, redes sociais etc.) - precisa do host de verdade
            // (subdominio da igreja), nao da APP_URL fixa da plataforma
            // (essa e usada so pro retorno do Mercado Pago, ver
            // config/mercadopago.php).
            'linkDoacao' => $this->urlAbsoluta('/doar'),
            'assinatura' => Assinatura::ultima(),
            'pagamentoConfigurado' => (new MercadoPagoClient())->configurado(),
            // Pix so esta disponivel pra igrejas provisionadas
            // automaticamente (com registro central de tenant) - a fatura
            // Pix mensal depende desse registro pra o webhook (que roda
            // sempre na instalacao central) saber a quem pertence cada
            // pagamento. A instalacao original continua so com cartao.
            'pixDisponivel' => TenantResolver::atual() !== null,
            'tenant' => TenantResolver::atual(),
            'livros' => BibliaLivro::all(),
            'versoesBiblia' => BibliaVersao::todas(),
            'success' => Session::flash('config_success'),
            'errors' => Session::flash('config_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
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
                'description' => 'KADOSYS Igrejas - Renovação ' . Plano::label($tenant->plano),
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
            'pageTitle' => 'Teste grátis encerrado - KADOSYS Igrejas',
            'activeMenu' => 'trial-expirado',
            'breadcrumb' => ['Dashboard', 'Teste grátis encerrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'tenant' => $tenant,
        ], 'dashboard');
    }

    public function atualizarLogo(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('config_errors', ['Sessão expirada. Tente novamente.']);
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
            Session::flash('config_errors', ['A imagem deve ter no máximo 5MB.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('config_errors', ['Formato inválido. Envie PNG, JPG, WEBP ou SVG.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('config_errors', ['Não foi possível salvar a imagem no servidor.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $this->removerArquivosLogo($destinoDir);

        $nomeArquivo = 'logo.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('config_errors', ['Não foi possível salvar a imagem no servidor.']);
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

    /**
     * Liga/desliga o auto-cadastro publico de membros (ver
     * MembroPublicoController) - quando desligado (padrao), a igreja
     * continua cadastrando cada membro manualmente pelo modulo Membros.
     */
    public function atualizarCadastroMembros(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $habilitado = (string) $this->request->input('cadastro_membros_habilitado', '') === '1';
            ConfiguracaoIgreja::atualizarCadastroMembros($habilitado);
            Session::flash('config_success', $habilitado
                ? 'Auto-cadastro de membros habilitado - o link já aparece na tela de login.'
                : 'Auto-cadastro de membros desabilitado.');
        }

        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Cadastra/atualiza a chave Pix da igreja para doacoes publicas via
     * Pix estatico (ver DoacaoController) - dinheiro cai direto na
     * conta da igreja, sem passar pela plataforma.
     */
    public function atualizarChavePix(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $chave = trim((string) $this->request->input('pix_chave', ''));
            $nomeBeneficiario = trim((string) $this->request->input('pix_nome_beneficiario', ''));

            if ($chave === '') {
                ConfiguracaoIgreja::removerChavePix();
                Session::flash('config_success', 'Chave Pix removida - a página de doação fica indisponível até cadastrar uma nova.');
            } else {
                $nomeBeneficiario = $nomeBeneficiario !== '' ? $nomeBeneficiario : (string) (ConfiguracaoIgreja::atual()->nomeIgreja ?? 'Igreja');
                ConfiguracaoIgreja::atualizarChavePix($chave, $nomeBeneficiario);
                Session::flash('config_success', 'Chave Pix salva - a página de doação já está disponível.');
            }
        }

        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Mensagem opcional exibida ao lado da logo na tela de Pix do
     * telao (junto com os QR de dizimo/oferta) - texto livre digitado
     * pela igreja ou uma referencia biblica, resolvida na hora em que
     * o telao busca o estado (ver ProjecaoEstado::montarPixJson()).
     */
    public function atualizarMensagemPix(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $tipo = (string) $this->request->input('pix_mensagem_tipo', 'nenhuma');

            if (!in_array($tipo, ['nenhuma', 'texto', 'versiculo'], true)) {
                $tipo = 'nenhuma';
            }

            $texto = null;
            $bibliaVersao = null;
            $livroId = null;
            $capitulo = null;
            $versiculoInicio = null;
            $versiculoFim = null;

            if ($tipo === 'texto') {
                $texto = trim((string) $this->request->input('pix_mensagem_texto', ''));
                $tipo = $texto !== '' ? 'texto' : 'nenhuma';
            } elseif ($tipo === 'versiculo') {
                $bibliaVersao = (string) $this->request->input('pix_mensagem_biblia_versao', '');
                $livroIdInput = trim((string) $this->request->input('pix_mensagem_livro_id', ''));
                $capituloInput = trim((string) $this->request->input('pix_mensagem_capitulo', ''));
                $inicioInput = trim((string) $this->request->input('pix_mensagem_versiculo_inicio', ''));
                $fimInput = trim((string) $this->request->input('pix_mensagem_versiculo_fim', ''));

                $valido = BibliaVersao::valida($bibliaVersao)
                    && $livroIdInput !== '' && ctype_digit($livroIdInput)
                    && $capituloInput !== '' && ctype_digit($capituloInput)
                    && $inicioInput !== '' && ctype_digit($inicioInput);

                if ($valido) {
                    $livroId = (int) $livroIdInput;
                    $capitulo = (int) $capituloInput;
                    $versiculoInicio = (int) $inicioInput;
                    $versiculoFim = ($fimInput !== '' && ctype_digit($fimInput)) ? (int) $fimInput : $versiculoInicio;

                    if ($versiculoFim < $versiculoInicio) {
                        [$versiculoInicio, $versiculoFim] = [$versiculoFim, $versiculoInicio];
                    }
                } else {
                    $tipo = 'nenhuma';
                    $bibliaVersao = null;
                }
            }

            ConfiguracaoIgreja::atualizarMensagemPix(
                $tipo,
                $texto,
                $bibliaVersao,
                $livroId,
                $capitulo,
                $versiculoInicio,
                $versiculoFim
            );

            Session::flash('config_success', 'Mensagem da tela de Pix atualizada.');
        }

        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Mensagem personalizada do e-mail automatico de aniversario (ver
     * cron/enviar_parabens_aniversario.php e
     * ConfiguracaoIgreja::MENSAGEM_ANIVERSARIO_PADRAO).
     */
    public function atualizarMensagemAniversario(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $mensagem = trim((string) $this->request->input('mensagem_aniversario', ''));
            ConfiguracaoIgreja::atualizarMensagemAniversario($mensagem !== '' ? $mensagem : null);

            Session::flash('config_success', 'Mensagem de aniversário atualizada.');
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
