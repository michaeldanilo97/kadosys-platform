<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\FinanceiroCategoria;
use Igrejas\Models\FinanceiroLancamento;
use Igrejas\Models\Membro;

/**
 * Controller do modulo Financeiro: lancamentos (dizimos, ofertas,
 * despesas) com filtro por tipo/categoria/mes/busca, totais do periodo
 * filtrado, e gestao das categorias usadas pra organizar os
 * lancamentos.
 */
final class FinanceiroController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $filtros = [
            'tipo' => (string) $this->request->input('tipo', ''),
            'categoria_id' => (string) $this->request->input('categoria_id', ''),
            'mes' => (string) $this->request->input('mes', date('Y-m')),
            'busca' => trim((string) $this->request->input('busca', '')),
        ];

        $result = FinanceiroLancamento::paginate($page, self::PER_PAGE, $filtros);
        $totais = FinanceiroLancamento::totais($filtros);

        echo $this->view('dashboard.financeiro.index', [
            'pageTitle' => 'Financeiro - KADOSYS Igrejas',
            'activeMenu' => 'financeiro',
            'breadcrumb' => ['Dashboard', 'Financeiro'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'lancamentos' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'filtros' => $filtros,
            'totais' => $totais,
            'categorias' => FinanceiroCategoria::all(),
            'success' => Session::flash('financeiro_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.financeiro.form', [
            'pageTitle' => 'Novo lancamento - KADOSYS Igrejas',
            'activeMenu' => 'financeiro',
            'breadcrumb' => ['Dashboard', 'Financeiro', 'Novo lancamento'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'lancamento' => null,
            'categorias' => FinanceiroCategoria::all(),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('financeiro_old') ?? [],
            'errors' => Session::flash('financeiro_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('financeiro_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/financeiro/novo');
        }

        $data = $this->request->only(['tipo', 'categoria_id', 'membro_id', 'descricao', 'valor', 'forma_pagamento', 'data_lancamento', 'observacoes']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('financeiro_errors', $errors);
            Session::flash('financeiro_old', $data);
            $this->redirect('/dashboard/financeiro/novo');
        }

        FinanceiroLancamento::create($data);

        Session::flash('financeiro_success', 'Lancamento cadastrado com sucesso.');
        $this->redirect('/dashboard/financeiro');
    }

    public function edit(string $id): void
    {
        $lancamento = FinanceiroLancamento::find((int) $id);

        if (!$lancamento) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.financeiro.form', [
            'pageTitle' => 'Editar lancamento - KADOSYS Igrejas',
            'activeMenu' => 'financeiro',
            'breadcrumb' => ['Dashboard', 'Financeiro', 'Editar'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'lancamento' => $lancamento,
            'categorias' => FinanceiroCategoria::all(),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('financeiro_old') ?? [],
            'errors' => Session::flash('financeiro_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!FinanceiroLancamento::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('financeiro_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect("/dashboard/financeiro/{$id}/editar");
        }

        $data = $this->request->only(['tipo', 'categoria_id', 'membro_id', 'descricao', 'valor', 'forma_pagamento', 'data_lancamento', 'observacoes']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('financeiro_errors', $errors);
            Session::flash('financeiro_old', $data);
            $this->redirect("/dashboard/financeiro/{$id}/editar");
        }

        FinanceiroLancamento::update((int) $id, $data);

        Session::flash('financeiro_success', 'Lancamento atualizado com sucesso.');
        $this->redirect('/dashboard/financeiro');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FinanceiroLancamento::delete((int) $id);
            Session::flash('financeiro_success', 'Lancamento removido com sucesso.');
        }

        $this->redirect('/dashboard/financeiro');
    }

    public function categorias(): void
    {
        echo $this->view('dashboard.financeiro.categorias', [
            'pageTitle' => 'Categorias - KADOSYS Igrejas',
            'activeMenu' => 'financeiro',
            'breadcrumb' => ['Dashboard', 'Financeiro', 'Categorias'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'categorias' => FinanceiroCategoria::all(),
            'success' => Session::flash('financeiro_success'),
            'errors' => Session::flash('financeiro_errors') ?? [],
            'csrfToken' => Csrf::token(),
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function storeCategoria(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('financeiro_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/financeiro/categorias');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $tipo = (string) $this->request->input('tipo', '');

        if ($nome === '') {
            Session::flash('financeiro_errors', ['Informe o nome da categoria.']);
            $this->redirect('/dashboard/financeiro/categorias');
        }

        if (!in_array($tipo, ['entrada', 'saida'], true)) {
            Session::flash('financeiro_errors', ['Escolha se a categoria e de entrada ou saida.']);
            $this->redirect('/dashboard/financeiro/categorias');
        }

        FinanceiroCategoria::create($nome, $tipo);

        Session::flash('financeiro_success', 'Categoria cadastrada com sucesso.');
        $this->redirect('/dashboard/financeiro/categorias');
    }

    public function alternarStatusCategoria(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FinanceiroCategoria::alternarStatus((int) $id);
        }

        $this->redirect('/dashboard/financeiro/categorias');
    }

    public function destroyCategoria(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FinanceiroCategoria::delete((int) $id);
            Session::flash('financeiro_success', 'Categoria removida com sucesso.');
        }

        $this->redirect('/dashboard/financeiro/categorias');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (!in_array($data['tipo'] ?? null, ['entrada', 'saida'], true)) {
            $errors[] = 'Escolha se o lancamento e uma entrada ou saida.';
        }

        if (trim((string) ($data['descricao'] ?? '')) === '') {
            $errors[] = 'Informe uma descricao para o lancamento.';
        }

        $valor = (float) str_replace(',', '.', (string) ($data['valor'] ?? '0'));
        if ($valor <= 0) {
            $errors[] = 'Informe um valor maior que zero.';
        }

        if (trim((string) ($data['data_lancamento'] ?? '')) === '') {
            $errors[] = 'Informe a data do lancamento.';
        }

        $categoriaId = trim((string) ($data['categoria_id'] ?? ''));
        if ($categoriaId !== '' && FinanceiroCategoria::find((int) $categoriaId) === null) {
            $errors[] = 'Categoria selecionada invalida.';
        }

        $membroId = trim((string) ($data['membro_id'] ?? ''));
        if ($membroId !== '' && Membro::find((int) $membroId) === null) {
            $errors[] = 'Membro selecionado invalido.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
            'activeMenu' => 'financeiro',
            'breadcrumb' => ['Dashboard', 'Financeiro', 'Nao encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
