<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Caixa;
use Barbearias\Models\FinanceiroLancamento;
use Barbearias\Models\Produto;
use Barbearias\Models\User;

final class ProdutoController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Produto::paginate($barbeariaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.produtos.index', [
            'pageTitle' => 'Produtos - KADOSYS Barbearias',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'produtos' => $resultado['items'],
            'estoqueBaixo' => Produto::comEstoqueBaixo($barbeariaId),
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'formasPagamento' => FinanceiroLancamento::FORMAS_PAGAMENTO,
            'success' => Session::flash('produto_success'),
            'errors' => Session::flash('produto_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.produtos.form', [
            'pageTitle' => 'Novo produto - KADOSYS Barbearias',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'produto' => null,
            'old' => Session::flash('produto_old') ?? [],
            'errors' => Session::flash('produto_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/produtos');
        }

        $dados = $this->request->only(['nome', 'preco', 'estoque_atual', 'estoque_minimo']);
        [$errors, $preco, $estoqueAtual, $estoqueMinimo] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('produto_errors', $errors);
            Session::flash('produto_old', $dados);
            $this->redirect('/dashboard/produtos/novo');
        }

        Produto::create($this->barbeariaId(), (string) $dados['nome'], $preco, $estoqueAtual, $estoqueMinimo);

        Session::flash('produto_success', 'Produto cadastrado com sucesso.');
        $this->redirect('/dashboard/produtos');
    }

    public function edit(string $id): void
    {
        $produto = Produto::find((int) $id, $this->barbeariaId());

        if ($produto === null) {
            $this->redirect('/dashboard/produtos');
        }

        echo $this->view('dashboard.produtos.form', [
            'pageTitle' => 'Editar produto - KADOSYS Barbearias',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'produto' => $produto,
            'old' => Session::flash('produto_old') ?? [],
            'errors' => Session::flash('produto_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Produto::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/produtos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/produtos');
        }

        $dados = $this->request->only(['nome', 'preco', 'estoque_atual', 'estoque_minimo']);
        [$errors, $preco, $estoqueAtual, $estoqueMinimo] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('produto_errors', $errors);
            Session::flash('produto_old', $dados);
            $this->redirect('/dashboard/produtos/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Produto::update((int) $id, $barbeariaId, (string) $dados['nome'], $preco, $estoqueAtual, $estoqueMinimo, $ativo);

        Session::flash('produto_success', 'Produto atualizado com sucesso.');
        $this->redirect('/dashboard/produtos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Produto::delete((int) $id, $this->barbeariaId());
            Session::flash('produto_success', 'Produto removido.');
        }

        $this->redirect('/dashboard/produtos');
    }

    /**
     * Venda avulsa (nao vinculada a agendamento): baixa o estoque de
     * forma atomica e, se sobrou estoque suficiente, registra um
     * lancamento de receita no financeiro (dentro do caixa aberto, se
     * houver um).
     */
    public function vender(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $produto = Produto::find((int) $id, $barbeariaId);

        if ($produto === null) {
            $this->redirect('/dashboard/produtos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/produtos');
        }

        $quantidade = (int) $this->request->input('quantidade', 0);
        $formaPagamento = (string) $this->request->input('forma_pagamento', '');
        $errors = [];

        if ($quantidade < 1) {
            $errors[] = 'Informe uma quantidade válida pra venda.';
        }

        if (!in_array($formaPagamento, FinanceiroLancamento::FORMAS_PAGAMENTO, true)) {
            $errors[] = 'Escolha uma forma de pagamento válida.';
        }

        if ($errors === [] && !Produto::baixarEstoque($produto->id, $barbeariaId, $quantidade)) {
            $errors[] = 'Estoque insuficiente pra essa quantidade.';
        }

        if ($errors !== []) {
            Session::flash('produto_errors', $errors);
            $this->redirect('/dashboard/produtos');
        }

        $caixa = Caixa::aberto($barbeariaId);

        FinanceiroLancamento::create(
            $barbeariaId,
            $caixa?->id,
            null,
            $this->usuario()?->id,
            FinanceiroLancamento::TIPO_RECEITA,
            'Produto - ' . $produto->nome,
            $formaPagamento,
            $produto->preco * $quantidade,
            $quantidade . 'x ' . $produto->nome,
            (new \DateTimeImmutable())->format('Y-m-d'),
            $produto->id,
            $quantidade,
        );

        Session::flash('produto_success', 'Venda registrada: ' . $quantidade . 'x ' . $produto->nome . '.');
        $this->redirect('/dashboard/produtos');
    }

    /** @return array{0: array<int, string>, 1: float, 2: int, 3: int} */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do produto.';
        }

        $precoInformado = str_replace(',', '.', (string) ($dados['preco'] ?? ''));
        $preco = is_numeric($precoInformado) ? (float) $precoInformado : -1;

        if ($preco < 0) {
            $errors[] = 'Informe um preço válido.';
        }

        $estoqueAtual = (int) ($dados['estoque_atual'] ?? 0);
        $estoqueMinimo = (int) ($dados['estoque_minimo'] ?? 0);

        if ($estoqueAtual < 0) {
            $errors[] = 'O estoque atual não pode ser negativo.';
        }

        if ($estoqueMinimo < 0) {
            $errors[] = 'O estoque mínimo não pode ser negativo.';
        }

        return [$errors, max(0, $preco), max(0, $estoqueAtual), max(0, $estoqueMinimo)];
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
