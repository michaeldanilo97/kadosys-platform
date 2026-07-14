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
            'pageTitle' => 'Usuários - KADOSYS Igrejas',
            'activeMenu' => 'usuarios',
            'breadcrumb' => ['Dashboard', 'Usuários'],
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
            'pageTitle' => 'Novo usuário - KADOSYS Igrejas',
            'activeMenu' => 'usuarios',
            'breadcrumb' => ['Dashboard', 'Usuários', 'Novo'],
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
            Session::flash('usuario_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/usuarios/novo');
        }

        $data = $this->request->only(['name', 'email', 'password', 'password_confirmacao', 'role', 'musico', 'lider_louvor', 'cargo', 'instrumento']);
        $errors = $this->validate($data, null);

        if ($errors !== []) {
            Session::flash('usuario_errors', $errors);
            Session::flash('usuario_old', $data);
            $this->redirect('/dashboard/usuarios/novo');
        }

        $userId = User::create($data);

        if (($data['role'] ?? null) === User::ROLE_USUARIO) {
            User::aplicarPerfilPadrao($userId);
        }

        Session::flash('usuario_success', 'Usuário cadastrado com sucesso.');
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
            'breadcrumb' => ['Dashboard', 'Usuários', $usuarioEditado->name],
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
            Session::flash('usuario_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/usuarios/{$id}/editar");
        }

        $data = $this->request->only(['name', 'email', 'password', 'password_confirmacao', 'role', 'active', 'musico', 'lider_louvor', 'cargo', 'instrumento']);
        $errors = $this->validate($data, $usuarioEditado);

        $souEuMesmo = $usuarioEditado->id === (new Auth($this->config))->user()?->id;

        // Nao da pra se auto-rebaixar/desativar - evita um admin se
        // trancar pra fora sem querer.
        if ($souEuMesmo && ((string) ($data['role'] ?? '') !== User::ROLE_ADMIN || empty($data['active']))) {
            $errors[] = 'Você não pode remover o próprio acesso de administrador nem se desativar.';
        }

        if (
            $usuarioEditado->role === User::ROLE_ADMIN
            && $usuarioEditado->active
            && (((string) ($data['role'] ?? '')) !== User::ROLE_ADMIN || empty($data['active']))
            && User::countAdminsAtivos() <= 1
        ) {
            $errors[] = 'Precisa haver pelo menos um administrador ativo - promova outro usuário antes de rebaixar ou desativar este.';
        }

        if ($errors !== []) {
            Session::flash('usuario_errors', $errors);
            Session::flash('usuario_old', $data);
            $this->redirect("/dashboard/usuarios/{$id}/editar");
        }

        $usuarioEditado->update($data);

        Session::flash('usuario_success', 'Usuário atualizado com sucesso.');
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
            Session::flash('usuario_errors', ['Você não pode excluir a própria conta.']);
            $this->redirect('/dashboard/usuarios');
        }

        if ($usuarioEditado->role === User::ROLE_ADMIN && $usuarioEditado->active && User::countAdminsAtivos() <= 1) {
            Session::flash('usuario_errors', ['Precisa haver pelo menos um administrador ativo - promova outro usuário antes de excluir este.']);
            $this->redirect('/dashboard/usuarios');
        }

        User::delete($usuarioEditado->id);

        Session::flash('usuario_success', 'Usuário removido com sucesso.');
        $this->redirect('/dashboard/usuarios');
    }

    private function buscarOuFalhar(int $id): ?User
    {
        $usuarioEditado = User::findById($id);

        if ($usuarioEditado === null) {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
                'activeMenu' => 'usuarios',
                'breadcrumb' => ['Dashboard', 'Usuários', 'Não encontrado'],
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
            $errors[] = 'Informe o nome do usuário.';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        } elseif (User::emailEmUso($email, $usuarioEditado?->id)) {
            $errors[] = 'Já existe um usuário com esse e-mail.';
        }

        if (!in_array($data['role'] ?? null, [User::ROLE_ADMIN, User::ROLE_USUARIO], true)) {
            $errors[] = 'Papel inválido.';
        }

        $senha = (string) ($data['password'] ?? '');
        $senhaConfirmacao = (string) ($data['password_confirmacao'] ?? '');

        // Na criacao a senha e obrigatoria; na edicao, so se o admin
        // quiser trocar (campo em branco mantem a senha atual).
        if ($usuarioEditado === null || $senha !== '') {
            if (mb_strlen($senha) < 8) {
                $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
            } elseif ($senha !== $senhaConfirmacao) {
                $errors[] = 'A confirmação de senha não confere.';
            }
        }

        return $errors;
    }
}
