<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\Membro;
use Igrejas\Models\Ministerio;

/**
 * Controller do modulo Ministerios: cadastro, listagem com busca e
 * paginacao, edicao, remocao e gestao de lider/voluntarios (membros
 * vinculados a cada ministerio).
 */
final class MinisterioController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Ministerio::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.ministerios.index', [
            'pageTitle' => 'Ministerios - KADOSYS Igrejas',
            'activeMenu' => 'ministerios',
            'breadcrumb' => ['Dashboard', 'Ministerios'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'ministerios' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('ministerio_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.ministerios.form', [
            'pageTitle' => 'Novo ministerio - KADOSYS Igrejas',
            'activeMenu' => 'ministerios',
            'breadcrumb' => ['Dashboard', 'Ministerios', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'ministerio' => null,
            'voluntarios' => [],
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('ministerio_old') ?? [],
            'errors' => Session::flash('ministerio_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('ministerio_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/ministerios/novo');
        }

        $data = $this->request->only(['nome', 'descricao', 'lider_membro_id', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('ministerio_errors', $errors);
            Session::flash('ministerio_old', $data);
            $this->redirect('/dashboard/ministerios/novo');
        }

        Ministerio::create($data);

        Session::flash('ministerio_success', 'Ministerio cadastrado com sucesso.');
        $this->redirect('/dashboard/ministerios');
    }

    public function edit(string $id): void
    {
        $ministerio = Ministerio::find((int) $id);

        if (!$ministerio) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.ministerios.form', [
            'pageTitle' => 'Editar ' . $ministerio->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'ministerios',
            'breadcrumb' => ['Dashboard', 'Ministerios', $ministerio->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'ministerio' => $ministerio,
            'voluntarios' => Ministerio::voluntarios($ministerio->id),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('ministerio_old') ?? [],
            'errors' => Session::flash('ministerio_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!Ministerio::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('ministerio_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect("/dashboard/ministerios/{$id}/editar");
        }

        $data = $this->request->only(['nome', 'descricao', 'lider_membro_id', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('ministerio_errors', $errors);
            Session::flash('ministerio_old', $data);
            $this->redirect("/dashboard/ministerios/{$id}/editar");
        }

        Ministerio::update((int) $id, $data);

        Session::flash('ministerio_success', 'Ministerio atualizado com sucesso.');
        $this->redirect('/dashboard/ministerios');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Ministerio::delete((int) $id);
            Session::flash('ministerio_success', 'Ministerio removido com sucesso.');
        }

        $this->redirect('/dashboard/ministerios');
    }

    public function addVoluntario(string $id): void
    {
        $membroId = (int) $this->request->input('membro_id', 0);

        if (Csrf::verify($this->request->input('_csrf_token')) && $membroId > 0) {
            Ministerio::addVoluntario((int) $id, $membroId);
            Session::flash('ministerio_success', 'Voluntario adicionado ao ministerio.');
        }

        $this->redirect("/dashboard/ministerios/{$id}/editar");
    }

    public function removeVoluntario(string $id, string $membroId): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Ministerio::removeVoluntario((int) $id, (int) $membroId);
            Session::flash('ministerio_success', 'Voluntario removido do ministerio.');
        }

        $this->redirect("/dashboard/ministerios/{$id}/editar");
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome do ministerio.';
        }

        $status = $data['status'] ?? 'ativo';
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $errors[] = 'Status invalido.';
        }

        $liderId = trim((string) ($data['lider_membro_id'] ?? ''));
        if ($liderId !== '' && Membro::find((int) $liderId) === null) {
            $errors[] = 'Lider selecionado invalido.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
            'activeMenu' => 'ministerios',
            'breadcrumb' => ['Dashboard', 'Ministerios', 'Nao encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
