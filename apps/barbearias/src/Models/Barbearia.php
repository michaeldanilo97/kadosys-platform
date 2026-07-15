<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Model de Barbearia (o "tenant" do multi-tenant logico).
 *
 * Diferente do KADOSYS Igrejas (onde o tenant e um registro no banco
 * CENTRAL, separado do banco isolado de cada igreja, e "ativar" exige
 * provisionar banco+subdominio), aqui a barbearia e so mais uma linha
 * dentro do MESMO banco compartilhado - "ativar" e um simples UPDATE
 * nesta mesma tabela, sem nenhuma infraestrutura por meio. Por isso
 * nao existe uma tabela separada de "tentativas de cadastro": a
 * propria barbearia nasce com status 'pendente' (Pix/cartao aguardando
 * confirmacao) ou 'ativo' (trial, sem cobranca imediata) - ver
 * Barbearias\Controllers\CadastroController.
 */
final class Barbearia
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_ATIVA = 'ativo';
    public const STATUS_SUSPENSA = 'suspenso';

    private const SELECT_COLUNAS = 'id, nome, slug, telefone, documento_tipo, documento, razao_social,
        plano, metodo_pagamento, mp_preapproval_id, trial_expira_em, proximo_vencimento, plano_agendado,
        ultimo_acesso_em, status, created_at';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $slug,
        public readonly ?string $telefone,
        public readonly string $documentoTipo,
        public readonly string $documento,
        public readonly ?string $razaoSocial,
        public readonly string $plano,
        public readonly string $metodoPagamento,
        public readonly ?string $mpPreapprovalId,
        public readonly ?string $trialExpiraEm,
        public readonly ?string $proximoVencimento,
        public readonly ?string $planoAgendado,
        public readonly ?string $ultimoAcessoEm,
        public readonly string $status,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM barbearias WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * So retorna barbearias ATIVAS - usado pela pagina publica de
     * agendamento (Barbearias\Controllers\AgendamentoPublicoController),
     * que nao pode expor o link de uma barbearia suspensa/com
     * pagamento pendente.
     */
    public static function findBySlugAtiva(string $slug): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM barbearias WHERE slug = :slug AND status = 'ativo' LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function buscarPorMpPreapprovalId(string $preapprovalId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM barbearias WHERE mp_preapproval_id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $preapprovalId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function slugDisponivel(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM barbearias WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() === false;
    }

    /**
     * Verifica se esse CPF/CNPJ ja usou o teste gratis antes - impede
     * criar uma conta nova em trial so trocando de e-mail/barbearia.
     */
    public static function documentoJaUsouTrial(string $documento): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM barbearias WHERE documento = :documento AND trial_expira_em IS NOT NULL LIMIT 1'
        );
        $stmt->execute(['documento' => $documento]);

        return $stmt->fetch() !== false;
    }

    public static function criar(
        string $nome,
        string $slug,
        string $documentoTipo,
        string $documento,
        ?string $razaoSocial,
        string $plano,
        string $metodoPagamento,
        ?\DateTimeImmutable $trialExpiraEm,
        ?string $telefone = null,
    ): int {
        $status = $metodoPagamento === 'trial' ? self::STATUS_ATIVA : self::STATUS_PENDENTE;

        $stmt = Database::connection()->prepare(
            'INSERT INTO barbearias
                (nome, slug, telefone, documento_tipo, documento, razao_social, plano, metodo_pagamento,
                 trial_expira_em, status, created_at)
             VALUES
                (:nome, :slug, :telefone, :documento_tipo, :documento, :razao_social, :plano, :metodo_pagamento,
                 :trial_expira_em, :status, NOW())'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'slug' => trim($slug),
            'telefone' => $telefone,
            'documento_tipo' => $documentoTipo,
            'documento' => $documento,
            'razao_social' => $razaoSocial,
            'plano' => $plano,
            'metodo_pagamento' => $metodoPagamento,
            'trial_expira_em' => $trialExpiraEm?->format('Y-m-d H:i:s'),
            'status' => $status,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function atualizarPerfil(int $id, string $nome, ?string $telefone): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE barbearias SET nome = :nome, telefone = :telefone WHERE id = :id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'telefone' => $telefone !== null && trim($telefone) !== '' ? trim($telefone) : null,
            'id' => $id,
        ]);
    }

    public static function marcarAtiva(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE barbearias SET status = 'ativo' WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function atualizarMpPreapprovalId(int $id, string $preapprovalId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE barbearias SET mp_preapproval_id = :preapproval_id WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'preapproval_id' => $preapprovalId]);
    }

    public static function atualizarProximoVencimento(int $id, \DateTimeImmutable $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE barbearias SET proximo_vencimento = :data WHERE id = :id'
        );
        $stmt->execute(['data' => $data->format('Y-m-d'), 'id' => $id]);
    }

    public static function atualizarUltimoAcesso(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE barbearias SET ultimo_acesso_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Barbearias ativas que pagam por Pix (precisam de fatura nova
     * gerada a cada ciclo - ver cron/gerar_faturas_pix.php). Quem paga
     * por cartao renova sozinho, direto no Mercado Pago via preapproval.
     *
     * @return array<int, self>
     */
    public static function ativasComPagamentoPix(): array
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . " WHERE status = 'ativo' AND metodo_pagamento = 'pix'"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function selectBase(): string
    {
        return 'SELECT ' . self::SELECT_COLUNAS . ' FROM barbearias';
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            slug: (string) $row['slug'],
            telefone: $row['telefone'] ?? null,
            documentoTipo: (string) $row['documento_tipo'],
            documento: (string) $row['documento'],
            razaoSocial: $row['razao_social'] ?? null,
            plano: (string) $row['plano'],
            metodoPagamento: (string) $row['metodo_pagamento'],
            mpPreapprovalId: $row['mp_preapproval_id'] ?? null,
            trialExpiraEm: $row['trial_expira_em'] ?? null,
            proximoVencimento: $row['proximo_vencimento'] ?? null,
            planoAgendado: $row['plano_agendado'] ?? null,
            ultimoAcessoEm: $row['ultimo_acesso_em'] ?? null,
            status: (string) $row['status'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
