<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\AgendaEvento;
use Igrejas\Models\Membro;

/**
 * Controller do modulo Agenda: eventos, reunioes e reservas de espaco
 * da igreja, com listagem com busca e paginacao, edicao e remocao.
 */
final class AgendaController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = AgendaEvento::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.agenda.index', [
            'pageTitle' => 'Agenda - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda'],
            'user' => (new Auth($this->config))->user(),
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
        echo $this->view('dashboard.agenda.form', [
            'pageTitle' => 'Novo evento - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'evento' => null,
            'membrosAtivos' => Membro::allActive(),
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

        $data = $this->request->only(['titulo', 'tipo', 'data', 'hora_inicio', 'hora_fim', 'local', 'responsavel_membro_id', 'descricao', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('agenda_errors', $errors);
            Session::flash('agenda_old', $data);
            $this->redirect('/dashboard/agenda/novo');
        }

        AgendaEvento::create($data);

        Session::flash('agenda_success', 'Evento cadastrado com sucesso.');
        $this->redirect('/dashboard/agenda');
    }

    public function edit(string $id): void
    {
        $evento = AgendaEvento::find((int) $id);

        if (!$evento) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.agenda.form', [
            'pageTitle' => 'Editar ' . $evento->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'agenda',
            'breadcrumb' => ['Dashboard', 'Agenda', $evento->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'evento' => $evento,
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('agenda_old') ?? [],
            'errors' => Session::flash('agenda_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!AgendaEvento::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('agenda_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/agenda/{$id}/editar");
        }

        $data = $this->request->only(['titulo', 'tipo', 'data', 'hora_inicio', 'hora_fim', 'local', 'responsavel_membro_id', 'descricao', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('agenda_errors', $errors);
            Session::flash('agenda_old', $data);
            $this->redirect("/dashboard/agenda/{$id}/editar");
        }

        AgendaEvento::update((int) $id, $data);

        Session::flash('agenda_success', 'Evento atualizado com sucesso.');
        $this->redirect('/dashboard/agenda');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            AgendaEvento::delete((int) $id);
            Session::flash('agenda_success', 'Evento removido com sucesso.');
        }

        $this->redirect('/dashboard/agenda');
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
