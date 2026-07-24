<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\KidsAvatar;
use Igrejas\Models\KidsAvatarCompra;
use Igrejas\Models\KidsConteudo;
use Igrejas\Models\KidsCrianca;
use Igrejas\Models\KidsEmblema;
use Igrejas\Models\KidsMissaoDiaria;
use Igrejas\Models\KidsRankingIgreja;

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

    private const UPLOAD_DIR_FOTOS_DESAFIO = 'uploads/kids-desafios';

    private const TAMANHO_MAXIMO_FOTO = 8 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO_FOTO = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        $crianca = $this->criancaLogada();
        $acessoApp = KidsCrianca::registrarAcessoApp($crianca->id);

        // registrarAcessoApp() pode ter concedido XP/moedas de marco de
        // sequencia - releitura pra a tela (contador de moedas etc)
        // refletir o saldo atualizado, nao o de antes do bonus.
        if ($acessoApp !== null && ($acessoApp['xp'] > 0 || $acessoApp['moedas'] > 0)) {
            $crianca = $this->criancaLogada();
        }

        // Verifica aqui (alem do momento de concluir um conteudo) pra
        // pegar emblemas que so fazem sentido fora desse fluxo - vencer
        // um duelo (sem recarregar pagina) ou bater a sequencia de
        // acesso, que acabou de ser atualizada acima.
        $novosEmblemas = KidsEmblema::verificarNovos($crianca);

        if ($novosEmblemas !== []) {
            $crianca = $this->criancaLogada();
        }

        echo $this->view('kids.home', [
            'pageTitle' => 'KADOSYS Kids',
            'crianca' => $crianca,
            'contagens' => KidsConteudo::contagemPublicadaPorTipo(),
            'recentes' => KidsConteudo::recentesPublicados(6),
            'missoes' => KidsMissaoDiaria::deHoje($crianca->id),
            'acessoApp' => $acessoApp,
            'novosEmblemas' => $novosEmblemas,
            'csrfToken' => Csrf::token(),
        ], 'kids-app');
    }

    /**
     * Galeria de emblemas: catalogo completo, com os ja conquistados
     * destacados (ver KidsEmblema::catalogoComStatus()).
     */
    public function emblemas(): void
    {
        $crianca = $this->criancaLogada();

        echo $this->view('kids.emblemas', [
            'pageTitle' => 'Meus Emblemas - KADOSYS Kids',
            'crianca' => $crianca,
            'emblemas' => KidsEmblema::catalogoComStatus($crianca->id),
        ], 'kids-app');
    }

    /**
     * Ranking da Biblioteca Kids: top criancas da propria igreja (por
     * XP) + top igrejas entre todo o KADOSYS (por XP total das
     * criancas) - o segundo so aparece pra igrejas provisionadas
     * automaticamente (ver KidsRankingIgreja::topIgrejas()), sem expor
     * nenhuma crianca de uma igreja pra outra, so o nome da igreja.
     */
    public function ranking(): void
    {
        $crianca = $this->criancaLogada();

        echo $this->view('kids.ranking', [
            'pageTitle' => 'Ranking - KADOSYS Kids',
            'crianca' => $crianca,
            'rankingIgreja' => KidsCrianca::rankingDaIgreja($crianca->id),
            'rankingEntreIgrejas' => KidsRankingIgreja::topIgrejas(),
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
            'desafioErro' => Session::flash('kids_desafio_erro'),
            'missaoConcluida' => Session::flash('kids_missao_concluida'),
            'novosEmblemas' => Session::flash('kids_novos_emblemas') ?? [],
        ], 'kids-app');
    }

    public function concluir(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);
        $crianca = $this->criancaLogada();

        if ($conteudo !== null && Csrf::verify($this->request->input('_csrf_token'))) {
            $ganhouAgora = $conteudo->registrarConclusaoPor($crianca->id);
            $this->flashPontosComMissao($crianca->id, $conteudo->id, $ganhouAgora ? $conteudo->xpRecompensa : 0, $ganhouAgora ? $conteudo->moedasRecompensa : 0);

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
     * Concluir um desafio exige uma foto de verdade da boa acao feita -
     * diferente do concluir() padrao (que so registra), aqui o upload
     * em si e a acao que libera XP/moedas (ver
     * kids-interacoes.js [data-desafio-form] pro preview no navegador).
     */
    public function concluirDesafio(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);
        $crianca = $this->criancaLogada();

        if ($conteudo === null || $conteudo->tipo !== 'desafio' || !Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect("/kids/conteudo/{$id}");
        }

        $foto = $this->request->file('foto');

        if ($foto === null || $foto['error'] !== UPLOAD_ERR_OK) {
            Session::flash('kids_desafio_erro', 'Escolha uma foto da boa ação pra enviar.');
            $this->redirect("/kids/conteudo/{$id}");
        }

        if ($foto['size'] > self::TAMANHO_MAXIMO_FOTO) {
            Session::flash('kids_desafio_erro', 'A foto deve ter no máximo 8MB.');
            $this->redirect("/kids/conteudo/{$id}");
        }

        $mime = mime_content_type($foto['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO_FOTO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('kids_desafio_erro', 'Formato de foto não suportado. Envie JPG, PNG ou WEBP.');
            $this->redirect("/kids/conteudo/{$id}");
        }

        $destinoDir = $this->diretorioFotosDesafio();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('kids_desafio_erro', 'Não foi possível salvar a foto. Tente novamente.');
            $this->redirect("/kids/conteudo/{$id}");
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($foto['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('kids_desafio_erro', 'Não foi possível salvar a foto. Tente novamente.');
            $this->redirect("/kids/conteudo/{$id}");
        }

        $caminhoRelativo = self::UPLOAD_DIR_FOTOS_DESAFIO . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo;
        $ganhouAgora = $conteudo->registrarConclusaoPor($crianca->id, $caminhoRelativo);
        $this->flashPontosComMissao($crianca->id, $conteudo->id, $ganhouAgora ? $conteudo->xpRecompensa : 0, $ganhouAgora ? $conteudo->moedasRecompensa : 0);

        $this->redirect("/kids/conteudo/{$id}");
    }

    /**
     * Junta a recompensa normal da conclusao com o bonus da missao do
     * dia (se o conteudo concluido agora fizer parte dela - ver
     * KidsMissaoDiaria::marcarConcluidaSeForMissao()) num unico flash
     * de pontos, e marca um segundo flash separado so pra mostrar a
     * mensagem de "missão do dia concluída" na tela.
     */
    private function flashPontosComMissao(int $criancaId, int $conteudoId, int $xpBase, int $moedasBase): void
    {
        $bonusMissao = KidsMissaoDiaria::marcarConcluidaSeForMissao($criancaId, $conteudoId);

        $xpTotal = $xpBase + ($bonusMissao['xp'] ?? 0);
        $moedasTotal = $moedasBase + ($bonusMissao['moedas'] ?? 0);

        if ($xpTotal > 0 || $moedasTotal > 0) {
            Session::flash('kids_app_pontos', ['xp' => $xpTotal, 'moedas' => $moedasTotal]);
        }

        if ($bonusMissao !== null) {
            Session::flash('kids_missao_concluida', true);
        }

        $novosEmblemas = KidsEmblema::verificarNovos(KidsCrianca::find($criancaId));

        if ($novosEmblemas !== []) {
            Session::flash('kids_novos_emblemas', $novosEmblemas);
        }
    }

    private function tenantSlugOuCentral(): string
    {
        return TenantResolver::atual()?->slug ?? 'central';
    }

    private function diretorioFotosDesafio(): string
    {
        return dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR_FOTOS_DESAFIO . '/' . $this->tenantSlugOuCentral();
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
            'catalogoPeles' => KidsAvatar::catalogoPeles(),
            'catalogoRoupas' => KidsAvatar::catalogoRoupas(),
            'nivel' => $nivel,
            'comprados' => KidsAvatarCompra::compradosPor($crianca->id),
            'csrfToken' => Csrf::token(),
            'salvo' => Session::flash('kids_avatar_salvo'),
            'compraErro' => Session::flash('kids_avatar_compra_erro'),
            'compraOk' => Session::flash('kids_avatar_compra_ok'),
        ], 'kids-app');
    }

    /**
     * Compra de item exclusivo da loja do Avatar (ver
     * KidsAvatarCompra::comprar()) - so os itens com "custoMoedas"
     * preenchido no catalogo (KidsAvatar) podem ser comprados; os demais
     * (desbloqueio por nivel) ignoram esta rota mesmo que alguem tente
     * forcar o POST.
     */
    public function avatarComprar(): void
    {
        $crianca = $this->criancaLogada();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/kids/avatar');
        }

        $categoria = (string) $this->request->input('categoria', '');
        $slug = (string) $this->request->input('slug', '');

        $catalogos = [
            'chapeu' => KidsAvatar::catalogoChapeus(),
            'acessorio' => KidsAvatar::catalogoAcessorios(),
            'fundo' => KidsAvatar::catalogoFundos(),
            'titulo' => KidsAvatar::catalogoTitulos(),
            'roupa' => KidsAvatar::catalogoRoupas(),
        ];

        $item = KidsAvatar::encontrar($catalogos[$categoria] ?? [], $slug);

        if ($item === null || empty($item['custoMoedas'])) {
            $this->redirect('/kids/avatar');
        }

        $resultado = KidsAvatarCompra::comprar($crianca->id, $categoria, $slug, (int) $item['custoMoedas']);

        if ($resultado === KidsAvatarCompra::RESULTADO_OK) {
            Session::flash('kids_avatar_compra_ok', $item['nome']);
        } elseif ($resultado === KidsAvatarCompra::RESULTADO_SEM_MOEDAS) {
            Session::flash('kids_avatar_compra_erro', 'Moedas insuficientes pra comprar "' . $item['nome'] . '".');
        }

        $this->redirect('/kids/avatar');
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
                $this->nullableInput('avatar_pele'),
                $this->nullableInput('avatar_roupa'),
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
