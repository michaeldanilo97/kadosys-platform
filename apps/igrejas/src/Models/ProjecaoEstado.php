<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use Igrejas\Core\PixEstatico;

/**
 * Estado atual exibido em uma sessao de projecao: um versiculo biblico,
 * um video do YouTube (com seu estado de reproducao), a logo da igreja
 * ou tela em branco.
 *
 * O campo "versao" (revisao) e incrementado a cada alteracao e usado
 * pelo telao e pelo tablet do preletor para saber, via polling, quando
 * ha algo novo para buscar - sem precisar de WebSocket (nao disponivel
 * na hospedagem compartilhada). Nao confundir com "bibliaVersao", que e
 * a traducao biblica selecionada (nvi/acf/aa, ver BibliaVersao).
 */
final class ProjecaoEstado
{
    public function __construct(
        public readonly int $sessaoId,
        public readonly string $modo,
        public readonly ?string $controladoPor,
        public readonly ?int $livroId,
        public readonly ?string $livroNome,
        public readonly ?string $livroAbreviacao,
        public readonly ?string $bibliaVersao,
        public readonly ?array $bibliaMarcacao,
        public readonly ?int $capitulo,
        public readonly ?int $versiculoInicio,
        public readonly ?int $versiculoFim,
        public readonly ?string $videoUrl,
        public readonly string $videoEstado,
        public readonly ?int $videoTempoAtual,
        public readonly ?int $videoDuracao,
        public readonly int $videoVolume,
        public readonly bool $videoMudo,
        public readonly int $videoReiniciarId,
        public readonly ?int $imagemId,
        public readonly ?string $imagemPath,
        public readonly int $versao,
        public readonly int $leituraId,
        public readonly string $updatedAt,
    ) {
    }

    public static function atual(int $sessaoId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT pe.*, bl.nome AS livro_nome, bl.abreviacao AS livro_abreviacao, pi.path AS imagem_path
             FROM projecao_estados pe
             LEFT JOIN biblia_livros bl ON bl.id = pe.livro_id
             LEFT JOIN projecao_imagens pi ON pi.id = pe.imagem_id
             WHERE pe.sessao_id = :sessao_id
             LIMIT 1'
        );
        $stmt->execute(['sessao_id' => $sessaoId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function definirBiblia(
        int $sessaoId,
        string $bibliaVersao,
        int $livroId,
        int $capitulo,
        int $inicio,
        int $fim,
        ?string $origem = null
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET
                modo = "biblia", controlado_por = :origem, biblia_versao = :biblia_versao, livro_id = :livro_id, capitulo = :capitulo,
                versiculo_inicio = :inicio, versiculo_fim = :fim, biblia_marcacao = NULL,
                versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'origem' => self::normalizarOrigem($origem),
            'biblia_versao' => $bibliaVersao,
            'livro_id' => $livroId,
            'capitulo' => $capitulo,
            'inicio' => min($inicio, $fim),
            'fim' => max($inicio, $fim),
        ]);
    }

    /**
     * Marcacao a lapis do preletor sobre o texto atualmente projetado.
     * Substitui integralmente os tracos anteriores (o cliente sempre
     * envia o conjunto completo de tracos correntes) e e limpa
     * automaticamente sempre que a referencia biblica muda, em
     * definirBiblia() acima.
     *
     * @param array<int, array<int, array{x: float, y: float}>> $tracos
     */
    public static function definirMarcacao(int $sessaoId, array $tracos): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET
                biblia_marcacao = :marcacao, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id AND modo = "biblia"'
        );
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'marcacao' => $tracos === [] ? null : json_encode($tracos),
        ]);
    }

    public static function definirVideo(int $sessaoId, string $url, ?string $origem = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET
                modo = "video", controlado_por = :origem, video_url = :video_url, video_estado = "tocando",
                video_tempo_atual = NULL, video_duracao = NULL, video_volume = 100, video_mudo = 0,
                versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'origem' => self::normalizarOrigem($origem), 'video_url' => $url]);
    }

    /**
     * Progresso de reproducao do video (tempo atual/duracao, em
     * segundos), reportado periodicamente pelo telao - que e o unico
     * lugar onde o player realmente existe. Nao incrementa "versao" de
     * proposito: o tempo muda a cada poucos segundos e nao deve disparar
     * o re-render completo do estado nas outras telas (so o operador le
     * esses dois campos, para mostrar o progresso do video).
     */
    public static function atualizarTempoVideo(int $sessaoId, int $tempoAtual, int $duracao): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET video_tempo_atual = :tempo, video_duracao = :duracao
             WHERE sessao_id = :sessao_id AND modo = "video"'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'tempo' => $tempoAtual, 'duracao' => $duracao]);
    }

    public static function definirEstadoVideo(int $sessaoId, string $estado): void
    {
        if (!in_array($estado, ['tocando', 'pausado', 'fadeout'], true)) {
            return;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET video_estado = :estado, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id AND modo = "video"'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'estado' => $estado]);
    }

    /**
     * Ajusta o volume do video (0-100) a partir do slider do operador.
     * Desliga o mudo junto - arrastar o slider e uma escolha explicita de
     * volume audivel, entao um mudo anterior nao deve continuar calando
     * o video (ver alternarMudoVideo() abaixo pra o botao dedicado).
     */
    public static function definirVolumeVideo(int $sessaoId, int $volume): void
    {
        $volume = max(0, min(100, $volume));

        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET video_volume = :volume, video_mudo = 0, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id AND modo = "video"'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'volume' => $volume]);
    }

    /**
     * Liga/desliga o mudo sem alterar o volume configurado - permite
     * "voltar como estava" ao desmutar, em vez de sempre voltar a 100%.
     */
    public static function alternarMudoVideo(int $sessaoId, bool $mudo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET video_mudo = :mudo, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id AND modo = "video"'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'mudo' => $mudo ? 1 : 0]);
    }

    /**
     * Pede pro telao voltar o video atual pro segundo 0 - mesmo padrao
     * de contador de lerAgora() (video_reiniciar_id), ja que reiniciar o
     * MESMO video em exibicao nao muda nenhum conteudo (nao precisa
     * incrementar "versao"), so um pedido que o telao detecta via
     * polling mesmo sem nenhuma outra mudanca de estado.
     */
    public static function reiniciarVideo(int $sessaoId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET video_reiniciar_id = video_reiniciar_id + 1, video_tempo_atual = 0
             WHERE sessao_id = :sessao_id AND modo = "video"'
        );
        $stmt->execute(['sessao_id' => $sessaoId]);
    }

    public static function mostrarLogo(int $sessaoId, ?string $origem = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET modo = "logo", controlado_por = :origem, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'origem' => self::normalizarOrigem($origem)]);
    }

    /**
     * Exibicao rapida de Pix no telao - pensado pro momento do culto em
     * que o responsavel pelo projetor mostra o QR pra congregacao
     * inteira, sem que cada pessoa precise abrir o proprio link.
     * Dizimo e oferta usam a MESMA chave Pix da igreja (so o txid dentro
     * do payload muda, pra identificar cada um no extrato depois) e sao
     * exibidos JUNTOS, ja que normalmente sao recolhidos no mesmo
     * momento do culto - ver montarPixJson() pra como os dois QR sao
     * montados, e telao.js pra como sao desenhados bem afastados um do
     * outro (perto demais, a camera do celular as vezes le o QR
     * errado).
     */
    public static function mostrarPix(int $sessaoId, ?string $origem = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET modo = "pix", controlado_por = :origem, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'origem' => self::normalizarOrigem($origem)]);
    }

    /**
     * Exibe uma imagem da galeria (ver ProjecaoImagem) em tela cheia no
     * telao - cartazes, avisos especiais etc. escolhidos pelo operador.
     */
    public static function mostrarImagem(int $sessaoId, int $imagemId, ?string $origem = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET modo = "imagem", controlado_por = :origem, imagem_id = :imagem_id, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'origem' => self::normalizarOrigem($origem), 'imagem_id' => $imagemId]);
    }

    public static function limpar(int $sessaoId, ?string $origem = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET modo = "blank", controlado_por = :origem, versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'origem' => self::normalizarOrigem($origem)]);
    }

    /**
     * Restringe "origem" aos dois valores validos do enum
     * `controlado_por` - qualquer coisa fora disso (ausente, adulterada
     * etc.) vira NULL, que o poll trata como "ninguem no comando"
     * (nenhuma confirmacao de "assumir comando" e exigida nesse caso).
     */
    private static function normalizarOrigem(?string $origem): ?string
    {
        return in_array($origem, ['operador', 'preletor'], true) ? $origem : null;
    }

    /**
     * Pede pro telao ler em voz alta (sintetizador de voz do navegador,
     * ver telao.js) o texto biblico projetado agora - "Ler agora" no
     * painel do operador. So incrementa um contador dedicado
     * (leitura_id), sem mexer em "versao"/modo/conteudo, ja que uma
     * leitura pode ser pedida de novo pro MESMO versiculo (releitura) e
     * o telao precisa perceber o pedido mesmo sem nenhuma outra mudanca
     * de estado.
     */
    public static function lerAgora(int $sessaoId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET leitura_id = leitura_id + 1
             WHERE sessao_id = :sessao_id AND modo = "biblia"'
        );
        $stmt->execute(['sessao_id' => $sessaoId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function paraJson(): array
    {
        $versiculos = [];
        $proximaPreview = null;

        if ($this->modo === 'biblia' && $this->livroId !== null && $this->capitulo !== null && $this->bibliaVersao !== null) {
            $versiculos = array_map(
                static fn (BibliaVersiculo $v) => ['numero' => $v->versiculo, 'texto' => $v->texto],
                BibliaVersiculo::doIntervalo(
                    $this->bibliaVersao,
                    $this->livroId,
                    $this->capitulo,
                    $this->versiculoInicio ?? 1,
                    $this->versiculoFim ?? $this->versiculoInicio ?? 1
                )
            );

            $proximaPreview = $this->calcularPreview();
        }

        return [
            'versao' => $this->versao,
            'leituraId' => $this->leituraId,
            'modo' => $this->modo,
            'controladoPor' => $this->controladoPor,
            'biblia' => [
                'livroId' => $this->livroId,
                'livroNome' => $this->livroNome,
                'livroAbreviacao' => $this->livroAbreviacao,
                'bibliaVersao' => $this->bibliaVersao,
                'bibliaVersaoNome' => BibliaVersao::nome($this->bibliaVersao),
                'capitulo' => $this->capitulo,
                'versiculoInicio' => $this->versiculoInicio,
                'versiculoFim' => $this->versiculoFim,
                'versiculos' => $versiculos,
                'proximaPreview' => $proximaPreview,
                'marcacao' => $this->bibliaMarcacao ?? [],
            ],
            'video' => [
                'url' => $this->videoUrl,
                'estado' => $this->videoEstado,
                'tempoAtual' => $this->videoTempoAtual,
                'duracao' => $this->videoDuracao,
                'volume' => $this->videoVolume,
                'mudo' => $this->videoMudo,
                'reiniciarId' => $this->videoReiniciarId,
            ],
            'pix' => $this->modo === 'pix' ? $this->montarPixJson() : ['qrCodes' => [], 'mensagem' => null],
            'imagem' => [
                'id' => $this->imagemId,
                'path' => $this->imagemPath,
            ],
        ];
    }

    /**
     * Estado completo da tela de Pix: os DOIS QR (BR Code, ver
     * PixEstatico), a logo da igreja e a mensagem opcional configurada
     * em Configuracoes (texto livre ou versiculo biblico resolvido na
     * hora). QR sempre sem valor fixo (cada pessoa digita o proprio
     * valor no banco). Dizimo e oferta usam a mesma chave Pix da igreja
     * (o dinheiro cai na mesma conta de qualquer jeito) - so o txid e a
     * descricao visivel ao pagador dentro do payload mudam, pra
     * facilitar identificar cada um depois no extrato/no app do banco.
     * Exibidos juntos porque normalmente sao recolhidos no mesmo
     * momento do culto.
     *
     * Se a igreja nunca cadastrou uma chave Pix (ver
     * ConfiguracaoIgreja::doacaoPixHabilitada()), volta com "qrCodes"
     * vazio - o telao mostra um aviso em vez de um QR quebrado. A logo
     * nao entra aqui: o telao reaproveita a mesma logo ja carregada na
     * pagina (data-logo-url, ver telao/show.php), sem precisar mandar
     * de novo a cada poll.
     *
     * @return array{qrCodes: array<int, array{categoria: string, label: string, payload: string}>, mensagem: ?string}
     */
    private function montarPixJson(): array
    {
        $configuracao = ConfiguracaoIgreja::atual();

        if (!$configuracao->doacaoPixHabilitada()) {
            return ['qrCodes' => [], 'mensagem' => null];
        }

        $categorias = ['dizimo' => 'Dízimo', 'oferta' => 'Oferta'];
        $qrCodes = [];

        foreach ($categorias as $categoria => $label) {
            $qrCodes[] = [
                'categoria' => $categoria,
                'label' => $label,
                'payload' => PixEstatico::montarPayload(
                    chave: (string) $configuracao->pixChave,
                    nomeBeneficiario: $configuracao->pixNomeBeneficiario ?? (string) $configuracao->nomeIgreja,
                    cidade: (string) ($configuracao->cidade ?? 'BRASIL'),
                    valor: null,
                    txid: strtoupper($categoria),
                    descricao: $label,
                ),
            ];
        }

        return [
            'qrCodes' => $qrCodes,
            'mensagem' => $this->resolverMensagemPix($configuracao),
        ];
    }

    /**
     * Resolve a mensagem configurada pra tela de Pix: texto livre
     * digitado pela igreja, ou o texto do versiculo biblico escolhido
     * (buscado agora, igual a leitura biblica normal do telao - assim
     * a mensagem sempre reflete a traducao/texto atual da tabela
     * biblia_versiculos, sem precisar duplicar o texto em
     * configuracoes_igreja).
     */
    private function resolverMensagemPix(ConfiguracaoIgreja $configuracao): ?string
    {
        if ($configuracao->pixMensagemTipo === 'texto') {
            $texto = trim((string) $configuracao->pixMensagemTexto);

            return $texto !== '' ? $texto : null;
        }

        if ($configuracao->pixMensagemTipo === 'versiculo'
            && $configuracao->pixMensagemLivroId !== null
            && $configuracao->pixMensagemBibliaVersao !== null
            && $configuracao->pixMensagemCapitulo !== null
        ) {
            $inicio = $configuracao->pixMensagemVersiculoInicio ?? 1;
            $fim = $configuracao->pixMensagemVersiculoFim ?? $inicio;

            $versiculos = BibliaVersiculo::doIntervalo(
                $configuracao->pixMensagemBibliaVersao,
                $configuracao->pixMensagemLivroId,
                $configuracao->pixMensagemCapitulo,
                $inicio,
                $fim
            );

            if ($versiculos === []) {
                return null;
            }

            $texto = implode(' ', array_map(static fn (BibliaVersiculo $v) => $v->texto, $versiculos));
            $livro = BibliaLivro::find($configuracao->pixMensagemLivroId);
            $referencia = trim(($livro?->nome ?? '') . ' ' . $configuracao->pixMensagemCapitulo
                . ':' . $inicio . ($fim !== $inicio ? '-' . $fim : ''));

            return trim($texto) . ' (' . $referencia . ')';
        }

        return null;
    }

    /**
     * Previa do proximo versiculo (estilo "next slide" de softwares de
     * projecao como o Holyrics), calculada a partir do fim do intervalo
     * atualmente exibido. Usada pelo operador e pelo preletor para saber
     * o que vem a seguir antes de avancar.
     *
     * @return array{livroNome: ?string, capitulo: int, versiculo: int, texto: ?string}|null
     */
    private function calcularPreview(): ?array
    {
        if ($this->livroId === null || $this->capitulo === null || $this->bibliaVersao === null) {
            return null;
        }

        $posicaoAtual = $this->versiculoFim ?? $this->versiculoInicio ?? 1;
        $proxima = BibliaVersiculo::proximaReferencia($this->bibliaVersao, $this->livroId, $this->capitulo, $posicaoAtual);

        if ($proxima === null) {
            return null;
        }

        $versiculos = BibliaVersiculo::doIntervalo(
            $this->bibliaVersao,
            $proxima['livro_id'],
            $proxima['capitulo'],
            $proxima['versiculo'],
            $proxima['versiculo']
        );

        return [
            'livroNome' => BibliaLivro::find($proxima['livro_id'])?->nome,
            'capitulo' => $proxima['capitulo'],
            'versiculo' => $proxima['versiculo'],
            'texto' => $versiculos[0]->texto ?? null,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            sessaoId: (int) $row['sessao_id'],
            modo: (string) $row['modo'],
            controladoPor: $row['controlado_por'] ?? null,
            livroId: $row['livro_id'] !== null ? (int) $row['livro_id'] : null,
            livroNome: $row['livro_nome'] ?? null,
            livroAbreviacao: $row['livro_abreviacao'] ?? null,
            bibliaVersao: $row['biblia_versao'] ?? null,
            bibliaMarcacao: isset($row['biblia_marcacao']) && $row['biblia_marcacao'] !== null
                ? (json_decode((string) $row['biblia_marcacao'], true) ?: [])
                : null,
            capitulo: $row['capitulo'] !== null ? (int) $row['capitulo'] : null,
            versiculoInicio: $row['versiculo_inicio'] !== null ? (int) $row['versiculo_inicio'] : null,
            versiculoFim: $row['versiculo_fim'] !== null ? (int) $row['versiculo_fim'] : null,
            videoUrl: $row['video_url'] ?? null,
            videoEstado: (string) $row['video_estado'],
            videoTempoAtual: $row['video_tempo_atual'] !== null ? (int) $row['video_tempo_atual'] : null,
            videoDuracao: $row['video_duracao'] !== null ? (int) $row['video_duracao'] : null,
            videoVolume: (int) ($row['video_volume'] ?? 100),
            videoMudo: (bool) ($row['video_mudo'] ?? false),
            videoReiniciarId: (int) ($row['video_reiniciar_id'] ?? 0),
            imagemId: $row['imagem_id'] !== null ? (int) $row['imagem_id'] : null,
            imagemPath: $row['imagem_path'] ?? null,
            versao: (int) $row['versao'],
            leituraId: (int) ($row['leitura_id'] ?? 0),
            updatedAt: (string) $row['updated_at'],
        );
    }
}
