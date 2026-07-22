<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\PlanoMatricula;
use Academias\Models\User;

final class PlanoMatriculaController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = PlanoMatricula::paginate($academiaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.planos-matricula.index', [
            'pageTitle' => 'Planos de Matrícula - KADOSYS Academias',
            'activeMenu' => 'planos-matricula',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'planos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('plano_matricula_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.planos-matricula.form', [
            'pageTitle' => 'Novo plano de matrícula - KADOSYS Academias',
            'activeMenu' => 'planos-matricula',
            'user' => $this->usuario(),
            'academia' => Academia::find($this->academiaId()),
            'plano' => null,
            'old' => Session::flash('plano_matricula_old') ?? [],
            'errors' => Session::flash('plano_matricula_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/planos-matricula');
        }

        $dados = $this->request->only(['nome', 'preco', 'duracao_dias', 'descricao']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('plano_matricula_errors', $errors);
            Session::flash('plano_matricula_old', $dados);
            $this->redirect('/dashboard/planos-matricula/novo');
        }

        PlanoMatricula::create(
            $this->academiaId(),
            (string) $dados['nome'],
            $this->paraFloat($dados['preco'] ?? '0'),
            (int) $dados['duracao_dias'],
            $dados['descricao'],
        );

        Session::flash('plano_matricula_success', 'Plano de matrícula cadastrado com sucesso.');
        $this->redirect('/dashboard/planos-matricula');
    }

    public function edit(string $id): void
    {
        $plano = PlanoMatricula::find((int) $id, $this->academiaId());

        if ($plano === null) {
            $this->redirect('/dashboard/planos-matricula');
        }

        echo $this->view('dashboard.planos-matricula.form', [
            'pageTitle' => 'Editar plano de matrícula - KADOSYS Academias',
            'activeMenu' => 'planos-matricula',
            'user' => $this->usuario(),
            'academia' => Academia::find($this->academiaId()),
            'plano' => $plano,
            'old' => Session::flash('plano_matricula_old') ?? [],
            'errors' => Session::flash('plano_matricula_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $academiaId = $this->academiaId();

        if (PlanoMatricula::find((int) $id, $academiaId) === null) {
            $this->redirect('/dashboard/planos-matricula');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/planos-matricula');
        }

        $dados = $this->request->only(['nome', 'preco', 'duracao_dias', 'descricao', 'ativo']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('plano_matricula_errors', $errors);
            Session::flash('plano_matricula_old', $dados);
            $this->redirect('/dashboard/planos-matricula/' . $id . '/editar');
        }

        PlanoMatricula::update(
            (int) $id,
            $academiaId,
            (string) $dados['nome'],
            $this->paraFloat($dados['preco'] ?? '0'),
            (int) $dados['duracao_dias'],
            $dados['descricao'],
            (bool) ($dados['ativo'] ?? false),
        );

        Session::flash('plano_matricula_success', 'Plano de matrícula atualizado com sucesso.');
        $this->redirect('/dashboard/planos-matricula');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            PlanoMatricula::delete((int) $id, $this->academiaId());
            Session::flash('plano_matricula_success', 'Plano de matrícula removido.');
        }

        $this->redirect('/dashboard/planos-matricula');
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do plano.';
        }

        if ($this->paraFloat($dados['preco'] ?? '') <= 0) {
            $errors[] = 'Informe um preço válido.';
        }

        $duracao = (int) ($dados['duracao_dias'] ?? 0);

        if ($duracao <= 0) {
            $errors[] = 'Informe a duração em dias (maior que zero).';
        }

        return $errors;
    }

    private function paraFloat(string $valor): float
    {
        $valor = str_replace(['.', ','], ['', '.'], trim($valor));

        return (float) $valor;
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
