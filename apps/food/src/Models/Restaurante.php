<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Model de Restaurante (o "tenant" do multi-tenant logico).
 *
 * Mesmo padrao ja usado no KADOSYS Barbearias/Academias: o restaurante e
 * so mais uma linha dentro do MESMO banco compartilhado - "ativar" e um
 * simples UPDATE nesta mesma tabela, sem nenhuma infraestrutura por meio
 * (diferente do KADOSYS Igrejas, que provisiona banco+subdominio por
 * tenant). A propria linha nasce com status 'pendente' (Pix/cartao
 * aguardando confirmacao) ou 'ativo' (trial, sem cobranca imediata) -
 * ver Food\Controllers\CadastroController.
 */
final class Restaurante
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_ATIVA = 'ativo';
    public const STATUS_SUSPENSA = 'suspenso';

    private const SELECT_COLUNAS = 'id, nome, slug, telefone, documento_tipo, documento, razao_social,
        logo_path, cor_primaria,
        plano, metodo_pagamento, mp_preapproval_id, trial_expira_em, proximo_vencimento, plano_agendado,
        ultimo_acesso_em, status, cancelado_em,
        pix_chave, pix_nome_beneficiario, pix_cidade, created_at';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $slug,
        public readonly ?string $telefone,
        public readonly string $documentoTipo,
        public readonly string $documento,
        public readonly ?string $razaoSocial,
        public readonly ?string $logoPath,
        public readonly ?string $corPrimaria,
        public readonly string $plano,
        public readonly string $metodoPagamento,
        public readonly ?string $mpPreapprovalId,
        public readonly ?string $trialExpiraEm,
        public readonly ?string $proximoVencimento,
        public readonly ?string $planoAgendado,
        public readonly ?string $ultimoAcessoEm,
        public readonly string $status,
        public readonly ?string $canceladoEm = null,
        public readonly ?string $pixChave = null,
        public readonly ?string $pixNomeBeneficiario = null,
        public readonly ?string $pixCidade = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    public function pixConfigurado(): bool
    {
        return $this->pixChave !== null && $this->pixChave !== '';
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM restaurantes WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findBySlugAtiva(string $slug): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM restaurantes WHERE slug = :slug AND status = 'ativo' LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function buscarPorMpPreapprovalId(string $preapprovalId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM restaurantes WHERE mp_preapproval_id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $preapprovalId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function slugDisponivel(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM restaurantes WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() === false;
    }

    /**
     * Verifica se esse CPF/CNPJ ja usou o teste gratis antes - impede
     * criar uma conta nova em trial so trocando de e-mail/restaurante.
     */
    public static function documentoJaUsouTrial(string $documento): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM restaurantes WHERE documento = :documento AND trial_expira_em IS NOT NULL LIMIT 1'
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
            'INSERT INTO restaurantes
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
            'UPDATE restaurantes SET nome = :nome, telefone = :telefone WHERE id = :id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'telefone' => $telefone !== null && trim($telefone) !== '' ? trim($telefone) : null,
            'id' => $id,
        ]);
    }

    /**
     * Dados fiscais INFORMATIVOS (razao social/CPF-CNPJ) - so aparecem
     * em comprovantes/relatorios internos, sem emissao de NF-e (isso
     * exigiria certificado digital + integracao com a SEFAZ, fora de
     * escopo desta entrega).
     */
    public static function atualizarDadosFiscais(int $id, string $documentoTipo, string $documento, ?string $razaoSocial): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET documento_tipo = :documento_tipo, documento = :documento, razao_social = :razao_social
             WHERE id = :id'
        );
        $stmt->execute([
            'documento_tipo' => in_array($documentoTipo, ['cpf', 'cnpj'], true) ? $documentoTipo : 'cpf',
            'documento' => preg_replace('/\D/', '', $documento) ?? '',
            'razao_social' => $razaoSocial !== null && trim($razaoSocial) !== '' ? trim($razaoSocial) : null,
            'id' => $id,
        ]);
    }

    /** NULL usa a cor padrao do app (laranja/vermelho, tema food). */
    public static function atualizarCorPrimaria(int $id, ?string $corPrimaria): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET cor_primaria = :cor_primaria WHERE id = :id'
        );
        $stmt->execute(['cor_primaria' => $corPrimaria, 'id' => $id]);
    }

    public static function atualizarLogo(int $id, ?string $logoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET logo_path = :logo_path WHERE id = :id'
        );
        $stmt->execute(['logo_path' => $logoPath, 'id' => $id]);
    }

    public static function marcarAtiva(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE restaurantes SET status = 'ativo' WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function marcarSuspensa(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE restaurantes SET status = 'suspenso' WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Cancelamento self-service da assinatura (ver
     * Food\Controllers\ConfiguracaoController::cancelarAssinatura) - o
     * acesso continua liberado ate proximo_vencimento (ja pago), so
     * depois disso o cron suspender_assinaturas_canceladas.php bloqueia
     * de fato.
     */
    public static function marcarCancelamento(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET cancelado_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Reativacao (self-service, enquanto ainda dentro do ciclo pago) OU
     * automatica, via WebhookController, quando um restaurante ja
     * suspenso faz um novo pagamento pela tela /dashboard/assinatura.
     */
    public static function cancelarCancelamento(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET cancelado_em = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Restaurantes que cancelaram a assinatura e cujo ciclo ja pago
     * terminou - usado pelo cron diario
     * suspender_assinaturas_canceladas.php pra so entao bloquear o
     * acesso de fato.
     *
     * @return array<int, self>
     */
    public static function canceladosComCicloEncerrado(): array
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . " WHERE cancelado_em IS NOT NULL AND status = 'ativo'
                AND proximo_vencimento IS NOT NULL AND proximo_vencimento < CURDATE()"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Troca o plano do restaurante imediatamente - sem proporcionalidade
     * nem agendamento pro proximo ciclo. A proxima cobranca (Pix ou
     * cartao) ja sai no valor do novo plano.
     */
    public static function atualizarPlano(int $id, string $plano): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET plano = :plano WHERE id = :id'
        );
        $stmt->execute(['plano' => $plano, 'id' => $id]);
    }

    public static function atualizarPix(int $id, ?string $chave, ?string $nomeBeneficiario, ?string $cidade): void
    {
        $limpar = static fn (?string $valor): ?string => $valor !== null && trim($valor) !== '' ? trim($valor) : null;

        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET pix_chave = :chave, pix_nome_beneficiario = :nome, pix_cidade = :cidade WHERE id = :id'
        );
        $stmt->execute([
            'chave' => $limpar($chave),
            'nome' => $limpar($nomeBeneficiario),
            'cidade' => $limpar($cidade),
            'id' => $id,
        ]);
    }

    public static function atualizarMpPreapprovalId(int $id, string $preapprovalId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET mp_preapproval_id = :preapproval_id WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'preapproval_id' => $preapprovalId]);
    }

    public static function atualizarProximoVencimento(int $id, \DateTimeImmutable $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET proximo_vencimento = :data WHERE id = :id'
        );
        $stmt->execute(['data' => $data->format('Y-m-d'), 'id' => $id]);
    }

    public static function atualizarUltimoAcesso(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE restaurantes SET ultimo_acesso_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Restaurantes ativos que pagam por Pix (precisam de fatura nova
     * gerada a cada ciclo - ver cron/gerar_faturas_pix.php). Quem paga
     * por cartao renova sozinho, direto no Mercado Pago via preapproval.
     *
     * @return array<int, self>
     */
    public static function ativosComPagamentoPix(): array
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . " WHERE status = 'ativo' AND metodo_pagamento = 'pix'"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function selectBase(): string
    {
        return 'SELECT ' . self::SELECT_COLUNAS . ' FROM restaurantes';
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
            logoPath: $row['logo_path'] ?? null,
            corPrimaria: $row['cor_primaria'] ?? null,
            plano: (string) $row['plano'],
            metodoPagamento: (string) $row['metodo_pagamento'],
            mpPreapprovalId: $row['mp_preapproval_id'] ?? null,
            trialExpiraEm: $row['trial_expira_em'] ?? null,
            proximoVencimento: $row['proximo_vencimento'] ?? null,
            planoAgendado: $row['plano_agendado'] ?? null,
            ultimoAcessoEm: $row['ultimo_acesso_em'] ?? null,
            status: (string) $row['status'],
            canceladoEm: $row['cancelado_em'] ?? null,
            pixChave: $row['pix_chave'] ?? null,
            pixNomeBeneficiario: $row['pix_nome_beneficiario'] ?? null,
            pixCidade: $row['pix_cidade'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
