<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Plano;
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
            'modulosDisponiveis' => $this->modulosConfiguraveis(),
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

        $modulosValidos = array_keys($this->modulosConfiguraveis());
        $modulosSelecionados = array_values(array_intersect((array) $this->request->input('modulos', []), $modulosValidos));

        User::definirModulosPermitidos($usuarioEditado->id, $modulosSelecionados);

        Session::flash('permissao_success', 'Permissões de ' . $usuarioEditado->name . ' atualizadas com sucesso.');
        $this->redirect("/dashboard/permissoes/{$id}/editar");
    }

    /**
     * Modulos configuraveis por Permissoes: exclui os exclusivos de
     * admin (nunca configuraveis, ver User::MODULOS_SOMENTE_ADMIN) e os
     * que o plano atual da igreja nem libera (restringir algo ja
     * bloqueado pelo plano nao faz sentido) - respeitando o trial
     * gratis, que libera tudo temporariamente (mesma regra usada em
     * todo o resto do painel, ver AuthMiddleware/PlanoMiddleware).
     *
     * @return array<string, array{title:string, icon:string}>
     */
    private function modulosConfiguraveis(): array
    {
        $planoAtual = ConfiguracaoIgreja::atual()->plano;
        $emTrial = TenantResolver::atual()?->metodoPagamento === 'trial';
        $modulos = [];

        foreach (DashboardController::modules() as $slug => $modulo) {
            if (in_array($slug, User::MODULOS_SOMENTE_ADMIN, true)) {
                continue;
            }

            // Louvores nao e configuravel aqui - o acesso e todo-ou-nada
            // via a flag "musico" (ver tela de Usuarios), nao uma
            // liberacao manual por usuario como os demais modulos.
            if (in_array($slug, User::MODULOS_SOMENTE_MUSICO, true)) {
                continue;
            }

            if (!Plano::disponivel($planoAtual, $slug, $emTrial)) {
                continue;
            }

            $modulos[$slug] = $modulo;
        }

        return $modulos;
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
