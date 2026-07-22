<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Caixa;
use Barbearias\Models\Cliente;
use Barbearias\Models\FidelidadeMovimento;
use Barbearias\Models\FilaAtendimento;
use Barbearias\Models\FinanceiroLancamento;
use Barbearias\Models\Profissional;
use Barbearias\Models\User;

/**
 * Painel da fila de atendimento (equipe) - so faz sentido pra barbearia
 * com modo_atendimento = 'fila' (ver Barbearias\Models\Barbearia::usaFila()).
 */
final class FilaController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $fila = FilaAtendimento::ativos($barbeariaId);

        echo $this->view('dashboard.fila.index', [
            'pageTitle' => 'Fila de atendimento - KADOSYS Barbearias',
            'activeMenu' => 'fila',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'fila' => $fila,
            'profissionais' => Profissional::ativos($barbeariaId),
            'success' => Session::flash('fila_success'),
            'errors' => Session::flash('fila_errors') ?? [],
        ], 'dashboard');
    }

    public function adicionar(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fila');
        }

        $nome = trim((string) $this->request->input('nome', ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            Session::flash('fila_errors', ['Informe o nome do cliente.']);
            $this->redirect('/dashboard/fila');
        }

        $telefone = trim((string) $this->request->input('telefone', ''));
        $profissionalId = (int) $this->request->input('profissional_id', 0);
        $clienteId = $telefone !== '' ? $this->vincularCliente($barbeariaId, $nome, $telefone) : null;

        FilaAtendimento::entrar($barbeariaId, $nome, $telefone !== '' ? $telefone : null, $profissionalId > 0 ? $profissionalId : null, $clienteId);

        Session::flash('fila_success', 'Cliente adicionado à fila.');
        $this->redirect('/dashboard/fila');
    }

    public function chamar(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Csrf::verify($this->request->input('_csrf_token')) && FilaAtendimento::find((int) $id, $barbeariaId) !== null) {
            FilaAtendimento::chamar((int) $id, $barbeariaId);
            Session::flash('fila_success', 'Cliente chamado.');
        }

        $this->redirect('/dashboard/fila');
    }

    /**
     * Tela de "concluir + registrar pagamento" (mesmo padrao de
     * AgendamentoController::pagamentoForm) - so assim da pra lancar no
     * caixa/financeiro e conceder pontos de fidelidade a um atendimento
     * feito via fila (antes, "concluir" so mudava o status, sem
     * nenhum valor associado).
     */
    public function concluirForm(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $item = FilaAtendimento::find((int) $id, $barbeariaId);

        if ($item === null || !in_array($item->status, [FilaAtendimento::STATUS_AGUARDANDO, FilaAtendimento::STATUS_EM_ATENDIMENTO], true)) {
            $this->redirect('/dashboard/fila');
        }

        echo $this->view('dashboard.fila.concluir', [
            'pageTitle' => 'Concluir atendimento - KADOSYS Barbearias',
            'activeMenu' => 'fila',
            'user' => $this->usuario(),
            'item' => $item,
            'formasPagamento' => FinanceiroLancamento::FORMAS_PAGAMENTO,
            'errors' => Session::flash('fila_concluir_errors') ?? [],
            'old' => Session::flash('fila_concluir_old') ?? [],
        ], 'dashboard');
    }

    public function concluir(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $item = FilaAtendimento::find((int) $id, $barbeariaId);

        if ($item === null || !in_array($item->status, [FilaAtendimento::STATUS_AGUARDANDO, FilaAtendimento::STATUS_EM_ATENDIMENTO], true)) {
            $this->redirect('/dashboard/fila');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fila');
        }

        $formaPagamento = (string) $this->request->input('forma_pagamento', '');
        $valorInformado = str_replace(',', '.', (string) $this->request->input('valor', ''));
        $valor = is_numeric($valorInformado) ? (float) $valorInformado : -1;
        $errors = [];

        if (!in_array($formaPagamento, FinanceiroLancamento::FORMAS_PAGAMENTO, true)) {
            $errors[] = 'Escolha uma forma de pagamento válida.';
        }

        if ($valor <= 0) {
            $errors[] = 'Informe um valor válido.';
        }

        if ($errors !== []) {
            Session::flash('fila_concluir_errors', $errors);
            Session::flash('fila_concluir_old', ['forma_pagamento' => $formaPagamento, 'valor' => $this->request->input('valor', '')]);
            $this->redirect('/dashboard/fila/' . $id . '/concluir');
        }

        FilaAtendimento::concluir((int) $id, $barbeariaId);

        if (!FinanceiroLancamento::existeParaFilaAtendimento((int) $id, $barbeariaId)) {
            $caixa = Caixa::aberto($barbeariaId);

            FinanceiroLancamento::create(
                $barbeariaId,
                $caixa?->id,
                null,
                $this->usuario()?->id,
                FinanceiroLancamento::TIPO_RECEITA,
                'Atendimento - Fila',
                $formaPagamento,
                $valor,
                'Pagamento de ' . $item->nome . ' (fila)',
                (new \DateTimeImmutable())->format('Y-m-d'),
                null,
                null,
                (int) $id,
            );

            if ($item->clienteId !== null) {
                $this->concederPontosFidelidade($barbeariaId, $item->clienteId, $item->nome, $valor);
            }
        }

        Session::flash('fila_success', 'Atendimento concluído e pagamento registrado.');
        $this->redirect('/dashboard/fila');
    }

    /**
     * Mesma regra de AgendamentoController::concederPontosFidelidade,
     * so que sem agendamento associado (o movimento fica com
     * agendamento_id nulo - a tabela ja suporta isso, usada tambem no
     * resgate de recompensas).
     */
    private function concederPontosFidelidade(int $barbeariaId, int $clienteId, string $nomeCliente, float $valor): void
    {
        $pontosPorReal = Barbearia::find($barbeariaId)?->fidelidadePontosPorReal;

        if ($pontosPorReal === null || $pontosPorReal <= 0) {
            return;
        }

        $pontos = (int) floor($valor * $pontosPorReal);

        if ($pontos < 1) {
            return;
        }

        Cliente::adicionarPontos($clienteId, $barbeariaId, $pontos);

        FidelidadeMovimento::create(
            $barbeariaId,
            $clienteId,
            FidelidadeMovimento::TIPO_GANHO,
            $pontos,
            null,
            null,
            'Atendimento via fila: ' . $nomeCliente,
        );
    }

    public function cancelar(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Csrf::verify($this->request->input('_csrf_token')) && FilaAtendimento::find((int) $id, $barbeariaId) !== null) {
            FilaAtendimento::cancelar((int) $id, $barbeariaId);
            Session::flash('fila_success', 'Removido da fila.');
        }

        $this->redirect('/dashboard/fila');
    }

    /**
     * Mesmo padrao do agendamento e da fila publica: liga a entrada na
     * fila a uma conta de cliente pelo telefone, criando uma nova se
     * ainda nao existir.
     */
    private function vincularCliente(int $barbeariaId, string $nome, string $telefone): int
    {
        $cliente = Cliente::buscarPorTelefone($barbeariaId, $telefone);

        if ($cliente === null) {
            return Cliente::create($barbeariaId, $nome, $telefone, null);
        }

        Cliente::update($cliente->id, $barbeariaId, $nome, $telefone, $cliente->email, $cliente->dataNascimento, $cliente->cpf);

        return $cliente->id;
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
