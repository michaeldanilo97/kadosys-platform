<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Fornecedor;
use Food\Models\Restaurante;
use Food\Models\User;

final class FornecedorController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Fornecedor::paginate($restauranteId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.fornecedores.index', [
            'pageTitle' => 'Fornecedores - KADOSYS Food',
            'activeMenu' => 'fornecedores',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'fornecedores' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('fornecedor_success'),
            'errors' => Session::flash('fornecedor_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.fornecedores.form', [
            'pageTitle' => 'Novo fornecedor - KADOSYS Food',
            'activeMenu' => 'fornecedores',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'fornecedor' => null,
            'old' => Session::flash('fornecedor_old') ?? [],
            'errors' => Session::flash('fornecedor_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fornecedores');
        }

        $dados = $this->request->only([
            'nome', 'telefone', 'whatsapp', 'email', 'contato', 'prazo_dias', 'forma_pagamento', 'observacoes',
        ]);
        [$errors, $prazoDias] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('fornecedor_errors', $errors);
            Session::flash('fornecedor_old', $dados);
            $this->redirect('/dashboard/fornecedores/novo');
        }

        Fornecedor::create(
            $this->restauranteId(),
            (string) $dados['nome'],
            $dados['telefone'] !== null ? (string) $dados['telefone'] : null,
            $dados['whatsapp'] !== null ? (string) $dados['whatsapp'] : null,
            $dados['email'] !== null ? (string) $dados['email'] : null,
            $dados['contato'] !== null ? (string) $dados['contato'] : null,
            $prazoDias,
            $dados['forma_pagamento'] !== null ? (string) $dados['forma_pagamento'] : null,
            $dados['observacoes'] !== null ? (string) $dados['observacoes'] : null,
        );

        Session::flash('fornecedor_success', 'Fornecedor cadastrado com sucesso.');
        $this->redirect('/dashboard/fornecedores');
    }

    public function edit(string $id): void
    {
        $fornecedor = Fornecedor::find((int) $id, $this->restauranteId());

        if ($fornecedor === null) {
            $this->redirect('/dashboard/fornecedores');
        }

        echo $this->view('dashboard.fornecedores.form', [
            'pageTitle' => 'Editar fornecedor - KADOSYS Food',
            'activeMenu' => 'fornecedores',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'fornecedor' => $fornecedor,
            'old' => Session::flash('fornecedor_old') ?? [],
            'errors' => Session::flash('fornecedor_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Fornecedor::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/fornecedores');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fornecedores');
        }

        $dados = $this->request->only([
            'nome', 'telefone', 'whatsapp', 'email', 'contato', 'prazo_dias', 'forma_pagamento', 'observacoes',
        ]);
        [$errors, $prazoDias] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('fornecedor_errors', $errors);
            Session::flash('fornecedor_old', $dados);
            $this->redirect('/dashboard/fornecedores/' . $id . '/editar');
        }

        Fornecedor::update(
            (int) $id,
            $restauranteId,
            (string) $dados['nome'],
            $dados['telefone'] !== null ? (string) $dados['telefone'] : null,
            $dados['whatsapp'] !== null ? (string) $dados['whatsapp'] : null,
            $dados['email'] !== null ? (string) $dados['email'] : null,
            $dados['contato'] !== null ? (string) $dados['contato'] : null,
            $prazoDias,
            $dados['forma_pagamento'] !== null ? (string) $dados['forma_pagamento'] : null,
            $dados['observacoes'] !== null ? (string) $dados['observacoes'] : null,
        );

        Session::flash('fornecedor_success', 'Fornecedor atualizado com sucesso.');
        $this->redirect('/dashboard/fornecedores');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Fornecedor::delete((int) $id, $this->restauranteId());
            Session::flash('fornecedor_success', 'Fornecedor removido.');
        }

        $this->redirect('/dashboard/fornecedores');
    }

    /** @return array{0: array<int, string>, 1: int|null} */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do fornecedor.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        }

        $prazoInformado = trim((string) ($dados['prazo_dias'] ?? ''));
        $prazoDias = null;

        if ($prazoInformado !== '') {
            if (!ctype_digit($prazoInformado)) {
                $errors[] = 'O prazo de entrega precisa ser um número de dias válido.';
            } else {
                $prazoDias = (int) $prazoInformado;
            }
        }

        return [$errors, $prazoDias];
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
