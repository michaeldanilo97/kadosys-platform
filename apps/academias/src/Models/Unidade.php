<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Unidade fisica (filial) de uma academia. Toda academia nasce com
 * uma unidade "principal" automatica (ver criarPrincipal(), chamada no
 * cadastro publico e no seed de dev) - quem nunca criou uma segunda
 * unidade nao ve NENHUMA tela nova relacionada a isso: o painel so
 * pede pra escolher unidade quando temMultiplasAtivas() e verdadeiro.
 *
 * Sempre precisa sobrar pelo menos uma unidade por academia - excluir()
 * e alternarAtiva(false) bloqueiam a operacao se ela deixaria a
 * academia sem nenhuma unidade (ou sem nenhuma ATIVA, no caso de
 * desativar), senao o agendamento publico e o cadastro de profissional
 * ficariam sem nenhum lugar pra vincular.
 */
final class Unidade
{
    private const SELECT_COLUNAS = 'id, academia_id, nome, slug, endereco, cidade, estado, cep,
        telefone, whatsapp, principal, ativa, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly string $nome,
        public readonly string $slug,
        public readonly ?string $endereco,
        public readonly ?string $cidade,
        public readonly ?string $estado,
        public readonly ?string $cep,
        public readonly ?string $telefone,
        public readonly ?string $whatsapp,
        public readonly bool $principal,
        public readonly bool $ativa,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id, int $academiaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM unidades WHERE id = :id AND academia_id = :academia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findBySlug(int $academiaId, string $slug): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM unidades
             WHERE academia_id = :academia_id AND slug = :slug AND ativa = 1 LIMIT 1"
        );
        $stmt->execute(['academia_id' => $academiaId, 'slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function todas(int $academiaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM unidades WHERE academia_id = :academia_id
             ORDER BY principal DESC, nome ASC'
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /** @return array<int, self> */
    public static function ativas(int $academiaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM unidades WHERE academia_id = :academia_id AND ativa = 1
             ORDER BY principal DESC, nome ASC"
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contarAtivas(int $academiaId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM unidades WHERE academia_id = :academia_id AND ativa = 1"
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Verdadeiro so quando ha mais de uma unidade ATIVA - e o que
     * decide, em todo o painel e no agendamento publico, se os
     * seletores de unidade aparecem ou ficam escondidos.
     */
    public static function temMultiplasAtivas(int $academiaId): bool
    {
        return self::contarAtivas($academiaId) > 1;
    }

    public static function principal(int $academiaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM unidades WHERE academia_id = :academia_id AND principal = 1 LIMIT 1'
        );
        $stmt->execute(['academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Cria a unidade inicial de uma academia recem-criada - chamada
     * logo depois de Academia::criar() no cadastro publico e no seed
     * de dev, pra toda academia ja nascer com uma unidade sem exigir
     * nenhuma configuracao manual.
     */
    public static function criarPrincipal(int $academiaId, string $nomeSugerido): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO unidades (academia_id, nome, slug, principal, ativa, created_at)
             VALUES (:academia_id, :nome, 'principal', 1, 1, NOW())"
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'nome' => trim($nomeSugerido) !== '' ? trim($nomeSugerido) : 'Unidade Principal',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function criar(
        int $academiaId,
        string $nome,
        ?string $endereco,
        ?string $cidade,
        ?string $estado,
        ?string $cep,
        ?string $telefone,
        ?string $whatsapp,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO unidades (academia_id, nome, slug, endereco, cidade, estado, cep, telefone, whatsapp, principal, ativa, created_at)
             VALUES (:academia_id, :nome, :slug, :endereco, :cidade, :estado, :cep, :telefone, :whatsapp, 0, 1, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'nome' => trim($nome),
            'slug' => self::gerarSlugUnico($academiaId, $nome),
            'endereco' => self::nullable($endereco),
            'cidade' => self::nullable($cidade),
            'estado' => self::nullable($estado),
            'cep' => self::nullable($cep),
            'telefone' => self::nullable($telefone),
            'whatsapp' => self::nullable($whatsapp),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function atualizar(
        int $id,
        int $academiaId,
        string $nome,
        ?string $endereco,
        ?string $cidade,
        ?string $estado,
        ?string $cep,
        ?string $telefone,
        ?string $whatsapp,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE unidades SET nome = :nome, endereco = :endereco, cidade = :cidade, estado = :estado,
                cep = :cep, telefone = :telefone, whatsapp = :whatsapp
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'endereco' => self::nullable($endereco),
            'cidade' => self::nullable($cidade),
            'estado' => self::nullable($estado),
            'cep' => self::nullable($cep),
            'telefone' => self::nullable($telefone),
            'whatsapp' => self::nullable($whatsapp),
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    /**
     * Retorna false (e nao aplica nada) se desativar essa unidade
     * deixaria a academia sem nenhuma unidade ativa.
     */
    public static function alternarAtiva(int $id, int $academiaId, bool $ativa): bool
    {
        if (!$ativa && self::contarAtivas($academiaId) <= 1) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE unidades SET ativa = :ativa WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute(['ativa' => $ativa ? 1 : 0, 'id' => $id, 'academia_id' => $academiaId]);

        return true;
    }

    /**
     * Retorna false (e nao exclui nada) se essa for a unica unidade da
     * academia - toda academia precisa ter sempre pelo menos uma.
     */
    public static function excluir(int $id, int $academiaId): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM unidades WHERE academia_id = :academia_id');
        $stmt->execute(['academia_id' => $academiaId]);

        if ((int) $stmt->fetchColumn() <= 1) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM unidades WHERE id = :id AND academia_id = :academia_id');
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);

        return true;
    }

    private static function gerarSlugUnico(int $academiaId, string $nome): string
    {
        $base = self::normalizarSlug($nome);
        $base = $base === '' ? 'unidade' : $base;
        $slug = $base;
        $sufixo = 2;

        while (self::slugEmUso($academiaId, $slug)) {
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }

        return $slug;
    }

    private static function slugEmUso(int $academiaId, string $slug): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM unidades WHERE academia_id = :academia_id AND slug = :slug LIMIT 1'
        );
        $stmt->execute(['academia_id' => $academiaId, 'slug' => $slug]);

        return $stmt->fetch() !== false;
    }

    private static function normalizarSlug(string $valor): string
    {
        $valor = mb_strtolower($valor);
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT', $valor);
        $valor = $transliterado !== false ? $transliterado : $valor;
        $valor = preg_replace('/[^a-z0-9]+/', '-', $valor) ?? '';

        return trim($valor, '-');
    }

    private static function nullable(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            academiaId: (int) $row['academia_id'],
            nome: (string) $row['nome'],
            slug: (string) $row['slug'],
            endereco: $row['endereco'] ?? null,
            cidade: $row['cidade'] ?? null,
            estado: $row['estado'] ?? null,
            cep: $row['cep'] ?? null,
            telefone: $row['telefone'] ?? null,
            whatsapp: $row['whatsapp'] ?? null,
            principal: (bool) $row['principal'],
            ativa: (bool) $row['ativa'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
