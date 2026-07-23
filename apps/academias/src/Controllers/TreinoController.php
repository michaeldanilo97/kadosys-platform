<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\AlunoAuth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Models\Academia;
use Academias\Models\FichaExercicio;
use Academias\Models\FichaTreino;
use Academias\Models\TreinoExecucao;

/**
 * Area do aluno pro treino do dia - listar as fichas ativas dele e
 * marcar exercicios como feitos (com a carga usada), que alimenta o
 * grafico de evolucao. Exige aluno logado, mesmo padrao de
 * AlunoAreaController (nao reusa ela pra nao misturar "conta" com
 * "treino" no mesmo controller).
 */
final class TreinoController extends Controller
{
    public function index(string $slug): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        $aluno = (new AlunoAuth($this->config))->user($academia->id);

        if ($aluno === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar?next=' . urlencode('/minha-conta/' . $slug . '/treino'));
        }

        echo $this->view('public.minha-conta.treino-index', [
            'pageTitle' => 'Meu treino - ' . $academia->nome,
            'academia' => $academia,
            'aluno' => $aluno,
            'fichas' => FichaTreino::ativasDoAluno($academia->id, $aluno->id),
        ], 'site');
    }

    public function show(string $slug, string $fichaId): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        $aluno = (new AlunoAuth($this->config))->user($academia->id);

        if ($aluno === null) {
            $proximo = '/minha-conta/' . $slug . '/treino/' . $fichaId;
            $this->redirect('/minha-conta/' . $slug . '/entrar?next=' . urlencode($proximo));
        }

        $ficha = FichaTreino::find((int) $fichaId, $academia->id);

        if ($ficha === null || $ficha->alunoId !== $aluno->id) {
            $this->renderNotFound();

            return;
        }

        $exercicios = FichaExercicio::porFicha($ficha->id);
        $execucoes = [];

        foreach ($exercicios as $exercicio) {
            $execucoes[$exercicio->id] = [
                'hoje' => TreinoExecucao::hojeDoExercicio($exercicio->id, $aluno->id),
                'evolucao' => TreinoExecucao::evolucaoDoExercicio($exercicio->id, $aluno->id),
            ];
        }

        echo $this->view('public.minha-conta.treino-ficha', [
            'pageTitle' => $ficha->nome . ' - ' . $academia->nome,
            'academia' => $academia,
            'aluno' => $aluno,
            'ficha' => $ficha,
            'exercicios' => $exercicios,
            'execucoes' => $execucoes,
            'csrf' => Csrf::field(),
        ], 'site');
    }

    public function registrar(string $slug, string $fichaId, string $exercicioId): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        $aluno = (new AlunoAuth($this->config))->user($academia->id);
        $ficha = $aluno !== null ? FichaTreino::find((int) $fichaId, $academia->id) : null;
        $exercicio = $ficha !== null ? FichaExercicio::find((int) $exercicioId, $ficha->id) : null;

        if ($aluno !== null && $ficha !== null && $ficha->alunoId === $aluno->id && $exercicio !== null
            && Csrf::verify($this->request->input('_csrf_token'))
        ) {
            $carga = trim((string) str_replace(',', '.', (string) $this->request->input('carga_usada_kg', '')));
            $series = trim((string) $this->request->input('series_completas', ''));

            TreinoExecucao::registrar(
                $exercicio->id,
                $aluno->id,
                $carga === '' ? null : (float) $carga,
                $series === '' ? null : max(0, (int) $series),
            );
        }

        $this->redirect('/minha-conta/' . $slug . '/treino/' . $fichaId);
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada'], 'site');
    }
}
