<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Emblemas/conquistas por marcos especiais (100 conteudos concluidos,
 * primeiro duelo vencido etc). O catalogo (nome, emoji, descricao,
 * criterio) e estatico em PHP, mesmo padrao ja usado no catalogo do
 * avatar (KidsAvatar) - so o "quais a crianca ja tem" mora no banco
 * (kids_emblemas_conquistados). Cada emblema concede um bonus fixo de
 * XP/moedas na primeira vez, igual as demais recompensas do modulo.
 */
final class KidsEmblema
{
    private const BONUS_XP = 10;
    private const BONUS_MOEDAS = 5;

    /**
     * @var array<string, array{nome: string, emoji: string, descricao: string}>
     */
    public const CATALOGO = [
        'primeiro-passo' => ['nome' => 'Primeiro Passo', 'emoji' => '🎯', 'descricao' => 'Completou o primeiro conteúdo da Biblioteca.'],
        'leitor-dedicado' => ['nome' => 'Leitor Dedicado', 'emoji' => '📚', 'descricao' => 'Completou 10 conteúdos da Biblioteca.'],
        'explorador-da-fe' => ['nome' => 'Explorador da Fé', 'emoji' => '🧭', 'descricao' => 'Completou 50 conteúdos da Biblioteca.'],
        'centuriao' => ['nome' => 'Centurião', 'emoji' => '🏆', 'descricao' => 'Completou 100 conteúdos da Biblioteca.'],
        'primeira-vitoria' => ['nome' => 'Primeira Vitória', 'emoji' => '⚔️', 'descricao' => 'Venceu o primeiro duelo contra um amigo.'],
        'campeao-dos-duelos' => ['nome' => 'Campeão dos Duelos', 'emoji' => '🥇', 'descricao' => 'Venceu 5 duelos contra amigos.'],
        'sequencia-de-fogo' => ['nome' => 'Sequência de Fogo', 'emoji' => '🔥', 'descricao' => 'Acessou a Biblioteca 7 dias seguidos.'],
        'fiel-todo-mes' => ['nome' => 'Fiel Todo Mês', 'emoji' => '🌟', 'descricao' => 'Acessou a Biblioteca 30 dias seguidos.'],
        'mestre-dos-jogos' => ['nome' => 'Mestre dos Jogos', 'emoji' => '🎮', 'descricao' => 'Completou 10 jogos diferentes.'],
        'lenda-kids' => ['nome' => 'Lenda Kids', 'emoji' => '👑', 'descricao' => 'Chegou ao nível máximo.'],
    ];

    /**
     * Slugs ja conquistados por uma crianca.
     *
     * @return array<int, string>
     */
    public static function conquistadosPor(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT emblema_slug FROM kids_emblemas_conquistados WHERE crianca_id = :crianca_id'
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Catalogo completo com o status de cada emblema pra essa crianca
     * (conquistado ou nao, e quando) - usado na galeria /kids/emblemas.
     *
     * @return array<int, array{slug: string, nome: string, emoji: string, descricao: string, conquistado: bool, conquistadoEm: ?string}>
     */
    public static function catalogoComStatus(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT emblema_slug, conquistado_em FROM kids_emblemas_conquistados WHERE crianca_id = :crianca_id'
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        $conquistados = [];
        foreach ($stmt->fetchAll() as $linha) {
            $conquistados[$linha['emblema_slug']] = $linha['conquistado_em'];
        }

        $lista = [];

        foreach (self::CATALOGO as $slug => $info) {
            $lista[] = [
                'slug' => $slug,
                'nome' => $info['nome'],
                'emoji' => $info['emoji'],
                'descricao' => $info['descricao'],
                'conquistado' => isset($conquistados[$slug]),
                'conquistadoEm' => $conquistados[$slug] ?? null,
            ];
        }

        return $lista;
    }

    /**
     * Confere os criterios de todos os emblemas ainda nao conquistados
     * por essa crianca contra o estado atual dela (conteudos
     * concluidos, duelos vencidos, sequencia, jogos, nivel) e grava
     * qualquer um que agora se qualifique - idempotente (UNIQUE em
     * crianca_id+emblema_slug), seguro de chamar em qualquer momento.
     * Retorna so os que foram conquistados NESTA chamada, pra exibir
     * um banner de comemoração.
     *
     * @return array<int, array{slug: string, nome: string, emoji: string}>
     */
    public static function verificarNovos(KidsCrianca $crianca): array
    {
        $jaTem = array_flip(self::conquistadosPor($crianca->id));
        $pendentes = array_diff_key(self::CATALOGO, $jaTem);

        if ($pendentes === []) {
            return [];
        }

        $stats = self::estatisticas($crianca);
        $novos = [];

        foreach ($pendentes as $slug => $info) {
            if (self::criterioAtendido($slug, $stats)) {
                self::conceder($crianca->id, $slug);
                $novos[] = ['slug' => $slug, 'nome' => $info['nome'], 'emoji' => $info['emoji']];
            }
        }

        return $novos;
    }

    /**
     * @return array{totalConcluidos: int, duelosVencidos: int, jogosConcluidos: int, sequenciaApp: int, nivel: int}
     */
    private static function estatisticas(KidsCrianca $crianca): array
    {
        $pdo = Database::connection();

        $totalConcluidos = $pdo->prepare('SELECT COUNT(*) FROM kids_conteudo_conclusoes WHERE crianca_id = :id');
        $totalConcluidos->execute(['id' => $crianca->id]);

        $duelosVencidos = $pdo->prepare("SELECT COUNT(*) FROM kids_duelos WHERE vencedor_id = :id AND status = 'finalizado'");
        $duelosVencidos->execute(['id' => $crianca->id]);

        $jogosConcluidos = $pdo->prepare(
            "SELECT COUNT(*) FROM kids_conteudo_conclusoes c
             INNER JOIN kids_conteudos k ON k.id = c.conteudo_id
             WHERE c.crianca_id = :id AND k.tipo = 'jogo'"
        );
        $jogosConcluidos->execute(['id' => $crianca->id]);

        return [
            'totalConcluidos' => (int) $totalConcluidos->fetchColumn(),
            'duelosVencidos' => (int) $duelosVencidos->fetchColumn(),
            'jogosConcluidos' => (int) $jogosConcluidos->fetchColumn(),
            'sequenciaApp' => $crianca->sequenciaAppDias,
            'nivel' => $crianca->nivel(),
        ];
    }

    /**
     * @param array{totalConcluidos: int, duelosVencidos: int, jogosConcluidos: int, sequenciaApp: int, nivel: int} $stats
     */
    private static function criterioAtendido(string $slug, array $stats): bool
    {
        return match ($slug) {
            'primeiro-passo' => $stats['totalConcluidos'] >= 1,
            'leitor-dedicado' => $stats['totalConcluidos'] >= 10,
            'explorador-da-fe' => $stats['totalConcluidos'] >= 50,
            'centuriao' => $stats['totalConcluidos'] >= 100,
            'primeira-vitoria' => $stats['duelosVencidos'] >= 1,
            'campeao-dos-duelos' => $stats['duelosVencidos'] >= 5,
            'sequencia-de-fogo' => $stats['sequenciaApp'] >= 7,
            'fiel-todo-mes' => $stats['sequenciaApp'] >= 30,
            'mestre-dos-jogos' => $stats['jogosConcluidos'] >= 10,
            'lenda-kids' => $stats['nivel'] >= KidsAvatar::NIVEL_MAXIMO,
            default => false,
        };
    }

    private static function conceder(int $criancaId, string $slug): void
    {
        $pdo = Database::connection();

        $inserir = $pdo->prepare(
            'INSERT IGNORE INTO kids_emblemas_conquistados (crianca_id, emblema_slug, conquistado_em)
             VALUES (:crianca_id, :slug, NOW())'
        );
        $inserir->execute(['crianca_id' => $criancaId, 'slug' => $slug]);

        if ($inserir->rowCount() > 0) {
            KidsCrianca::adicionarPontos($criancaId, self::BONUS_XP, self::BONUS_MOEDAS);
        }
    }
}
