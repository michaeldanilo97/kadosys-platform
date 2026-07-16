<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FilaAtendimento;
use Barbearias\Models\Profissional;

/**
 * Fila publica (sem login) - o cliente final entra na fila de espera
 * direto pelo link "/fila/{slug}", sem precisar agendar horario. So
 * existe pra barbearias com modo_atendimento = 'fila' (ver
 * Barbearias\Models\Barbearia::usaFila()).
 */
final class FilaPublicaController extends Controller
{
    public function mostrar(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null || !$barbearia->usaFila()) {
            $this->renderNotFound();

            return;
        }

        $aguardando = FilaAtendimento::contarAguardando($barbearia->id);

        echo $this->view('public.fila', [
            'pageTitle' => 'Fila de espera - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'profissionais' => Profissional::ativos($barbearia->id),
            'aguardando' => $aguardando,
            'esperaEstimada' => FilaAtendimento::estimarEsperaMinutos($barbearia->id, $aguardando),
            'csrf' => Csrf::field(),
            'errors' => Session::flash('fila_publica_errors') ?? [],
            'old' => Session::flash('fila_publica_old') ?? [],
        ], 'site');
    }

    public function entrar(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null || !$barbearia->usaFila()) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('fila_publica_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/fila/' . $slug);
        }

        $dados = $this->request->only(['nome', 'telefone', 'profissional_id']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('fila_publica_errors', $errors);
            Session::flash('fila_publica_old', $dados);
            $this->redirect('/fila/' . $slug);
        }

        $profissionalId = (int) ($dados['profissional_id'] ?? 0);
        $profissional = $profissionalId > 0 ? Profissional::find($profissionalId, $barbearia->id) : null;

        $id = FilaAtendimento::entrar(
            $barbearia->id,
            (string) $dados['nome'],
            $this->apenasDigitos((string) ($dados['telefone'] ?? '')) ?: null,
            $profissional?->id,
        );

        Session::flash('fila_publica_entrada', $id);
        $this->redirect('/fila/' . $slug . '/confirmado');
    }

    public function confirmado(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);
        $filaId = Session::flash('fila_publica_entrada');

        if ($barbearia === null || !$barbearia->usaFila() || $filaId === null) {
            $this->redirect('/fila/' . $slug);
        }

        $item = FilaAtendimento::find((int) $filaId, $barbearia->id);

        if ($item === null) {
            $this->redirect('/fila/' . $slug);
        }

        $ativos = FilaAtendimento::ativos($barbearia->id);
        $posicao = 1;

        foreach ($ativos as $indice => $outro) {
            if ($outro->id === $item->id) {
                $posicao = $indice + 1;
                break;
            }
        }

        $pessoasNaFrente = max(0, $posicao - 1);

        echo $this->view('public.fila-confirmado', [
            'pageTitle' => 'Você está na fila - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'item' => $item,
            'posicao' => $posicao,
            'esperaEstimada' => FilaAtendimento::estimarEsperaMinutos($barbearia->id, $pessoasNaFrente),
        ], 'site');
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];

        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        $telefone = $this->apenasDigitos((string) ($dados['telefone'] ?? ''));

        if ($telefone !== '' && mb_strlen($telefone) < 10) {
            $errors[] = 'Informe um telefone válido com DDD (ou deixe em branco).';
        }

        return $errors;
    }

    private function apenasDigitos(string $valor): string
    {
        return preg_replace('/\D+/', '', $valor) ?? '';
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada'], 'site');
    }
}
