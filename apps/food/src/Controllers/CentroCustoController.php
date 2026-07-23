<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\CentroCusto;
use Food\Models\Restaurante;
use Food\Models\User;

final class CentroCustoController extends Controller
{
    public function index(): void
    {
        $restauranteId = $this->restauranteId();

        echo $this->view('dashboard.financeiro.centros-custo.index', [
            'pageTitle' => 'Centros de Custo - KADOSYS Food',
            'activeMenu' => 'financeiro',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'centros' => CentroCusto::todos($restauranteId),
            'success' => Session::flash('centro_custo_success'),
            'errors' => Session::flash('centro_custo_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro/centros-custo');
        }

        $nome = trim((string) $this->request->input('nome', ''));

        if ($nome === '') {
            Session::flash('centro_custo_errors', ['Informe o nome do centro de custo.']);
            $this->redirect('/dashboard/financeiro/centros-custo');
        }

        CentroCusto::create($restauranteId, $nome);

        Session::flash('centro_custo_success', 'Centro de custo cadastrado com sucesso.');
        $this->redirect('/dashboard/financeiro/centros-custo');
    }

    public function update(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (CentroCusto::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/financeiro/centros-custo');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/financeiro/centros-custo');
        }

        $nome = trim((string) $this->request->input('nome', ''));

        if ($nome === '') {
            Session::flash('centro_custo_errors', ['Informe o nome do centro de custo.']);
            $this->redirect('/dashboard/financeiro/centros-custo');
        }

        $ativo = $this->request->input('ativo') !== null;

        CentroCusto::update((int) $id, $restauranteId, $nome, $ativo);

        Session::flash('centro_custo_success', 'Centro de custo atualizado com sucesso.');
        $this->redirect('/dashboard/financeiro/centros-custo');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            CentroCusto::delete((int) $id, $this->restauranteId());
            Session::flash('centro_custo_success', 'Centro de custo removido.');
        }

        $this->redirect('/dashboard/financeiro/centros-custo');
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
