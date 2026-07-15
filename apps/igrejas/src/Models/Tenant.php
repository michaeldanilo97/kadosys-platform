<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Diretorio central de igrejas provisionadas automaticamente (ver
 * Igrejas\Controllers\CadastroController). Cada linha representa um
 * banco de dados proprio, isolado, criado via API do cPanel - nao ha
 * multi-tenant de verdade (dados compartilhados num banco so); isso e
 * so o "indice" usado pra resolver, a partir do subdominio, qual banco
 * uma requisicao deve usar.
 */
final class Tenant
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $nomeIgreja,
        public readonly string $documentoTipo,
        public readonly string $documento,
        public readonly ?string $razaoSocial,
        public readonly string $plano,
        public readonly string $metodoPagamento,
        public readonly ?string $trialExpiraEm,
        public readonly ?string $proximoVencimento,
        public readonly ?string $planoAgendado,
        public readonly string $subdominio,
        public readonly string $dbName,
        public readonly string $dbUser,
        public readonly string $dbPassword,
        public readonly string $status,
        public readonly string $criadoEm,
        public readonly ?string $ultimoAcessoEm = null,
    ) {
    }

    public static function slugDisponivel(string $slug): bool
    {
        $stmt = Database::central()->prepare('SELECT 1 FROM plataforma_tenants WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() === false;
    }

    /**
     * Verifica se esse CPF/CNPJ ja usou o teste gratis antes - impede
     * criar uma conta nova em trial so trocando de e-mail/igreja. So
     * conta igrejas que de fato chegaram a ter um trial_expira_em
     * preenchido (nao importa se ja venceu ou nao).
     */
    public static function documentoJaUsouTrial(string $documento): bool
    {
        $stmt = Database::central()->prepare(
            'SELECT 1 FROM plataforma_tenants WHERE documento = :documento AND trial_expira_em IS NOT NULL LIMIT 1'
        );
        $stmt->execute(['documento' => $documento]);

        return $stmt->fetch() !== false;
    }

    public static function criar(
        string $slug,
        string $nomeIgreja,
        string $documentoTipo,
        string $documento,
        ?string $razaoSocial,
        string $plano,
        string $metodoPagamento,
        ?\DateTimeImmutable $trialExpiraEm,
        string $subdominio,
        string $dbName,
        string $dbUser,
        string $dbPassword,
    ): int {
        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_tenants
                (slug, nome_igreja, documento_tipo, documento, razao_social, plano, metodo_pagamento,
                 trial_expira_em, subdominio, db_name, db_user, db_password, status)
             VALUES
                (:slug, :nome_igreja, :documento_tipo, :documento, :razao_social, :plano, :metodo_pagamento,
                 :trial_expira_em, :subdominio, :db_name, :db_user, :db_password, "provisionando")'
        );
        $stmt->execute([
            'slug' => $slug,
            'nome_igreja' => $nomeIgreja,
            'documento_tipo' => $documentoTipo,
            'documento' => $documento,
            'razao_social' => $razaoSocial,
            'plano' => $plano,
            'metodo_pagamento' => $metodoPagamento,
            'trial_expira_em' => $trialExpiraEm?->format('Y-m-d H:i:s'),
            'subdominio' => $subdominio,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPassword,
        ]);

        return (int) Database::central()->lastInsertId();
    }

    public static function marcarAtivo(int $id): void
    {
        $stmt = Database::central()->prepare('UPDATE plataforma_tenants SET status = "ativo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Mantem o "plano" do registro central sincronizado com
     * configuracoes_igreja.plano (que mora no banco isolado de cada
     * tenant) sempre que uma troca de plano e confirmada - ver
     * AssinaturaController::processarFaturaPix(). Sem isso,
     * cron/gerar_faturas_pix.php geraria a proxima fatura com o plano
     * antigo, ja que ele so enxerga esse registro central.
     */
    public static function atualizarPlano(int $id, string $plano): void
    {
        $stmt = Database::central()->prepare('UPDATE plataforma_tenants SET plano = :plano WHERE id = :id');
        $stmt->execute(['plano' => $plano, 'id' => $id]);
    }

    /**
     * Marca ate quando o ciclo pago atual vale - usado pra calcular o
     * valor proporcional de um upgrade e pra saber quando aplicar um
     * downgrade agendado (ver AssinaturaController e
     * cron/aplicar_trocas_agendadas.php). Atualizado a cada pagamento
     * confirmado (fatura Pix paga ou assinatura de cartao autorizada).
     */
    public static function atualizarProximoVencimento(int $id, \DateTimeImmutable $data): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_tenants SET proximo_vencimento = :data WHERE id = :id'
        );
        $stmt->execute(['data' => $data->format('Y-m-d'), 'id' => $id]);
    }

    /**
     * Agenda um downgrade pra so entrar em vigor no fim do ciclo ja
     * pago (proximo_vencimento) - a igreja mantem o plano atual (ja
     * pago) ate la, sem reembolso. Ver cron/aplicar_trocas_agendadas.php
     * pra quando isso e de fato aplicado.
     */
    public static function agendarTrocaPlano(int $id, string $planoAgendado): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_tenants SET plano_agendado = :plano_agendado WHERE id = :id'
        );
        $stmt->execute(['plano_agendado' => $planoAgendado, 'id' => $id]);
    }

    /**
     * Cancela uma troca de plano agendada (ex.: a igreja mudou de ideia
     * antes do downgrade entrar em vigor, ou pediu um upgrade que
     * substitui o downgrade agendado).
     */
    public static function cancelarTrocaAgendada(int $id): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_tenants SET plano_agendado = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Tenants ativos com uma troca de plano agendada cujo ciclo pago ja
     * venceu - usado por cron/aplicar_trocas_agendadas.php.
     *
     * @return array<int, self>
     */
    public static function comTrocaAgendadaVencida(): array
    {
        $stmt = Database::central()->prepare(
            self::SELECT_BASE . " WHERE status = 'ativo' AND plano_agendado IS NOT NULL
                AND proximo_vencimento IS NOT NULL AND proximo_vencimento <= CURDATE()"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Usado quando um tenant ja ativo troca de metodo de pagamento pela
     * tela de Configuracoes (ver AssinaturaController::iniciar) - ex.:
     * escolhe pagar por Pix ao trocar de plano, mesmo ja tendo sido
     * provisionado originalmente por cartao.
     */
    public static function atualizarMetodoPagamento(int $id, string $metodoPagamento): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_tenants SET metodo_pagamento = :metodo_pagamento WHERE id = :id'
        );
        $stmt->execute(['metodo_pagamento' => $metodoPagamento, 'id' => $id]);
    }

    /**
     * Registra o momento do ultimo login com sucesso de QUALQUER usuario
     * dessa igreja - chamado em AuthController::login(). So um timestamp
     * central (nao por usuario individual), suficiente pra dar ao dono da
     * plataforma uma nocao de quais igrejas estao ativas de verdade (ver
     * painel /plataforma/igrejas).
     */
    public static function atualizarUltimoAcesso(int $id): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_tenants SET ultimo_acesso_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function buscarPorSubdominio(string $subdominio): ?self
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' WHERE subdominio = :subdominio LIMIT 1');
        $stmt->execute(['subdominio' => $subdominio]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    public static function buscarPorId(int $id): ?self
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    /**
     * @return array<int, self>
     */
    public static function ativosComPagamentoPix(): array
    {
        $stmt = Database::central()->prepare(
            self::SELECT_BASE . " WHERE status = 'ativo' AND metodo_pagamento = 'pix'"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Todos os tenants ativos, independente do metodo de pagamento -
     * usado por crons que precisam varrer toda igreja provisionada
     * automaticamente (ex.: e-mail de aniversario).
     *
     * @return array<int, self>
     */
    public static function ativas(): array
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . " WHERE status = 'ativo'");
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Entre as igrejas ativas, quais tem um usuario com esse e-mail
     * cadastrado no banco isolado delas - usado pela tela de login do
     * dominio raiz (fora de qualquer subdominio) pra descobrir em qual
     * igreja a pessoa deve entrar, ja que nao existe uma tabela central
     * de usuarios (cada `users` mora isolada no banco de cada igreja,
     * ver AuthController::localizarIgrejas()). Uma falha ao conectar
     * num banco especifico (ex.: igreja com banco fora do ar) so pula
     * aquele tenant, sem derrubar a busca inteira.
     *
     * @return array<int, self>
     */
    public static function ativasComEmailCadastrado(string $email): array
    {
        $encontradas = [];

        foreach (self::ativas() as $tenant) {
            try {
                $pdo = Database::conexaoAvulsa($tenant->dbName, $tenant->dbUser, $tenant->dbPassword);
                $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $email]);

                if ($stmt->fetch() !== false) {
                    $encontradas[] = $tenant;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $encontradas;
    }

    /**
     * Todas as igrejas provisionadas, mais recentes primeiro - usada
     * pelo painel administrativo da plataforma (ver PlataformaController).
     *
     * @return array<int, self>
     */
    public static function listarTodas(): array
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' ORDER BY id DESC');
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Remove o registro central da igreja - chamado so depois que
     * Igrejas\Core\Desprovisionador ja tentou excluir os recursos dela
     * no cPanel (banco, usuario, subdominio). Libera o slug/subdominio
     * pra ser reutilizado por outro cadastro no futuro.
     */
    public static function excluir(int $id): void
    {
        $stmt = Database::central()->prepare('DELETE FROM plataforma_tenants WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private const SELECT_BASE = 'SELECT id, slug, nome_igreja, documento_tipo, documento, razao_social, plano,
            metodo_pagamento, trial_expira_em, proximo_vencimento, plano_agendado, subdominio, db_name, db_user,
            db_password, status, created_at, ultimo_acesso_em
        FROM plataforma_tenants';

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            slug: $row['slug'],
            nomeIgreja: $row['nome_igreja'],
            documentoTipo: $row['documento_tipo'],
            documento: $row['documento'],
            razaoSocial: $row['razao_social'],
            plano: $row['plano'],
            metodoPagamento: $row['metodo_pagamento'],
            trialExpiraEm: $row['trial_expira_em'],
            proximoVencimento: $row['proximo_vencimento'],
            planoAgendado: $row['plano_agendado'],
            subdominio: $row['subdominio'],
            dbName: $row['db_name'],
            dbUser: $row['db_user'],
            dbPassword: $row['db_password'],
            status: $row['status'],
            criadoEm: $row['created_at'],
            ultimoAcessoEm: $row['ultimo_acesso_em'] ?? null,
        );
    }
}
