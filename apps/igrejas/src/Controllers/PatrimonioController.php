<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\PatrimonioBem;

/**
 * Controller do modulo Patrimonio: bens, imoveis e equipamentos da
 * igreja, com listagem com busca e paginacao, edicao e remocao.
 */
final class PatrimonioController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = PatrimonioBem::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.patrimonio.index', [
            'pageTitle' => 'Patrimonio - KADOSYS Igrejas',
            'activeMenu' => 'patrimonio',
            'breadcrumb' => ['Dashboard', 'Patrimonio'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'bens' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'valorTotalAtivo' => PatrimonioBem::valorTotalAtivo(),
            'countAtivos' => PatrimonioBem::countAtivos(),
            'success' => Session::flash('patrimonio_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.patrimonio.form', [
            'pageTitle' => 'Novo bem - KADOSYS Igrejas',
            'activeMenu' => 'patrimonio',
            'breadcrumb' => ['Dashboard', 'Patrimonio', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'bem' => null,
            'old' => Session::flash('patrimonio_old') ?? [],
            'errors' => Session::flash('patrimonio_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('patrimonio_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/patrimonio/novo');
        }

        $data = $this->request->only(['nome', 'categoria', 'numero_patrimonio', 'descricao', 'valor_estimado', 'data_aquisicao', 'local', 'status', 'observacoes']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('patrimonio_errors', $errors);
            Session::flash('patrimonio_old', $data);
            $this->redirect('/dashboard/patrimonio/novo');
        }

        PatrimonioBem::create($data);

        Session::flash('patrimonio_success', 'Bem cadastrado com sucesso.');
        $this->redirect('/dashboard/patrimonio');
    }

    public function edit(string $id): void
    {
        $bem = PatrimonioBem::find((int) $id);

        if (!$bem) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.patrimonio.form', [
            'pageTitle' => 'Editar ' . $bem->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'patrimonio',
            'breadcrumb' => ['Dashboard', 'Patrimonio', $bem->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'bem' => $bem,
            'old' => Session::flash('patrimonio_old') ?? [],
            'errors' => Session::flash('patrimonio_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!PatrimonioBem::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('patrimonio_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect("/dashboard/patrimonio/{$id}/editar");
        }

        $data = $this->request->only(['nome', 'categoria', 'numero_patrimonio', 'descricao', 'valor_estimado', 'data_aquisicao', 'local', 'status', 'observacoes']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('patrimonio_errors', $errors);
            Session::flash('patrimonio_old', $data);
            $this->redirect("/dashboard/patrimonio/{$id}/editar");
        }

        PatrimonioBem::update((int) $id, $data);

        Session::flash('patrimonio_success', 'Bem atualizado com sucesso.');
        $this->redirect('/dashboard/patrimonio');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            PatrimonioBem::delete((int) $id);
            Session::flash('patrimonio_success', 'Bem removido com sucesso.');
        }

        $this->redirect('/dashboard/patrimonio');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome do bem.';
        }

        $valor = trim((string) ($data['valor_estimado'] ?? ''));
        if ($valor !== '' && (float) str_replace(',', '.', $valor) < 0) {
            $errors[] = 'O valor estimado nao pode ser negativo.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
            'activeMenu' => 'patrimonio',
            'breadcrumb' => ['Dashboard', 'Patrimonio', 'Nao encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
