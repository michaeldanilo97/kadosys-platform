<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\AgendaEvento;
use Igrejas\Models\Culto;
use Igrejas\Models\Membro;
use Igrejas\Models\User;

/**
 * Controller do modulo Agenda: calendario mensal (cultos + eventos +
 * aniversariantes) e a listagem/CRUD de eventos, reunioes e reservas
 * de espaco da igreja.
 *
 * Um evento pode ser publico (visivel pra todo mundo, ex.: ensaio do
 * grupo) ou privado (so quem criou ve, ex.: um compromisso pessoal) -
 * ver AgendaEvento::podeSerVistoPor().
 */
final class AgendaController extends Controller
{
    private const PER_PAGE = 15;

    public function calendario(): void
    {
        $user = (new Auth($this->config))->user();
        $mesParam = trim((string) $this->request->input('mes', ''));
        $referencia = \DateTime::createFromFormat('Y-m', $mesParam) ?: new \DateTime('first day of this month');
        $mes = $referencia->format('Y-m');

        echo $this->view('dashboard.agenda.calendario', [
            'pageTitle' => 'Agenda - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda'],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'mesReferencia' => $referencia,
            'mesAnterior' => (clone $referencia)->modify('-1 month')->format('Y-m'),
            'mesProximo' => (clone $referencia)->modify('+1 month')->format('Y-m'),
            'cultos' => Culto::noMes($mes),
            'eventos' => AgendaEvento::noMesVisiveisPara($mes, $user->id),
            'aniversariantes' => Membro::aniversariantesNoMes((int) $referencia->format('n')),
            'success' => Session::flash('agenda_success'),
        ], 'dashboard');
    }

    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = AgendaEvento::paginate($page, self::PER_PAGE, $search, $user->id);

        echo $this->view('dashboard.agenda.index', [
            'pageTitle' => 'Agenda - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda', 'Lista'],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'eventos' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('agenda_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        $user = (new Auth($this->config))->user();

        echo $this->view('dashboard.agenda.form', [
            'pageTitle' => 'Novo evento - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda', 'Novo'],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'evento' => null,
            'membrosAtivos' => Membro::allActive(),
            'podeTornarPublico' => User::podeAcessarModulo($user, 'agenda', User::NIVEL_EDITAR),
            'old' => Session::flash('agenda_old') ?? [],
            'errors' => Session::flash('agenda_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('agenda_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/agenda/novo');
        }

        $data = $this->request->only(['titulo', 'tipo', 'data', 'hora_inicio', 'hora_fim', 'local', 'responsavel_membro_id', 'descricao', 'status', 'visibilidade']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('agenda_errors', $errors);
            Session::flash('agenda_old', $data);
            $this->redirect('/dashboard/agenda/novo');
        }

        $user = (new Auth($this->config))->user();
        AgendaEvento::create($data, $user->id);

        Session::flash('agenda_success', 'Evento cadastrado com sucesso.');
        $this->redirect('/dashboard/agenda');
    }

    public function edit(string $id): void
    {
        $evento = AgendaEvento::find((int) $id);
        $user = (new Auth($this->config))->user();

        if (!$evento || !$this->podeGerenciar($evento, $user)) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.agenda.form', [
            'pageTitle' => 'Editar ' . $evento->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda', $evento->titulo],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'evento' => $evento,
            'membrosAtivos' => Membro::allActive(),
            'podeTornarPublico' => User::podeAcessarModulo($user, 'agenda', User::NIVEL_EDITAR),
            'old' => Session::flash('agenda_old') ?? [],
            'errors' => Session::flash('agenda_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $evento = AgendaEvento::find((int) $id);
        $user = (new Auth($this->config))->user();

        if (!$evento || !$this->podeGerenciar($evento, $user)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('agenda_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/agenda/{$id}/editar");
        }

        $data = $this->request->only(['titulo', 'tipo', 'data', 'hora_inicio', 'hora_fim', 'local', 'responsavel_membro_id', 'descricao', 'status', 'visibilidade']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('agenda_errors', $errors);
            Session::flash('agenda_old', $data);
            $this->redirect("/dashboard/agenda/{$id}/editar");
        }

        // Quem so pode mexer aqui por ser dono de um evento privado (ver
        // AuthMiddleware::podeGerenciarEventoPrivado, sem nivel "editar"
        // liberado no modulo) nao pode aproveitar essa brecha pra tornar
        // o proprio evento publico - isso exigiria nivel "editar" de
        // verdade, senao vira uma forma de burlar a permissao.
        if (!User::podeAcessarModulo($user, 'agenda', User::NIVEL_EDITAR)) {
            $data['visibilidade'] = 'privado';
        }

        AgendaEvento::update((int) $id, $data);

        Session::flash('agenda_success', 'Evento atualizado com sucesso.');
        $this->redirect('/dashboard/agenda');
    }

    public function destroy(string $id): void
    {
        $evento = AgendaEvento::find((int) $id);
        $user = (new Auth($this->config))->user();

        if ($evento && $this->podeGerenciar($evento, $user) && Csrf::verify($this->request->input('_csrf_token'))) {
            AgendaEvento::delete((int) $id);
            Session::flash('agenda_success', 'Evento removido com sucesso.');
        }

        $this->redirect('/dashboard/agenda');
    }

    /**
     * Um evento privado so pode ser editado/excluido por quem criou (ou
     * por um admin) - eventos publicos continuam abertos pra qualquer
     * usuario logado gerenciar, como sempre foi neste modulo.
     */
    private function podeGerenciar(AgendaEvento $evento, User $user): bool
    {
        if (!$evento->ehPrivado()) {
            return true;
        }

        return $evento->criadoPorUserId === $user->id || $user->role === User::ROLE_ADMIN;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['titulo'] ?? '')) === '') {
            $errors[] = 'Informe o título do evento.';
        }

        $dataEvento = trim((string) ($data['data'] ?? ''));
        if ($dataEvento === '' || \DateTime::createFromFormat('Y-m-d', $dataEvento) === false) {
            $errors[] = 'Informe uma data válida.';
        }

        $responsavelId = trim((string) ($data['responsavel_membro_id'] ?? ''));
        if ($responsavelId !== '' && Membro::find((int) $responsavelId) === null) {
            $errors[] = 'Responsável selecionado inválido.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
