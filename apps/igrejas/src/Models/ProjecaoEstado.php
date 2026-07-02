<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

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
        public readonly ?int $livroId,
        public readonly ?string $livroNome,
        public readonly ?string $livroAbreviacao,
        public readonly ?string $bibliaVersao,
        public readonly ?int $capitulo,
        public readonly ?int $versiculoInicio,
        public readonly ?int $versiculoFim,
        public readonly ?string $videoUrl,
        public readonly string $videoEstado,
        public readonly int $versao,
        public readonly string $updatedAt,
    ) {
    }

    public static function atual(int $sessaoId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT pe.*, bl.nome AS livro_nome, bl.abreviacao AS livro_abreviacao
             FROM projecao_estados pe
             LEFT JOIN biblia_livros bl ON bl.id = pe.livro_id
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
        int $fim
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET
                modo = "biblia", biblia_versao = :biblia_versao, livro_id = :livro_id, capitulo = :capitulo,
                versiculo_inicio = :inicio, versiculo_fim = :fim,
                versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'biblia_versao' => $bibliaVersao,
            'livro_id' => $livroId,
            'capitulo' => $capitulo,
            'inicio' => min($inicio, $fim),
            'fim' => max($inicio, $fim),
        ]);
    }

    public static function definirVideo(int $sessaoId, string $url): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET
                modo = "video", video_url = :video_url, video_estado = "tocando",
                versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId, 'video_url' => $url]);
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

    public static function mostrarLogo(int $sessaoId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET modo = "logo", versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
        );
        $stmt->execute(['sessao_id' => $sessaoId]);
    }

    public static function limpar(int $sessaoId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_estados SET modo = "blank", versao = versao + 1, updated_at = NOW()
             WHERE sessao_id = :sessao_id'
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
            'modo' => $this->modo,
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
            ],
            'video' => [
                'url' => $this->videoUrl,
                'estado' => $this->videoEstado,
            ],
        ];
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
            livroId: $row['livro_id'] !== null ? (int) $row['livro_id'] : null,
            livroNome: $row['livro_nome'] ?? null,
            livroAbreviacao: $row['livro_abreviacao'] ?? null,
            bibliaVersao: $row['biblia_versao'] ?? null,
            capitulo: $row['capitulo'] !== null ? (int) $row['capitulo'] : null,
            versiculoInicio: $row['versiculo_inicio'] !== null ? (int) $row['versiculo_inicio'] : null,
            versiculoFim: $row['versiculo_fim'] !== null ? (int) $row['versiculo_fim'] : null,
            videoUrl: $row['video_url'] ?? null,
            videoEstado: (string) $row['video_estado'],
            versao: (int) $row['versao'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
