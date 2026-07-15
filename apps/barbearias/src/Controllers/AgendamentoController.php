<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;
use Barbearias\Models\User;

final class AgendamentoController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));
        $status = (string) $this->request->input('status', '');

        $resultado = Agendamento::paginate($barbeariaId, $page, self::POR_PAGINA, $search, $status);

        echo $this->view('dashboard.agendamentos.index', [
            'pageTitle' => 'Agendamentos - KADOSYS Barbearias',
            'activeMenu' => 'agendamentos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'agendamentos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'status' => $status,
            'success' => Session::flash('agendamento_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        $barbeariaId = $this->barbeariaId();

        $this->renderForm(null, $barbeariaId, Session::flash('agendamento_old') ?? [], Session::flash('agendamento_errors') ?? []);
    }

    public function store(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/agendamentos');
        }

        $dados = $this->request->only(['profissional_id', 'servico_id', 'cliente_id', 'data', 'hora', 'observacoes']);
        [$errors, $dataHora] = $this->validar($dados, $barbeariaId);

        if ($errors !== []) {
            Session::flash('agendamento_errors', $errors);
            Session::flash('agendamento_old', $dados);
            $this->redirect('/dashboard/agendamentos/novo');
        }

        Agendamento::create(
            $barbeariaId,
            (int) $dados['profissional_id'],
            (int) $dados['servico_id'],
            (int) $dados['cliente_id'],
            $dataHora,
            $dados['observacoes'],
        );

        Session::flash('agendamento_success', 'Agendamento criado com sucesso.');
        $this->redirect('/dashboard/agendamentos');
    }

    public function edit(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $agendamento = Agendamento::find((int) $id, $barbeariaId);

        if ($agendamento === null) {
            $this->redirect('/dashboard/agendamentos');
        }

        $this->renderForm($agendamento, $barbeariaId, Session::flash('agendamento_old') ?? [], Session::flash('agendamento_errors') ?? []);
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Agendamento::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/agendamentos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/agendamentos');
        }

        $dados = $this->request->only(['profissional_id', 'servico_id', 'cliente_id', 'data', 'hora', 'observacoes', 'status']);
        [$errors, $dataHora] = $this->validar($dados, $barbeariaId);

        $status = in_array($dados['status'] ?? '', [Agendamento::STATUS_AGENDADO, Agendamento::STATUS_CONCLUIDO, Agendamento::STATUS_CANCELADO], true)
            ? $dados['status']
            : Agendamento::STATUS_AGENDADO;

        if ($errors !== []) {
            Session::flash('agendamento_errors', $errors);
            Session::flash('agendamento_old', $dados);
            $this->redirect('/dashboard/agendamentos/' . $id . '/editar');
        }

        Agendamento::update(
            (int) $id,
            $barbeariaId,
            (int) $dados['profissional_id'],
            (int) $dados['servico_id'],
            (int) $dados['cliente_id'],
            $dataHora,
            $status,
            $dados['observacoes'],
        );

        Session::flash('agendamento_success', 'Agendamento atualizado com sucesso.');
        $this->redirect('/dashboard/agendamentos');
    }

    /**
     * Atalho pra marcar como concluido/cancelado direto na listagem,
     * sem precisar abrir o formulario de edicao inteiro.
     */
    public function status(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Agendamento::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/agendamentos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/agendamentos');
        }

        $novoStatus = (string) $this->request->input('novo_status', '');

        if (in_array($novoStatus, [Agendamento::STATUS_AGENDADO, Agendamento::STATUS_CONCLUIDO, Agendamento::STATUS_CANCELADO], true)) {
            Agendamento::atualizarStatus((int) $id, $barbeariaId, $novoStatus);
        }

        $this->redirect('/dashboard/agendamentos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Agendamento::delete((int) $id, $this->barbeariaId());
            Session::flash('agendamento_success', 'Agendamento removido.');
        }

        $this->redirect('/dashboard/agendamentos');
    }

    private function renderForm(?Agendamento $agendamento, int $barbeariaId, array $old, array $errors): void
    {
        echo $this->view('dashboard.agendamentos.form', [
            'pageTitle' => ($agendamento === null ? 'Novo' : 'Editar') . ' agendamento - KADOSYS Barbearias',
            'activeMenu' => 'agendamentos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'agendamento' => $agendamento,
            'profissionais' => Profissional::ativos($barbeariaId),
            'servicos' => Servico::ativos($barbeariaId),
            'clientes' => Cliente::todos($barbeariaId),
            'old' => $old,
            'errors' => $errors,
        ], 'dashboard');
    }

    /** @return array{0: array<int, string>, 1: string} */
    private function validar(array $dados, int $barbeariaId): array
    {
        $errors = [];

        $profissionalId = (int) ($dados['profissional_id'] ?? 0);

        if ($profissionalId <= 0 || Profissional::find($profissionalId, $barbeariaId) === null) {
            $errors[] = 'Escolha um profissional válido.';
        }

        $servicoId = (int) ($dados['servico_id'] ?? 0);

        if ($servicoId <= 0 || Servico::find($servicoId, $barbeariaId) === null) {
            $errors[] = 'Escolha um serviço válido.';
        }

        $clienteId = (int) ($dados['cliente_id'] ?? 0);

        if ($clienteId <= 0 || Cliente::find($clienteId, $barbeariaId) === null) {
            $errors[] = 'Escolha um cliente válido.';
        }

        $data = trim((string) ($dados['data'] ?? ''));
        $hora = trim((string) ($dados['hora'] ?? ''));
        $dataHora = '';

        if ($data === '' || $hora === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
            $errors[] = 'Informe uma data e hora válidas.';
        } else {
            $dataHora = $data . ' ' . $hora . ':00';
        }

        return [$errors, $dataHora];
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
