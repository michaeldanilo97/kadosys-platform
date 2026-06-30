<?php

declare(strict_types=1);

namespace Kadosys\Apps\Igrejas\Controllers;

use Kadosys\Core\Http\Controller;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Security\Session;

/**
 * AuthController
 *
 * Controller responsavel pelo fluxo de autenticacao do aplicativo
 * "igrejas": exibicao do formulario de login, tentativa de login
 * e logout. Toda a logica de autenticacao em si pertence ao
 * servico Auth do Core; este Controller apenas orquestra a
 * interacao HTTP.
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth.login');
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $remember = (bool) $request->input('remember', false);

        $success = $this->auth()->attempt($email, $password, $remember);

        if (!$success) {
            Session::put('_login_error', 'Credenciais invalidas. Verifique seu e-mail e senha.');

            return $this->redirect(url('/login'));
        }

        return $this->redirect(url('/dashboard'));
    }

    public function logout(Request $request): Response
    {
        $this->auth()->logout();

        return $this->redirect(url('/login'));
    }
}
