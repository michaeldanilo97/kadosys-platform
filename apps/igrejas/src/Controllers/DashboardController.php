<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Culto;
use Igrejas\Models\FinanceiroLancamento;
use Igrejas\Models\Membro;
use Igrejas\Models\Ministerio;
use Igrejas\Models\Plano;

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
     * @return array<string, array{title:string, icon:string, description:string, planoMinimo:string}>
     */
    public static function modules(): array
    {
        $modules = [
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
            'projecao' => [
                'title' => 'Projecao',
                'icon' => 'bi-easel2',
                'description' => 'Telao do culto: biblia, videos e controle do preletor em tempo real.',
            ],
            'playbacks' => [
                'title' => 'Playbacks',
                'icon' => 'bi-music-note-beamed',
                'description' => 'Biblioteca de playbacks para o ministerio de louvor, liberada em todos os planos.',
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

        foreach ($modules as $slug => &$module) {
            $module['planoMinimo'] = Plano::minimoParaModulo($slug);
        }

        return $modules;
    }

    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $inicioDoMes = new \DateTimeImmutable('first day of this month 00:00:00');
        $planoAtual = ConfiguracaoIgreja::atual()->plano;
        $emTrial = TenantResolver::atual()?->metodoPagamento === 'trial';
        $financeiroDisponivel = Plano::disponivel($planoAtual, 'financeiro', $emTrial);

        echo $this->view('dashboard.index', [
            'pageTitle' => 'Dashboard - KADOSYS Igrejas',
            'activeMenu' => 'dashboard',
            'breadcrumb' => ['Dashboard'],
            'user' => $user,
            'modules' => self::modules(),
            'membrosAtivos' => Membro::countActive(),
            'novosMembros' => Membro::countCreatedSince($inicioDoMes),
            'ministeriosAtivos' => Ministerio::countActive(),
            'proximoCulto' => Culto::proximoAgendado(),
            'financeiroDisponivel' => $financeiroDisponivel,
            'financeiroTotais' => $financeiroDisponivel ? FinanceiroLancamento::totaisMesAtual() : null,
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

    /**
     * Tela exibida quando o usuario tenta acessar um modulo que nao faz
     * parte do plano contratado da igreja (ver PlanoMiddleware, que
     * redireciona pra ca em vez de deixar a pagina do modulo carregar).
     */
    public function planoBloqueado(): void
    {
        $modules = self::modules();
        $slug = (string) $this->request->input('modulo', '');
        $module = $modules[$slug] ?? null;

        if ($module === null) {
            $this->redirect('/dashboard');
        }

        echo $this->view('dashboard.plano-bloqueado', [
            'pageTitle' => $module['title'] . ' - KADOSYS Igrejas',
            'activeMenu' => $slug,
            'breadcrumb' => ['Dashboard', $module['title']],
            'user' => (new Auth($this->config))->user(),
            'modules' => $modules,
            'module' => $module,
            'planoAtual' => ConfiguracaoIgreja::atual()->plano,
        ], 'dashboard');
    }
}
