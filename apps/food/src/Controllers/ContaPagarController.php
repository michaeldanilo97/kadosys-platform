<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\CentroCusto;
use Food\Models\ContaPagar;
use Food\Models\Restaurante;
use Food\Models\User;

final class ContaPagarController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $statusInformado = (string) $this->request->input('status', '');
        $status = in_array($statusInformado, [ContaPagar::STATUS_PENDENTE, ContaPagar::STATUS_PAGA, ContaPagar::STATUS_CANCELADA], true)
            ? $statusInformado
            : '';

        $resultado = ContaPagar::paginate($restauranteId, $page, self::POR_PAGINA, $status);

        echo $this->view('dashboard.financeiro.contas-a-pagar.index', [
            'pageTitle' => 'Contas a Pagar - KADOSYS Food',
            'activeMenu' => 'financeiro',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'contas' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'statusSelecionado' => $status,
            'centrosCusto' => CentroCusto::ativos($restauranteId),
            'success' => Session::flash('conta_pagar_success'),
            'errors' => Session::flash('conta_pagar_errors') ?? [],
            'old' => Session::flash('conta_pagar_old') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro/contas-a-pagar');
        }

        $dados = $this->request->only(['centro_custo_id', 'descricao', 'categoria', 'valor', 'vencimento', 'recorrente', 'parcela_total', 'observacoes']);
        $errors = [];

        $descricao = trim((string) ($dados['descricao'] ?? ''));

        if ($descricao === '') {
            $errors[] = 'Informe a descrição da conta.';
        }

        $valor = $this->paraFloat((string) ($dados['valor'] ?? ''));

        if ($valor <= 0) {
            $errors[] = 'Informe um valor válido.';
        }

        $vencimento = trim((string) ($dados['vencimento'] ?? ''));

        if ($vencimento === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) {
            $errors[] = 'Informe uma data de vencimento válida.';
        }

        $centroCustoId = $this->paraNullableInt((string) ($dados['centro_custo_id'] ?? ''));

        if ($centroCustoId !== null && CentroCusto::find($centroCustoId, $restauranteId) === null) {
            $errors[] = 'Centro de custo inválido.';
            $centroCustoId = null;
        }

        $recorrente = $this->request->input('recorrente') !== null;
        $parcelaTotal = $recorrente ? $this->paraNullableInt((string) ($dados['parcela_total'] ?? '')) : null;

        if ($errors !== []) {
            Session::flash('conta_pagar_errors', $errors);
            Session::flash('conta_pagar_old', $dados);
            $this->redirect('/dashboard/financeiro/contas-a-pagar');
        }

        ContaPagar::create(
            $restauranteId,
            $centroCustoId,
            $descricao,
            $this->vazioParaNulo((string) ($dados['categoria'] ?? '')),
            $valor,
            $vencimento,
            $recorrente,
            $parcelaTotal,
            $this->vazioParaNulo((string) ($dados['observacoes'] ?? '')),
        );

        Session::flash('conta_pagar_success', 'Conta a pagar cadastrada com sucesso.');
        $this->redirect('/dashboard/financeiro/contas-a-pagar');
    }

    public function pagar(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (ContaPagar::marcarPaga((int) $id, $restauranteId)) {
                Session::flash('conta_pagar_success', 'Conta marcada como paga.');
            } else {
                Session::flash('conta_pagar_errors', ['Essa conta já foi paga ou cancelada.']);
            }
        }

        $this->redirect('/dashboard/financeiro/contas-a-pagar');
    }

    public function cancelar(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (ContaPagar::cancelar((int) $id, $restauranteId)) {
                Session::flash('conta_pagar_success', 'Conta cancelada.');
            } else {
                Session::flash('conta_pagar_errors', ['Essa conta já foi paga ou cancelada.']);
            }
        }

        $this->redirect('/dashboard/financeiro/contas-a-pagar');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ContaPagar::delete((int) $id, $this->restauranteId());
            Session::flash('conta_pagar_success', 'Conta removida.');
        }

        $this->redirect('/dashboard/financeiro/contas-a-pagar');
    }

    private function paraFloat(string $valor): float
    {
        $normalizado = str_replace(',', '.', $valor);

        return is_numeric($normalizado) ? (float) $normalizado : 0.0;
    }

    private function paraNullableInt(string $valor): ?int
    {
        $valor = trim($valor);

        return $valor !== '' && ctype_digit($valor) ? (int) $valor : null;
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
