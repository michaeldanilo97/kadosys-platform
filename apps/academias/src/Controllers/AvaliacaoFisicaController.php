<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\AvaliacaoFisica;
use Academias\Models\Professor;
use Academias\Models\User;

/**
 * CRUD de avaliacoes fisicas (equipe/professor) - bioimpedancia
 * simplificada: peso + medidas periodicas, que alimentam o grafico de
 * evolucao no painel do aluno (ver Academias\Controllers\AvaliacaoController).
 */
final class AvaliacaoFisicaController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $resultado = AvaliacaoFisica::paginate($academiaId, $page, self::POR_PAGINA);

        $avaliacoesComAluno = array_map(
            static fn (AvaliacaoFisica $avaliacao): array => ['avaliacao' => $avaliacao, 'aluno' => Aluno::find($avaliacao->alunoId, $academiaId)],
            $resultado['items'],
        );

        echo $this->view('dashboard.avaliacoes-fisicas.index', [
            'pageTitle' => 'Avaliação Física - KADOSYS Academias',
            'activeMenu' => 'avaliacoes-fisicas',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'avaliacoes' => $avaliacoesComAluno,
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'success' => Session::flash('avaliacao_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        $academiaId = $this->academiaId();

        echo $this->view('dashboard.avaliacoes-fisicas.form', [
            'pageTitle' => 'Nova avaliação física - KADOSYS Academias',
            'activeMenu' => 'avaliacoes-fisicas',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'avaliacao' => null,
            'alunos' => Aluno::ativos($academiaId),
            'professores' => Professor::ativos($academiaId),
            'old' => Session::flash('avaliacao_old') ?? [],
            'errors' => Session::flash('avaliacao_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/avaliacoes-fisicas');
        }

        $academiaId = $this->academiaId();
        $dados = $this->dadosFormulario();
        $errors = $this->validar($dados, $academiaId);

        if ($errors !== []) {
            Session::flash('avaliacao_errors', $errors);
            Session::flash('avaliacao_old', $dados);
            $this->redirect('/dashboard/avaliacoes-fisicas/novo');
        }

        $professorId = trim((string) ($dados['professor_id'] ?? ''));

        AvaliacaoFisica::create(
            $academiaId,
            (int) $dados['aluno_id'],
            $professorId !== '' ? (int) $professorId : null,
            (string) $dados['data_avaliacao'],
            $this->floatOuNull($dados['peso_kg'] ?? null) ?? 0.0,
            $this->floatOuNull($dados['percentual_gordura'] ?? null),
            $this->floatOuNull($dados['medida_peito_cm'] ?? null),
            $this->floatOuNull($dados['medida_cintura_cm'] ?? null),
            $this->floatOuNull($dados['medida_quadril_cm'] ?? null),
            $this->floatOuNull($dados['medida_braco_cm'] ?? null),
            $this->floatOuNull($dados['medida_coxa_cm'] ?? null),
            $dados['observacao'] ?? null,
        );

        Session::flash('avaliacao_success', 'Avaliação registrada com sucesso.');
        $this->redirect('/dashboard/avaliacoes-fisicas');
    }

    public function edit(string $id): void
    {
        $academiaId = $this->academiaId();
        $avaliacao = AvaliacaoFisica::find((int) $id, $academiaId);

        if ($avaliacao === null) {
            $this->redirect('/dashboard/avaliacoes-fisicas');
        }

        echo $this->view('dashboard.avaliacoes-fisicas.form', [
            'pageTitle' => 'Editar avaliação física - KADOSYS Academias',
            'activeMenu' => 'avaliacoes-fisicas',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'avaliacao' => $avaliacao,
            'alunos' => Aluno::ativos($academiaId),
            'professores' => Professor::ativos($academiaId),
            'old' => Session::flash('avaliacao_old') ?? [],
            'errors' => Session::flash('avaliacao_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $academiaId = $this->academiaId();
        $avaliacao = AvaliacaoFisica::find((int) $id, $academiaId);

        if ($avaliacao === null) {
            $this->redirect('/dashboard/avaliacoes-fisicas');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/avaliacoes-fisicas/' . $id . '/editar');
        }

        $dados = $this->dadosFormulario();
        $errors = $this->validar($dados, $academiaId);

        if ($errors !== []) {
            Session::flash('avaliacao_errors', $errors);
            Session::flash('avaliacao_old', $dados);
            $this->redirect('/dashboard/avaliacoes-fisicas/' . $id . '/editar');
        }

        $professorId = trim((string) ($dados['professor_id'] ?? ''));

        AvaliacaoFisica::update(
            $avaliacao->id,
            $academiaId,
            (int) $dados['aluno_id'],
            $professorId !== '' ? (int) $professorId : null,
            (string) $dados['data_avaliacao'],
            $this->floatOuNull($dados['peso_kg'] ?? null) ?? 0.0,
            $this->floatOuNull($dados['percentual_gordura'] ?? null),
            $this->floatOuNull($dados['medida_peito_cm'] ?? null),
            $this->floatOuNull($dados['medida_cintura_cm'] ?? null),
            $this->floatOuNull($dados['medida_quadril_cm'] ?? null),
            $this->floatOuNull($dados['medida_braco_cm'] ?? null),
            $this->floatOuNull($dados['medida_coxa_cm'] ?? null),
            $dados['observacao'] ?? null,
        );

        Session::flash('avaliacao_success', 'Avaliação atualizada.');
        $this->redirect('/dashboard/avaliacoes-fisicas');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            AvaliacaoFisica::delete((int) $id, $this->academiaId());
            Session::flash('avaliacao_success', 'Avaliação removida.');
        }

        $this->redirect('/dashboard/avaliacoes-fisicas');
    }

    private function dadosFormulario(): array
    {
        return $this->request->only([
            'aluno_id', 'professor_id', 'data_avaliacao', 'peso_kg', 'percentual_gordura',
            'medida_peito_cm', 'medida_cintura_cm', 'medida_quadril_cm', 'medida_braco_cm', 'medida_coxa_cm', 'observacao',
        ]);
    }

    /** @return array<int, string> */
    private function validar(array $dados, int $academiaId): array
    {
        $errors = [];
        $alunoId = (int) ($dados['aluno_id'] ?? 0);

        if ($alunoId <= 0 || Aluno::find($alunoId, $academiaId) === null) {
            $errors[] = 'Selecione o aluno avaliado.';
        }

        $dataAvaliacao = trim((string) ($dados['data_avaliacao'] ?? ''));

        if ($dataAvaliacao === '') {
            $errors[] = 'Informe a data da avaliação.';
        }

        $peso = $this->floatOuNull($dados['peso_kg'] ?? null);

        if ($peso === null || $peso <= 0) {
            $errors[] = 'Informe o peso (kg).';
        }

        return $errors;
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
