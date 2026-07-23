<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Categoria;
use Food\Models\Restaurante;
use Food\Models\User;

final class CategoriaController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Categoria::paginate($restauranteId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.categorias.index', [
            'pageTitle' => 'Categorias - KADOSYS Food',
            'activeMenu' => 'categorias',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'categorias' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('categoria_success'),
            'errors' => Session::flash('categoria_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.categorias.form', [
            'pageTitle' => 'Nova categoria - KADOSYS Food',
            'activeMenu' => 'categorias',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'categoria' => null,
            'old' => Session::flash('categoria_old') ?? [],
            'errors' => Session::flash('categoria_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/categorias');
        }

        $dados = $this->request->only(['nome']);
        [$errors] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('categoria_errors', $errors);
            Session::flash('categoria_old', $dados);
            $this->redirect('/dashboard/categorias/nova');
        }

        Categoria::create($this->restauranteId(), (string) $dados['nome']);

        Session::flash('categoria_success', 'Categoria cadastrada com sucesso.');
        $this->redirect('/dashboard/categorias');
    }

    public function edit(string $id): void
    {
        $categoria = Categoria::find((int) $id, $this->restauranteId());

        if ($categoria === null) {
            $this->redirect('/dashboard/categorias');
        }

        echo $this->view('dashboard.categorias.form', [
            'pageTitle' => 'Editar categoria - KADOSYS Food',
            'activeMenu' => 'categorias',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'categoria' => $categoria,
            'old' => Session::flash('categoria_old') ?? [],
            'errors' => Session::flash('categoria_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Categoria::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/categorias');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/categorias');
        }

        $dados = $this->request->only(['nome']);
        [$errors] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('categoria_errors', $errors);
            Session::flash('categoria_old', $dados);
            $this->redirect('/dashboard/categorias/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Categoria::update((int) $id, $restauranteId, (string) $dados['nome'], $ativo);

        Session::flash('categoria_success', 'Categoria atualizada com sucesso.');
        $this->redirect('/dashboard/categorias');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Categoria::delete((int) $id, $this->restauranteId());
            Session::flash('categoria_success', 'Categoria removida.');
        }

        $this->redirect('/dashboard/categorias');
    }

    /** @return array{0: array<int, string>} */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome da categoria.';
        }

        return [$errors];
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function restauranteId(): int
    {
        return $this->usuario()?->restauranteId ?? 0;
    }
}
