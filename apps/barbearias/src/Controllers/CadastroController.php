<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Documento;
use Barbearias\Core\MercadoPagoClient;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FaturaBarbearia;
use Barbearias\Models\Plano;
use Barbearias\Models\User;

/**
 * Cadastro publico autoatendido: barbearia + administrador + plano,
 * com assinatura via Mercado Pago (mesma conta/credenciais do KADOSYS
 * Igrejas, ver config/mercadopago.php).
 *
 * Bem mais simples que o equivalente em Igrejas\Controllers\CadastroController:
 * como nao ha banco/subdominio pra provisionar por cliente, a
 * barbearia (e o usuario admin dela) ja sao criados na hora, com
 * status 'pendente' (Pix/cartao aguardando confirmacao) ou 'ativo'
 * (trial, sem cobranca imediata) - nao existe uma tabela separada de
 * "tentativas de cadastro", nem uma tela de espera por DNS.
 */
final class CadastroController extends Controller
{
    private const SENHA_MINIMA = 8;

    public function form(): void
    {
        $old = Session::flash('cadastro_old') ?? [];

        // Permite pre-selecionar o plano vindo dos botoes "Comecar
        // agora" de cada card de plano na landing page (?plano=essencial).
        if (!isset($old['plano'])) {
            $planoQuery = (string) $this->request->input('plano', '');

            if (Plano::valido($planoQuery)) {
                $old['plano'] = $planoQuery;
            }
        }

        // Permite pre-selecionar o teste gratis vindo do botao "Testar
        // gratis" da landing page (?metodo_pagamento=trial).
        if (!isset($old['metodo_pagamento'])) {
            $metodoQuery = (string) $this->request->input('metodo_pagamento', '');

            if (in_array($metodoQuery, ['cartao', 'pix', 'trial'], true)) {
                $old['metodo_pagamento'] = $metodoQuery;
            }
        }

        echo $this->view('cadastro.form', [
            'pageTitle' => 'Criar conta - KADOSYS Barbearias',
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cadastro_errors') ?? [],
            'old' => $old,
            'planos' => Plano::VALOR_MENSAL,
            'trialDias' => Plano::TRIAL_DIAS,
        ], 'site');
    }

    public function enviar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('cadastro_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/cadastro');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $slugInformado = trim((string) $this->request->input('slug', ''));
        $telefone = trim((string) $this->request->input('telefone', ''));
        $adminNome = trim((string) $this->request->input('admin_nome', ''));
        $adminEmail = trim((string) $this->request->input('admin_email', ''));
        $senha = (string) $this->request->input('senha', '');
        $senhaConfirmacao = (string) $this->request->input('senha_confirmacao', '');
        $plano = (string) $this->request->input('plano', '');
        $metodoPagamento = (string) $this->request->input('metodo_pagamento', 'cartao');
        $documentoTipo = (string) $this->request->input('documento_tipo', 'cpf');
        $documentoInformado = trim((string) $this->request->input('documento', ''));
        $razaoSocial = trim((string) $this->request->input('razao_social', ''));

        if (!in_array($metodoPagamento, ['cartao', 'pix', 'trial'], true)) {
            $metodoPagamento = 'cartao';
        }

        if (!in_array($documentoTipo, ['cpf', 'cnpj'], true)) {
            $documentoTipo = 'cpf';
        }

        $documento = Documento::apenasDigitos($documentoInformado);

        $old = [
            'nome' => $nome,
            'slug' => $slugInformado,
            'telefone' => $telefone,
            'admin_nome' => $adminNome,
            'admin_email' => $adminEmail,
            'plano' => $plano,
            'metodo_pagamento' => $metodoPagamento,
            'documento_tipo' => $documentoTipo,
            'documento' => $documentoInformado,
            'razao_social' => $razaoSocial,
        ];

        $slug = $this->normalizarSlug($slugInformado !== '' ? $slugInformado : $nome);
        $errors = $this->validar($nome, $slug, $adminNome, $adminEmail, $senha, $senhaConfirmacao, $plano, $documentoTipo, $documento, $razaoSocial);

        if ($metodoPagamento === 'trial' && $documento !== '' && Barbearia::documentoJaUsouTrial($documento)) {
            $errors[] = 'Esse CPF/CNPJ já usou o teste grátis antes. Escolha um plano pago pra continuar.';
        }

        if ($errors !== []) {
            Session::flash('cadastro_errors', $errors);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $razaoSocialFinal = $documentoTipo === 'cnpj' ? $razaoSocial : null;
        $trialExpiraEm = $metodoPagamento === 'trial' ? new \DateTimeImmutable('+' . Plano::TRIAL_DIAS . ' days') : null;

        $barbeariaId = Barbearia::criar(
            $nome,
            $slug,
            $documentoTipo,
            $documento,
            $razaoSocialFinal,
            $plano,
            $metodoPagamento,
            $trialExpiraEm,
            $telefone !== '' ? $telefone : null,
        );

        User::create($barbeariaId, $adminNome, $adminEmail, $senha);

        if ($metodoPagamento === 'trial') {
            // Sem pagamento nenhum pra esperar - loga direto e manda pro
            // painel, ja que acabamos de validar a senha escolhida.
            $usuario = User::findByEmail($adminEmail);

            if ($usuario !== null) {
                (new Auth($this->config))->login($usuario);
            }

            $this->redirect('/dashboard');
        }

        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            Session::flash('cadastro_errors', ['Cadastro com pagamento online indisponível no momento. Tente novamente mais tarde.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $mpConfig = require dirname(__DIR__, 2) . '/config/mercadopago.php';

        if ($mpConfig['app_url'] === '') {
            Session::flash('cadastro_errors', ['Cadastro temporariamente indisponível. Tente novamente mais tarde.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $referenciaExterna = 'kadosys-barbearias-' . $barbeariaId . '-' . bin2hex(random_bytes(4));

        if ($metodoPagamento === 'pix') {
            $this->criarCobrancaPix($mp, $barbeariaId, $plano, $adminEmail, $referenciaExterna, $old);
        }

        try {
            $resposta = $mp->criarAssinatura([
                // O Mercado Pago limita "reason" a 40 caracteres.
                'reason' => 'KADOSYS Barbearias - Plano ' . Plano::label($plano),
                'amount' => Plano::valorMensal($plano),
                'payerEmail' => $adminEmail,
                'backUrl' => $mpConfig['app_url'] . $this->url('/cadastro/retorno'),
                'externalReference' => $referenciaExterna,
            ]);
        } catch (\RuntimeException $exception) {
            Session::flash('cadastro_errors', ['Não foi possível iniciar o pagamento agora. Tente novamente em instantes.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        if ($resposta['status'] >= 300 || !isset($resposta['body']['init_point'], $resposta['body']['id'])) {
            Session::flash('cadastro_errors', ['O Mercado Pago recusou a assinatura. Tente novamente.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        Barbearia::atualizarMpPreapprovalId($barbeariaId, (string) $resposta['body']['id']);

        header('Location: ' . $resposta['body']['init_point']);
        exit;
    }

    /**
     * Cria a cobranca Pix avulsa (chamada so quando metodo_pagamento =
     * pix) e redireciona pra tela com o QR code - nunca retorna.
     */
    private function criarCobrancaPix(
        MercadoPagoClient $mp,
        int $barbeariaId,
        string $plano,
        string $adminEmail,
        string $referenciaExterna,
        array $old,
    ): void {
        $vencimento = new \DateTimeImmutable('+3 days');

        try {
            $resposta = $mp->criarPagamentoPix([
                'description' => 'KADOSYS Barbearias - Plano ' . Plano::label($plano),
                'amount' => Plano::valorMensal($plano),
                'payerEmail' => $adminEmail,
                'externalReference' => $referenciaExterna,
                'expiraEm' => $vencimento,
            ]);
        } catch (\RuntimeException $exception) {
            Session::flash('cadastro_errors', ['Não foi possível gerar a cobrança Pix agora. Tente novamente em instantes.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
        $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

        if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
            Session::flash('cadastro_errors', ['O Mercado Pago recusou a cobrança Pix. Tente novamente.']);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $faturaId = FaturaBarbearia::criar(
            $barbeariaId,
            $plano,
            FaturaBarbearia::TIPO_RENOVACAO,
            Plano::valorMensal($plano),
            (string) $resposta['body']['id'],
            $qrCode,
            $qrCodeBase64 !== null ? (string) $qrCodeBase64 : null,
            $vencimento,
        );

        $this->redirect('/cadastro/pix/' . $faturaId);
    }

    /**
     * Tela com o QR code / copia-e-cola da cobranca Pix do cadastro. So
     * mostra dados nao sensiveis - o id na URL nao precisa de
     * autenticacao porque o unico jeito de "adivinhar" o id de outra
     * pessoa e forca bruta num numero sequencial, e o pior que exporia
     * e o QR code de uma cobranca que so aquele CPF/conta consegue
     * pagar mesmo.
     */
    public function pix(string $id): void
    {
        $fatura = FaturaBarbearia::find((int) $id);

        if ($fatura === null || $fatura->pixQrCode === null) {
            $this->redirect('/cadastro');
        }

        echo $this->view('cadastro.pix', [
            'pageTitle' => 'Pagar com Pix - KADOSYS Barbearias',
            'fatura' => $fatura,
        ], 'site');
    }

    /**
     * Endpoint de polling (JS da tela de Pix) - so devolve o status.
     */
    public function pixStatus(string $id): void
    {
        $fatura = FaturaBarbearia::find((int) $id);

        $this->jsonResponse([
            'status' => $fatura?->status ?? 'desconhecido',
        ]);
    }

    /**
     * Pagina de retorno do Checkout Pro. A confirmacao em si (marcar a
     * barbearia como ativa) e feita pelo webhook do Mercado Pago de
     * forma assincrona - pode nao ter chegado ainda quando o navegador
     * volta pra ca, entao esta tela nunca promete "pronto", so confirma
     * que o pedido foi recebido.
     */
    public function retorno(): void
    {
        echo $this->view('cadastro.retorno', [
            'pageTitle' => 'Cadastro em processamento - KADOSYS Barbearias',
        ], 'site');
    }

    /** @return array<int, string> */
    private function validar(
        string $nome,
        string $slug,
        string $adminNome,
        string $adminEmail,
        string $senha,
        string $senhaConfirmacao,
        string $plano,
        string $documentoTipo,
        string $documento,
        string $razaoSocial,
    ): array {
        $errors = [];

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe o nome da barbearia (mínimo 3 caracteres).';
        }

        if ($slug === '' || mb_strlen($slug) < 3) {
            $errors[] = 'O identificador precisa ter pelo menos 3 letras (só letras, números e hífen).';
        } elseif (!Barbearia::slugDisponivel($slug)) {
            $errors[] = 'Esse identificador já está em uso. Escolha outro.';
        }

        if ($adminNome === '' || mb_strlen($adminNome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        } elseif (User::emailEmUso($adminEmail)) {
            $errors[] = 'Esse e-mail já está cadastrado.';
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $errors[] = 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.';
        } elseif ($senha !== $senhaConfirmacao) {
            $errors[] = 'A confirmação de senha não confere.';
        }

        if (!Plano::valido($plano)) {
            $errors[] = 'Escolha um plano válido.';
        }

        if (!Documento::validar($documentoTipo, $documento)) {
            $errors[] = $documentoTipo === 'cnpj' ? 'Informe um CNPJ válido.' : 'Informe um CPF válido.';
        }

        if ($documentoTipo === 'cnpj' && ($razaoSocial === '' || mb_strlen($razaoSocial) < 3)) {
            $errors[] = 'Informe a razão social da barbearia.';
        }

        return $errors;
    }

    /**
     * Normaliza um texto livre (nome da barbearia ou identificador
     * digitado) - minusculo, sem acento, so letras/numeros/hifen.
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
