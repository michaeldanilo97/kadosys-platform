<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;
use Food\Core\IfoodTaxaEntrega;

/**
 * Motor de agregacao central do dashboard e dos relatorios - unico
 * lugar com as queries de KPI, pra Dashboard/RelatorioController
 * nunca duplicarem a mesma soma com uma pequena diferenca entre si
 * (mesma filosofia de "formula unica" ja usada em Food\Core\Custeio).
 *
 * Pedidos com split payment (Fase 6, PDV) geram VARIAS linhas em
 * financeiro_lancamentos pro MESMO pedido_id (uma por forma de
 * pagamento) - por isso toda query que precisa cruzar
 * financeiro_lancamentos com pedido_itens/produtos passa primeiro por
 * uma subquery `DISTINCT pedido_id`, pra nunca contar o mesmo item
 * vendido mais de uma vez.
 */
final class Relatorio
{
    /**
     * @return array{receita: float, despesa: float, custoDireto: float, lucroBruto: float, lucroLiquido: float}
     */
    public static function resumoPeriodo(int $restauranteId, string $inicio, string $fim): array
    {
        $resumoFinanceiro = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $inicio, $fim);
        $despesaContas = self::despesaContasPagasNoPeriodo($restauranteId, $inicio, $fim);
        $custoDireto = self::custoDiretoVendido($restauranteId, $inicio, $fim);

        $receita = $resumoFinanceiro['receitas'];
        $despesa = $resumoFinanceiro['despesas'] + $despesaContas;
        $lucroBruto = $receita - $custoDireto;
        $lucroLiquido = $lucroBruto - $despesa;

        return [
            'receita' => $receita,
            'despesa' => $despesa,
            'custoDireto' => $custoDireto,
            'lucroBruto' => $lucroBruto,
            'lucroLiquido' => $lucroLiquido,
        ];
    }

    /**
     * Contagem de pedidos ainda ativos (nao cancelados) por status -
     * usado no card "Pedidos" do dashboard, snapshot atual (nao filtra
     * por periodo).
     *
     * @return array<string, int>
     */
    public static function pedidosPorStatus(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT status, COUNT(*) AS total FROM pedidos
             WHERE restaurante_id = :restaurante_id AND status != 'cancelado'
             GROUP BY status"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        $resultado = [];

        foreach ($stmt->fetchAll() as $row) {
            $resultado[(string) $row['status']] = (int) $row['total'];
        }

        return $resultado;
    }

    /**
     * @return array{quantidade: int, ticketMedio: float}
     */
    public static function vendasPeriodo(int $restauranteId, string $inicio, string $fim): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT pedido_id) AS quantidade, COALESCE(SUM(valor), 0) AS total
             FROM financeiro_lancamentos
             WHERE restaurante_id = :restaurante_id AND tipo = :tipo AND pedido_id IS NOT NULL
               AND data_lancamento BETWEEN :inicio AND :fim'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'tipo' => FinanceiroLancamento::TIPO_RECEITA,
            'inicio' => $inicio,
            'fim' => $fim,
        ]);
        $row = $stmt->fetch();

        $quantidade = (int) ($row['quantidade'] ?? 0);
        $total = (float) ($row['total'] ?? 0);

        return [
            'quantidade' => $quantidade,
            'ticketMedio' => $quantidade > 0 ? round($total / $quantidade, 2) : 0.0,
        ];
    }

    /**
     * @return array<int, array{produtoId: int, nome: string, quantidade: float, receita: float}>
     */
    public static function produtosMaisVendidos(int $restauranteId, string $inicio, string $fim, int $limite = 10): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT p.id AS produto_id, p.nome, SUM(pi.quantidade) AS quantidade, SUM(pi.subtotal) AS receita
             FROM (
                 SELECT DISTINCT pedido_id FROM financeiro_lancamentos
                 WHERE restaurante_id = :restaurante_id AND tipo = 'receita' AND pedido_id IS NOT NULL
                   AND data_lancamento BETWEEN :inicio AND :fim
             ) pc
             INNER JOIN pedido_itens pi ON pi.pedido_id = pc.pedido_id
             INNER JOIN produtos p ON p.id = pi.produto_id
             WHERE p.restaurante_id = :restaurante_id_produtos
             GROUP BY p.id, p.nome
             ORDER BY quantidade DESC
             LIMIT {$limite}"
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'restaurante_id_produtos' => $restauranteId,
            'inicio' => $inicio,
            'fim' => $fim,
        ]);

        return array_map(
            static fn (array $row): array => [
                'produtoId' => (int) $row['produto_id'],
                'nome' => (string) $row['nome'],
                'quantidade' => (float) $row['quantidade'],
                'receita' => (float) $row['receita'],
            ],
            $stmt->fetchAll(),
        );
    }

    /**
     * @return array<int, array{produtoId: int, nome: string, quantidade: float, receita: float, custo: float, lucro: float}>
     */
    public static function produtosMaisLucrativos(int $restauranteId, string $inicio, string $fim, int $limite = 10): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT p.id AS produto_id, p.nome, SUM(pi.quantidade) AS quantidade, SUM(pi.subtotal) AS receita,
                SUM(pi.quantidade * p.custo_total) AS custo
             FROM (
                 SELECT DISTINCT pedido_id FROM financeiro_lancamentos
                 WHERE restaurante_id = :restaurante_id AND tipo = 'receita' AND pedido_id IS NOT NULL
                   AND data_lancamento BETWEEN :inicio AND :fim
             ) pc
             INNER JOIN pedido_itens pi ON pi.pedido_id = pc.pedido_id
             INNER JOIN produtos p ON p.id = pi.produto_id
             WHERE p.restaurante_id = :restaurante_id_produtos
             GROUP BY p.id, p.nome
             ORDER BY (SUM(pi.subtotal) - SUM(pi.quantidade * p.custo_total)) DESC
             LIMIT {$limite}"
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'restaurante_id_produtos' => $restauranteId,
            'inicio' => $inicio,
            'fim' => $fim,
        ]);

        return array_map(
            static function (array $row): array {
                $receita = (float) $row['receita'];
                $custo = (float) $row['custo'];

                return [
                    'produtoId' => (int) $row['produto_id'],
                    'nome' => (string) $row['nome'],
                    'quantidade' => (float) $row['quantidade'],
                    'receita' => $receita,
                    'custo' => $custo,
                    'lucro' => $receita - $custo,
                ];
            },
            $stmt->fetchAll(),
        );
    }

    public static function clientesAtivos(int $restauranteId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM clientes WHERE restaurante_id = :restaurante_id AND ativo = 1'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return (int) $stmt->fetchColumn();
    }

    public static function clientesNovos(int $restauranteId, string $inicio, string $fim): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM clientes
             WHERE restaurante_id = :restaurante_id AND DATE(created_at) BETWEEN :inicio AND :fim'
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'inicio' => $inicio, 'fim' => $fim]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Comissao iFood ESTIMADA do periodo - so o percentual (12%) sobre
     * o valor dos pedidos de origem iFood confirmados. A taxa fixa de
     * entrega por distancia (Food\Core\IfoodTaxaEntrega) nao entra
     * aqui porque o pedido nao guarda a distancia da entrega - fica
     * como uma aproximacao, avisada na tela.
     *
     * @return array{receitaIfood: float, comissaoEstimada: float}
     */
    public static function comissaoIfoodPeriodo(int $restauranteId, string $inicio, string $fim): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(fl.valor), 0) AS receita
             FROM financeiro_lancamentos fl
             INNER JOIN pedidos p ON p.id = fl.pedido_id
             WHERE fl.restaurante_id = :restaurante_id AND fl.tipo = 'receita' AND fl.pedido_id IS NOT NULL
               AND p.origem = 'ifood_manual'
               AND fl.data_lancamento BETWEEN :inicio AND :fim"
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'inicio' => $inicio, 'fim' => $fim]);
        $receitaIfood = (float) $stmt->fetchColumn();

        return [
            'receitaIfood' => $receitaIfood,
            'comissaoEstimada' => round($receitaIfood * (IfoodTaxaEntrega::COMISSAO_PERCENTUAL / 100), 2),
        ];
    }

    /**
     * Fluxo de caixa dos ultimos N meses (incluindo o atual) - usado no
     * grafico de linha do dashboard/relatorios.
     *
     * @return array<int, array{mes: string, receitas: float, despesas: float, saldo: float}>
     */
    public static function fluxoCaixaMensal(int $restauranteId, int $meses = 6): array
    {
        $hoje = new \DateTimeImmutable('today');
        $resultado = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $referencia = $hoje->modify("-{$i} months");
            $inicio = $referencia->format('Y-m-01');
            $fim = $referencia->format('Y-m-t');

            $resumo = FinanceiroLancamento::resumoDoPeriodo($restauranteId, $inicio, $fim);
            $despesaContas = self::despesaContasPagasNoPeriodo($restauranteId, $inicio, $fim);
            $despesaTotal = $resumo['despesas'] + $despesaContas;

            $resultado[] = [
                'mes' => $referencia->format('m/Y'),
                'receitas' => $resumo['receitas'],
                'despesas' => $despesaTotal,
                'saldo' => $resumo['receitas'] - $despesaTotal,
            ];
        }

        return $resultado;
    }

    /**
     * Custo direto (soma de quantidade x custo_total por unidade,
     * usando o cache de custo do produto) de tudo o que foi vendido
     * (pedidos confirmados) no periodo - usado pra chegar no lucro
     * bruto. Mesma subquery DISTINCT pedido_id do resto da classe, pra
     * nao multiplicar o custo em vendas com split payment.
     */
    private static function custoDiretoVendido(int $restauranteId, string $inicio, string $fim): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(pi.quantidade * p.custo_total), 0)
             FROM (
                 SELECT DISTINCT pedido_id FROM financeiro_lancamentos
                 WHERE restaurante_id = :restaurante_id AND tipo = 'receita' AND pedido_id IS NOT NULL
                   AND data_lancamento BETWEEN :inicio AND :fim
             ) pc
             INNER JOIN pedido_itens pi ON pi.pedido_id = pc.pedido_id
             INNER JOIN produtos p ON p.id = pi.produto_id"
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'inicio' => $inicio, 'fim' => $fim]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Soma das contas_a_pagar marcadas como pagas DENTRO do periodo
     * (por pago_em, nao vencimento) - complementa as despesas manuais
     * (sangria etc) ja contadas em financeiro_lancamentos, sem contar
     * a mesma despesa duas vezes (contas a pagar nunca geram um
     * financeiro_lancamento automatico).
     */
    private static function despesaContasPagasNoPeriodo(int $restauranteId, string $inicio, string $fim): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM contas_a_pagar
             WHERE restaurante_id = :restaurante_id AND status = 'paga' AND pago_em BETWEEN :inicio AND :fim"
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'inicio' => $inicio, 'fim' => $fim]);

        return (float) $stmt->fetchColumn();
    }
}
