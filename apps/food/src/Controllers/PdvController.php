<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\PixEstatico;
use Food\Core\Session;
use Food\Models\Caixa;
use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\PedidoPagamento;
use Food\Models\Produto;
use Food\Models\Restaurante;
use Food\Models\User;

/**
 * PDV (venda touch de balcao). O "carrinho" em construcao e sempre UM
 * Pedido de verdade (origem=balcao, status=montagem) - o id fica
 * guardado na sessao (pdv_pedido_id) so pra saber qual retomar; toda a
 * regra de negocio (item, calculo de total, baixa de estoque) e a
 * MESMA ja usada pela tela normal de Pedidos, sem duplicar nada aqui.
 * Isso evita ter um "carrinho" client-side/paralelo com sua propria
 * logica de calculo.
 */
final class PdvController extends Controller
{
    private const SESSION_PEDIDO_ID = 'pdv_pedido_id';

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $caixa = Caixa::aberto($restauranteId);

        if ($caixa === null) {
            echo $this->view('dashboard.pdv.sem_caixa', [
                'pageTitle' => 'PDV - KADOSYS Food',
                'activeMenu' => 'pdv',
                'user' => $this->usuario(),
                'restaurante' => Restaurante::find($restauranteId),
            ], 'dashboard');

            return;
        }

        $pedido = $this->pedidoAtualOuNovo($restauranteId);
        $produtos = Produto::ativos($restauranteId);

        echo $this->view('dashboard.pdv.index', [
            'pageTitle' => 'PDV - KADOSYS Food',
            'activeMenu' => 'pdv',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'pedido' => $pedido,
            'itens' => PedidoItem::doPedido($pedido->id),
            'produtos' => $produtos,
            'success' => Session::flash('pdv_success'),
            'errors' => Session::flash('pdv_errors') ?? [],
        ], 'dashboard');
    }

    public function itemAdicionar(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/pdv');
        }

        $pedido = $this->pedidoAtual($restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pdv');
        }

        $produtoId = (int) $this->request->input('produto_id', 0);
        $quantidadeInformada = (string) $this->request->input('quantidade', '1');
        $quantidade = ctype_digit($quantidadeInformada) ? max(1, (int) $quantidadeInformada) : 1;

        $produto = Produto::find($produtoId, $restauranteId);

        if ($produto === null) {
            Session::flash('pdv_errors', ['Produto não encontrado.']);
            $this->redirect('/dashboard/pdv');
        }

        PedidoItem::create($pedido->id, $restauranteId, $produtoId, $quantidade, $produto->precoBalcao, null);
        Pedido::recalcularValores($pedido->id, $restauranteId);

        $this->redirect('/dashboard/pdv');
    }

    public function itemRemover(string $itemId): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $pedido = $this->pedidoAtual($restauranteId);

            if ($pedido !== null) {
                PedidoItem::delete((int) $itemId, $pedido->id, $restauranteId);
                Pedido::recalcularValores($pedido->id, $restauranteId);
            }
        }

        $this->redirect('/dashboard/pdv');
    }

    public function pagamentoForm(): void
    {
        $restauranteId = $this->restauranteId();
        $pedido = $this->pedidoAtual($restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pdv');
        }

        $itens = PedidoItem::doPedido($pedido->id);

        if ($itens === []) {
            Session::flash('pdv_errors', ['Adicione ao menos um item antes de ir para o pagamento.']);
            $this->redirect('/dashboard/pdv');
        }

        $pagamentos = PedidoPagamento::doPedido($pedido->id);
        $somaPaga = PedidoPagamento::somaPagamentos($pedido->id);
        $restante = round(max(0.0, $pedido->valorTotal - $somaPaga), 2);

        $restaurante = Restaurante::find($restauranteId);
        $pixPayload = null;

        if ($restante > 0 && $restaurante !== null && $restaurante->pixConfigurado()) {
            $pixPayload = PixEstatico::montarPayload(
                (string) $restaurante->pixChave,
                (string) $restaurante->pixNomeBeneficiario,
                (string) $restaurante->pixCidade,
                $restante,
                'PDV' . $pedido->id,
                'Pedido #' . $pedido->id,
            );
        }

        echo $this->view('dashboard.pdv.pagamento', [
            'pageTitle' => 'Pagamento - PDV - KADOSYS Food',
            'activeMenu' => 'pdv',
            'user' => $this->usuario(),
            'restaurante' => $restaurante,
            'pedido' => $pedido,
            'itens' => $itens,
            'pagamentos' => $pagamentos,
            'somaPaga' => $somaPaga,
            'restante' => $restante,
            'pixPayload' => $pixPayload,
            'success' => Session::flash('pdv_success'),
            'errors' => Session::flash('pdv_errors') ?? [],
        ], 'dashboard');
    }

    public function pagamentoAdicionar(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/pdv/pagamento');
        }

        $pedido = $this->pedidoAtual($restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pdv');
        }

        $formaPagamento = (string) $this->request->input('forma_pagamento', '');

        if (!in_array($formaPagamento, Pedido::FORMAS_PAGAMENTO, true)) {
            Session::flash('pdv_errors', ['Escolha uma forma de pagamento válida.']);
            $this->redirect('/dashboard/pdv/pagamento');
        }

        $somaPaga = PedidoPagamento::somaPagamentos($pedido->id);
        $restante = round(max(0.0, $pedido->valorTotal - $somaPaga), 2);

        if ($restante <= 0) {
            Session::flash('pdv_errors', ['Este pedido já está totalmente pago.']);
            $this->redirect('/dashboard/pdv/pagamento');
        }

        if ($formaPagamento === 'dinheiro') {
            $valorRecebido = $this->paraFloat((string) $this->request->input('valor_recebido', '0'));

            if ($valorRecebido <= 0) {
                Session::flash('pdv_errors', ['Informe o valor recebido em dinheiro.']);
                $this->redirect('/dashboard/pdv/pagamento');
            }

            $valorAplicado = min($valorRecebido, $restante);
            $troco = round($valorRecebido - $valorAplicado, 2);

            PedidoPagamento::create($pedido->id, $formaPagamento, $valorAplicado, $valorRecebido, $troco);
        } else {
            $valor = $this->paraFloat((string) $this->request->input('valor', '0'));

            if ($valor <= 0) {
                Session::flash('pdv_errors', ['Informe um valor válido.']);
                $this->redirect('/dashboard/pdv/pagamento');
            }

            if ($valor > $restante + 0.01) {
                Session::flash('pdv_errors', ['O valor informado é maior que o restante a pagar.']);
                $this->redirect('/dashboard/pdv/pagamento');
            }

            PedidoPagamento::create($pedido->id, $formaPagamento, $valor, null, null);
        }

        $this->redirect('/dashboard/pdv/pagamento');
    }

    public function pagamentoRemover(string $pagamentoId): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $pedido = $this->pedidoAtual($restauranteId);

            if ($pedido !== null) {
                PedidoPagamento::delete((int) $pagamentoId, $pedido->id);
            }
        }

        $this->redirect('/dashboard/pdv/pagamento');
    }

    public function finalizar(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/pdv/pagamento');
        }

        $pedido = $this->pedidoAtual($restauranteId);

        if ($pedido === null) {
            $this->redirect('/dashboard/pdv');
        }

        $somaPaga = PedidoPagamento::somaPagamentos($pedido->id);

        if ($somaPaga + 0.01 < $pedido->valorTotal) {
            Session::flash('pdv_errors', ['O pagamento ainda não cobre o valor total da venda.']);
            $this->redirect('/dashboard/pdv/pagamento');
        }

        $caixa = Caixa::aberto($restauranteId);

        if ($caixa === null) {
            Session::flash('pdv_errors', ['Não há caixa aberto para registrar a venda.']);
            $this->redirect('/dashboard/caixa');
        }

        $resultado = Pedido::finalizar($pedido->id, $restauranteId, $caixa->id);

        if (!$resultado['sucesso']) {
            Session::flash('pdv_errors', [$resultado['erro']]);
            $this->redirect('/dashboard/pdv/pagamento');
        }

        Session::remove(self::SESSION_PEDIDO_ID);
        Session::flash('pdv_success', 'Venda finalizada com sucesso.');
        $this->redirect('/dashboard/pdv/' . $pedido->id . '/recibo');
    }

    public function cancelar(): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $pedido = $this->pedidoAtual($restauranteId);

            if ($pedido !== null) {
                Pedido::cancelar($pedido->id, $restauranteId);
            }

            Session::remove(self::SESSION_PEDIDO_ID);
        }

        $this->redirect('/dashboard/pdv');
    }

    public function recibo(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $pedido = Pedido::find((int) $id, $restauranteId);

        if ($pedido === null || $pedido->status === Pedido::STATUS_MONTAGEM) {
            $this->redirect('/dashboard/pdv');
        }

        echo $this->view('dashboard.pdv.recibo', [
            'pageTitle' => 'Recibo - Pedido #' . $pedido->id,
            'restaurante' => Restaurante::find($restauranteId),
            'pedido' => $pedido,
            'itens' => PedidoItem::doPedido($pedido->id),
            'pagamentos' => PedidoPagamento::doPedido($pedido->id),
        ]);
    }

    private function pedidoAtualOuNovo(int $restauranteId): Pedido
    {
        $pedido = $this->pedidoAtual($restauranteId);

        if ($pedido !== null) {
            return $pedido;
        }

        $novoId = Pedido::create($restauranteId, null, Pedido::ORIGEM_BALCAO, 'dinheiro', null, null, 0.0, null);
        Session::set(self::SESSION_PEDIDO_ID, $novoId);

        return Pedido::find($novoId, $restauranteId);
    }

    private function pedidoAtual(int $restauranteId): ?Pedido
    {
        $pedidoId = (int) Session::get(self::SESSION_PEDIDO_ID, 0);

        if ($pedidoId <= 0) {
            return null;
        }

        $pedido = Pedido::find($pedidoId, $restauranteId);

        if ($pedido === null || $pedido->status !== Pedido::STATUS_MONTAGEM) {
            Session::remove(self::SESSION_PEDIDO_ID);

            return null;
        }

        return $pedido;
    }

    private function paraFloat(string $valor): float
    {
        $normalizado = str_replace(',', '.', $valor);

        return is_numeric($normalizado) ? (float) $normalizado : 0.0;
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
