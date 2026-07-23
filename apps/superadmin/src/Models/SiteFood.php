<?php

declare(strict_types=1);

namespace Superadmin\Models;

use Superadmin\Core\DatabaseFood;

/**
 * Leitura (e exclusao) da tabela restaurantes do KADOSYS Food - banco
 * unico compartilhado, isolamento logico por restaurante_id (ver
 * Food\Models\Restaurante, a fonte original deste schema). Excluir uma
 * linha aqui e suficiente: todo o resto (pedidos, produtos, financeiro
 * etc.) cai em cascata via FK ON DELETE CASCADE.
 */
final class SiteFood
{
    private const SELECT_BASE = 'SELECT id, nome, slug, plano, metodo_pagamento, status,
        trial_expira_em, proximo_vencimento, ultimo_acesso_em, created_at
        FROM restaurantes';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $slug,
        public readonly string $plano,
        public readonly string $metodoPagamento,
        public readonly string $status,
        public readonly ?string $trialExpiraEm,
        public readonly ?string $proximoVencimento,
        public readonly ?string $ultimoAcessoEm,
        public readonly string $criadoEm,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function listarTodas(): array
    {
        $stmt = DatabaseFood::connection()->query(self::SELECT_BASE . ' ORDER BY id DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = DatabaseFood::connection()->prepare(self::SELECT_BASE . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        $stmt = DatabaseFood::connection()->prepare(
            'UPDATE restaurantes SET status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * Libera/estende manualmente o acesso de um restaurante. Sempre
     * reativa (status = 'ativo') e desfaz um cancelamento self-service
     * pendente (cancelado_em), senao o cron
     * suspender_assinaturas_canceladas.php suspenderia de novo assim
     * que o ciclo pago encerrar - e so entao ataca o campo que de fato
     * bloqueia cada metodo de pagamento: trial_expira_em (trial) ou a
     * fatura Pix mais recente (pix). Quem paga por cartao so depende do
     * proprio status (ver AuthMiddleware), entao reativar ja basta.
     */
    public static function estenderAcesso(int $id, int $dias): string
    {
        $restaurante = self::find($id);

        if ($restaurante === null) {
            return 'Restaurante nao encontrado.';
        }

        $agora = new \DateTimeImmutable();

        $stmt = DatabaseFood::connection()->prepare(
            "UPDATE restaurantes SET status = 'ativo', cancelado_em = NULL WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);

        if ($restaurante->metodoPagamento === 'trial') {
            $base = $restaurante->trialExpiraEm !== null ? new \DateTimeImmutable($restaurante->trialExpiraEm) : $agora;
            $novaData = ($base > $agora ? $base : $agora)->modify("+{$dias} days");

            $stmt = DatabaseFood::connection()->prepare(
                'UPDATE restaurantes SET trial_expira_em = :data WHERE id = :id'
            );
            $stmt->execute(['data' => $novaData->format('Y-m-d H:i:s'), 'id' => $id]);

            return 'Teste gratis estendido ate ' . $novaData->format('d/m/Y') . '.';
        }

        if ($restaurante->metodoPagamento === 'pix') {
            $stmt = DatabaseFood::connection()->prepare(
                'SELECT id, vencimento FROM restaurante_faturas WHERE restaurante_id = :restaurante_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['restaurante_id' => $id]);
            $fatura = $stmt->fetch();

            if ($fatura === false) {
                return 'Site ativado. Nenhuma fatura Pix encontrada pra estender o vencimento.';
            }

            $vencimentoAtual = new \DateTimeImmutable($fatura['vencimento']);
            $novaData = ($vencimentoAtual > $agora ? $vencimentoAtual : $agora)->modify("+{$dias} days");

            $stmt = DatabaseFood::connection()->prepare(
                "UPDATE restaurante_faturas SET status = 'paga', pago_em = NOW(), vencimento = :vencimento WHERE id = :id"
            );
            $stmt->execute(['vencimento' => $novaData->format('Y-m-d H:i:s'), 'id' => $fatura['id']]);

            $stmt = DatabaseFood::connection()->prepare(
                'UPDATE restaurantes SET proximo_vencimento = :data WHERE id = :id'
            );
            $stmt->execute(['data' => $novaData->format('Y-m-d'), 'id' => $id]);

            return 'Fatura Pix marcada como paga - proximo vencimento em ' . $novaData->format('d/m/Y') . '.';
        }

        return 'Site ativado com sucesso.';
    }

    /**
     * Situacao REAL de acesso do restaurante - diferente do campo
     * `status` sozinho, reproduz a mesma decisao que
     * Food\Core\Middleware\AuthMiddleware usa pra bloquear o dashboard
     * (trial vencido ou fatura Pix vencida). Cartao so depende do
     * proprio `status` (o restaurante nao guarda historico de status de
     * assinatura de cartao como o Igrejas faz).
     *
     * @return array{bloqueado: bool, motivo: string, vencimento: ?string}
     */
    public function acesso(): array
    {
        if ($this->status === 'suspenso') {
            return ['bloqueado' => true, 'motivo' => 'Suspenso', 'vencimento' => $this->proximoVencimento];
        }

        if ($this->status === 'pendente') {
            return ['bloqueado' => true, 'motivo' => 'Pagamento inicial pendente', 'vencimento' => null];
        }

        if ($this->metodoPagamento === 'trial') {
            $vencido = $this->trialExpiraEm !== null && new \DateTimeImmutable() > new \DateTimeImmutable($this->trialExpiraEm);

            return [
                'bloqueado' => $vencido,
                'motivo' => $vencido ? 'Teste gratis vencido' : 'Em teste gratis',
                'vencimento' => $this->trialExpiraEm,
            ];
        }

        if ($this->metodoPagamento === 'pix') {
            $stmt = DatabaseFood::connection()->prepare(
                'SELECT status, vencimento FROM restaurante_faturas WHERE restaurante_id = :restaurante_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['restaurante_id' => $this->id]);
            $fatura = $stmt->fetch();

            if ($fatura === false) {
                return ['bloqueado' => false, 'motivo' => 'Sem fatura Pix ainda', 'vencimento' => null];
            }

            $vencida = $fatura['status'] === 'expirada'
                || ($fatura['status'] === 'pendente' && new \DateTimeImmutable() > new \DateTimeImmutable($fatura['vencimento']));

            return [
                'bloqueado' => $vencida,
                'motivo' => $vencida ? 'Fatura Pix vencida' : ($fatura['status'] === 'paga' ? 'Pix em dia' : 'Pix pendente'),
                'vencimento' => $fatura['vencimento'],
            ];
        }

        return ['bloqueado' => false, 'motivo' => 'Cartao (assinatura MP ativa)', 'vencimento' => $this->proximoVencimento];
    }

    /**
     * A propria linha - o cascade das FKs cuida do resto (ver
     * install.sql do apps/food).
     */
    public static function excluir(int $id): void
    {
        $stmt = DatabaseFood::connection()->prepare('DELETE FROM restaurantes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: $row['nome'],
            slug: $row['slug'],
            plano: $row['plano'],
            metodoPagamento: $row['metodo_pagamento'],
            status: $row['status'],
            trialExpiraEm: $row['trial_expira_em'],
            proximoVencimento: $row['proximo_vencimento'],
            ultimoAcessoEm: $row['ultimo_acesso_em'],
            criadoEm: $row['created_at'],
        );
    }
}
