<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Mapa Biblico da Biblioteca Kids: catalogo estatico dos lugares mais
 * importantes da Biblia (nome, emoji, descricao curta e posicao no
 * mapa ilustrado - "top"/"left" em percentual, pra plotar o pin via
 * CSS) - mesmo padrao ja usado no catalogo de emblemas (KidsEmblema).
 * So "quais locais a crianca ja explorou" mora no banco
 * (kids_mapa_explorados).
 */
final class KidsMapaLocal
{
    private const BONUS_XP = 4;

    /**
     * @var array<string, array{nome: string, emoji: string, descricao: string, top: int, left: int}>
     */
    public const CATALOGO = [
        'egito' => ['nome' => 'Egito', 'emoji' => '🐫', 'descricao' => 'José foi vendido como escravo aqui, mas Deus o usou pra salvar sua família da fome. Depois, o povo de Israel viveu escravo aqui por muitos anos.', 'top' => 78, 'left' => 18],
        'mar-vermelho' => ['nome' => 'Mar Vermelho', 'emoji' => '🌊', 'descricao' => 'Deus abriu um caminho em meio ao mar pra o povo de Israel escapar do exército do Egito!', 'top' => 70, 'left' => 32],
        'monte-sinai' => ['nome' => 'Monte Sinai', 'emoji' => '⛰️', 'descricao' => 'Foi aqui que Deus deu os Dez Mandamentos pra Moisés, no topo da montanha.', 'top' => 82, 'left' => 44],
        'jerico' => ['nome' => 'Jericó', 'emoji' => '🎺', 'descricao' => 'As muralhas desta cidade caíram depois que o povo de Israel marchou ao redor dela tocando trombetas!', 'top' => 44, 'left' => 50],
        'jerusalem' => ['nome' => 'Jerusalém', 'emoji' => '🏛️', 'descricao' => 'A cidade santa, com o Templo. Foi aqui que Jesus foi crucificado e ressuscitou ao terceiro dia.', 'top' => 51, 'left' => 70],
        'belem' => ['nome' => 'Belém', 'emoji' => '⭐', 'descricao' => 'Uma cidadezinha pequena onde Jesus nasceu, numa manjedoura, numa noite muito especial.', 'top' => 60, 'left' => 58],
        'nazare' => ['nome' => 'Nazaré', 'emoji' => '🏡', 'descricao' => 'A cidade onde Jesus cresceu, ajudando José na carpintaria.', 'top' => 24, 'left' => 58],
        'rio-jordao' => ['nome' => 'Rio Jordão', 'emoji' => '💧', 'descricao' => 'João Batista batizou Jesus nas águas deste rio.', 'top' => 36, 'left' => 66],
        'mar-da-galileia' => ['nome' => 'Mar da Galileia', 'emoji' => '⛵', 'descricao' => 'Jesus andou sobre as águas deste mar e chamou seus primeiros discípulos, que eram pescadores aqui.', 'top' => 18, 'left' => 65],
        'cafarnaum' => ['nome' => 'Cafarnaum', 'emoji' => '🐟', 'descricao' => 'A cidade onde Jesus morou durante seu ministério e fez muitos milagres.', 'top' => 12, 'left' => 68],
        'babilonia' => ['nome' => 'Babilônia', 'emoji' => '🦁', 'descricao' => 'Daniel foi jogado numa cova de leões aqui, mas Deus fechou a boca dos leões e o protegeu!', 'top' => 60, 'left' => 92],
        'monte-ararate' => ['nome' => 'Monte Ararate', 'emoji' => '🚢', 'descricao' => 'Depois do grande dilúvio, foi aqui que a arca de Noé finalmente descansou.', 'top' => 6, 'left' => 88],
    ];

    /**
     * Slugs ja explorados por uma crianca.
     *
     * @return array<int, string>
     */
    public static function exploradosPor(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT local_slug FROM kids_mapa_explorados WHERE crianca_id = :crianca_id'
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Marca um local como explorado - idempotente (UNIQUE em
     * crianca_id+local_slug), so concede o bonus de XP na primeira
     * vez. Retorna true quando o bonus foi concedido agora.
     */
    public static function registrarExploracao(int $criancaId, string $slug): bool
    {
        if (!isset(self::CATALOGO[$slug])) {
            return false;
        }

        $pdo = Database::connection();

        $inserir = $pdo->prepare(
            'INSERT IGNORE INTO kids_mapa_explorados (crianca_id, local_slug, explorado_em)
             VALUES (:crianca_id, :slug, NOW())'
        );
        $inserir->execute(['crianca_id' => $criancaId, 'slug' => $slug]);

        if ($inserir->rowCount() > 0) {
            KidsCrianca::adicionarPontos($criancaId, self::BONUS_XP, 0);

            return true;
        }

        return false;
    }
}
