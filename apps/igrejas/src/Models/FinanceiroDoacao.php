<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Doacao via Pix estatico (chave da propria igreja - ver
 * Igrejas\Core\PixEstatico e DoacaoController). Como nao ha gateway de
 * pagamento envolvido, o Banco Central nao notifica ninguem quando um
 * Pix por chave e pago - o proprio doador confirma manualmente (botao
 * "Ja fiz o Pix"), o que gera o FinanceiroLancamento correspondente.
 */
final class FinanceiroDoacao
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $nomeDoador,
        public readonly ?int $categoriaId,
        public readonly ?string $categoriaNome,
        public readonly float $valor,
        public readonly ?string $mensagem,
        public readonly string $txid,
        public readonly string $status,
        public readonly ?int $lancamentoId,
        public readonly ?string $confirmadaEm,
        public readonly string $createdAt,
    ) {
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT fd.*, fc.nome AS categoria_nome
             FROM financeiro_doacoes fd
             LEFT JOIN financeiro_categorias fc ON fc.id = fd.categoria_id
             WHERE fd.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @param array{nome_doador?: ?string, categoria_id?: ?int, valor: float, mensagem?: ?string} $data
     */
    public static function criar(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_doacoes (nome_doador, categoria_id, valor, mensagem, txid, status, created_at)
             VALUES (:nome_doador, :categoria_id, :valor, :mensagem, :txid, "pendente", NOW())'
        );
        $stmt->execute([
            'nome_doador' => self::nullable($data['nome_doador'] ?? null),
            'categoria_id' => $data['categoria_id'] ?? null,
            'valor' => $data['valor'],
            'mensagem' => self::nullable($data['mensagem'] ?? null),
            'txid' => self::gerarTxid(),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Marca a doacao como confirmada pelo proprio doador e cria o
     * lancamento financeiro correspondente (entrada, forma "pix") - a
     * igreja continua livre pra editar/excluir esse lancamento como
     * qualquer outro caso a conciliacao com o extrato do banco aponte
     * alguma divergencia.
     */
    public static function confirmar(int $id): void
    {
        $doacao = self::find($id);

        if (!$doacao || $doacao->status === 'confirmada') {
            return;
        }

        $descricao = 'Doacao via Pix' . ($doacao->nomeDoador !== null ? ' - ' . $doacao->nomeDoador : '');
        $observacoes = 'Confirmada pelo doador em ' . date('d/m/Y H:i') . ' - sem confirmacao automatica do banco, verifique o extrato.';

        if ($doacao->mensagem !== null) {
            $observacoes .= "\n\nMensagem do doador: " . $doacao->mensagem;
        }

        $lancamentoId = FinanceiroLancamento::create([
            'tipo' => 'entrada',
            'categoria_id' => $doacao->categoriaId,
            'membro_id' => null,
            'descricao' => $descricao,
            'valor' => $doacao->valor,
            'forma_pagamento' => 'pix',
            'data_lancamento' => date('Y-m-d'),
            'observacoes' => $observacoes,
        ]);

        $stmt = Database::connection()->prepare(
            'UPDATE financeiro_doacoes
             SET status = "confirmada", lancamento_id = :lancamento_id, confirmada_em = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['lancamento_id' => $lancamentoId, 'id' => $id]);
    }

    /**
     * Txid do Pix (campo 62/05 do BR Code) - so aceita letras e numeros
     * (exigencia do padrao), curto o bastante pra caber no limite de 25
     * caracteres do campo mesmo com o prefixo.
     */
    private static function gerarTxid(): string
    {
        return 'KDS' . strtoupper(bin2hex(random_bytes(8)));
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nomeDoador: $row['nome_doador'],
            categoriaId: $row['categoria_id'] !== null ? (int) $row['categoria_id'] : null,
            categoriaNome: $row['categoria_nome'] ?? null,
            valor: (float) $row['valor'],
            mensagem: $row['mensagem'],
            txid: (string) $row['txid'],
            status: (string) $row['status'],
            lancamentoId: $row['lancamento_id'] !== null ? (int) $row['lancamento_id'] : null,
            confirmadaEm: $row['confirmada_em'],
            createdAt: (string) $row['created_at'],
        );
    }
}
