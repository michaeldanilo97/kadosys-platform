<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\CentroCusto;
use Food\Models\Cliente;
use Food\Models\ContaReceber;
use Food\Models\Restaurante;
use Food\Models\User;

final class ContaReceberController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $statusInformado = (string) $this->request->input('status', '');
        $status = in_array($statusInformado, [ContaReceber::STATUS_PENDENTE, ContaReceber::STATUS_RECEBIDA, ContaReceber::STATUS_CANCELADA], true)
            ? $statusInformado
            : '';

        $resultado = ContaReceber::paginate($restauranteId, $page, self::POR_PAGINA, $status);

        echo $this->view('dashboard.financeiro.contas-a-receber.index', [
            'pageTitle' => 'Contas a Receber - KADOSYS Food',
            'activeMenu' => 'financeiro',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'contas' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'statusSelecionado' => $status,
            'centrosCusto' => CentroCusto::ativos($restauranteId),
            'clientes' => Cliente::ativos($restauranteId),
            'success' => Session::flash('conta_receber_success'),
            'errors' => Session::flash('conta_receber_errors') ?? [],
            'old' => Session::flash('conta_receber_old') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro/contas-a-receber');
        }

        $dados = $this->request->only(['centro_custo_id', 'cliente_id', 'descricao', 'categoria', 'valor', 'vencimento', 'observacoes']);
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

        $clienteId = $this->paraNullableInt((string) ($dados['cliente_id'] ?? ''));

        if ($clienteId !== null && Cliente::find($clienteId, $restauranteId) === null) {
            $errors[] = 'Cliente inválido.';
            $clienteId = null;
        }

        if ($errors !== []) {
            Session::flash('conta_receber_errors', $errors);
            Session::flash('conta_receber_old', $dados);
            $this->redirect('/dashboard/financeiro/contas-a-receber');
        }

        ContaReceber::create(
            $restauranteId,
            $centroCustoId,
            $clienteId,
            $descricao,
            $this->vazioParaNulo((string) ($dados['categoria'] ?? '')),
            $valor,
            $vencimento,
            $this->vazioParaNulo((string) ($dados['observacoes'] ?? '')),
        );

        Session::flash('conta_receber_success', 'Conta a receber cadastrada com sucesso.');
        $this->redirect('/dashboard/financeiro/contas-a-receber');
    }

    public function receber(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (ContaReceber::marcarRecebida((int) $id, $restauranteId)) {
                Session::flash('conta_receber_success', 'Conta marcada como recebida.');
            } else {
                Session::flash('conta_receber_errors', ['Essa conta já foi recebida ou cancelada.']);
            }
        }

        $this->redirect('/dashboard/financeiro/contas-a-receber');
    }

    public function cancelar(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (ContaReceber::cancelar((int) $id, $restauranteId)) {
                Session::flash('conta_receber_success', 'Conta cancelada.');
            } else {
                Session::flash('conta_receber_errors', ['Essa conta já foi recebida ou cancelada.']);
            }
        }

        $this->redirect('/dashboard/financeiro/contas-a-receber');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ContaReceber::delete((int) $id, $this->restauranteId());
            Session::flash('conta_receber_success', 'Conta removida.');
        }

        $this->redirect('/dashboard/financeiro/contas-a-receber');
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
