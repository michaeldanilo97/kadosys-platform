<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Models\BibliaVersao;
use Igrejas\Models\BibliaVersiculo;
use Igrejas\Models\ProjecaoEstado;
use Igrejas\Models\ProjecaoImagem;
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

        $bibliaVersao = (string) $this->request->input('biblia_versao', BibliaVersao::PADRAO);
        $livroId = (int) $this->request->input('livro_id', 0);
        $capitulo = (int) $this->request->input('capitulo', 0);
        $inicio = (int) $this->request->input('versiculo_inicio', 0);
        $fim = (int) $this->request->input('versiculo_fim', 0) ?: $inicio;

        if (!BibliaVersao::valida($bibliaVersao) || $livroId <= 0 || $capitulo <= 0 || $inicio <= 0) {
            $this->jsonResponse(['erro' => 'Dados inválidos.'], 422);
        }

        ProjecaoEstado::definirBiblia($sessao->id, $bibliaVersao, $livroId, $capitulo, $inicio, $fim, $this->origemDaRequisicao());
        $this->jsonResponse(['ok' => true] + (ProjecaoEstado::atual($sessao->id)?->paraJson() ?? []));
    }

    /**
     * Avanca ou retrocede um versiculo a partir da referencia biblica
     * atualmente projetada (navegacao por teclado do operador/preletor,
     * sem precisar reabrir o formulario a cada versiculo).
     */
    public function navegarBiblia(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $direcao = (string) $this->request->input('direcao', 'proximo');

        $estadoAtual = ProjecaoEstado::atual($sessao->id);

        if (!$estadoAtual || $estadoAtual->modo !== 'biblia' || $estadoAtual->livroId === null || $estadoAtual->capitulo === null || $estadoAtual->bibliaVersao === null) {
            $this->jsonResponse(['erro' => 'Nenhuma referência bíblica em projeção no momento.'], 422);
        }

        $posicaoAtual = $estadoAtual->versiculoFim ?? $estadoAtual->versiculoInicio ?? 1;

        $nova = $direcao === 'anterior'
            ? BibliaVersiculo::referenciaAnterior($estadoAtual->bibliaVersao, $estadoAtual->livroId, $estadoAtual->capitulo, $posicaoAtual)
            : BibliaVersiculo::proximaReferencia($estadoAtual->bibliaVersao, $estadoAtual->livroId, $estadoAtual->capitulo, $posicaoAtual);

        if ($nova === null) {
            $this->jsonResponse(['erro' => 'Limite da bíblia atingido.'], 422);
        }

        ProjecaoEstado::definirBiblia(
            $sessao->id,
            $estadoAtual->bibliaVersao,
            $nova['livro_id'],
            $nova['capitulo'],
            $nova['versiculo'],
            $nova['versiculo'],
            $this->origemDaRequisicao()
        );

        $this->jsonResponse(['ok' => true] + (ProjecaoEstado::atual($sessao->id)?->paraJson() ?? []));
    }

    /**
     * Numero de versiculos de um capitulo, usado para popular o select
     * de versiculo no operador e no preletor (sem precisar digitar um
     * numero "no escuro", sem saber onde o capitulo termina).
     */
    public function capituloInfo(string $token): void
    {
        $this->sessaoOuErro($token);

        $bibliaVersao = (string) $this->request->input('versao', BibliaVersao::PADRAO);
        $livroId = (int) $this->request->input('livro_id', 0);
        $capitulo = (int) $this->request->input('capitulo', 0);

        if (!BibliaVersao::valida($bibliaVersao) || $livroId <= 0 || $capitulo <= 0) {
            $this->jsonResponse(['erro' => 'Dados inválidos.'], 422);
        }

        $this->jsonResponse(['totalVersiculos' => BibliaVersiculo::totalVersiculos($bibliaVersao, $livroId, $capitulo)]);
    }

    /**
     * Marcacao a lapis do preletor sobre o texto em projecao. Os tracos
     * chegam como fracao (0..1) das dimensoes do "palco" de projecao -
     * nao pixels - para que a mesma marcacao caiba corretamente tanto no
     * tablet do preletor quanto no telao, que tem tamanhos diferentes.
     */
    public function definirMarcacao(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $bruto = json_decode((string) $this->request->input('tracos', '[]'), true);

        if (!is_array($bruto)) {
            $this->jsonResponse(['erro' => 'Dados inválidos.'], 422);
        }

        $tracos = [];
        foreach ($bruto as $traco) {
            if (!is_array($traco)) {
                continue;
            }

            $pontos = [];
            foreach ($traco as $ponto) {
                if (!is_array($ponto) || !isset($ponto['x'], $ponto['y']) || !is_numeric($ponto['x']) || !is_numeric($ponto['y'])) {
                    continue;
                }

                $pontos[] = ['x' => max(0.0, min(1.0, (float) $ponto['x'])), 'y' => max(0.0, min(1.0, (float) $ponto['y']))];
            }

            if ($pontos !== []) {
                $tracos[] = $pontos;
            }
        }

        ProjecaoEstado::definirMarcacao($sessao->id, $tracos);
        $this->jsonResponse(['ok' => true]);
    }

    /**
     * "Ler agora" - pede pro telao ler em voz alta o texto biblico
     * projetado no momento (ver ProjecaoEstado::lerAgora() e telao.js).
     */
    public function lerBiblia(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::lerAgora($sessao->id);
        $this->jsonResponse(['ok' => true]);
    }

    public function definirVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $url = trim((string) $this->request->input('url', ''));

        if ($url === '' || self::extrairIdYoutube($url) === null) {
            $this->jsonResponse(['erro' => 'Informe um link válido do YouTube.'], 422);
        }

        ProjecaoEstado::definirVideo($sessao->id, $url, $this->origemDaRequisicao());
        $this->jsonResponse(['ok' => true]);
    }

    public function definirEstadoVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $estado = (string) $this->request->input('estado', '');

        ProjecaoEstado::definirEstadoVideo($sessao->id, $estado);
        $this->jsonResponse(['ok' => true]);
    }

    public function definirVolumeVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $volume = (int) $this->request->input('volume', -1);

        if ($volume < 0 || $volume > 100) {
            $this->jsonResponse(['erro' => 'Volume inválido.'], 422);
        }

        ProjecaoEstado::definirVolumeVideo($sessao->id, $volume);
        $this->jsonResponse(['ok' => true]);
    }

    public function alternarMudoVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $mudo = (string) $this->request->input('mudo', '0') === '1';

        ProjecaoEstado::alternarMudoVideo($sessao->id, $mudo);
        $this->jsonResponse(['ok' => true]);
    }

    public function reiniciarVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::reiniciarVideo($sessao->id);
        $this->jsonResponse(['ok' => true]);
    }

    /**
     * Progresso de reproducao (tempo atual/duracao) reportado
     * periodicamente pelo telao, para o operador ver o andamento exato
     * do video sem precisar olhar para o projetor.
     */
    public function atualizarTempoVideo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $tempoAtual = (int) $this->request->input('tempo_atual', -1);
        $duracao = (int) $this->request->input('duracao', -1);

        if ($tempoAtual < 0 || $duracao < 0) {
            $this->jsonResponse(['erro' => 'Dados inválidos.'], 422);
        }

        ProjecaoEstado::atualizarTempoVideo($sessao->id, $tempoAtual, $duracao);
        $this->jsonResponse(['ok' => true]);
    }

    public function mostrarLogo(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::mostrarLogo($sessao->id, $this->origemDaRequisicao());
        $this->jsonResponse(['ok' => true]);
    }

    public function limpar(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::limpar($sessao->id, $this->origemDaRequisicao());
        $this->jsonResponse(['ok' => true]);
    }

    /**
     * Exibicao rapida de Pix (dizimo + oferta juntos) - ver ProjecaoEstado::mostrarPix().
     */
    public function mostrarPix(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        ProjecaoEstado::mostrarPix($sessao->id, $this->origemDaRequisicao());
        $this->jsonResponse(['ok' => true]);
    }

    public function mostrarImagem(string $token): void
    {
        $sessao = $this->sessaoOuErro($token);
        $imagemId = (int) $this->request->input('imagem_id', 0);

        if ($imagemId <= 0 || !ProjecaoImagem::find($imagemId)) {
            $this->jsonResponse(['erro' => 'Imagem inválida.'], 422);
        }

        ProjecaoEstado::mostrarImagem($sessao->id, $imagemId, $this->origemDaRequisicao());
        $this->jsonResponse(['ok' => true]);
    }

    private function sessaoOuErro(string $token): ProjecaoSessao
    {
        $sessao = ProjecaoSessao::findAtivaByToken($token);

        if (!$sessao) {
            $this->jsonResponse(['erro' => 'Sessão de projeção encerrada.'], 404);
        }

        return $sessao;
    }

    /**
     * Quem esta fazendo a chamada (painel do operador ou tablet do
     * preletor) - enviado explicitamente pelo cliente (ver
     * projecao-admin.js/preletor.js), ja que estas rotas nao tem
     * sessao/login proprios pra inferir isso (autorizacao e so pelo
     * token na URL). Usado pra registrar "quem esta no comando" (ver
     * ProjecaoEstado::normalizarOrigem()) - a normalizacao final (so
     * aceitar "operador"/"preletor") acontece no model.
     */
    private function origemDaRequisicao(): string
    {
        return (string) $this->request->input('origem', 'operador');
    }

    public static function extrairIdYoutube(string $url): ?string
    {
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
