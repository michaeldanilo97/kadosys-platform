<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\Restaurante;
use Food\Models\User;

/**
 * Tela de Producao (cozinha/TV): kanban dos pedidos confirmados
 * (recebido -> em_preparo -> finalizado -> saiu_para_entrega ->
 * entregue), com timer de tempo decorrido e som de pedido novo - ambos
 * calculados no navegador (ver public/assets/js/producao.js). O
 * servidor so fornece os dados atuais via dados() (polling) e o avanco
 * de status via avancar(); a UI real e sempre a pagina recarregada,
 * nunca renderizada dinamicamente em JS.
 */
final class ProducaoController extends Controller
{
    public function index(): void
    {
        $dados = $this->dadosDoQuadro('Produção - KADOSYS Food');
        $dados['acoes'] = true;

        echo $this->view('dashboard.producao.index', $dados, 'dashboard');
    }

    public function tv(): void
    {
        $dados = $this->dadosDoQuadro('Produção (TV) - KADOSYS Food');
        $dados['acoes'] = false;

        echo $this->view('dashboard.producao.tv', $dados);
    }

    /**
     * Endpoint JSON de polling - so retorna os IDs/status atuais, pra
     * producao.js comparar com o que ja tinha visto e decidir se toca o
     * som de pedido novo e recarrega a pagina.
     */
    public function dados(): void
    {
        $restauranteId = $this->restauranteId();
        $pedidos = Pedido::emProducao($restauranteId);

        $this->jsonResponse([
            'pedidos' => array_map(
                static fn (Pedido $pedido): array => ['id' => $pedido->id, 'status' => $pedido->status],
                $pedidos,
            ),
        ]);
    }

    public function avancar(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Pedido::avancarStatus((int) $id, $restauranteId);
        }

        $this->back();
    }

    /** @return array<string, mixed> */
    private function dadosDoQuadro(string $pageTitle): array
    {
        $restauranteId = $this->restauranteId();
        $pedidos = Pedido::emProducao($restauranteId);

        $itensPorPedido = [];

        foreach ($pedidos as $pedido) {
            $itensPorPedido[$pedido->id] = PedidoItem::doPedido($pedido->id);
        }

        return [
            'pageTitle' => $pageTitle,
            'activeMenu' => 'producao',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'recebidos' => array_values(array_filter($pedidos, static fn (Pedido $p): bool => $p->status === Pedido::STATUS_RECEBIDO)),
            'emPreparo' => array_values(array_filter($pedidos, static fn (Pedido $p): bool => $p->status === Pedido::STATUS_EM_PREPARO)),
            'finalizados' => array_values(array_filter($pedidos, static fn (Pedido $p): bool => $p->status === Pedido::STATUS_FINALIZADO)),
            'saiuParaEntrega' => array_values(array_filter($pedidos, static fn (Pedido $p): bool => $p->status === Pedido::STATUS_SAIU_PARA_ENTREGA)),
            'entreguesHoje' => Pedido::entreguesHoje($restauranteId),
            'itensPorPedido' => $itensPorPedido,
        ];
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
