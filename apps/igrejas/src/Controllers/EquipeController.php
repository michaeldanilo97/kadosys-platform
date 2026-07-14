<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Models\User;

/**
 * Galeria da equipe (estilo rede social): nome, foto e cargo/instrumento
 * de cada usuario com acesso ao sistema E com um cargo de verdade na
 * equipe (musico, midia ou equipamento) - ver
 * User::todosAtivosParaEquipe(). Quem tem so o cargo padrao "membro"
 * (sem funcao definida, ex.: um admin que so usa o sistema) nao entra
 * aqui - o card dela pertence ao modulo Membros, nao a este.
 */
final class EquipeController extends Controller
{
    public function index(): void
    {
        echo $this->view('dashboard.equipe.index', [
            'pageTitle' => 'Equipe - KADOSYS Igrejas',
            'activeMenu' => 'equipe',
            'breadcrumb' => ['Dashboard', 'Equipe'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membrosEquipe' => User::todosAtivosParaEquipe(),
        ], 'dashboard');
    }

    /**
     * Perfil de uma pessoa da equipe, aberto ao clicar no nome/foto na
     * galeria - somente leitura aqui (edicao de cargo/foto continua em
     * PerfilController quando e a propria pessoa, e edicao de
     * role/senha/permissoes continua em Usuarios/Permissoes, restritas
     * a admin). So exibe pessoas ativas E com cargo de verdade na
     * equipe, mesmo escopo de User::todosAtivosParaEquipe() - quem
     * esta com o cargo padrao "membro" nao tem perfil aqui.
     */
    public function show(string $id): void
    {
        $pessoa = User::findById((int) $id);

        if ($pessoa === null || !$pessoa->active || $pessoa->cargo === User::CARGO_MEMBRO) {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
                'activeMenu' => 'equipe',
                'breadcrumb' => ['Dashboard', 'Equipe', 'Não encontrado'],
                'user' => (new Auth($this->config))->user(),
                'modules' => DashboardController::modules(),
            ], 'dashboard');

            return;
        }

        $logado = (new Auth($this->config))->user();

        echo $this->view('dashboard.equipe.show', [
            'pageTitle' => $pessoa->name . ' - Equipe - KADOSYS Igrejas',
            'activeMenu' => 'equipe',
            'breadcrumb' => ['Dashboard', 'Equipe', $pessoa->name],
            'user' => $logado,
            'modules' => DashboardController::modules(),
            'pessoa' => $pessoa,
            'ehAdmin' => $logado?->role === User::ROLE_ADMIN,
            'acessoModulos' => $this->resumoAcesso($pessoa),
        ], 'dashboard');
    }

    /**
     * Resumo, so pra exibicao, de quais modulos essa pessoa acessa e
     * com qual nivel - reflete a mesma regra de User::podeAcessarModulo(),
     * mas sem checar plano/trial modulo a modulo de novo (ja filtrado por
     * DashboardController::modulosConfiguraveisParaPermissoes()).
     *
     * @return array<int, array{title:string, icon:string, nivel:string}>
     */
    private function resumoAcesso(User $pessoa): array
    {
        if ($pessoa->role === User::ROLE_ADMIN) {
            return [];
        }

        $modulosConfiguraveis = DashboardController::modulosConfiguraveisParaPermissoes();
        $permitidos = User::modulosPermitidos($pessoa->id);
        $semRestricao = $permitidos === [];

        $resumo = [];
        foreach ($modulosConfiguraveis as $slug => $modulo) {
            $nivel = $semRestricao ? User::NIVEL_EDITAR : ($permitidos[$slug] ?? null);

            if ($nivel === null) {
                continue;
            }

            $resumo[] = [
                'title' => $modulo['title'],
                'icon' => $modulo['icon'],
                'nivel' => $nivel,
            ];
        }

        return $resumo;
    }
}
