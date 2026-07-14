<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de KidsConteudo (modulo KADOSYS Kids > Biblioteca): historias,
 * videos, audios, devocionais, quiz, atividades para colorir etc.
 *
 * "origem" distingue o conteudo oficial da KADOSYS (semeado em
 * database/install.sql, somente leitura pra igreja - ver
 * KidsConteudoController::editavel()) do conteudo proprio de cada
 * igreja, criado pela propria equipe.
 */
final class KidsConteudo
{
    public const ORIGEM_KADOSYS = 'kadosys';
    public const ORIGEM_IGREJA = 'igreja';

    /**
     * Tipos de conteudo da biblioteca (slug => label/emoji), unica
     * fonte de verdade usada tanto no CMS (Kids > Conteudos) quanto na
     * tela colorida da criança (Kids > Biblioteca).
     *
     * @var array<string, array{label: string, emoji: string}>
     */
    public const TIPOS = [
        'historia' => ['label' => 'Histórias', 'emoji' => '📖'],
        'video' => ['label' => 'Vídeos', 'emoji' => '🎥'],
        'audio' => ['label' => 'Áudios', 'emoji' => '🎧'],
        'slide' => ['label' => 'Slides', 'emoji' => '🖼️'],
        'hq' => ['label' => 'HQs', 'emoji' => '💥'],
        'devocional' => ['label' => 'Devocionais', 'emoji' => '🙏'],
        'estudo' => ['label' => 'Estudos', 'emoji' => '📚'],
        'plano_leitura' => ['label' => 'Planos de leitura', 'emoji' => '🗓️'],
        'quiz' => ['label' => 'Quiz', 'emoji' => '❓'],
        'colorir' => ['label' => 'Colorir', 'emoji' => '🎨'],
        'pdf' => ['label' => 'PDFs', 'emoji' => '📄'],
        'atividade' => ['label' => 'Atividades', 'emoji' => '✏️'],
        'jogo' => ['label' => 'Jogos', 'emoji' => '🎮'],
        'desafio' => ['label' => 'Desafios', 'emoji' => '🏆'],
        'versiculo_ilustrado' => ['label' => 'Versículos ilustrados', 'emoji' => '⭐'],
    ];

    public function __construct(
        public readonly int $id,
        public readonly string $tipo,
        public readonly string $origem,
        public readonly string $titulo,
        public readonly ?string $descricao,
        public readonly ?string $categoria,
        public readonly ?string $tema,
        public readonly ?string $livroBiblico,
        public readonly ?string $personagem,
        public readonly ?string $dificuldade,
        public readonly ?int $idadeMin,
        public readonly ?int $idadeMax,
        public readonly ?int $duracaoMinutos,
        public readonly int $xpRecompensa,
        public readonly int $moedasRecompensa,
        public readonly ?string $textoConteudo,
        public readonly ?string $midiaUrl,
        public readonly ?string $midiaPath,
        public readonly ?string $capaPath,
        public readonly ?array $quizPerguntas,
        public readonly string $status,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search = '', string $tipo = '', string $origem = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(titulo LIKE :search_titulo OR categoria LIKE :search_categoria OR tema LIKE :search_tema)';
            $params['search_titulo'] = '%' . $search . '%';
            $params['search_categoria'] = '%' . $search . '%';
            $params['search_tema'] = '%' . $search . '%';
        }

        if ($tipo !== '' && isset(self::TIPOS[$tipo])) {
            $conditions[] = 'tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        if (in_array($origem, [self::ORIGEM_KADOSYS, self::ORIGEM_IGREJA], true)) {
            $conditions[] = 'origem = :origem';
            $params['origem'] = $origem;
        }

        $where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM kids_conteudos {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM kids_conteudos {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM kids_conteudos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Conteudos publicados de um tipo - usado na Biblioteca (tela
     * colorida da criança), que so mostra o que ja esta pronto pra
     * consumo (nunca rascunhos).
     *
     * @return array<int, self>
     */
    public static function publicadosPorTipo(string $tipo): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM kids_conteudos WHERE tipo = :tipo AND status = 'publicado' ORDER BY origem DESC, titulo ASC"
        );
        $stmt->execute(['tipo' => $tipo]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Contagem de conteudos publicados por tipo - usado nos cartoes
     * grandes da tela inicial da Biblioteca.
     *
     * @return array<string, int>
     */
    public static function contagemPublicadaPorTipo(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT tipo, COUNT(*) AS total FROM kids_conteudos WHERE status = 'publicado' GROUP BY tipo"
        );
        $stmt->execute();

        $contagens = array_fill_keys(array_keys(self::TIPOS), 0);
        foreach ($stmt->fetchAll() as $row) {
            $contagens[$row['tipo']] = (int) $row['total'];
        }

        return $contagens;
    }

    /**
     * Conteudos publicados mais recentes - usado em "Novidades" na
     * tela inicial da Biblioteca.
     *
     * @return array<int, self>
     */
    public static function recentesPublicados(int $limit = 8): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM kids_conteudos WHERE status = 'publicado' ORDER BY created_at DESC LIMIT " . max(1, $limit)
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function countPublicados(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM kids_conteudos WHERE status = 'publicado'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO kids_conteudos
                (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade,
                 idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo,
                 midia_url, quiz_perguntas, status, criado_por, created_at)
             VALUES
                (:tipo, :origem, :titulo, :descricao, :categoria, :tema, :livro_biblico, :personagem, :dificuldade,
                 :idade_min, :idade_max, :duracao_minutos, :xp_recompensa, :moedas_recompensa, :texto_conteudo,
                 :midia_url, :quiz_perguntas, :status, :criado_por, NOW())'
        );
        $stmt->execute(self::bindings($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_conteudos SET
                tipo = :tipo, titulo = :titulo, descricao = :descricao, categoria = :categoria, tema = :tema,
                livro_biblico = :livro_biblico, personagem = :personagem, dificuldade = :dificuldade,
                idade_min = :idade_min, idade_max = :idade_max, duracao_minutos = :duracao_minutos,
                xp_recompensa = :xp_recompensa, moedas_recompensa = :moedas_recompensa,
                texto_conteudo = :texto_conteudo, midia_url = :midia_url, quiz_perguntas = :quiz_perguntas,
                status = :status
             WHERE id = :id AND origem = :origem_atual'
        );
        $bindings = self::bindings($data);
        unset($bindings['origem'], $bindings['criado_por']);
        $stmt->execute($bindings + ['id' => $id, 'origem_atual' => self::ORIGEM_IGREJA]);
    }

    public static function atualizarCapa(int $id, string $capaPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE kids_conteudos SET capa_path = :capa_path WHERE id = :id');
        $stmt->execute(['capa_path' => $capaPath, 'id' => $id]);
    }

    public static function atualizarMidia(int $id, string $midiaPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE kids_conteudos SET midia_path = :midia_path WHERE id = :id');
        $stmt->execute(['midia_path' => $midiaPath, 'id' => $id]);
    }

    /**
     * Só apaga conteúdo próprio da igreja - o oficial da KADOSYS é
     * somente leitura (ver editavel()).
     */
    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM kids_conteudos WHERE id = :id AND origem = :origem');
        $stmt->execute(['id' => $id, 'origem' => self::ORIGEM_IGREJA]);
    }

    public static function jaConcluidoPor(int $criancaId, int $conteudoId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM kids_conteudo_conclusoes WHERE crianca_id = :crianca_id AND conteudo_id = :conteudo_id LIMIT 1'
        );
        $stmt->execute(['crianca_id' => $criancaId, 'conteudo_id' => $conteudoId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Registra a conclusao do conteudo por uma crianca e concede
     * XP/moedas - so na primeira vez (UNIQUE em kids_conteudo_conclusoes
     * impede conceder pontos de novo se a crianca repetir o conteudo).
     * Retorna true se os pontos foram concedidos agora.
     */
    public function registrarConclusaoPor(int $criancaId): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO kids_conteudo_conclusoes (crianca_id, conteudo_id, created_at)
             VALUES (:crianca_id, :conteudo_id, NOW())'
        );
        $stmt->execute(['crianca_id' => $criancaId, 'conteudo_id' => $this->id]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        KidsCrianca::adicionarPontos($criancaId, $this->xpRecompensa, $this->moedasRecompensa);

        return true;
    }

    public function editavel(): bool
    {
        return $this->origem === self::ORIGEM_IGREJA;
    }

    public function tipoInfo(): array
    {
        return self::TIPOS[$this->tipo] ?? ['label' => ucfirst($this->tipo), 'emoji' => '📦'];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $idadeMin = trim((string) ($data['idade_min'] ?? ''));
        $idadeMax = trim((string) ($data['idade_max'] ?? ''));
        $duracao = trim((string) ($data['duracao_minutos'] ?? ''));
        $xp = trim((string) ($data['xp_recompensa'] ?? ''));
        $moedas = trim((string) ($data['moedas_recompensa'] ?? ''));
        $dificuldade = trim((string) ($data['dificuldade'] ?? ''));
        $quizPerguntas = $data['quiz_perguntas'] ?? null;

        return [
            'tipo' => array_key_exists((string) ($data['tipo'] ?? ''), self::TIPOS) ? $data['tipo'] : 'historia',
            'origem' => self::ORIGEM_IGREJA,
            'titulo' => trim((string) $data['titulo']),
            'descricao' => self::nullable($data['descricao'] ?? null),
            'categoria' => self::nullable($data['categoria'] ?? null),
            'tema' => self::nullable($data['tema'] ?? null),
            'livro_biblico' => self::nullable($data['livro_biblico'] ?? null),
            'personagem' => self::nullable($data['personagem'] ?? null),
            'dificuldade' => in_array($dificuldade, ['facil', 'medio', 'dificil'], true) ? $dificuldade : null,
            'idade_min' => $idadeMin === '' ? null : (int) $idadeMin,
            'idade_max' => $idadeMax === '' ? null : (int) $idadeMax,
            'duracao_minutos' => $duracao === '' ? null : (int) $duracao,
            'xp_recompensa' => $xp === '' ? 10 : max(0, (int) $xp),
            'moedas_recompensa' => $moedas === '' ? 5 : max(0, (int) $moedas),
            'texto_conteudo' => self::nullable($data['texto_conteudo'] ?? null),
            'midia_url' => self::nullable($data['midia_url'] ?? null),
            'quiz_perguntas' => is_array($quizPerguntas) ? json_encode($quizPerguntas, JSON_UNESCAPED_UNICODE) : self::nullable($quizPerguntas),
            'status' => in_array($data['status'] ?? null, ['rascunho', 'publicado'], true) ? $data['status'] : 'publicado',
            'criado_por' => isset($data['criado_por']) ? (int) $data['criado_por'] : null,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        $quizPerguntas = null;
        if (!empty($row['quiz_perguntas'])) {
            $decoded = json_decode((string) $row['quiz_perguntas'], true);
            $quizPerguntas = is_array($decoded) ? $decoded : null;
        }

        return new self(
            id: (int) $row['id'],
            tipo: (string) $row['tipo'],
            origem: (string) $row['origem'],
            titulo: (string) $row['titulo'],
            descricao: $row['descricao'],
            categoria: $row['categoria'],
            tema: $row['tema'],
            livroBiblico: $row['livro_biblico'],
            personagem: $row['personagem'],
            dificuldade: $row['dificuldade'],
            idadeMin: $row['idade_min'] !== null ? (int) $row['idade_min'] : null,
            idadeMax: $row['idade_max'] !== null ? (int) $row['idade_max'] : null,
            duracaoMinutos: $row['duracao_minutos'] !== null ? (int) $row['duracao_minutos'] : null,
            xpRecompensa: (int) $row['xp_recompensa'],
            moedasRecompensa: (int) $row['moedas_recompensa'],
            textoConteudo: $row['texto_conteudo'],
            midiaUrl: $row['midia_url'],
            midiaPath: $row['midia_path'],
            capaPath: $row['capa_path'],
            quizPerguntas: $quizPerguntas,
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        );
    }
}
