<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;

/**
 * Controller do Dashboard administrativo.
 *
 * Nesta etapa (Sprint 1) somente a estrutura das paginas do menu lateral
 * e criada, conforme escopo definido. As funcionalidades de cada modulo
 * (Membros, Ministerios, Financeiro, etc.) serao implementadas em sprints
 * futuras. Para evitar duplicacao de codigo, todas as paginas "em
 * construcao" sao renderizadas por um unico metodo (page) a partir de um
 * mapa central de modulos do menu.
 */
final class DashboardController extends Controller
{
    /**
     * Mapa central dos modulos do menu lateral: slug => [titulo, icone, descricao].
     * Unica fonte de verdade usada tanto para montar o menu quanto para
     * renderizar a pagina de estrutura de cada modulo.
     *
     * @return array<string, array{title:string, icon:string, description:string}>
     */
    public static function modules(): array
    {
        return [
            'membros' => [
                'title' => 'Membros',
                'icon' => 'bi-people',
                'description' => 'Cadastro, historico e acompanhamento dos membros da igreja.',
            ],
            'ministerios' => [
                'title' => 'Ministerios',
                'icon' => 'bi-diagram-3',
                'description' => 'Organizacao dos ministerios e seus respectivos lideres e voluntarios.',
            ],
            'grupos' => [
                'title' => 'Grupos',
                'icon' => 'bi-people-fill',
                'description' => 'Pequenos grupos, celulas e classes, com encontros e participantes.',
            ],
            'cultos' => [
                'title' => 'Cultos',
                'icon' => 'bi-calendar2-week',
                'description' => 'Programacao, escalas e registro de frequencia dos cultos.',
            ],
            'agenda' => [
                'title' => 'Agenda',
                'icon' => 'bi-calendar3',
                'description' => 'Eventos, compromissos e reserva de espacos da igreja.',
            ],
            'financeiro' => [
                'title' => 'Financeiro',
                'icon' => 'bi-cash-coin',
                'description' => 'Dizimos, ofertas, despesas e relatorios financeiros.',
            ],
            'patrimonio' => [
                'title' => 'Patrimonio',
                'icon' => 'bi-building',
                'description' => 'Controle de bens, imoveis e equipamentos da igreja.',
            ],
            'comunicacao' => [
                'title' => 'Comunicacao',
                'icon' => 'bi-megaphone',
                'description' => 'Avisos, mensagens e comunicados para membros e lideranca.',
            ],
            'relatorios' => [
                'title' => 'Relatorios',
                'icon' => 'bi-bar-chart-line',
                'description' => 'Indicadores e relatorios consolidados da igreja.',
            ],
            'usuarios' => [
                'title' => 'Usuarios',
                'icon' => 'bi-person-badge',
                'description' => 'Gestao dos usuarios com acesso ao sistema.',
            ],
            'permissoes' => [
                'title' => 'Permissoes',
                'icon' => 'bi-shield-lock',
                'description' => 'Perfis de acesso e permissoes por modulo.',
            ],
            'configuracoes' => [
                'title' => 'Configuracoes',
                'icon' => 'bi-gear',
                'description' => 'Dados da igreja, preferencias e configuracoes gerais do sistema.',
            ],
        ];
    }

    public function index(): void
    {
        $user = (new Auth($this->config))->user();

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Dashboard - KADOSYS Igrejas',
            'activeMenu' => 'dashboard',
            'breadcrumb' => ['Dashboard'],
            'user' => $user,
            'modules' => self::modules(),
        ], 'dashboard');
    }

    public function page(string $slug): void
    {
        $modules = self::modules();

        if (!isset($modules[$slug])) {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
                'activeMenu' => '',
                'breadcrumb' => ['Dashboard', 'Pagina nao encontrada'],
                'user' => (new Auth($this->config))->user(),
                'modules' => $modules,
            ], 'dashboard');

            return;
        }

        $user = (new Auth($this->config))->user();
        $module = $modules[$slug];

        echo $this->view('dashboard.placeholder', [
            'pageTitle' => $module['title'] . ' - KADOSYS Igrejas',
            'activeMenu' => $slug,
            'breadcrumb' => ['Dashboard', $module['title']],
            'user' => $user,
            'modules' => $modules,
            'module' => $module,
        ], 'dashboard');
    }
}
