<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\FidelidadeMovimento;
use Barbearias\Models\FidelidadeRecompensa;
use Barbearias\Models\User;

/**
 * Programa de fidelidade: configuracao de pontos por real gasto,
 * cadastro de recompensas e resgate pra um cliente especifico. Os
 * pontos em si sao concedidos automaticamente ao registrar o
 * pagamento de um atendimento (ver
 * Barbearias\Controllers\AgendamentoController::pagamento).
 */
final class FidelidadeController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $termoBusca = trim((string) $this->request->input('busca_cliente', ''));

        $clienteSelecionado = null;
        $historico = [];
        $clientesEncontrados = [];

        $clienteId = (int) $this->request->input('cliente_id', 0);

        if ($clienteId > 0) {
            $clienteSelecionado = Cliente::find($clienteId, $barbeariaId);

            if ($clienteSelecionado !== null) {
                $historico = FidelidadeMovimento::historicoDoCliente($clienteSelecionado->id, $barbeariaId);
            }
        } elseif ($termoBusca !== '') {
            $clientesEncontrados = Cliente::buscarParaFidelidade($barbeariaId, $termoBusca);
        }

        echo $this->view('dashboard.fidelidade.index', [
            'pageTitle' => 'Fidelidade - KADOSYS Barbearias',
            'activeMenu' => 'fidelidade',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'recompensas' => FidelidadeRecompensa::todas($barbeariaId),
            'termoBusca' => $termoBusca,
            'clientesEncontrados' => $clientesEncontrados,
            'clienteSelecionado' => $clienteSelecionado,
            'historico' => $historico,
            'success' => Session::flash('fidelidade_success'),
            'errors' => Session::flash('fidelidade_errors') ?? [],
        ], 'dashboard');
    }

    public function configurar(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fidelidade');
        }

        $ativo = $this->request->input('fidelidade_ativa') !== null;
        $pontosInformado = str_replace(',', '.', (string) $this->request->input('pontos_por_real', ''));
        $pontos = is_numeric($pontosInformado) ? (float) $pontosInformado : -1;

        if ($ativo && $pontos <= 0) {
            Session::flash('fidelidade_errors', ['Informe quantos pontos o cliente ganha por real gasto (maior que zero).']);
            $this->redirect('/dashboard/fidelidade');
        }

        Barbearia::atualizarFidelidade($barbeariaId, $ativo ? $pontos : null);

        Session::flash('fidelidade_success', $ativo ? 'Programa de fidelidade ativado.' : 'Programa de fidelidade desativado.');
        $this->redirect('/dashboard/fidelidade');
    }

    public function recompensaStore(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fidelidade');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $pontosNecessarios = (int) $this->request->input('pontos_necessarios', 0);
        $errors = [];

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome da recompensa.';
        }

        if ($pontosNecessarios < 1) {
            $errors[] = 'Informe uma quantidade de pontos válida.';
        }

        if ($errors !== []) {
            Session::flash('fidelidade_errors', $errors);
            $this->redirect('/dashboard/fidelidade');
        }

        FidelidadeRecompensa::create($barbeariaId, $nome, $pontosNecessarios);

        Session::flash('fidelidade_success', 'Recompensa cadastrada.');
        $this->redirect('/dashboard/fidelidade');
    }

    public function recompensaDestroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FidelidadeRecompensa::delete((int) $id, $this->barbeariaId());
            Session::flash('fidelidade_success', 'Recompensa removida.');
        }

        $this->redirect('/dashboard/fidelidade');
    }

    public function resgatar(): void
    {
        $barbeariaId = $this->barbeariaId();
        $clienteId = (int) $this->request->input('cliente_id', 0);
        $recompensaId = (int) $this->request->input('recompensa_id', 0);

        $cliente = Cliente::find($clienteId, $barbeariaId);
        $recompensa = FidelidadeRecompensa::find($recompensaId, $barbeariaId);

        if ($cliente === null || $recompensa === null) {
            $this->redirect('/dashboard/fidelidade');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fidelidade?cliente_id=' . $cliente->id);
        }

        if (!Cliente::debitarPontos($cliente->id, $barbeariaId, $recompensa->pontosNecessarios)) {
            Session::flash('fidelidade_errors', ['Saldo de pontos insuficiente pra essa recompensa.']);
            $this->redirect('/dashboard/fidelidade?cliente_id=' . $cliente->id);
        }

        FidelidadeMovimento::create(
            $barbeariaId,
            $cliente->id,
            FidelidadeMovimento::TIPO_RESGATE,
            $recompensa->pontosNecessarios,
            null,
            $recompensa->id,
            'Resgate: ' . $recompensa->nome,
        );

        Session::flash('fidelidade_success', 'Recompensa "' . $recompensa->nome . '" resgatada.');
        $this->redirect('/dashboard/fidelidade?cliente_id=' . $cliente->id);
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
