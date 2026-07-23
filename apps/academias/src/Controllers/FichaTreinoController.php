<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\FichaExercicio;
use Academias\Models\FichaTreino;
use Academias\Models\Professor;
use Academias\Models\User;

/**
 * CRUD de fichas de treino (equipe/professor) - cada ficha pertence a
 * um aluno e reune os exercicios que ele deve fazer. Gerenciar os
 * exercicios de uma ficha acontece na mesma tela de edicao (ver
 * dashboard.fichas-treino.editar), nao em telas separadas.
 */
final class FichaTreinoController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $resultado = FichaTreino::paginate($academiaId, $page, self::POR_PAGINA);

        $fichasComAluno = array_map(
            static fn (FichaTreino $ficha): array => ['ficha' => $ficha, 'aluno' => Aluno::find($ficha->alunoId, $academiaId)],
            $resultado['items'],
        );

        echo $this->view('dashboard.fichas-treino.index', [
            'pageTitle' => 'Fichas de Treino - KADOSYS Academias',
            'activeMenu' => 'fichas-treino',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'fichas' => $fichasComAluno,
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'success' => Session::flash('ficha_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        $academiaId = $this->academiaId();

        echo $this->view('dashboard.fichas-treino.form', [
            'pageTitle' => 'Nova ficha de treino - KADOSYS Academias',
            'activeMenu' => 'fichas-treino',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'ficha' => null,
            'alunos' => Aluno::ativos($academiaId),
            'professores' => Professor::ativos($academiaId),
            'old' => Session::flash('ficha_old') ?? [],
            'errors' => Session::flash('ficha_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fichas-treino');
        }

        $academiaId = $this->academiaId();
        $dados = $this->request->only(['aluno_id', 'professor_id', 'nome', 'objetivo', 'validade_ate']);
        $errors = $this->validar($dados, $academiaId);

        if ($errors !== []) {
            Session::flash('ficha_errors', $errors);
            Session::flash('ficha_old', $dados);
            $this->redirect('/dashboard/fichas-treino/novo');
        }

        $professorId = trim((string) ($dados['professor_id'] ?? ''));

        $id = FichaTreino::create(
            $academiaId,
            (int) $dados['aluno_id'],
            $professorId !== '' ? (int) $professorId : null,
            (string) $dados['nome'],
            $dados['objetivo'],
            $dados['validade_ate'],
        );

        Session::flash('ficha_success', 'Ficha criada - agora adicione os exercícios.');
        $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
    }

    public function edit(string $id): void
    {
        $academiaId = $this->academiaId();
        $ficha = FichaTreino::find((int) $id, $academiaId);

        if ($ficha === null) {
            $this->redirect('/dashboard/fichas-treino');
        }

        $exercicioEmEdicaoId = (int) $this->request->input('exercicio', 0);
        $exercicioEmEdicao = $exercicioEmEdicaoId > 0 ? FichaExercicio::find($exercicioEmEdicaoId, $ficha->id) : null;

        echo $this->view('dashboard.fichas-treino.editar', [
            'pageTitle' => 'Editar ficha - KADOSYS Academias',
            'activeMenu' => 'fichas-treino',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'ficha' => $ficha,
            'aluno' => Aluno::find($ficha->alunoId, $academiaId),
            'alunos' => Aluno::ativos($academiaId),
            'professores' => Professor::ativos($academiaId),
            'exercicios' => FichaExercicio::porFicha($ficha->id),
            'exercicioEmEdicao' => $exercicioEmEdicao,
            'success' => Session::flash('ficha_success'),
            'errors' => Session::flash('ficha_errors') ?? [],
            'exercicioErrors' => Session::flash('exercicio_errors') ?? [],
            'exercicioOld' => Session::flash('exercicio_old') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $academiaId = $this->academiaId();
        $ficha = FichaTreino::find((int) $id, $academiaId);

        if ($ficha === null) {
            $this->redirect('/dashboard/fichas-treino');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
        }

        $dados = $this->request->only(['aluno_id', 'professor_id', 'nome', 'objetivo', 'validade_ate', 'ativa']);
        $errors = $this->validar($dados, $academiaId);

        if ($errors !== []) {
            Session::flash('ficha_errors', $errors);
            $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
        }

        $professorId = trim((string) ($dados['professor_id'] ?? ''));

        FichaTreino::update(
            $ficha->id,
            $academiaId,
            (int) $dados['aluno_id'],
            $professorId !== '' ? (int) $professorId : null,
            (string) $dados['nome'],
            $dados['objetivo'],
            $dados['validade_ate'],
            (bool) ($dados['ativa'] ?? false),
        );

        Session::flash('ficha_success', 'Ficha atualizada.');
        $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FichaTreino::delete((int) $id, $this->academiaId());
            Session::flash('ficha_success', 'Ficha removida.');
        }

        $this->redirect('/dashboard/fichas-treino');
    }

    public function storeExercicio(string $id): void
    {
        $academiaId = $this->academiaId();
        $ficha = FichaTreino::find((int) $id, $academiaId);

        if ($ficha === null) {
            $this->redirect('/dashboard/fichas-treino');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
        }

        $dados = $this->request->only([
            'nome_exercicio', 'grupo_muscular', 'series', 'repeticoes', 'carga_sugerida_kg', 'descanso_segundos', 'observacao',
        ]);
        $errors = $this->validarExercicio($dados);

        if ($errors !== []) {
            Session::flash('exercicio_errors', $errors);
            Session::flash('exercicio_old', $dados);
            $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
        }

        FichaExercicio::create(
            $ficha->id,
            (string) $dados['nome_exercicio'],
            $dados['grupo_muscular'],
            $this->intOuNull($dados['series'] ?? null),
            $dados['repeticoes'],
            $this->floatOuNull($dados['carga_sugerida_kg'] ?? null),
            $this->intOuNull($dados['descanso_segundos'] ?? null),
            $dados['observacao'],
        );

        Session::flash('ficha_success', 'Exercício adicionado.');
        $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
    }

    public function updateExercicio(string $id, string $exercicioId): void
    {
        $academiaId = $this->academiaId();
        $ficha = FichaTreino::find((int) $id, $academiaId);

        if ($ficha === null || FichaExercicio::find((int) $exercicioId, $ficha->id) === null) {
            $this->redirect('/dashboard/fichas-treino');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
        }

        $dados = $this->request->only([
            'nome_exercicio', 'grupo_muscular', 'series', 'repeticoes', 'carga_sugerida_kg', 'descanso_segundos', 'observacao',
        ]);
        $errors = $this->validarExercicio($dados);

        if ($errors !== []) {
            Session::flash('exercicio_errors', $errors);
            Session::flash('exercicio_old', $dados);
            $this->redirect('/dashboard/fichas-treino/' . $id . '/editar?exercicio=' . $exercicioId);
        }

        FichaExercicio::update(
            (int) $exercicioId,
            $ficha->id,
            (string) $dados['nome_exercicio'],
            $dados['grupo_muscular'],
            $this->intOuNull($dados['series'] ?? null),
            $dados['repeticoes'],
            $this->floatOuNull($dados['carga_sugerida_kg'] ?? null),
            $this->intOuNull($dados['descanso_segundos'] ?? null),
            $dados['observacao'],
        );

        Session::flash('ficha_success', 'Exercício atualizado.');
        $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
    }

    public function destroyExercicio(string $id, string $exercicioId): void
    {
        $academiaId = $this->academiaId();
        $ficha = FichaTreino::find((int) $id, $academiaId);

        if ($ficha !== null && Csrf::verify($this->request->input('_csrf_token'))) {
            FichaExercicio::delete((int) $exercicioId, $ficha->id);
            Session::flash('ficha_success', 'Exercício removido.');
        }

        $this->redirect('/dashboard/fichas-treino/' . $id . '/editar');
    }

    /** @return array<int, string> */
    private function validar(array $dados, int $academiaId): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe um nome pra ficha (ex.: "Treino A - Superior").';
        }

        $alunoId = (int) ($dados['aluno_id'] ?? 0);

        if ($alunoId <= 0 || Aluno::find($alunoId, $academiaId) === null) {
            $errors[] = 'Selecione o aluno dessa ficha.';
        }

        return $errors;
    }

    /** @return array<int, string> */
    private function validarExercicio(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome_exercicio'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do exercício.';
        }

        return $errors;
    }

    private function intOuNull(mixed $valor): ?int
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : max(0, (int) $valor);
    }

    private function floatOuNull(mixed $valor): ?float
    {
        $valor = trim((string) str_replace(',', '.', (string) $valor));

        return $valor === '' ? null : (float) $valor;
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
