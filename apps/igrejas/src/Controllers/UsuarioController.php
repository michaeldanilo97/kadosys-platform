<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\User;

/**
 * Controller do modulo Usuarios: cadastro, edicao e remocao de
 * usuarios com acesso ao sistema. So acessivel por 'admin' (ver
 * AuthMiddleware::bloquearSePermissaoNegada e
 * User::MODULOS_SOMENTE_ADMIN) - papeis e permissoes por modulo ficam
 * no modulo Permissoes (ver PermissaoController).
 */
final class UsuarioController extends Controller
{
    public function index(): void
    {
        echo $this->view('dashboard.usuarios.index', [
            'pageTitle' => 'Usuarios - KADOSYS Igrejas',
            'activeMenu' => 'usuarios',
            'breadcrumb' => ['Dashboard', 'Usuarios'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'usuarios' => User::all(),
            'usuarioAtualId' => (new Auth($this->config))->user()?->id,
            'success' => Session::flash('usuario_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.usuarios.form', [
            'pageTitle' => 'Novo usuario - KADOSYS Igrejas',
            'activeMenu' => 'usuarios',
            'breadcrumb' => ['Dashboard', 'Usuarios', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'usuarioEditado' => null,
            'old' => Session::flash('usuario_old') ?? [],
            'errors' => Session::flash('usuario_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('usuario_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/usuarios/novo');
        }

        $data = $this->request->only(['name', 'email', 'password', 'password_confirmacao', 'role']);
        $errors = $this->validate($data, null);

        if ($errors !== []) {
            Session::flash('usuario_errors', $errors);
            Session::flash('usuario_old', $data);
            $this->redirect('/dashboard/usuarios/novo');
        }

        User::create($data);

        Session::flash('usuario_success', 'Usuario cadastrado com sucesso.');
        $this->redirect('/dashboard/usuarios');
    }

    public function edit(string $id): void
    {
        $usuarioEditado = $this->buscarOuFalhar((int) $id);

        if ($usuarioEditado === null) {
            return;
        }

        echo $this->view('dashboard.usuarios.form', [
            'pageTitle' => 'Editar ' . $usuarioEditado->name . ' - KADOSYS Igrejas',
            'activeMenu' => 'usuarios',
            'breadcrumb' => ['Dashboard', 'Usuarios', $usuarioEditado->name],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'usuarioEditado' => $usuarioEditado,
            'old' => Session::flash('usuario_old') ?? [],
            'errors' => Session::flash('usuario_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $usuarioEditado = $this->buscarOuFalhar((int) $id);

        if ($usuarioEditado === null) {
            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('usuario_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect("/dashboard/usuarios/{$id}/editar");
        }

        $data = $this->request->only(['name', 'email', 'password', 'password_confirmacao', 'role', 'active']);
        $errors = $this->validate($data, $usuarioEditado);

        $souEuMesmo = $usuarioEditado->id === (new Auth($this->config))->user()?->id;

        // Nao da pra se auto-rebaixar/desativar - evita um admin se
        // trancar pra fora sem querer.
        if ($souEuMesmo && ((string) ($data['role'] ?? '') !== User::ROLE_ADMIN || empty($data['active']))) {
            $errors[] = 'Voce nao pode remover o proprio acesso de administrador nem se desativar.';
        }

        if (
            $usuarioEditado->role === User::ROLE_ADMIN
            && $usuarioEditado->active
            && (((string) ($data['role'] ?? '')) !== User::ROLE_ADMIN || empty($data['active']))
            && User::countAdminsAtivos() <= 1
        ) {
            $errors[] = 'Precisa haver pelo menos um administrador ativo - promova outro usuario antes de rebaixar ou desativar este.';
        }

        if ($errors !== []) {
            Session::flash('usuario_errors', $errors);
            Session::flash('usuario_old', $data);
            $this->redirect("/dashboard/usuarios/{$id}/editar");
        }

        $usuarioEditado->update($data);

        Session::flash('usuario_success', 'Usuario atualizado com sucesso.');
        $this->redirect('/dashboard/usuarios');
    }

    public function destroy(string $id): void
    {
        $usuarioEditado = $this->buscarOuFalhar((int) $id);

        if ($usuarioEditado === null) {
            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/usuarios');
        }

        if ($usuarioEditado->id === (new Auth($this->config))->user()?->id) {
            Session::flash('usuario_errors', ['Voce nao pode excluir a propria conta.']);
            $this->redirect('/dashboard/usuarios');
        }

        if ($usuarioEditado->role === User::ROLE_ADMIN && $usuarioEditado->active && User::countAdminsAtivos() <= 1) {
            Session::flash('usuario_errors', ['Precisa haver pelo menos um administrador ativo - promova outro usuario antes de excluir este.']);
            $this->redirect('/dashboard/usuarios');
        }

        User::delete($usuarioEditado->id);

        Session::flash('usuario_success', 'Usuario removido com sucesso.');
        $this->redirect('/dashboard/usuarios');
    }

    private function buscarOuFalhar(int $id): ?User
    {
        $usuarioEditado = User::findById($id);

        if ($usuarioEditado === null) {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
                'activeMenu' => 'usuarios',
                'breadcrumb' => ['Dashboard', 'Usuarios', 'Nao encontrado'],
                'user' => (new Auth($this->config))->user(),
                'modules' => DashboardController::modules(),
            ], 'dashboard');
        }

        return $usuarioEditado;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data, ?User $usuarioEditado): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = 'Informe o nome do usuario.';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail valido.';
        } elseif (User::emailEmUso($email, $usuarioEditado?->id)) {
            $errors[] = 'Ja existe um usuario com esse e-mail.';
        }

        if (!in_array($data['role'] ?? null, [User::ROLE_ADMIN, User::ROLE_USUARIO], true)) {
            $errors[] = 'Papel invalido.';
        }

        $senha = (string) ($data['password'] ?? '');
        $senhaConfirmacao = (string) ($data['password_confirmacao'] ?? '');

        // Na criacao a senha e obrigatoria; na edicao, so se o admin
        // quiser trocar (campo em branco mantem a senha atual).
        if ($usuarioEditado === null || $senha !== '') {
            if (mb_strlen($senha) < 8) {
                $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
            } elseif ($senha !== $senhaConfirmacao) {
                $errors[] = 'A confirmacao de senha nao confere.';
            }
        }

        return $errors;
    }
}
