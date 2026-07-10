<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
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
        echo $this->view('auth.login', [
            'pageTitle' => 'Entrar - KADOSYS Igrejas',
            'csrf' => Csrf::field(),
            'error' => Session::flash('login_error'),
            'old' => Session::flash('login_old') ?? [],
        ], 'auth');
    }

    public function login(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');
        $remember = (bool) $this->request->input('remember', false);
        $token = $this->request->input('_csrf_token');

        if (!Csrf::verify($token)) {
            Session::flash('login_error', 'Sessao expirada. Tente novamente.');
            $this->redirect('/login');
        }

        if ($email === '' || $password === '') {
            Session::flash('login_error', 'Informe e-mail e senha.');
            Session::flash('login_old', ['email' => $email]);
            $this->redirect('/login');
        }

        $auth = new Auth($this->config);

        if (!$auth->attempt($email, $password, $remember)) {
            Session::flash('login_error', 'E-mail ou senha invalidos.');
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
            Session::flash('forgot_status', 'Nao foi possivel processar a solicitacao. Tente novamente.');
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
            'Se o e-mail informado estiver cadastrado, voce recebera as instrucoes de recuperacao em breve.'
        );

        $this->redirect('/esqueci-senha');
    }
}
