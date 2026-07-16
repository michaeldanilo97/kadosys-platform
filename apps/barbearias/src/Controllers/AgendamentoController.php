<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\PixEstatico;
use Barbearias\Core\Session;
use Barbearias\Models\Agendamento;
use Barbearias\Models\AssinaturaCliente;
use Barbearias\Models\AssinaturaConsumo;
use Barbearias\Models\Barbearia;
use Barbearias\Models\BloqueioAgenda;
use Barbearias\Models\Caixa;
use Barbearias\Models\Cliente;
use Barbearias\Models\FidelidadeMovimento;
use Barbearias\Models\FinanceiroLancamento;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;
use Barbearias\Models\Unidade;
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
        $unidadeId = (int) $this->request->input('unidade', 0);
        $multiUnidade = Unidade::temMultiplasAtivas($barbeariaId);

        $resultado = Agendamento::paginate($barbeariaId, $page, self::POR_PAGINA, $search, $status, $multiUnidade ? $unidadeId : 0);

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
            'unidades' => $multiUnidade ? Unidade::ativas($barbeariaId) : [],
            'unidadeId' => $unidadeId,
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
            $this->unidadeIdDoFormulario($barbeariaId),
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
            $this->unidadeIdDoFormulario($barbeariaId),
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

    /**
     * Tela do "PDV rapido": ao concluir um agendamento, registra o
     * pagamento (forma + valor) como um lancamento de receita no
     * financeiro no mesmo passo, ja vinculado ao caixa aberto (se
     * houver um).
     */
    public function pagamentoForm(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $agendamento = Agendamento::find((int) $id, $barbeariaId);

        if ($agendamento === null || $agendamento->status !== Agendamento::STATUS_AGENDADO) {
            $this->redirect('/dashboard/agendamentos');
        }

        $assinatura = AssinaturaCliente::ativaDoCliente($agendamento->clienteId, $barbeariaId);
        $saldoAssinatura = null;

        if ($assinatura !== null) {
            $usados = AssinaturaConsumo::contarNoCiclo($assinatura->id, $assinatura->inicioCicloAtual(new \DateTimeImmutable('today')));
            $saldoAssinatura = ['assinatura' => $assinatura, 'usados' => $usados, 'restantes' => max(0, $assinatura->planoAtendimentosPorMes - $usados)];
        }

        $barbearia = Barbearia::find($barbeariaId);
        $pixPayload = null;

        if ($barbearia !== null && $barbearia->pixConfigurado()) {
            $pixPayload = PixEstatico::montarPayload(
                chave: (string) $barbearia->pixChave,
                nomeBeneficiario: $barbearia->pixNomeBeneficiario ?? $barbearia->nome,
                cidade: $barbearia->pixCidade ?? 'BRASIL',
                valor: $agendamento->servicoPreco,
                txid: 'AGD' . $agendamento->id,
                descricao: $agendamento->servicoNome,
            );
        }

        echo $this->view('dashboard.agendamentos.pagamento', [
            'pageTitle' => 'Registrar pagamento - KADOSYS Barbearias',
            'activeMenu' => 'agendamentos',
            'user' => $this->usuario(),
            'barbearia' => $barbearia,
            'agendamento' => $agendamento,
            'formasPagamento' => FinanceiroLancamento::FORMAS_PAGAMENTO,
            'saldoAssinatura' => $saldoAssinatura,
            'pixPayload' => $pixPayload,
            'errors' => Session::flash('agendamento_pagamento_errors') ?? [],
            'old' => Session::flash('agendamento_pagamento_old') ?? [],
        ], 'dashboard');
    }

    /**
     * Conclui o atendimento consumindo uma vaga da assinatura ativa do
     * cliente, em vez de registrar um pagamento avulso - a mensalidade
     * ja cobre isso (cobrada fora do sistema), entao NAO gera
     * lancamento financeiro.
     */
    public function usarAssinatura(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $agendamento = Agendamento::find((int) $id, $barbeariaId);

        if ($agendamento === null || $agendamento->status !== Agendamento::STATUS_AGENDADO) {
            $this->redirect('/dashboard/agendamentos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/agendamentos');
        }

        $assinatura = AssinaturaCliente::ativaDoCliente($agendamento->clienteId, $barbeariaId);

        if ($assinatura === null) {
            $this->redirect('/dashboard/agendamentos/' . $id . '/pagamento');
        }

        $usados = AssinaturaConsumo::contarNoCiclo($assinatura->id, $assinatura->inicioCicloAtual(new \DateTimeImmutable('today')));

        if ($usados >= $assinatura->planoAtendimentosPorMes) {
            Session::flash('agendamento_pagamento_errors', ['Essa assinatura já usou todos os atendimentos do ciclo atual.']);
            $this->redirect('/dashboard/agendamentos/' . $id . '/pagamento');
        }

        Agendamento::atualizarStatus((int) $id, $barbeariaId, Agendamento::STATUS_CONCLUIDO);

        if (!AssinaturaConsumo::existeParaAgendamento((int) $id)) {
            AssinaturaConsumo::create($assinatura->id, (int) $id);
        }

        Session::flash('agendamento_success', 'Agendamento concluído usando a assinatura.');
        $this->redirect('/dashboard/agendamentos');
    }

    public function pagamento(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $agendamento = Agendamento::find((int) $id, $barbeariaId);

        if ($agendamento === null || $agendamento->status !== Agendamento::STATUS_AGENDADO) {
            $this->redirect('/dashboard/agendamentos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/agendamentos');
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
            Session::flash('agendamento_pagamento_errors', $errors);
            Session::flash('agendamento_pagamento_old', ['forma_pagamento' => $formaPagamento, 'valor' => $this->request->input('valor', '')]);
            $this->redirect('/dashboard/agendamentos/' . $id . '/pagamento');
        }

        Agendamento::atualizarStatus((int) $id, $barbeariaId, Agendamento::STATUS_CONCLUIDO);

        if (!FinanceiroLancamento::existeParaAgendamento((int) $id, $barbeariaId)) {
            $caixa = Caixa::aberto($barbeariaId);

            FinanceiroLancamento::create(
                $barbeariaId,
                $caixa?->id,
                (int) $id,
                $this->usuario()?->id,
                FinanceiroLancamento::TIPO_RECEITA,
                'Serviço - ' . $agendamento->servicoNome,
                $formaPagamento,
                $valor,
                'Pagamento de ' . $agendamento->clienteNome,
                (new \DateTimeImmutable())->format('Y-m-d'),
            );

            $this->concederPontosFidelidade($barbeariaId, $agendamento, $valor);
        }

        Session::flash('agendamento_success', 'Agendamento concluído e pagamento registrado.');
        $this->redirect('/dashboard/agendamentos');
    }

    /**
     * Concede pontos de fidelidade ao cliente do atendimento, se a
     * barbearia tiver o programa ativado
     * (barbearias.fidelidade_pontos_por_real). Sem efeito nenhum
     * quando o programa esta desligado.
     */
    private function concederPontosFidelidade(int $barbeariaId, Agendamento $agendamento, float $valor): void
    {
        $pontosPorReal = Barbearia::find($barbeariaId)?->fidelidadePontosPorReal;

        if ($pontosPorReal === null || $pontosPorReal <= 0) {
            return;
        }

        $pontos = (int) floor($valor * $pontosPorReal);

        if ($pontos < 1) {
            return;
        }

        Cliente::adicionarPontos($agendamento->clienteId, $barbeariaId, $pontos);

        FidelidadeMovimento::create(
            $barbeariaId,
            $agendamento->clienteId,
            FidelidadeMovimento::TIPO_GANHO,
            $pontos,
            $agendamento->id,
            null,
            'Atendimento: ' . $agendamento->servicoNome,
        );
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
            'unidades' => Unidade::temMultiplasAtivas($barbeariaId) ? Unidade::ativas($barbeariaId) : [],
            'old' => $old,
            'errors' => $errors,
        ], 'dashboard');
    }

    /**
     * Quando a barbearia tem so uma unidade, o agendamento e vinculado
     * a ela automaticamente (sem nenhum campo no formulario) - so pede
     * pra escolher quando ha mais de uma unidade ativa.
     */
    private function unidadeIdDoFormulario(int $barbeariaId): ?int
    {
        if (!Unidade::temMultiplasAtivas($barbeariaId)) {
            return Unidade::principal($barbeariaId)?->id;
        }

        $informada = (int) $this->request->input('unidade_id', 0);

        return $informada > 0 && Unidade::find($informada, $barbeariaId) !== null ? $informada : null;
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
        $servico = $servicoId > 0 ? Servico::find($servicoId, $barbeariaId) : null;

        if ($servico === null) {
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

            if ($profissionalId > 0 && $servico !== null) {
                $inicio = new \DateTimeImmutable($dataHora);
                $fim = $inicio->modify('+' . $servico->duracaoMinutos . ' minutes');

                if (BloqueioAgenda::doProfissionalNoPeriodo($profissionalId, $inicio->format('Y-m-d H:i:s'), $fim->format('Y-m-d H:i:s')) !== []) {
                    $errors[] = 'Esse profissional está de férias/folga ou tem um bloqueio nesse horário.';
                }
            }
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
