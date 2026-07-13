<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\Grupo;
use Igrejas\Models\Membro;

/**
 * Controller do modulo Grupos: cadastro, listagem com busca e
 * paginacao, edicao, remocao e gestao de lider/participantes (membros
 * vinculados a cada grupo). Mesma estrutura do MinisterioController.
 */
final class GrupoController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Grupo::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.grupos.index', [
            'pageTitle' => 'Grupos - KADOSYS Igrejas',
            'activeMenu' => 'grupos',
            'breadcrumb' => ['Dashboard', 'Grupos'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'grupos' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('grupo_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.grupos.form', [
            'pageTitle' => 'Novo grupo - KADOSYS Igrejas',
            'activeMenu' => 'grupos',
            'breadcrumb' => ['Dashboard', 'Grupos', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'grupo' => null,
            'participantes' => [],
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('grupo_old') ?? [],
            'errors' => Session::flash('grupo_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('grupo_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/grupos/novo');
        }

        $data = $this->request->only(['nome', 'tipo', 'descricao', 'lider_membro_id', 'dia_semana', 'horario', 'local', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('grupo_errors', $errors);
            Session::flash('grupo_old', $data);
            $this->redirect('/dashboard/grupos/novo');
        }

        Grupo::create($data);

        Session::flash('grupo_success', 'Grupo cadastrado com sucesso.');
        $this->redirect('/dashboard/grupos');
    }

    public function edit(string $id): void
    {
        $grupo = Grupo::find((int) $id);

        if (!$grupo) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.grupos.form', [
            'pageTitle' => 'Editar ' . $grupo->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'grupos',
            'breadcrumb' => ['Dashboard', 'Grupos', $grupo->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'grupo' => $grupo,
            'participantes' => Grupo::participantes($grupo->id),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('grupo_old') ?? [],
            'errors' => Session::flash('grupo_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!Grupo::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('grupo_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/grupos/{$id}/editar");
        }

        $data = $this->request->only(['nome', 'tipo', 'descricao', 'lider_membro_id', 'dia_semana', 'horario', 'local', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('grupo_errors', $errors);
            Session::flash('grupo_old', $data);
            $this->redirect("/dashboard/grupos/{$id}/editar");
        }

        Grupo::update((int) $id, $data);

        Session::flash('grupo_success', 'Grupo atualizado com sucesso.');
        $this->redirect('/dashboard/grupos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Grupo::delete((int) $id);
            Session::flash('grupo_success', 'Grupo removido com sucesso.');
        }

        $this->redirect('/dashboard/grupos');
    }

    public function addParticipante(string $id): void
    {
        $membroId = (int) $this->request->input('membro_id', 0);

        if (Csrf::verify($this->request->input('_csrf_token')) && $membroId > 0) {
            Grupo::addParticipante((int) $id, $membroId);
            Session::flash('grupo_success', 'Participante adicionado ao grupo.');
        }

        $this->redirect("/dashboard/grupos/{$id}/editar");
    }

    public function removeParticipante(string $id, string $membroId): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Grupo::removeParticipante((int) $id, (int) $membroId);
            Session::flash('grupo_success', 'Participante removido do grupo.');
        }

        $this->redirect("/dashboard/grupos/{$id}/editar");
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome do grupo.';
        }

        $tipo = $data['tipo'] ?? 'grupo';
        if (!in_array($tipo, ['celula', 'classe', 'grupo'], true)) {
            $errors[] = 'Tipo inválido.';
        }

        $status = $data['status'] ?? 'ativo';
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $errors[] = 'Status inválido.';
        }

        $liderId = trim((string) ($data['lider_membro_id'] ?? ''));
        if ($liderId !== '' && Membro::find((int) $liderId) === null) {
            $errors[] = 'Líder selecionado inválido.';
        }

        $diaSemana = trim((string) ($data['dia_semana'] ?? ''));
        if ($diaSemana !== '' && !in_array($diaSemana, ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'], true)) {
            $errors[] = 'Dia da semana inválido.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'grupos',
            'breadcrumb' => ['Dashboard', 'Grupos', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
