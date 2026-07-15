<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Tenant;
use Igrejas\Models\User;

/**
 * Controller de autenticacao.
 *
 * Implementa o fluxo completo de login/logout com sessao, "lembrar-me" e
 * protecao CSRF. A recuperacao de senha tem, nesta etapa, a estrutura
 * pronta (formulario, validacao e geracao de token), sem o envio efetivo
 * de e-mail, que sera ligado em sprint futura junto ao servico de
 * comunicacao da plataforma.
 */
final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $tenant = TenantResolver::atual();
        $configuracao = $tenant !== null ? ConfiguracaoIgreja::atual() : null;
        $emailPreenchido = trim((string) $this->request->input('email', ''));

        // Fora de qualquer subdominio de igreja (dominio raiz da
        // plataforma), pede so o e-mail primeiro - ver localizarIgrejas(),
        // que descobre em qual(is) igreja(s) essa pessoa tem conta antes
        // de pedir a senha. So mostra o formulario completo quando ja se
        // sabe pra onde a senha deve ir: dentro do subdominio de uma
        // igreja especifica, ou de volta aqui com o e-mail ja resolvido
        // (0 ou 1 igreja encontrada).
        $pedirSoEmail = $tenant === null && $emailPreenchido === '';

        echo $this->view('auth.login', [
            'pageTitle' => ($configuracao?->nomeIgreja ?? 'KADOSYS Igrejas') . ' - Entrar',
            'csrf' => Csrf::field(),
            'error' => Session::flash('login_error'),
            'old' => Session::flash('login_old') ?? ['email' => $emailPreenchido],
            'configuracao' => $configuracao,
            'pedirSoEmail' => $pedirSoEmail,
        ], 'auth');
    }

    /**
     * Primeiro passo do login no dominio raiz: recebe so o e-mail e
     * descobre em qual(is) igreja(s) ativa(s) essa pessoa tem conta (ver
     * Tenant::ativasComEmailCadastrado()), ja que nao existe uma tabela
     * central de usuarios - cada igreja tem seu proprio banco isolado.
     *
     * - 0 igrejas encontradas: nao revela isso (evita confirmar/negar se
     *   um e-mail existe) - so volta pro /login com o e-mail preenchido,
     *   pronto pra tentar a senha contra a instalacao atual normalmente.
     * - 1 igreja encontrada: manda direto pro subdominio dela, sem
     *   friccao extra.
     * - 2+ igrejas encontradas: mostra a tela de selecao.
     */
    public function localizarIgrejas(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $token = $this->request->input('_csrf_token');

        if (!Csrf::verify($token)) {
            Session::flash('login_error', 'Sessão expirada. Tente novamente.');
            $this->redirect('/login');
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            Session::flash('login_error', 'Informe um e-mail válido.');
            $this->redirect('/login');
        }

        $rootDomain = (string) (require dirname(__DIR__, 2) . '/config/cpanel.php')['root_domain'];

        // So faz sentido buscar em outras igrejas no dominio raiz da
        // plataforma - dentro do subdominio de uma igreja especifica ja
        // se sabe pra qual banco ir, e sem CPANEL_ROOT_DOMAIN configurado
        // nao ha como montar um link valido pra outro subdominio.
        if (TenantResolver::atual() !== null || $rootDomain === '') {
            $this->redirect('/login?email=' . urlencode($email));
        }

        $encontradas = Tenant::ativasComEmailCadastrado($email);

        if (count($encontradas) === 1) {
            $this->redirecionarParaFora('https://' . $encontradas[0]->subdominio . '/login?email=' . urlencode($email));
        }

        if (count($encontradas) === 0) {
            $this->redirect('/login?email=' . urlencode($email));
        }

        echo $this->view('auth.selecionar-igreja', [
            'pageTitle' => 'Selecione sua igreja - KADOSYS Igrejas',
            'email' => $email,
            'igrejas' => $encontradas,
        ], 'auth');
    }

    /**
     * Redireciona pra uma URL absoluta em outro (sub)dominio -
     * Controller::redirect() so serve pra caminhos relativos dentro
     * desta mesma instalacao (prefixa base_path), entao nao da pra
     * reaproveitar aqui.
     */
    private function redirecionarParaFora(string $urlAbsoluta): never
    {
        header('Location: ' . $urlAbsoluta);
        exit;
    }

    public function login(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');
        $remember = (bool) $this->request->input('remember', false);
        $token = $this->request->input('_csrf_token');

        if (!Csrf::verify($token)) {
            Session::flash('login_error', 'Sessão expirada. Tente novamente.');
            $this->redirect('/login');
        }

        if ($email === '' || $password === '') {
            Session::flash('login_error', 'Informe e-mail e senha.');
            Session::flash('login_old', ['email' => $email]);
            $this->redirect('/login');
        }

        $auth = new Auth($this->config);

        if (!$auth->attempt($email, $password, $remember)) {
            Session::flash('login_error', 'E-mail ou senha inválidos.');
            Session::flash('login_old', ['email' => $email]);
            $this->redirect('/login');
        }

        // So um timestamp central por igreja (nao por usuario) - da ao
        // dono da plataforma uma nocao de quais igrejas estao ativas de
        // verdade (ver PlataformaController::igrejas()). So existe pra
        // igrejas de subdominio (com registro central, ver TenantResolver).
        $tenant = TenantResolver::atual();

        if ($tenant !== null) {
            Tenant::atualizarUltimoAcesso($tenant->id);
        }

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        (new Auth($this->config))->logout();
        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        echo $this->view('auth.esqueci-senha', [
            'pageTitle' => 'Recuperar senha - KADOSYS Igrejas',
            'csrf' => Csrf::field(),
            'status' => Session::flash('forgot_status'),
        ], 'auth');
    }

    public function sendForgotPassword(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $token = $this->request->input('_csrf_token');

        if (!Csrf::verify($token) || $email === '') {
            Session::flash('forgot_status', 'Não foi possível processar a solicitação. Tente novamente.');
            $this->redirect('/esqueci-senha');
        }

        $user = User::findByEmail($email);

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = new \DateTimeImmutable('+1 hour');

            User::createPasswordResetToken($email, $tokenHash, $expiresAt);

            // O envio do e-mail com o link de redefinicao sera implementado
            // junto ao modulo de Comunicacao em sprint futura. O token ja
            // fica registrado em password_resets, pronto para uso.
        }

        // Mensagem identica exista ou nao o e-mail, para nao revelar quais
        // enderecos estao cadastrados na base.
        Session::flash(
            'forgot_status',
            'Se o e-mail informado estiver cadastrado, você receberá as instruções de recuperação em breve.'
        );

        $this->redirect('/esqueci-senha');
    }
}
