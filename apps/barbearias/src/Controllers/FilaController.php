<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FilaAtendimento;
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

        FilaAtendimento::entrar($barbeariaId, $nome, $telefone !== '' ? $telefone : null, $profissionalId > 0 ? $profissionalId : null);

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

    public function concluir(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Csrf::verify($this->request->input('_csrf_token')) && FilaAtendimento::find((int) $id, $barbeariaId) !== null) {
            FilaAtendimento::concluir((int) $id, $barbeariaId);
            Session::flash('fila_success', 'Atendimento concluído.');
        }

        $this->redirect('/dashboard/fila');
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

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
