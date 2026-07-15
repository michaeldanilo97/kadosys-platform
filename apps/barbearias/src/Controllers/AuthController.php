<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\User;

/**
 * Controller de autenticacao.
 */
final class AuthController extends Controller
{
    /**
     * Nao ha landing page publica ainda - a raiz da aplicacao so
     * redireciona pro login.
     */
    public function home(): void
    {
        $this->redirect('/login');
    }

    public function showLogin(): void
    {
        echo $this->view('auth.login', [
            'pageTitle' => 'KADOSYS Barbearias - Entrar',
            'csrf' => Csrf::field(),
            'error' => Session::flash('login_error'),
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
                !$usuario->active => 'Esse usuário está desativado. Fale com quem administra sua barbearia.',
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
}
