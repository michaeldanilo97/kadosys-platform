<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\BloqueioAgenda;
use Barbearias\Models\Profissional;
use Barbearias\Models\User;

final class BloqueioController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();

        echo $this->view('dashboard.bloqueios.index', [
            'pageTitle' => 'Bloqueios de agenda - KADOSYS Barbearias',
            'activeMenu' => 'bloqueios',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'bloqueios' => BloqueioAgenda::futuros($barbeariaId),
            'success' => Session::flash('bloqueio_success'),
            'errors' => Session::flash('bloqueio_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        $barbeariaId = $this->barbeariaId();

        echo $this->view('dashboard.bloqueios.form', [
            'pageTitle' => 'Novo bloqueio - KADOSYS Barbearias',
            'activeMenu' => 'bloqueios',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissionais' => Profissional::ativos($barbeariaId),
            'old' => Session::flash('bloqueio_old') ?? [],
            'errors' => Session::flash('bloqueio_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/bloqueios');
        }

        $dados = $this->request->only(['profissional_id', 'data_inicio', 'data_fim', 'motivo', 'tipo']);
        $errors = $this->validar($dados, $barbeariaId);

        if ($errors !== []) {
            Session::flash('bloqueio_errors', $errors);
            Session::flash('bloqueio_old', $dados);
            $this->redirect('/dashboard/bloqueios/novo');
        }

        BloqueioAgenda::create(
            $barbeariaId,
            (int) $dados['profissional_id'],
            $this->paraDatetime((string) $dados['data_inicio']) ?? '',
            $this->paraDatetime((string) $dados['data_fim']) ?? '',
            $dados['motivo'],
            (string) $dados['tipo'],
        );

        Session::flash('bloqueio_success', 'Bloqueio registrado com sucesso.');
        $this->redirect('/dashboard/bloqueios');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            BloqueioAgenda::delete((int) $id, $this->barbeariaId());
            Session::flash('bloqueio_success', 'Bloqueio removido.');
        }

        $this->redirect('/dashboard/bloqueios');
    }

    /** @return array<int, string> */
    private function validar(array $dados, int $barbeariaId): array
    {
        $errors = [];

        $profissionalId = (int) ($dados['profissional_id'] ?? 0);

        if ($profissionalId <= 0 || Profissional::find($profissionalId, $barbeariaId) === null) {
            $errors[] = 'Escolha um profissional válido.';
        }

        if (!in_array($dados['tipo'] ?? '', BloqueioAgenda::TIPOS, true)) {
            $errors[] = 'Escolha um tipo de bloqueio válido.';
        }

        $inicio = $this->paraDatetime((string) ($dados['data_inicio'] ?? ''));
        $fim = $this->paraDatetime((string) ($dados['data_fim'] ?? ''));

        if ($inicio === null || $fim === null) {
            $errors[] = 'Informe um início e um fim válidos.';
        } elseif ($fim <= $inicio) {
            $errors[] = 'O fim do bloqueio precisa ser depois do início.';
        }

        return $errors;
    }

    /**
     * Converte o valor de um <input type="datetime-local"> ("2026-08-01T09:00")
     * pro formato do MySQL ("2026-08-01 09:00:00").
     */
    private function paraDatetime(string $valor): ?string
    {
        $valor = trim($valor);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $valor)) {
            return null;
        }

        return str_replace('T', ' ', $valor) . ':00';
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
