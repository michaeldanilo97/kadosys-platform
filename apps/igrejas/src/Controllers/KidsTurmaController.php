<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\KidsTurma;
use Igrejas\Models\Membro;

/**
 * Controller do modulo KADOSYS Kids > Turmas: cadastro, listagem com
 * busca/paginacao, edicao e remocao das turmas do ministerio infantil.
 * Mesma estrutura do GrupoController, sem a gestao de participantes
 * (aqui e a crianca que aponta pra turma, ver KidsCriancaController).
 */
final class KidsTurmaController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = KidsTurma::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.kids.turmas.index', [
            'pageTitle' => 'Turmas Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Turmas'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'turmas' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('kids_turma_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.kids.turmas.form', [
            'pageTitle' => 'Nova turma Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Turmas', 'Nova'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'turma' => null,
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('kids_turma_old') ?? [],
            'errors' => Session::flash('kids_turma_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_turma_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/kids/turmas/novo');
        }

        $data = $this->request->only(['nome', 'faixa_etaria_min', 'faixa_etaria_max', 'professor_membro_id', 'descricao', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('kids_turma_errors', $errors);
            Session::flash('kids_turma_old', $data);
            $this->redirect('/dashboard/kids/turmas/novo');
        }

        KidsTurma::create($data);

        Session::flash('kids_turma_success', 'Turma cadastrada com sucesso.');
        $this->redirect('/dashboard/kids/turmas');
    }

    public function edit(string $id): void
    {
        $turma = KidsTurma::find((int) $id);

        if (!$turma) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.kids.turmas.form', [
            'pageTitle' => 'Editar ' . $turma->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Turmas', $turma->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'turma' => $turma,
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('kids_turma_old') ?? [],
            'errors' => Session::flash('kids_turma_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!KidsTurma::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_turma_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/kids/turmas/{$id}/editar");
        }

        $data = $this->request->only(['nome', 'faixa_etaria_min', 'faixa_etaria_max', 'professor_membro_id', 'descricao', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('kids_turma_errors', $errors);
            Session::flash('kids_turma_old', $data);
            $this->redirect("/dashboard/kids/turmas/{$id}/editar");
        }

        KidsTurma::update((int) $id, $data);

        Session::flash('kids_turma_success', 'Turma atualizada com sucesso.');
        $this->redirect('/dashboard/kids/turmas');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            KidsTurma::delete((int) $id);
            Session::flash('kids_turma_success', 'Turma removida com sucesso.');
        }

        $this->redirect('/dashboard/kids/turmas');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome da turma.';
        }

        $min = trim((string) ($data['faixa_etaria_min'] ?? ''));
        $max = trim((string) ($data['faixa_etaria_max'] ?? ''));
        if ($min !== '' && $max !== '' && (int) $min > (int) $max) {
            $errors[] = 'A idade mínima não pode ser maior que a máxima.';
        }

        $professorId = trim((string) ($data['professor_membro_id'] ?? ''));
        if ($professorId !== '' && Membro::find((int) $professorId) === null) {
            $errors[] = 'Professor selecionado inválido.';
        }

        $status = $data['status'] ?? 'ativo';
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $errors[] = 'Status inválido.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Turmas', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
