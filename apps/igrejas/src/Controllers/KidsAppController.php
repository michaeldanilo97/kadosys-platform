<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\KidsAvatar;
use Igrejas\Models\KidsConteudo;
use Igrejas\Models\KidsCrianca;

/**
 * "Modo criança" de verdade: a mesma Biblioteca de conteúdo (ver
 * KidsConteudo), mas servida fora do painel administrativo, atrás do
 * login por PIN (ver KidsLoginController/KidsSessaoMiddleware) em vez
 * de exigir um adulto logado no sistema - pensado pra criança usar
 * sozinha num tablet/celular.
 */
final class KidsAppController extends Controller
{
    /** Custo em moedas de cada pedido de ajuda no quiz. */
    private const CUSTO_AJUDA_QUIZ = 5;

    public function index(): void
    {
        $crianca = $this->criancaLogada();

        echo $this->view('kids.home', [
            'pageTitle' => 'KADOSYS Kids',
            'crianca' => $crianca,
            'contagens' => KidsConteudo::contagemPublicadaPorTipo(),
            'recentes' => KidsConteudo::recentesPublicados(6),
            'csrfToken' => Csrf::token(),
        ], 'kids-app');
    }

    public function porTipo(string $tipo): void
    {
        if (!array_key_exists($tipo, KidsConteudo::TIPOS)) {
            $this->redirect('/kids');
        }

        $crianca = $this->criancaLogada();

        echo $this->view('kids.tipo', [
            'pageTitle' => KidsConteudo::TIPOS[$tipo]['label'] . ' - KADOSYS Kids',
            'crianca' => $crianca,
            'tipo' => $tipo,
            'conteudos' => KidsConteudo::publicadosPorTipo($tipo),
            'concluidosIds' => KidsConteudo::concluidosPorCrianca($crianca->id, $tipo),
        ], 'kids-app');
    }

    public function show(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);

        if (!$conteudo || $conteudo->status !== 'publicado') {
            $this->redirect('/kids');
        }

        $crianca = $this->criancaLogada();

        echo $this->view('kids.show', [
            'pageTitle' => $conteudo->titulo . ' - KADOSYS Kids',
            'crianca' => $crianca,
            'conteudo' => $conteudo,
            'jaConcluido' => KidsConteudo::jaConcluidoPor($crianca->id, $conteudo->id),
            'csrfToken' => Csrf::token(),
            'custoAjudaQuiz' => self::CUSTO_AJUDA_QUIZ,
            'pontosGanhos' => Session::flash('kids_app_pontos'),
        ], 'kids-app');
    }

    public function concluir(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);
        $crianca = $this->criancaLogada();

        if ($conteudo !== null && Csrf::verify($this->request->input('_csrf_token'))) {
            $ganhouAgora = $conteudo->registrarConclusaoPor($crianca->id);

            if ($ganhouAgora) {
                Session::flash('kids_app_pontos', [
                    'xp' => $conteudo->xpRecompensa,
                    'moedas' => $conteudo->moedasRecompensa,
                ]);
            }

            // No quiz, terminar um leva direto pro proximo ainda nao
            // feito - sem esse pulo automatico a crianca precisava
            // voltar pra lista e escolher manualmente toda vez.
            if ($conteudo->tipo === 'quiz') {
                $proximo = KidsConteudo::proximoNaoConcluidoPorTipo('quiz', $crianca->id);

                if ($proximo !== null) {
                    $this->redirect("/kids/conteudo/{$proximo->id}");
                }
            }
        }

        $this->redirect("/kids/conteudo/{$id}");
    }

    /**
     * Pedido de ajuda numa pergunta do quiz (botao "Pedir ajuda 🪙"):
     * desconta CUSTO_AJUDA_QUIZ moedas da crianca e devolve, em JSON, os
     * indices de 2 alternativas erradas pra esconder (efeito "cartas na
     * manga" - sobra a certa + 1 errada) - chamado via fetch, sem
     * recarregar a pagina, pra nao perder o progresso do quiz no
     * navegador (ver script em kids/show.php).
     */
    public function quizAjuda(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);
        $crianca = $this->criancaLogada();

        if (
            $conteudo === null
            || $conteudo->tipo !== 'quiz'
            || $conteudo->quizPerguntas === null
            || !Csrf::verify($this->request->input('_csrf_token'))
        ) {
            $this->jsonResponse(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
        }

        $indice = (int) $this->request->input('pergunta_indice', -1);
        $pergunta = $conteudo->quizPerguntas[$indice] ?? null;

        if ($pergunta === null) {
            $this->jsonResponse(['ok' => false, 'erro' => 'Pergunta inválida.'], 400);
        }

        if (!KidsCrianca::gastarMoedas($crianca->id, self::CUSTO_AJUDA_QUIZ)) {
            $this->jsonResponse(['ok' => false, 'erro' => 'Moedas insuficientes.', 'moedas' => $crianca->moedas]);
        }

        $correta = (int) $pergunta['correta'];
        $erradas = array_values(array_diff(array_keys($pergunta['alternativas']), [$correta]));
        shuffle($erradas);
        $remover = array_slice($erradas, 0, max(0, count($erradas) - 1));

        $this->jsonResponse([
            'ok' => true,
            'remover' => array_values($remover),
            'moedas' => $crianca->moedas - self::CUSTO_AJUDA_QUIZ,
        ]);
    }

    /**
     * Tela do Avatar da Crianca: nivel/progresso calculados a partir do
     * xp acumulado (ver KidsAvatar::progresso()), e cada categoria do
     * catalogo (chapeu/acessorio/fundo/titulo) marcada como desbloqueada
     * ou nao pro nivel atual - a view decide como mostrar cada estado
     * (equipado, desbloqueado ou ainda bloqueado).
     */
    public function avatar(): void
    {
        $crianca = $this->criancaLogada();
        $nivel = $crianca->nivel();

        echo $this->view('kids.avatar', [
            'pageTitle' => 'Meu Avatar - KADOSYS Kids',
            'crianca' => $crianca,
            'progresso' => KidsAvatar::progresso($crianca->xp),
            'catalogoChapeus' => KidsAvatar::catalogoChapeus(),
            'catalogoAcessorios' => KidsAvatar::catalogoAcessorios(),
            'catalogoFundos' => KidsAvatar::catalogoFundos(),
            'catalogoTitulos' => KidsAvatar::catalogoTitulos(),
            'nivel' => $nivel,
            'csrfToken' => Csrf::token(),
            'salvo' => Session::flash('kids_avatar_salvo'),
        ], 'kids-app');
    }

    public function avatarSalvar(): void
    {
        $crianca = $this->criancaLogada();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            KidsCrianca::atualizarAvatar(
                $crianca->id,
                $this->nullableInput('avatar_chapeu'),
                $this->nullableInput('avatar_acessorio'),
                $this->nullableInput('avatar_fundo'),
                $this->nullableInput('avatar_titulo'),
            );

            Session::flash('kids_avatar_salvo', true);
        }

        $this->redirect('/kids/avatar');
    }

    private function nullableInput(string $campo): ?string
    {
        $valor = trim((string) $this->request->input($campo, ''));

        return $valor === '' ? null : $valor;
    }

    /**
     * A crianca sempre existe aqui - KidsSessaoMiddleware ja garantiu
     * isso antes do controller ser chamado.
     */
    private function criancaLogada(): KidsCrianca
    {
        return KidsCrianca::find((int) Session::get(KidsSessaoMiddleware::SESSION_KEY));
    }
}
