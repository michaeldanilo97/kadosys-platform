<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\AlunoAuth;
use Academias\Core\Controller;
use Academias\Models\Academia;
use Academias\Models\AvaliacaoFisica;

/**
 * Area do aluno pra ver o historico e a evolucao (peso/%gordura) das
 * suas avaliacoes fisicas - so leitura, quem registra e a equipe pelo
 * dashboard (ver AvaliacaoFisicaController).
 */
final class AvaliacaoController extends Controller
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
            $this->redirect('/minha-conta/' . $slug . '/entrar?next=' . urlencode('/minha-conta/' . $slug . '/avaliacao'));
        }

        echo $this->view('public.minha-conta.avaliacao', [
            'pageTitle' => 'Minha avaliação física - ' . $academia->nome,
            'academia' => $academia,
            'aluno' => $aluno,
            'historico' => AvaliacaoFisica::historicoDoAluno($academia->id, $aluno->id),
        ], 'site');
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada'], 'site');
    }
}
