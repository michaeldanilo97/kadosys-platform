<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Caixa;
use Food\Models\FinanceiroLancamento;
use Food\Models\Restaurante;
use Food\Models\User;

final class CaixaController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $caixa = Caixa::aberto($restauranteId);
        $page = max(1, (int) $this->request->input('pagina', 1));
        $historico = Caixa::historico($restauranteId, $page, self::POR_PAGINA);

        echo $this->view('dashboard.caixa.index', [
            'pageTitle' => 'Caixa - KADOSYS Food',
            'activeMenu' => 'caixa',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'caixa' => $caixa,
            'saldoEsperado' => $caixa !== null ? Caixa::saldoEsperado($caixa) : 0.0,
            'lancamentosDoCaixa' => $caixa !== null ? FinanceiroLancamento::doCaixa($caixa->id, $restauranteId) : [],
            'historico' => $historico['items'],
            'total' => $historico['total'],
            'page' => $historico['page'],
            'lastPage' => $historico['lastPage'],
            'success' => Session::flash('caixa_success'),
            'errors' => Session::flash('caixa_errors') ?? [],
        ], 'dashboard');
    }

    public function abrir(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/caixa');
        }

        if (Caixa::aberto($restauranteId) !== null) {
            Session::flash('caixa_errors', ['Já existe um caixa aberto.']);
            $this->redirect('/dashboard/caixa');
        }

        $valor = $this->paraFloat((string) $this->request->input('valor_abertura', '0'));
        $observacoes = (string) $this->request->input('observacoes', '');

        Caixa::abrir($restauranteId, $this->usuario()?->id, $valor, $observacoes);

        Session::flash('caixa_success', 'Caixa aberto com sucesso.');
        $this->redirect('/dashboard/caixa');
    }

    public function fechar(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/caixa');
        }

        $caixa = Caixa::aberto($restauranteId);

        if ($caixa === null) {
            Session::flash('caixa_errors', ['Não há caixa aberto para fechar.']);
            $this->redirect('/dashboard/caixa');
        }

        $valor = $this->paraFloat((string) $this->request->input('valor_fechamento_informado', '0'));
        $observacoes = (string) $this->request->input('observacoes', '');

        Caixa::fechar($caixa->id, $restauranteId, $valor, $observacoes);

        Session::flash('caixa_success', 'Caixa fechado com sucesso.');
        $this->redirect('/dashboard/caixa');
    }

    public function sangria(): void
    {
        $this->registrarMovimento(
            FinanceiroLancamento::TIPO_DESPESA,
            FinanceiroLancamento::CATEGORIA_SANGRIA,
            'Sangria registrada com sucesso.',
        );
    }

    public function suprimento(): void
    {
        $this->registrarMovimento(
            FinanceiroLancamento::TIPO_RECEITA,
            FinanceiroLancamento::CATEGORIA_SUPRIMENTO,
            'Suprimento registrado com sucesso.',
        );
    }

    private function registrarMovimento(string $tipo, string $categoria, string $mensagemSucesso): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/caixa');
        }

        $caixa = Caixa::aberto($restauranteId);

        if ($caixa === null) {
            Session::flash('caixa_errors', ['Abra o caixa antes de registrar movimentações.']);
            $this->redirect('/dashboard/caixa');
        }

        $valor = $this->paraFloat((string) $this->request->input('valor', '0'));
        $motivo = trim((string) $this->request->input('motivo', ''));

        if ($valor <= 0) {
            Session::flash('caixa_errors', ['Informe um valor válido.']);
            $this->redirect('/dashboard/caixa');
        }

        if ($motivo === '') {
            Session::flash('caixa_errors', ['Informe o motivo da movimentação.']);
            $this->redirect('/dashboard/caixa');
        }

        FinanceiroLancamento::create(
            $restauranteId,
            null,
            $tipo,
            $categoria,
            'dinheiro',
            $valor,
            $motivo,
            (new \DateTimeImmutable())->format('Y-m-d'),
            $caixa->id,
        );

        Session::flash('caixa_success', $mensagemSucesso);
        $this->redirect('/dashboard/caixa');
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
