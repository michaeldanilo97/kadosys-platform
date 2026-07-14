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
use Igrejas\Models\PlataformaAviso;
use Igrejas\Models\User;

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
                'description' => 'Cadastro, histórico e acompanhamento dos membros da igreja.',
            ],
            'equipe' => [
                'title' => 'Equipe',
                'icon' => 'bi-grid-3x3-gap-fill',
                'description' => 'Galeria com foto, cargo e instrumento de quem tem acesso ao sistema.',
            ],
            'ministerios' => [
                'title' => 'Ministérios',
                'icon' => 'bi-diagram-3',
                'description' => 'Organização dos ministérios e seus respectivos líderes e voluntários.',
            ],
            'grupos' => [
                'title' => 'Grupos',
                'icon' => 'bi-people-fill',
                'description' => 'Pequenos grupos, células e classes, com encontros e participantes.',
            ],
            'cultos' => [
                'title' => 'Cultos',
                'icon' => 'bi-calendar2-week',
                'description' => 'Programação, escalas e registro de frequência dos cultos.',
            ],
            'projecao' => [
                'title' => 'Projeção',
                'icon' => 'bi-easel2',
                'description' => 'Telão do culto: bíblia, vídeos e controle do preletor em tempo real.',
            ],
            'playbacks' => [
                'title' => 'Playbacks',
                'icon' => 'bi-music-note-beamed',
                'description' => 'Biblioteca de playbacks para o ministério de louvor, liberada em todos os planos.',
            ],
            'louvores' => [
                'title' => 'Louvores',
                'icon' => 'bi-music-note-list',
                'description' => 'Letras, cifras e tons dos louvores, com histórico de mudanças - acesso liberado para músicos.',
            ],
            'agenda' => [
                'title' => 'Agenda',
                'icon' => 'bi-calendar3',
                'description' => 'Eventos, compromissos e reserva de espaços da igreja.',
            ],
            'financeiro' => [
                'title' => 'Financeiro',
                'icon' => 'bi-cash-coin',
                'description' => 'Dízimos, ofertas, despesas e relatórios financeiros.',
            ],
            'patrimonio' => [
                'title' => 'Patrimônio',
                'icon' => 'bi-building',
                'description' => 'Controle de bens, imóveis e equipamentos da igreja.',
            ],
            'comunicacao' => [
                'title' => 'Comunicação',
                'icon' => 'bi-megaphone',
                'description' => 'Avisos, mensagens e comunicados para membros e liderança.',
            ],
            'relatorios' => [
                'title' => 'Relatórios',
                'icon' => 'bi-bar-chart-line',
                'description' => 'Indicadores e relatórios consolidados da igreja.',
            ],
            'usuarios' => [
                'title' => 'Usuários',
                'icon' => 'bi-person-badge',
                'description' => 'Gestão dos usuários com acesso ao sistema.',
            ],
            'permissoes' => [
                'title' => 'Permissões',
                'icon' => 'bi-shield-lock',
                'description' => 'Perfis de acesso e permissões por módulo.',
            ],
            'configuracoes' => [
                'title' => 'Configurações',
                'icon' => 'bi-gear',
                'description' => 'Dados da igreja, preferências e configurações gerais do sistema.',
            ],
            'faturas' => [
                'title' => 'Faturas',
                'icon' => 'bi-receipt-cutoff',
                'description' => 'Histórico de cobranças, vencimento e pagamento do plano contratado.',
            ],
        ];

        foreach ($modules as $slug => &$module) {
            $module['planoMinimo'] = Plano::minimoParaModulo($slug);
        }

        return $modules;
    }

    /**
     * Modulos que fazem sentido configurar por permissao individual
     * (tela Permissoes) ou por perfil padrao da igreja (Configuracoes >
     * Permissoes padrao): exclui os exclusivos de admin (nunca
     * configuraveis, ver User::MODULOS_SOMENTE_ADMIN), Louvores (acesso
     * todo-ou-nada via a flag "musico", nao uma liberacao manual) e o
     * que o plano atual da igreja nem libera - respeitando o trial
     * gratis, que libera tudo temporariamente (mesma regra usada em
     * todo o resto do painel).
     *
     * @return array<string, array{title:string, icon:string}>
     */
    public static function modulosConfiguraveisParaPermissoes(): array
    {
        $planoAtual = ConfiguracaoIgreja::atual()->plano;
        $emTrial = TenantResolver::atual()?->metodoPagamento === 'trial';
        $modulos = [];

        foreach (self::modules() as $slug => $modulo) {
            if (in_array($slug, User::MODULOS_SOMENTE_ADMIN, true)) {
                continue;
            }

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
            'avisoPlataforma' => PlataformaAviso::ativoParaAudiencia($user?->role === User::ROLE_ADMIN),
        ], 'dashboard');
    }

    public function page(string $slug): void
    {
        $modules = self::modules();

        if (!isset($modules[$slug])) {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
                'activeMenu' => '',
                'breadcrumb' => ['Dashboard', 'Página não encontrada'],
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

    /**
     * Tela exibida quando um usuario com papel 'usuario' tenta acessar
     * um modulo fora da propria permissao (ver
     * AuthMiddleware::bloquearSePermissaoNegada, que redireciona pra ca
     * em vez de deixar a pagina do modulo carregar).
     */
    public function semPermissao(): void
    {
        $modules = self::modules();
        $slug = (string) $this->request->input('modulo', '');
        $module = $modules[$slug] ?? null;

        if ($module === null) {
            $this->redirect('/dashboard');
        }

        echo $this->view('dashboard.sem-permissao', [
            'pageTitle' => $module['title'] . ' - KADOSYS Igrejas',
            'activeMenu' => $slug,
            'breadcrumb' => ['Dashboard', $module['title']],
            'user' => (new Auth($this->config))->user(),
            'modules' => $modules,
            'module' => $module,
        ], 'dashboard');
    }
}
