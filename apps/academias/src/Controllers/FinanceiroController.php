<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\Caixa;
use Academias\Models\FinanceiroLancamento;
use Academias\Models\User;

final class FinanceiroController extends Controller
{
    private const POR_PAGINA = 20;

    public function index(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $tipo = (string) $this->request->input('tipo', '');

        $hoje = new \DateTimeImmutable();
        $resumoDia = FinanceiroLancamento::resumoDoPeriodo($academiaId, $hoje->format('Y-m-d'), $hoje->format('Y-m-d'));
        $resumoMes = FinanceiroLancamento::resumoDoPeriodo(
            $academiaId,
            $hoje->format('Y-m-01'),
            $hoje->format('Y-m-t'),
        );

        $resultado = FinanceiroLancamento::paginate($academiaId, $page, self::POR_PAGINA, $tipo);
        $caixa = Caixa::aberto($academiaId);

        echo $this->view('dashboard.financeiro.index', [
            'pageTitle' => 'Financeiro - KADOSYS Academias',
            'activeMenu' => 'financeiro',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'caixa' => $caixa,
            'lancamentosDoCaixa' => $caixa !== null ? FinanceiroLancamento::doCaixa($caixa->id, $academiaId) : [],
            'resumoDia' => $resumoDia,
            'resumoMes' => $resumoMes,
            'lancamentos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'tipo' => $tipo,
            'formasPagamento' => FinanceiroLancamento::FORMAS_PAGAMENTO,
            'success' => Session::flash('financeiro_success'),
            'errors' => Session::flash('financeiro_errors') ?? [],
            'old' => Session::flash('financeiro_old') ?? [],
        ], 'dashboard');
    }

    public function abrirCaixa(): void
    {
        $academiaId = $this->academiaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro');
        }

        if (Caixa::aberto($academiaId) !== null) {
            Session::flash('financeiro_errors', ['Já existe um caixa aberto.']);
            $this->redirect('/dashboard/financeiro');
        }

        $valor = $this->paraFloat($this->request->input('valor_abertura', '0'));
        $observacoes = (string) $this->request->input('observacoes', '');

        Caixa::abrir($academiaId, $this->usuario()?->id, $valor, $observacoes);

        Session::flash('financeiro_success', 'Caixa aberto com sucesso.');
        $this->redirect('/dashboard/financeiro');
    }

    public function fecharCaixa(): void
    {
        $academiaId = $this->academiaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro');
        }

        $caixa = Caixa::aberto($academiaId);

        if ($caixa === null) {
            Session::flash('financeiro_errors', ['Não há caixa aberto para fechar.']);
            $this->redirect('/dashboard/financeiro');
        }

        $valor = $this->paraFloat($this->request->input('valor_fechamento_informado', '0'));
        $observacoes = (string) $this->request->input('observacoes', '');

        Caixa::fechar($caixa->id, $academiaId, $valor, $observacoes);

        Session::flash('financeiro_success', 'Caixa fechado com sucesso.');
        $this->redirect('/dashboard/financeiro');
    }

    public function store(): void
    {
        $academiaId = $this->academiaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro');
        }

        $dados = $this->request->only(['tipo', 'categoria', 'forma_pagamento', 'valor', 'descricao', 'data_lancamento', 'aluno_id']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('financeiro_errors', $errors);
            Session::flash('financeiro_old', $dados);
            $this->redirect('/dashboard/financeiro');
        }

        $caixa = Caixa::aberto($academiaId);
        $alunoId = trim((string) ($dados['aluno_id'] ?? ''));

        FinanceiroLancamento::create(
            $academiaId,
            $caixa?->id,
            $alunoId !== '' && Aluno::find((int) $alunoId, $academiaId) !== null ? (int) $alunoId : null,
            $this->usuario()?->id,
            (string) $dados['tipo'],
            (string) $dados['categoria'],
            (string) $dados['forma_pagamento'],
            $this->paraFloat($dados['valor']),
            $dados['descricao'] !== null ? (string) $dados['descricao'] : null,
            (string) $dados['data_lancamento'],
        );

        Session::flash('financeiro_success', 'Lançamento registrado com sucesso.');
        $this->redirect('/dashboard/financeiro');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FinanceiroLancamento::delete((int) $id, $this->academiaId());
            Session::flash('financeiro_success', 'Lançamento removido.');
        }

        $this->redirect('/dashboard/financeiro');
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];

        if (!in_array($dados['tipo'] ?? '', [FinanceiroLancamento::TIPO_RECEITA, FinanceiroLancamento::TIPO_DESPESA], true)) {
            $errors[] = 'Escolha receita ou despesa.';
        }

        $categoria = trim((string) ($dados['categoria'] ?? ''));

        if ($categoria === '') {
            $errors[] = 'Informe a categoria do lançamento.';
        }

        if (!in_array($dados['forma_pagamento'] ?? '', FinanceiroLancamento::FORMAS_PAGAMENTO, true)) {
            $errors[] = 'Escolha uma forma de pagamento válida.';
        }

        $valor = $this->paraFloat((string) ($dados['valor'] ?? ''));

        if ($valor <= 0) {
            $errors[] = 'Informe um valor válido.';
        }

        $data = trim((string) ($dados['data_lancamento'] ?? ''));

        if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $errors[] = 'Informe uma data válida.';
        }

        return $errors;
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

    private function academiaId(): int
    {
        return $this->usuario()?->academiaId ?? 0;
    }
}
