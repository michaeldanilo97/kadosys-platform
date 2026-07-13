<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\Membro;

/**
 * Controller do modulo Membros: primeiro modulo de negocio da v2
 * (cadastro, listagem com busca/paginacao, edicao e remocao).
 */
final class MembroController extends Controller
{
    private const PER_PAGE = 15;

    private const FIELDS = [
        'nome', 'email', 'telefone', 'data_nascimento', 'genero',
        'estado_civil', 'endereco', 'cep', 'cidade', 'estado', 'data_membresia',
        'status', 'observacoes',
    ];

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Membro::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.membros.index', [
            'pageTitle' => 'Membros - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membros' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('membro_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.membros.form', [
            'pageTitle' => 'Novo membro - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membro' => null,
            'old' => Session::flash('membro_old') ?? [],
            'errors' => Session::flash('membro_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('membro_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/membros/novo');
        }

        $data = $this->request->only(self::FIELDS);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('membro_errors', $errors);
            Session::flash('membro_old', $data);
            $this->redirect('/dashboard/membros/novo');
        }

        Membro::create($data);

        Session::flash('membro_success', 'Membro cadastrado com sucesso.');
        $this->redirect('/dashboard/membros');
    }

    public function edit(string $id): void
    {
        $membro = Membro::find((int) $id);

        if (!$membro) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.membros.form', [
            'pageTitle' => 'Editar ' . $membro->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros', $membro->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membro' => $membro,
            'old' => Session::flash('membro_old') ?? [],
            'errors' => Session::flash('membro_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!Membro::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('membro_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/membros/{$id}/editar");
        }

        $data = $this->request->only(self::FIELDS);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('membro_errors', $errors);
            Session::flash('membro_old', $data);
            $this->redirect("/dashboard/membros/{$id}/editar");
        }

        Membro::update((int) $id, $data);

        Session::flash('membro_success', 'Membro atualizado com sucesso.');
        $this->redirect('/dashboard/membros');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Membro::delete((int) $id);
            Session::flash('membro_success', 'Membro removido com sucesso.');
        }

        $this->redirect('/dashboard/membros');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome do membro.';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
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
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
