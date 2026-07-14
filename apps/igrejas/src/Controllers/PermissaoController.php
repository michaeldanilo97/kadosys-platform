<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\User;

/**
 * Controller do modulo Permissoes: restringe, por usuario (papel
 * 'usuario'), quais modulos do plano contratado ficam liberados - ver
 * User::podeAcessarModulo() pra regra completa. Exclusivo do plano
 * Premium (ver Plano::MODULO_MINIMO['permissoes']).
 */
final class PermissaoController extends Controller
{
    public function index(): void
    {
        $usuarios = array_values(array_filter(User::all(), static fn (User $usuario) => $usuario->role === User::ROLE_USUARIO));

        echo $this->view('dashboard.permissoes.index', [
            'pageTitle' => 'Permissões - KADOSYS Igrejas',
            'activeMenu' => 'permissoes',
            'breadcrumb' => ['Dashboard', 'Permissões'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'usuarios' => $usuarios,
        ], 'dashboard');
    }

    public function edit(string $id): void
    {
        $usuarioEditado = $this->buscarUsuarioOuFalhar((int) $id);

        if ($usuarioEditado === null) {
            return;
        }

        echo $this->view('dashboard.permissoes.form', [
            'pageTitle' => 'Permissões de ' . $usuarioEditado->name . ' - KADOSYS Igrejas',
            'activeMenu' => 'permissoes',
            'breadcrumb' => ['Dashboard', 'Permissões', $usuarioEditado->name],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'usuarioEditado' => $usuarioEditado,
            'modulosDisponiveis' => DashboardController::modulosConfiguraveisParaPermissoes(),
            'modulosPermitidos' => User::modulosPermitidos($usuarioEditado->id),
            'success' => Session::flash('permissao_success'),
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $usuarioEditado = $this->buscarUsuarioOuFalhar((int) $id);

        if ($usuarioEditado === null) {
            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect("/dashboard/permissoes/{$id}/editar");
        }

        $modulosValidos = array_keys(DashboardController::modulosConfiguraveisParaPermissoes());
        $niveisEnviados = (array) $this->request->input('modulos', []);

        $modulosComNivel = [];
        foreach ($niveisEnviados as $slug => $nivel) {
            if (!in_array($slug, $modulosValidos, true) || !in_array($nivel, [User::NIVEL_VISUALIZAR, User::NIVEL_EDITAR], true)) {
                continue;
            }

            $modulosComNivel[$slug] = $nivel;
        }

        User::definirModulosPermitidos($usuarioEditado->id, $modulosComNivel);

        Session::flash('permissao_success', 'Permissões de ' . $usuarioEditado->name . ' atualizadas com sucesso.');
        $this->redirect("/dashboard/permissoes/{$id}/editar");
    }

    private function buscarUsuarioOuFalhar(int $id): ?User
    {
        $usuarioEditado = User::findById($id);

        if ($usuarioEditado === null || $usuarioEditado->role !== User::ROLE_USUARIO) {
            Session::flash('permissao_errors', ['Usuário inválido.']);
            $this->redirect('/dashboard/permissoes');
        }

        return $usuarioEditado;
    }
}
