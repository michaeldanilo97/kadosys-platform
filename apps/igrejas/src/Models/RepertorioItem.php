<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Um louvor dentro de um Repertorio, com a ordem em que vai tocar no
 * culto - dados do louvor (titulo/tom/andamento/cifra/letra) vem
 * sempre via JOIN com a tabela louvores, pra refletir a versao mais
 * atual do louvor (se alguem editar a cifra depois de montar o
 * repertorio, o Modo Culto ja mostra a versao nova).
 */
final class RepertorioItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $repertorioId,
        public readonly int $louvorId,
        public readonly int $ordem,
        public readonly string $tituloLouvor,
        public readonly ?string $tomAtual,
        public readonly ?int $andamentoBpm,
        public readonly ?string $letra,
        public readonly ?string $cifra,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function doRepertorio(int $repertorioId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ri.*, l.titulo AS louvor_titulo, l.tom_atual, l.andamento_bpm, l.letra, l.cifra
             FROM repertorio_itens ri
             INNER JOIN louvores l ON l.id = ri.louvor_id
             WHERE ri.repertorio_id = :repertorio_id
             ORDER BY ri.ordem ASC'
        );
        $stmt->execute(['repertorio_id' => $repertorioId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @return array<string, mixed>
     */
    public function paraJson(): array
    {
        return [
            'id' => $this->id,
            'louvorId' => $this->louvorId,
            'ordem' => $this->ordem,
            'titulo' => $this->tituloLouvor,
            'tomAtual' => $this->tomAtual,
            'andamentoBpm' => $this->andamentoBpm,
            'letra' => $this->letra,
            'cifra' => $this->cifra,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            repertorioId: (int) $row['repertorio_id'],
            louvorId: (int) $row['louvor_id'],
            ordem: (int) $row['ordem'],
            tituloLouvor: (string) $row['louvor_titulo'],
            tomAtual: $row['tom_atual'],
            andamentoBpm: $row['andamento_bpm'] !== null ? (int) $row['andamento_bpm'] : null,
            letra: $row['letra'],
            cifra: $row['cifra'],
        );
    }
}
