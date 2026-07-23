<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Compra;
use Food\Models\CompraItem;
use Food\Models\Fornecedor;
use Food\Models\Ingrediente;
use Food\Models\Produto;
use Food\Models\Restaurante;
use Food\Models\User;

final class CompraController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));

        $resultado = Compra::paginate($restauranteId, $page, self::POR_PAGINA);
        $fornecedoresPorId = [];

        foreach (Fornecedor::doRestaurante($restauranteId) as $fornecedor) {
            $fornecedoresPorId[$fornecedor->id] = $fornecedor->nome;
        }

        echo $this->view('dashboard.compras.index', [
            'pageTitle' => 'Compras - KADOSYS Food',
            'activeMenu' => 'compras',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'compras' => $resultado['items'],
            'fornecedoresPorId' => $fornecedoresPorId,
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'success' => Session::flash('compra_success'),
            'errors' => Session::flash('compra_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        $restauranteId = $this->restauranteId();

        echo $this->view('dashboard.compras.form', [
            'pageTitle' => 'Nova compra - KADOSYS Food',
            'activeMenu' => 'compras',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'fornecedores' => Fornecedor::doRestaurante($restauranteId),
            'old' => Session::flash('compra_old') ?? [],
            'errors' => Session::flash('compra_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/compras');
        }

        $restauranteId = $this->restauranteId();
        $dados = $this->request->only(['fornecedor_id', 'data_compra', 'frete', 'observacao']);

        $errors = [];
        $fornecedorIdInformado = trim((string) ($dados['fornecedor_id'] ?? ''));
        $fornecedorId = null;

        if ($fornecedorIdInformado !== '') {
            $fornecedorId = ctype_digit($fornecedorIdInformado) ? (int) $fornecedorIdInformado : 0;

            if (Fornecedor::find($fornecedorId, $restauranteId) === null) {
                $errors[] = 'Fornecedor inválido.';
                $fornecedorId = null;
            }
        }

        $dataCompra = trim((string) ($dados['data_compra'] ?? ''));

        if (!$this->dataValida($dataCompra)) {
            $errors[] = 'Informe uma data de compra válida.';
        }

        $freteInformado = str_replace(',', '.', (string) ($dados['frete'] ?? '0'));
        $frete = is_numeric($freteInformado) ? (float) $freteInformado : -1;

        if ($frete < 0) {
            $errors[] = 'Informe um valor de frete válido.';
        }

        if ($errors !== []) {
            Session::flash('compra_errors', $errors);
            Session::flash('compra_old', $dados);
            $this->redirect('/dashboard/compras/nova');
        }

        $observacao = trim((string) ($dados['observacao'] ?? ''));

        $id = Compra::create($restauranteId, $fornecedorId, $dataCompra, max(0.0, $frete), $observacao !== '' ? $observacao : null);

        $this->redirect('/dashboard/compras/' . $id);
    }

    public function show(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $compra = Compra::find((int) $id, $restauranteId);

        if ($compra === null) {
            $this->redirect('/dashboard/compras');
        }

        $fornecedor = $compra->fornecedorId !== null ? Fornecedor::find($compra->fornecedorId, $restauranteId) : null;

        echo $this->view('dashboard.compras.show', [
            'pageTitle' => 'Compra #' . $compra->id . ' - KADOSYS Food',
            'activeMenu' => 'compras',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'compra' => $compra,
            'fornecedor' => $fornecedor,
            'itens' => CompraItem::doCompra($compra->id),
            'ingredientes' => Ingrediente::ativos($restauranteId),
            'success' => Session::flash('compra_item_success'),
            'errors' => Session::flash('compra_item_errors') ?? [],
        ], 'dashboard');
    }

    public function itemAdicionar(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $compra = Compra::find((int) $id, $restauranteId);

        if ($compra === null) {
            $this->redirect('/dashboard/compras');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/compras/' . $id);
        }

        $ingredienteId = (int) $this->request->input('ingrediente_id', 0);
        $quantidadeInformada = str_replace(',', '.', (string) $this->request->input('quantidade', ''));
        $quantidade = is_numeric($quantidadeInformada) ? (float) $quantidadeInformada : -1;
        $precoInformado = str_replace(',', '.', (string) $this->request->input('preco_unitario', ''));
        $precoUnitario = is_numeric($precoInformado) ? (float) $precoInformado : -1;
        $validadeInformada = trim((string) $this->request->input('validade', ''));

        $ingrediente = Ingrediente::find($ingredienteId, $restauranteId);
        $errors = [];

        if ($ingrediente === null) {
            $errors[] = 'Escolha um ingrediente válido.';
        }

        if ($quantidade <= 0) {
            $errors[] = 'Informe uma quantidade válida.';
        }

        if ($precoUnitario < 0) {
            $errors[] = 'Informe um preço unitário válido.';
        }

        $validade = null;

        if ($validadeInformada !== '') {
            if (!$this->dataValida($validadeInformada)) {
                $errors[] = 'Informe uma data de validade válida.';
            } else {
                $validade = $validadeInformada;
            }
        }

        if ($errors !== []) {
            Session::flash('compra_item_errors', $errors);
            $this->redirect('/dashboard/compras/' . $id);
        }

        CompraItem::create($compra->id, $restauranteId, $ingredienteId, $quantidade, $ingrediente->unidade, $precoUnitario, $validade, $compra->dataCompra);

        // Preco do ingrediente pode ter mudado - recalcula em cascata o
        // custo/preco ideal de todo produto que usa esse ingrediente,
        // mesmo padrao de IngredienteController::update().
        Produto::recalcularCustoDeProdutosComIngrediente($ingredienteId, $restauranteId);

        Session::flash('compra_item_success', 'Item adicionado à compra.');
        $this->redirect('/dashboard/compras/' . $id);
    }

    private function dataValida(string $data): bool
    {
        $partida = \DateTimeImmutable::createFromFormat('Y-m-d', $data);

        return $partida !== false && $partida->format('Y-m-d') === $data;
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
