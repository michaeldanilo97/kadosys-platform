<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Unidade fisica (filial) de uma barbearia. Toda barbearia nasce com
 * uma unidade "principal" automatica (ver criarPrincipal(), chamada no
 * cadastro publico e no seed de dev) - quem nunca criou uma segunda
 * unidade nao ve NENHUMA tela nova relacionada a isso: o painel so
 * pede pra escolher unidade quando temMultiplasAtivas() e verdadeiro.
 *
 * Sempre precisa sobrar pelo menos uma unidade por barbearia - excluir()
 * e alternarAtiva(false) bloqueiam a operacao se ela deixaria a
 * barbearia sem nenhuma unidade (ou sem nenhuma ATIVA, no caso de
 * desativar), senao o agendamento publico e o cadastro de profissional
 * ficariam sem nenhum lugar pra vincular.
 */
final class Unidade
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, slug, endereco, cidade, estado, cep,
        telefone, whatsapp, principal, ativa, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
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

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM unidades WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findBySlug(int $barbeariaId, string $slug): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM unidades
             WHERE barbearia_id = :barbearia_id AND slug = :slug AND ativa = 1 LIMIT 1"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function todas(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM unidades WHERE barbearia_id = :barbearia_id
             ORDER BY principal DESC, nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /** @return array<int, self> */
    public static function ativas(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM unidades WHERE barbearia_id = :barbearia_id AND ativa = 1
             ORDER BY principal DESC, nome ASC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contarAtivas(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM unidades WHERE barbearia_id = :barbearia_id AND ativa = 1"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Verdadeiro so quando ha mais de uma unidade ATIVA - e o que
     * decide, em todo o painel e no agendamento publico, se os
     * seletores de unidade aparecem ou ficam escondidos.
     */
    public static function temMultiplasAtivas(int $barbeariaId): bool
    {
        return self::contarAtivas($barbeariaId) > 1;
    }

    public static function principal(int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM unidades WHERE barbearia_id = :barbearia_id AND principal = 1 LIMIT 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Cria a unidade inicial de uma barbearia recem-criada - chamada
     * logo depois de Barbearia::criar() no cadastro publico e no seed
     * de dev, pra toda barbearia ja nascer com uma unidade sem exigir
     * nenhuma configuracao manual.
     */
    public static function criarPrincipal(int $barbeariaId, string $nomeSugerido): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO unidades (barbearia_id, nome, slug, principal, ativa, created_at)
             VALUES (:barbearia_id, :nome, 'principal', 1, 1, NOW())"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nomeSugerido) !== '' ? trim($nomeSugerido) : 'Unidade Principal',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function criar(
        int $barbeariaId,
        string $nome,
        ?string $endereco,
        ?string $cidade,
        ?string $estado,
        ?string $cep,
        ?string $telefone,
        ?string $whatsapp,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO unidades (barbearia_id, nome, slug, endereco, cidade, estado, cep, telefone, whatsapp, principal, ativa, created_at)
             VALUES (:barbearia_id, :nome, :slug, :endereco, :cidade, :estado, :cep, :telefone, :whatsapp, 0, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'slug' => self::gerarSlugUnico($barbeariaId, $nome),
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
        int $barbeariaId,
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
             WHERE id = :id AND barbearia_id = :barbearia_id'
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
            'barbearia_id' => $barbeariaId,
        ]);
    }

    /**
     * Retorna false (e nao aplica nada) se desativar essa unidade
     * deixaria a barbearia sem nenhuma unidade ativa.
     */
    public static function alternarAtiva(int $id, int $barbeariaId, bool $ativa): bool
    {
        if (!$ativa && self::contarAtivas($barbeariaId) <= 1) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE unidades SET ativa = :ativa WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute(['ativa' => $ativa ? 1 : 0, 'id' => $id, 'barbearia_id' => $barbeariaId]);

        return true;
    }

    /**
     * Retorna false (e nao exclui nada) se essa for a unica unidade da
     * barbearia - toda barbearia precisa ter sempre pelo menos uma.
     */
    public static function excluir(int $id, int $barbeariaId): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM unidades WHERE barbearia_id = :barbearia_id');
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        if ((int) $stmt->fetchColumn() <= 1) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM unidades WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);

        return true;
    }

    private static function gerarSlugUnico(int $barbeariaId, string $nome): string
    {
        $base = self::normalizarSlug($nome);
        $base = $base === '' ? 'unidade' : $base;
        $slug = $base;
        $sufixo = 2;

        while (self::slugEmUso($barbeariaId, $slug)) {
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }

        return $slug;
    }

    private static function slugEmUso(int $barbeariaId, string $slug): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM unidades WHERE barbearia_id = :barbearia_id AND slug = :slug LIMIT 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'slug' => $slug]);

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
            barbeariaId: (int) $row['barbearia_id'],
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
