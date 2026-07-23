<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\CompraItem;
use Food\Models\EstoqueMovimento;
use Food\Models\Ingrediente;
use Food\Models\Restaurante;
use Food\Models\User;

final class EstoqueController extends Controller
{
    private const POR_PAGINA = 20;
    private const DIAS_ALERTA_VENCIMENTO = 7;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));

        $resultado = EstoqueMovimento::paginate($restauranteId, $page, self::POR_PAGINA);

        echo $this->view('dashboard.estoque.index', [
            'pageTitle' => 'Estoque - KADOSYS Food',
            'activeMenu' => 'estoque',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'estoqueBaixo' => Ingrediente::comEstoqueBaixo($restauranteId),
            'vencendo' => CompraItem::vencendoEm($restauranteId, self::DIAS_ALERTA_VENCIMENTO),
            'movimentos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'success' => Session::flash('estoque_success'),
            'errors' => Session::flash('estoque_errors') ?? [],
        ], 'dashboard');
    }

    public function movimentarForm(): void
    {
        $restauranteId = $this->restauranteId();

        echo $this->view('dashboard.estoque.form', [
            'pageTitle' => 'Registrar movimentação - KADOSYS Food',
            'activeMenu' => 'estoque',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'ingredientes' => Ingrediente::ativos($restauranteId),
            'tiposValidos' => EstoqueMovimento::TIPOS_VALIDOS,
            'old' => Session::flash('estoque_old') ?? [],
            'errors' => Session::flash('estoque_errors') ?? [],
        ], 'dashboard');
    }

    public function movimentar(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/estoque/movimentar');
        }

        $dados = $this->request->only(['ingrediente_id', 'tipo', 'quantidade', 'motivo']);

        $ingredienteId = (int) ($dados['ingrediente_id'] ?? 0);
        $tipo = (string) ($dados['tipo'] ?? '');
        $quantidadeInformada = str_replace(',', '.', (string) ($dados['quantidade'] ?? ''));
        $quantidade = is_numeric($quantidadeInformada) ? (float) $quantidadeInformada : -1;
        $motivo = trim((string) ($dados['motivo'] ?? ''));

        $errors = [];

        if (Ingrediente::find($ingredienteId, $restauranteId) === null) {
            $errors[] = 'Escolha um ingrediente válido.';
        }

        if (!in_array($tipo, EstoqueMovimento::TIPOS_VALIDOS, true)) {
            $errors[] = 'Escolha um tipo de movimentação válido.';
        }

        if ($quantidade <= 0) {
            $errors[] = $tipo === EstoqueMovimento::TIPO_INVENTARIO
                ? 'Informe a nova contagem de estoque (maior que zero).'
                : 'Informe uma quantidade válida.';
        }

        if ($errors !== []) {
            Session::flash('estoque_errors', $errors);
            Session::flash('estoque_old', $dados);
            $this->redirect('/dashboard/estoque/movimentar');
        }

        $sucesso = EstoqueMovimento::registrarManual(
            $restauranteId,
            $ingredienteId,
            $tipo,
            $quantidade,
            $motivo !== '' ? $motivo : null,
        );

        if (!$sucesso) {
            Session::flash('estoque_errors', ['Estoque insuficiente para essa saída/perda.']);
            Session::flash('estoque_old', $dados);
            $this->redirect('/dashboard/estoque/movimentar');
        }

        Session::flash('estoque_success', 'Movimentação registrada com sucesso.');
        $this->redirect('/dashboard/estoque');
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
