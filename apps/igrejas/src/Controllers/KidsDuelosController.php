<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\KidsConteudo;
use Igrejas\Models\KidsCrianca;
use Igrejas\Models\KidsDuelo;

/**
 * "Jogar com amigo": duelo de quiz 1x1 entre duas criancas da mesma
 * igreja (nunca entre igrejas diferentes - o isolamento e automatico,
 * cada banco e de uma igreja so). Sem chat de texto livre, so reacoes
 * de emoji pre-definidas (ver KidsDuelo::reagir()). Atualizado por
 * polling no navegador (ver kids-duelo.js), sem precisar de servidor de
 * WebSocket.
 */
final class KidsDuelosController extends Controller
{
    /**
     * Tela "Jogar com amigo": convites pendentes recebidos, duelos em
     * andamento (pra voltar se saiu no meio) e o formulario pra
     * desafiar alguem novo.
     */
    public function index(): void
    {
        $crianca = $this->criancaLogada();

        echo $this->view('kids.duelos.index', [
            'pageTitle' => 'Jogar com Amigo - KADOSYS Kids',
            'crianca' => $crianca,
            'pendentes' => KidsDuelo::pendentesPara($crianca->id),
            'emAndamento' => KidsDuelo::emAndamentoPara($crianca->id),
            'amigos' => array_values(array_filter(
                KidsCrianca::ativasComPin(),
                static fn (KidsCrianca $c) => $c->id !== $crianca->id
            )),
            'quizzes' => KidsConteudo::publicadosPorTipo('quiz'),
            'csrfToken' => Csrf::token(),
            'erro' => Session::flash('kids_duelo_erro'),
        ], 'kids-app');
    }

    public function criar(): void
    {
        $crianca = $this->criancaLogada();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/kids/duelos');
        }

        $conteudoId = (int) $this->request->input('conteudo_id', 0);
        $convidadoId = (int) $this->request->input('convidado_id', 0);

        $duelId = KidsDuelo::convidar($conteudoId, $crianca->id, $convidadoId);

        if ($duelId === null) {
            Session::flash('kids_duelo_erro', 'Não foi possível criar o desafio. Talvez já exista um duelo em aberto com esse amigo.');
        }

        $this->redirect('/kids/duelos');
    }

    public function aceitar(string $id): void
    {
        $crianca = $this->criancaLogada();

        if (Csrf::verify($this->request->input('_csrf_token')) && KidsDuelo::aceitar((int) $id, $crianca->id)) {
            $this->redirect("/kids/duelos/{$id}");
        }

        $this->redirect('/kids/duelos');
    }

    public function recusar(string $id): void
    {
        $crianca = $this->criancaLogada();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            KidsDuelo::recusar((int) $id, $crianca->id);
        }

        $this->redirect('/kids/duelos');
    }

    /**
     * Sala do duelo: renderiza as perguntas do quiz escolhido - cada
     * crianca responde no seu proprio ritmo, o progresso do oponente e
     * so acompanhado por polling (ver estado()).
     */
    public function sala(string $id): void
    {
        $crianca = $this->criancaLogada();
        $duelo = KidsDuelo::find((int) $id);

        if ($duelo === null || ($duelo['criadorId'] !== $crianca->id && $duelo['convidadoId'] !== $crianca->id)) {
            $this->redirect('/kids/duelos');
        }

        if ($duelo['status'] !== 'em_andamento' && $duelo['status'] !== 'finalizado') {
            $this->redirect('/kids/duelos');
        }

        $conteudo = KidsConteudo::find($duelo['conteudoId']);

        if ($conteudo === null || $conteudo->quizPerguntas === null) {
            $this->redirect('/kids/duelos');
        }

        echo $this->view('kids.duelos.sala', [
            'pageTitle' => 'Duelo - KADOSYS Kids',
            'crianca' => $crianca,
            'duelo' => $duelo,
            'conteudo' => $conteudo,
            'estadoInicial' => KidsDuelo::estadoPara((int) $id, $crianca->id),
            'csrfToken' => Csrf::token(),
        ], 'kids-app');
    }

    /**
     * Recebe o progresso (respostas certas ate agora + se ja terminou
     * todas) direto do JS da sala, a cada resposta certa - chamado via
     * fetch, sem recarregar a pagina.
     */
    public function progresso(string $id): void
    {
        $crianca = $this->criancaLogada();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->jsonResponse(['ok' => false], 400);
        }

        $progresso = (int) $this->request->input('progresso', 0);
        $terminou = (bool) $this->request->input('terminou', false);

        KidsDuelo::registrarProgresso((int) $id, $crianca->id, $progresso, $terminou);

        $estado = KidsDuelo::estadoPara((int) $id, $crianca->id);

        if ($estado === null) {
            $this->jsonResponse(['ok' => false], 404);
        }

        $this->jsonResponse(['ok' => true] + $estado);
    }

    /**
     * Polling do estado do duelo (progresso do oponente, reacoes,
     * resultado final) - chamado a cada ~1,5s pelo JS da sala.
     */
    public function estado(string $id): void
    {
        $crianca = $this->criancaLogada();
        $estado = KidsDuelo::estadoPara((int) $id, $crianca->id);

        if ($estado === null) {
            $this->jsonResponse(['ok' => false], 404);
        }

        $this->jsonResponse(['ok' => true] + $estado);
    }

    public function reagir(string $id): void
    {
        $crianca = $this->criancaLogada();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->jsonResponse(['ok' => false], 400);
        }

        $emoji = (string) $this->request->input('emoji', '');
        KidsDuelo::reagir((int) $id, $crianca->id, $emoji);

        $this->jsonResponse(['ok' => true]);
    }

    /**
     * Polling da home (ver kids-duelo.js): quantos convites pendentes a
     * crianca tem, pra mostrar o selo no botao "Jogar com amigo" sem
     * precisar recarregar a pagina inteira.
     */
    public function pendentesJson(): void
    {
        $crianca = $this->criancaLogada();

        $this->jsonResponse(['total' => count(KidsDuelo::pendentesPara($crianca->id))]);
    }

    private function criancaLogada(): KidsCrianca
    {
        return KidsCrianca::find((int) Session::get(KidsSessaoMiddleware::SESSION_KEY));
    }
}
