<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Aviso do dono da plataforma (KADOSYS) para as igrejas cadastradas,
 * mostrado no sino de notificacoes e no painel "Atualizacoes do
 * sistema" do Dashboard (ver DashboardController, PlataformaController
 * e layouts/dashboard.php). So um aviso fica ativo por vez (publicar um
 * novo encerra o anterior automaticamente), mesmo padrao ja usado em
 * ProjecaoSessao::iniciar().
 *
 * publicoAlvo escolhe quem enxerga o aviso: PUBLICO_ADMINS (so quem
 * tem role 'admin' em cada igreja), PUBLICO_MEMBROS (so quem nao e
 * admin) ou PUBLICO_TODOS (todo mundo). Util pra nao incomodar membros
 * comuns com avisos administrativos (cobranca, deploy) nem esconder de
 * admins avisos sobre recursos novos que tambem interessam a eles.
 */
final class PlataformaAviso
{
    public const PUBLICO_ADMINS = 'admins';
    public const PUBLICO_MEMBROS = 'membros';
    public const PUBLICO_TODOS = 'todos';

    /** @var array<string, string> */
    public const PUBLICO_LABELS = [
        self::PUBLICO_ADMINS => 'Somente admins',
        self::PUBLICO_MEMBROS => 'Somente membros',
        self::PUBLICO_TODOS => 'Todos',
    ];

    public function __construct(
        public readonly int $id,
        public readonly string $mensagem,
        public readonly bool $ativo,
        public readonly string $publicoAlvo,
        public readonly string $createdAt,
    ) {
    }

    public static function ativo(): ?self
    {
        $stmt = Database::central()->prepare(
            'SELECT * FROM plataforma_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Aviso ativo visivel pra quem esta olhando, de acordo com o papel -
     * admin ve avisos PUBLICO_ADMINS/PUBLICO_TODOS, membro comum ve
     * PUBLICO_MEMBROS/PUBLICO_TODOS. Usada pelo sino de notificacoes e
     * pelo painel "Atualizacoes do sistema" (ver layouts/dashboard.php e
     * DashboardController) - diferente de ativo(), que devolve o aviso
     * ativo sem filtro nenhum, usada na propria tela administrativa da
     * plataforma (quem publica ve o que publicou, independente do
     * publico escolhido).
     */
    public static function ativoParaAudiencia(bool $isAdmin): ?self
    {
        $publicos = $isAdmin
            ? [self::PUBLICO_ADMINS, self::PUBLICO_TODOS]
            : [self::PUBLICO_MEMBROS, self::PUBLICO_TODOS];

        $stmt = Database::central()->prepare(
            'SELECT * FROM plataforma_avisos WHERE ativo = 1 AND publico_alvo IN (:p1, :p2) ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['p1' => $publicos[0], 'p2' => $publicos[1]]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array<int, self>
     */
    public static function todos(): array
    {
        $stmt = Database::central()->query('SELECT * FROM plataforma_avisos ORDER BY id DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function publicar(string $mensagem, string $publicoAlvo = self::PUBLICO_TODOS): int
    {
        if (!isset(self::PUBLICO_LABELS[$publicoAlvo])) {
            $publicoAlvo = self::PUBLICO_TODOS;
        }

        $connection = Database::central();

        $connection->prepare('UPDATE plataforma_avisos SET ativo = 0 WHERE ativo = 1')->execute();

        $stmt = $connection->prepare(
            'INSERT INTO plataforma_avisos (mensagem, ativo, publico_alvo, created_at) VALUES (:mensagem, 1, :publico_alvo, NOW())'
        );
        $stmt->execute(['mensagem' => trim($mensagem), 'publico_alvo' => $publicoAlvo]);

        return (int) $connection->lastInsertId();
    }

    public static function encerrar(int $id): void
    {
        $stmt = Database::central()->prepare('UPDATE plataforma_avisos SET ativo = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            mensagem: (string) $row['mensagem'],
            ativo: (bool) $row['ativo'],
            publicoAlvo: (string) ($row['publico_alvo'] ?? self::PUBLICO_TODOS),
            createdAt: (string) $row['created_at'],
        );
    }
}
