<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\ListaEspera;
use Barbearias\Models\User;

final class ListaEsperaController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();

        echo $this->view('dashboard.lista-espera.index', [
            'pageTitle' => 'Lista de espera - KADOSYS Barbearias',
            'activeMenu' => 'lista-espera',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'entradas' => ListaEspera::aguardando($barbeariaId),
            'success' => Session::flash('lista_espera_success'),
        ], 'dashboard');
    }

    public function atender(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ListaEspera::atualizarStatus((int) $id, $this->barbeariaId(), ListaEspera::STATUS_ATENDIDO);
            Session::flash('lista_espera_success', 'Marcado como atendido.');
        }

        $this->redirect('/dashboard/lista-espera');
    }

    public function cancelar(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ListaEspera::atualizarStatus((int) $id, $this->barbeariaId(), ListaEspera::STATUS_CANCELADO);
            Session::flash('lista_espera_success', 'Removido da lista de espera.');
        }

        $this->redirect('/dashboard/lista-espera');
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
