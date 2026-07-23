<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Mailer;
use Food\Core\Session;
use Food\Models\User;

/**
 * Controller de autenticacao.
 */
final class AuthController extends Controller
{
    public function showLogin(): void
    {
        echo $this->view('auth.login', [
            'pageTitle' => 'KADOSYS Food - Entrar',
            'csrf' => Csrf::field(),
            'error' => Session::flash('login_error'),
            'success' => Session::flash('login_success'),
            'old' => Session::flash('login_old') ?? [],
        ], 'auth');
    }

    public function login(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');
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

        if (!$auth->attempt($email, $password)) {
            $usuario = User::findByEmail($email);

            $mensagem = match (true) {
                $usuario === null => 'Esse e-mail não está cadastrado.',
                !$usuario->active => 'Esse usuário está desativado. Fale com quem administra seu restaurante.',
                default => 'Senha incorreta.',
            };

            Session::flash('login_error', $mensagem);
            Session::flash('login_old', ['email' => $email]);
            $this->redirect('/login');
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
            'pageTitle' => 'KADOSYS Food - Recuperar senha',
            'csrf' => Csrf::field(),
            'status' => Session::flash('forgot_status'),
        ], 'auth');
    }

    /**
     * Sempre mostra a mesma mensagem, exista ou nao o e-mail cadastrado
     * - nao revela pra quem esta tentando adivinhar quais enderecos tem
     * conta no sistema.
     */
    public function sendForgotPassword(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $token = $this->request->input('_csrf_token');

        if (!Csrf::verify($token) || $email === '') {
            Session::flash('forgot_status', 'Não foi possível processar a solicitação. Tente novamente.');
            $this->redirect('/esqueci-senha');
        }

        $user = User::findByEmail($email);

        if ($user !== null && $user->active) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            User::invalidarPasswordResets($email);
            User::createPasswordResetToken($email, $tokenHash);

            $link = $this->urlAbsoluta('/redefinir-senha/' . $rawToken);
            $corpo = $this->view('emails.redefinir-senha', [
                'nomeUsuario' => $user->name,
                'link' => $link,
            ]);

            Mailer::enviar($user->email, $user->name, 'Recuperação de senha - KADOSYS Food', $corpo);
        }

        Session::flash(
            'forgot_status',
            'Se o e-mail informado estiver cadastrado, você receberá as instruções de recuperação em breve.'
        );
        $this->redirect('/esqueci-senha');
    }

    public function showResetPassword(string $token): void
    {
        $email = User::emailDoPasswordResetValido(hash('sha256', $token));

        if ($email === null) {
            Session::flash('login_error', 'Esse link de recuperação expirou ou já foi usado. Solicite um novo.');
            $this->redirect('/esqueci-senha');
        }

        echo $this->view('auth.redefinir-senha', [
            'pageTitle' => 'KADOSYS Food - Redefinir senha',
            'csrf' => Csrf::field(),
            'token' => $token,
            'errors' => Session::flash('reset_errors') ?? [],
        ], 'auth');
    }

    public function resetPassword(string $token): void
    {
        $csrfToken = $this->request->input('_csrf_token');
        $email = User::emailDoPasswordResetValido(hash('sha256', $token));

        if (!Csrf::verify($csrfToken) || $email === null) {
            Session::flash('login_error', 'Esse link de recuperação expirou ou já foi usado. Solicite um novo.');
            $this->redirect('/esqueci-senha');
        }

        $senha = (string) $this->request->input('password', '');
        $confirmacao = (string) $this->request->input('password_confirmation', '');

        if (mb_strlen($senha) < 6) {
            Session::flash('reset_errors', ['A senha precisa ter pelo menos 6 caracteres.']);
            $this->redirect('/redefinir-senha/' . $token);
        }

        if ($senha !== $confirmacao) {
            Session::flash('reset_errors', ['As senhas não coincidem.']);
            $this->redirect('/redefinir-senha/' . $token);
        }

        $user = User::findByEmail($email);

        if ($user === null) {
            Session::flash('login_error', 'Não foi possível redefinir a senha. Solicite um novo link.');
            $this->redirect('/esqueci-senha');
        }

        User::updatePassword($user->id, $user->restauranteId, $senha);
        User::invalidarPasswordResets($email);

        Session::flash('login_success', 'Senha redefinida com sucesso! Entre com sua nova senha.');
        $this->redirect('/login');
    }

    private function urlAbsoluta(string $path): string
    {
        $host = (string) ($this->request->server['HTTP_HOST'] ?? 'restaurantes.kadosys.com.br');
        $https = ($this->request->server['HTTPS'] ?? '') !== '' && ($this->request->server['HTTPS'] ?? '') !== 'off';

        return ($https ? 'https://' : 'http://') . $host . $this->url($path);
    }
}
