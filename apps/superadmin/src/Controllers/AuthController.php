<?php

declare(strict_types=1);

namespace Superadmin\Controllers;

use Superadmin\Core\Controller;
use Superadmin\Core\Csrf;
use Superadmin\Core\Middleware\AuthMiddleware;
use Superadmin\Core\Session;

/**
 * Login/logout do Super Admin - chave mestra unica (ver config/auth.php),
 * mesmo padrao ja usado no painel /plataforma do Igrejas.
 */
final class AuthController extends Controller
{
    public function entrar(): void
    {
        if (Session::get(AuthMiddleware::SESSION_KEY) === true) {
            $this->redirect('/sites');
        }

        echo $this->view('auth.entrar', [
            'pageTitle' => 'Entrar - KADOSYS Super Admin',
            'csrf' => Csrf::field(),
            'error' => Session::flash('login_error'),
        ], 'auth');
    }

    public function autenticar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('login_error', 'Sessão expirada. Tente novamente.');
            $this->redirect('/entrar');
        }

        $chave = (string) $this->request->input('chave', '');
        $config = require dirname(__DIR__, 2) . '/config/auth.php';

        if ($config['senha_hash'] === '' || !password_verify($chave, $config['senha_hash'])) {
            Session::flash('login_error', 'Chave inválida.');
            $this->redirect('/entrar');
        }

        Session::regenerate();
        Session::set(AuthMiddleware::SESSION_KEY, true);

        $this->redirect('/sites');
    }

    public function sair(): void
    {
        Session::remove(AuthMiddleware::SESSION_KEY);
        $this->redirect('/entrar');
    }
}
