<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\MercadoPagoClient;
use Igrejas\Core\Session;
use Igrejas\Models\Plano;
use Igrejas\Models\Provisionamento;
use Igrejas\Models\Tenant;

/**
 * Cadastro publico autoatendido: igreja + administrador + plano, com
 * assinatura via Mercado Pago. Diferente do resto do sistema (pensado
 * pra uma igreja ja existente e logada), aqui ainda nao existe conta
 * nenhuma - o acesso so e criado depois que o pagamento e confirmado
 * (ver PlanoMiddleware/AssinaturaController para o fluxo de quem ja e
 * cliente, e o webhook do Mercado Pago para o provisionamento em si).
 */
final class CadastroController extends Controller
{
    private const SENHA_MINIMA = 8;

    public function form(): void
    {
        $old = Session::flash('cadastro_old') ?? [];

        // Permite pre-selecionar o plano vindo dos botoes "Comecar agora"
        // de cada card de plano na landing page (?plano=essencial).
        if (!isset($old['plano'])) {
            $planoQuery = (string) $this->request->input('plano', '');

            if (isset(Plano::VALOR_MENSAL[$planoQuery])) {
                $old['plano'] = $planoQuery;
            }
        }

        echo $this->view('cadastro.form', [
            'pageTitle' => 'Criar conta - KADOSYS Igrejas',
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cadastro_errors') ?? [],
            'old' => $old,
            'planos' => Plano::VALOR_MENSAL,
        ], 'auth');
    }

    public function enviar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('cadastro_errors', ['Sessao expirada. Preencha o formulario novamente.']);
            $this->redirect('/cadastro');
        }

        $nomeIgreja = trim((string) $this->request->input('nome_igreja', ''));
        $slugInformado = trim((string) $this->request->input('slug', ''));
        $adminNome = trim((string) $this->request->input('admin_nome', ''));
        $adminEmail = trim((string) $this->request->input('admin_email', ''));
        $senha = (string) $this->request->input('senha', '');
        $senhaConfirmacao = (string) $this->request->input('senha_confirmacao', '');
        $plano = (string) $this->request->input('plano', '');

        $old = [
            'nome_igreja' => $nomeIgreja,
            'slug' => $slugInformado,
            'admin_nome' => $adminNome,
            'admin_email' => $adminEmail,
            'plano' => $plano,
        ];

        $slug = $this->normalizarSlug($slugInformado !== '' ? $slugInformado : $nomeIgreja);
        $errors = $this->validar($nomeIgreja, $slug, $adminNome, $adminEmail, $senha, $senhaConfirmacao, $plano);

        if ($errors !== []) {
            Session::flash('cadastro_errors', $errors);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            Session::flash('cadastro_errors', ['Cadastro com pagamento online indisponivel no momento. Tente novamente mais tarde.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $mpConfig = require dirname(__DIR__, 2) . '/config/mercadopago.php';

        if ($mpConfig['app_url'] === '') {
            Session::flash('cadastro_errors', ['Cadastro temporariamente indisponivel. Tente novamente mais tarde.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $provisionamentoId = Provisionamento::criar(
            $nomeIgreja,
            $slug,
            $adminNome,
            $adminEmail,
            password_hash($senha, PASSWORD_BCRYPT),
            $plano,
        );

        $referenciaExterna = 'kadosys-cadastro-' . $provisionamentoId . '-' . bin2hex(random_bytes(4));

        try {
            $resposta = $mp->criarAssinatura([
                // O Mercado Pago limita "reason" a 40 caracteres - nao da
                // pra incluir o nome da igreja aqui com seguranca (nomes
                // longos estourariam o limite e a API rejeitaria a
                // criacao da assinatura). O nome da igreja fica registrado
                // em plataforma_provisionamentos mesmo assim.
                'reason' => 'KADOSYS Igrejas - Plano ' . Plano::label($plano),
                'amount' => Plano::VALOR_MENSAL[$plano],
                'payerEmail' => $adminEmail,
                'backUrl' => $mpConfig['app_url'] . $this->url('/cadastro/retorno'),
                'externalReference' => $referenciaExterna,
            ]);
        } catch (\RuntimeException $exception) {
            Provisionamento::atualizarStatus($provisionamentoId, 'erro', $exception->getMessage());
            Session::flash('cadastro_errors', ['Nao foi possivel iniciar o pagamento agora. Tente novamente em instantes.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        if ($resposta['status'] >= 300 || !isset($resposta['body']['init_point'], $resposta['body']['id'])) {
            Provisionamento::atualizarStatus($provisionamentoId, 'erro', json_encode($resposta, JSON_UNESCAPED_UNICODE));
            Session::flash('cadastro_errors', ['O Mercado Pago recusou a assinatura. Tente novamente.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        Provisionamento::definirPreapprovalId($provisionamentoId, (string) $resposta['body']['id']);

        header('Location: ' . $resposta['body']['init_point']);
        exit;
    }

    /**
     * Pagina de retorno do Checkout Pro. O provisionamento em si (criar
     * banco, subdominio, etc.) e disparado pelo webhook do Mercado Pago
     * de forma assincrona - pode nao ter terminado (ou nem comecado)
     * ainda quando o navegador volta pra ca, entao esta tela nunca
     * promete "pronto", so confirma que o pedido foi recebido.
     */
    public function retorno(): void
    {
        echo $this->view('cadastro.retorno', [
            'pageTitle' => 'Cadastro em processamento - KADOSYS Igrejas',
        ], 'auth');
    }

    /** @return array<int, string> */
    private function validar(
        string $nomeIgreja,
        string $slug,
        string $adminNome,
        string $adminEmail,
        string $senha,
        string $senhaConfirmacao,
        string $plano,
    ): array {
        $errors = [];

        if ($nomeIgreja === '' || mb_strlen($nomeIgreja) < 3) {
            $errors[] = 'Informe o nome da igreja (minimo 3 caracteres).';
        }

        if ($slug === '' || mb_strlen($slug) < 3) {
            $errors[] = 'O subdominio precisa ter pelo menos 3 letras (so letras, numeros e hifen).';
        } elseif (!Tenant::slugDisponivel($slug) || Provisionamento::slugReservado($slug)) {
            $errors[] = 'Esse subdominio ja esta em uso. Escolha outro.';
        }

        if ($adminNome === '' || mb_strlen($adminNome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail valido.';
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $errors[] = 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.';
        } elseif ($senha !== $senhaConfirmacao) {
            $errors[] = 'A confirmacao de senha nao confere.';
        }

        if (!isset(Plano::VALOR_MENSAL[$plano])) {
            $errors[] = 'Escolha um plano valido.';
        }

        return $errors;
    }

    /**
     * Normaliza um texto livre (nome da igreja ou subdominio digitado)
     * pro formato exigido por um subdominio: minusculo, sem acento, so
     * letras/numeros/hifen.
     */
    private function normalizarSlug(string $valor): string
    {
        $valor = mb_strtolower($valor);
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT', $valor);
        $valor = $transliterado !== false ? $transliterado : $valor;
        $valor = preg_replace('/[^a-z0-9]+/', '-', $valor) ?? '';

        return trim($valor, '-');
    }
}
