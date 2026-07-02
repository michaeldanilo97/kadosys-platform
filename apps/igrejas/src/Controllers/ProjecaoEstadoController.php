<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Models\ProjecaoEstado;
use Igrejas\Models\ProjecaoSessao;

/**
 * Endpoints JSON de leitura/escrita do estado de projecao, usados por
 * todas as telas (painel do operador, telao e preletor) via polling.
 *
 * O token da sessao e o unico mecanismo de autorizacao aqui: quem tem o
 * token pode ler e alterar o estado, sem exigir login administrativo,
 * ja que sao telas de uso efemero durante um culto ao vivo.
 */
final class ProjecaoEstadoController extends Controller
{
    public function estado(string $token): void
    {
        header('Cache-Control: no-store');

        $sessao = ProjecaoSessao::findAtivaByToken($token);

        if (!$sessao) {
            $this->jsonResponse(['ativo' => false]);
        }

        $estado = ProjecaoEstado::atual($sessao->id);

        $this->jsonResponse(['ativo' => true] + ($estado?->paraJson() ?? []));
    }

    public function definirBiblia(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);

        $livroId = (int) $this->request->input('livro_id', 0);
        $capitulo = (int) $this->request->input('capitulo', 0);
        $inicio = (int) $this->request->input('versiculo_inicio', 0);
        $fim = (int) $this->request->input('versiculo_fim', 0) ?: $inicio;

        if ($livroId <= 0 || $capitulo <= 0 || $inicio <= 0) {
            $this->jsonResponse(['erro' => 'Dados invalidos.'], 422);
        }

        ProjecaoEstado::definirBiblia($sessao->id, $livroId, $capitulo, $inicio, $fim);
        $this->jsonResponse(['ok' => true]);
    }

    public function definirVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $url = trim((string) $this->request->input('url', ''));

        if ($url === '' || self::extrairIdYoutube($url) === null) {
            $this->jsonResponse(['erro' => 'Informe um link valido do YouTube.'], 422);
        }

        ProjecaoEstado::definirVideo($sessao->id, $url);
        $this->jsonResponse(['ok' => true]);
    }

    public function definirEstadoVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $estado = (string) $this->request->input('estado', '');

        ProjecaoEstado::definirEstadoVideo($sessao->id, $estado);
        $this->jsonResponse(['ok' => true]);
    }

    public function mostrarLogo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::mostrarLogo($sessao->id);
        $this->jsonResponse(['ok' => true]);
    }

    public function limpar(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::limpar($sessao->id);
        $this->jsonResponse(['ok' => true]);
    }

    private function sessaoOuErro(string $token): ProjecaoSessao
    {
        $sessao = ProjecaoSessao::findAtivaByToken($token);

        if (!$sessao) {
            $this->jsonResponse(['erro' => 'Sessao de projecao encerrada.'], 404);
        }

        return $sessao;
    }

    public static function extrairIdYoutube(string $url): ?string
    {
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
