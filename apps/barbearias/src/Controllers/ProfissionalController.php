<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Profissional;
use Barbearias\Models\User;

final class ProfissionalController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Profissional::paginate($barbeariaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.profissionais.index', [
            'pageTitle' => 'Profissionais - KADOSYS Barbearias',
            'activeMenu' => 'profissionais',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissionais' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('profissional_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.profissionais.form', [
            'pageTitle' => 'Novo profissional - KADOSYS Barbearias',
            'activeMenu' => 'profissionais',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'profissional' => null,
            'old' => Session::flash('profissional_old') ?? [],
            'errors' => Session::flash('profissional_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/profissionais');
        }

        $dados = $this->request->only(['nome', 'especialidade', 'telefone']);
        $errors = $this->validar($dados['nome'] ?? '');

        if ($errors !== []) {
            Session::flash('profissional_errors', $errors);
            Session::flash('profissional_old', $dados);
            $this->redirect('/dashboard/profissionais/novo');
        }

        Profissional::create($this->barbeariaId(), (string) $dados['nome'], $dados['especialidade'], $dados['telefone']);

        Session::flash('profissional_success', 'Profissional cadastrado com sucesso.');
        $this->redirect('/dashboard/profissionais');
    }

    public function edit(string $id): void
    {
        $profissional = Profissional::find((int) $id, $this->barbeariaId());

        if ($profissional === null) {
            $this->redirect('/dashboard/profissionais');
        }

        echo $this->view('dashboard.profissionais.form', [
            'pageTitle' => 'Editar profissional - KADOSYS Barbearias',
            'activeMenu' => 'profissionais',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'profissional' => $profissional,
            'old' => Session::flash('profissional_old') ?? [],
            'errors' => Session::flash('profissional_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Profissional::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/profissionais');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/profissionais');
        }

        $dados = $this->request->only(['nome', 'especialidade', 'telefone']);
        $errors = $this->validar($dados['nome'] ?? '');

        if ($errors !== []) {
            Session::flash('profissional_errors', $errors);
            Session::flash('profissional_old', $dados);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Profissional::update((int) $id, $barbeariaId, (string) $dados['nome'], $dados['especialidade'], $dados['telefone'], $ativo);

        Session::flash('profissional_success', 'Profissional atualizado com sucesso.');
        $this->redirect('/dashboard/profissionais');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Profissional::delete((int) $id, $this->barbeariaId());
            Session::flash('profissional_success', 'Profissional removido.');
        }

        $this->redirect('/dashboard/profissionais');
    }

    /** @return array<int, string> */
    private function validar(string $nome): array
    {
        $errors = [];

        if (trim($nome) === '' || mb_strlen(trim($nome)) < 2) {
            $errors[] = 'Informe o nome do profissional.';
        }

        return $errors;
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
