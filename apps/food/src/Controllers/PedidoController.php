<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Cliente;
use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\Produto;
use Food\Models\Restaurante;
use Food\Models\User;

final class PedidoController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));
        $statusInformado = (string) $this->request->input('status', '');
        $status = in_array($statusInformado, $this->statusValidos(), true) ? $statusInformado : null;

        $resultado = Pedido::paginate($restauranteId, $page, self::POR_PAGINA, $search, $status);

        echo $this->view('dashboard.pedidos.index', [
            'pageTitle' => 'Pedidos - KADOSYS Food',
            'activeMenu' => 'pedidos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'pedidos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'statusSelecionado' => $status,
            'success' => Session::flash('pedido_success'),
            'errors' => Session::flash('pedido_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        $restauranteId = $this->restauranteId();

        echo $this->view('dashboard.pedidos.form', [
            'pageTitle' => 'Novo pedido - KADOSYS Food',
            'activeMenu' => 'pedidos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'clientes' => Cliente::ativos($restauranteId),
            'old' => Session::flash('pedido_old') ?? [],
            'errors' => Session::flash('pedido_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/pedidos');
        }

        $restauranteId = $this->restauranteId();
        $dados = $this->request->only(['cliente_id', 'origem', 'forma_pagamento', 'endereco_entrega', 'cupom', 'desconto', 'observacoes']);

        $errors = [];

        $clienteIdInformado = trim((string) ($dados['cliente_id'] ?? ''));
        $clienteId = null;

        if ($clienteIdInformado !== '') {
            $clienteId = ctype_digit($clienteIdInformado) ? (int) $clienteIdInformado : 0;

            if (Cliente::find($clienteId, $restauranteId) === null) {
                $errors[] = 'Cliente inválido.';
                $clienteId = null;
            }
        }

        $origem = (string) ($dados['origem'] ?? '');

        if (!in_array($origem, Pedido::ORIGENS_VALIDAS, true)) {
            $errors[] = 'Escolha uma origem de pedido válida.';
        }

        $formaPagamento = (string) ($dados['forma_pagamento'] ?? '');

        if (!in_array($formaPagamento, Pedido::FORMAS_PAGAMENTO, true)) {
            $errors[] = 'Escolha uma forma de pagamento válida.';
        }

        $descontoInformado = str_replace(',', '.', (string) ($dados['desconto'] ?? '0'));
        $desconto = is_numeric($descontoInformado) ? (float) $descontoInformado : -1;

        if ($desconto < 0) {
            $errors[] = 'Informe um desconto válido.';
        }

        if ($errors !== []) {
            Session::flash('pedido_errors', $errors);
            Session::flash('pedido_old', $dados);
            $this->redirect('/dashboard/pedidos/novo');
        }

        $id = Pedido::create(
            $restauranteId,
            $clienteId,
            $origem,
            $formaPagamento,
            $this->vazioParaNulo((string) ($dados['endereco_entrega'] ?? '')),
            $this->vazioParaNulo((string) ($dados['cupom'] ?? '')),
            max(0.0, $desconto),
            $this->vazioParaNulo((string) ($dados['observacoes'] ?? '')),
        );

        $this->redirect('/dashboard/pedidos/' . $id);
    }

    public function show(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $pedido = Pedido::find((int) $id, $restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pedidos');
        }

        echo $this->view('dashboard.pedidos.show', [
            'pageTitle' => 'Pedido #' . $pedido->id . ' - KADOSYS Food',
            'activeMenu' => 'pedidos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'pedido' => $pedido,
            'itens' => PedidoItem::doPedido($pedido->id),
            'produtos' => Produto::ativos($restauranteId),
            'success' => Session::flash('pedido_item_success'),
            'errors' => Session::flash('pedido_item_errors') ?? [],
        ], 'dashboard');
    }

    public function itemAdicionar(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $pedido = Pedido::find((int) $id, $restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pedidos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/pedidos/' . $id);
        }

        if ($pedido->status !== Pedido::STATUS_MONTAGEM) {
            Session::flash('pedido_item_errors', ['Esse pedido já foi confirmado ou cancelado - não é mais possível alterar os itens.']);
            $this->redirect('/dashboard/pedidos/' . $id);
        }

        $produtoId = (int) $this->request->input('produto_id', 0);
        $quantidadeInformada = (string) $this->request->input('quantidade', '');
        $quantidade = ctype_digit($quantidadeInformada) ? (int) $quantidadeInformada : 0;
        $precoInformado = str_replace(',', '.', (string) $this->request->input('preco_unitario', ''));
        $precoUnitario = is_numeric($precoInformado) ? (float) $precoInformado : -1;
        $observacao = trim((string) $this->request->input('observacao', ''));

        $produto = Produto::find($produtoId, $restauranteId);
        $errors = [];

        if ($produto === null) {
            $errors[] = 'Escolha um produto válido.';
        }

        if ($quantidade < 1) {
            $errors[] = 'Informe uma quantidade válida.';
        }

        if ($precoUnitario < 0) {
            $errors[] = 'Informe um preço unitário válido.';
        }

        if ($errors !== []) {
            Session::flash('pedido_item_errors', $errors);
            $this->redirect('/dashboard/pedidos/' . $id);
        }

        PedidoItem::create($pedido->id, $restauranteId, $produtoId, $quantidade, $precoUnitario, $observacao !== '' ? $observacao : null);

        Session::flash('pedido_item_success', 'Item adicionado ao pedido.');
        $this->redirect('/dashboard/pedidos/' . $id);
    }

    public function itemRemover(string $id, string $itemId): void
    {
        $restauranteId = $this->restauranteId();
        $pedido = Pedido::find((int) $id, $restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pedidos');
        }

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if ($pedido->status !== Pedido::STATUS_MONTAGEM) {
                Session::flash('pedido_item_errors', ['Esse pedido já foi confirmado ou cancelado - não é mais possível alterar os itens.']);
                $this->redirect('/dashboard/pedidos/' . $id);
            }

            PedidoItem::delete((int) $itemId, $pedido->id, $restauranteId);
            Session::flash('pedido_item_success', 'Item removido do pedido.');
        }

        $this->redirect('/dashboard/pedidos/' . $id);
    }

    public function confirmar(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Pedido::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/pedidos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/pedidos/' . $id);
        }

        $resultado = Pedido::finalizar((int) $id, $restauranteId);

        if (!$resultado['sucesso']) {
            Session::flash('pedido_item_errors', [$resultado['erro']]);
            $this->redirect('/dashboard/pedidos/' . $id);
        }

        Session::flash('pedido_item_success', 'Pedido confirmado! Estoque baixado e financeiro lançado automaticamente.');
        $this->redirect('/dashboard/pedidos/' . $id);
    }

    public function cancelar(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Pedido::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/pedidos');
        }

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (Pedido::cancelar((int) $id, $restauranteId)) {
                Session::flash('pedido_item_success', 'Pedido cancelado.');
            } else {
                Session::flash('pedido_item_errors', ['Esse pedido já foi confirmado e não pode mais ser cancelado.']);
            }
        }

        $this->redirect('/dashboard/pedidos/' . $id);
    }

    /** @return array<int, string> */
    private function statusValidos(): array
    {
        return [
            Pedido::STATUS_MONTAGEM,
            Pedido::STATUS_RECEBIDO,
            Pedido::STATUS_EM_PREPARO,
            Pedido::STATUS_FINALIZADO,
            Pedido::STATUS_SAIU_PARA_ENTREGA,
            Pedido::STATUS_ENTREGUE,
            Pedido::STATUS_CANCELADO,
        ];
    }

    private function vazioParaNulo(string $valor): ?string
    {
        $valor = trim($valor);

        return $valor === '' ? null : $valor;
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
