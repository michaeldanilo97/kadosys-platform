<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Custeio;
use Food\Core\IfoodTaxaEntrega;
use Food\Core\Session;
use Food\Models\CusteioConfig;
use Food\Models\Restaurante;
use Food\Models\User;

/**
 * Precificacao Inteligente: simulador avulso (nao salva produto nenhum)
 * que reaproveita o MESMO motor de calculo (Food\Core\Custeio) usado
 * por Produto::recalcularCusto() - garantindo que o numero mostrado
 * aqui seja sempre identico ao que o produto de verdade usaria com os
 * mesmos parametros. Tambem expõe a taxa iFood Entrega II
 * (Food\Core\IfoodTaxaEntrega) como uma calculadora separada, pra
 * simular o valor liquido recebido de um pedido do iFood.
 */
final class PrecificacaoController extends Controller
{
    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $custeioConfig = CusteioConfig::obterOuCriar($restauranteId);

        echo $this->view('dashboard.precificacao.index', [
            'pageTitle' => 'Precificação Inteligente - KADOSYS Food',
            'activeMenu' => 'precificacao',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'custeioConfig' => $custeioConfig,
            'resultado' => null,
            'resultadoIfood' => null,
            'old' => [],
            'errors' => [],
            'success' => Session::flash('precificacao_success'),
        ], 'dashboard');
    }

    /**
     * Simula o custo/preco ideal sem salvar nada - so calcula e
     * renderiza a mesma tela de novo com o resultado preenchido.
     */
    public function simular(): void
    {
        $restauranteId = $this->restauranteId();
        $custeioConfig = CusteioConfig::obterOuCriar($restauranteId);

        $dados = $this->request->only([
            'custo_ingredientes_total', 'rendimento', 'margem_desejada', 'comissao_ifood', 'taxa_pagamento_online',
            'valor_energia', 'valor_gas', 'valor_agua', 'valor_embalagem', 'valor_etiqueta', 'valor_mao_obra',
            'valor_taxa_operacional', 'valor_desperdicio',
        ]);

        $errors = [];
        $custoIngredientesTotal = $this->paraFloat((string) ($dados['custo_ingredientes_total'] ?? ''));

        if ($custoIngredientesTotal < 0) {
            $errors[] = 'Informe o custo de ingredientes do lote.';
        }

        $rendimentoInformado = (string) ($dados['rendimento'] ?? '');
        $rendimento = ctype_digit($rendimentoInformado) ? (int) $rendimentoInformado : 0;

        if ($rendimento < 1) {
            $errors[] = 'Informe o rendimento (quantas unidades a receita rende).';
        }

        $resultado = null;

        if ($errors === []) {
            $overhead = [
                'energia' => $this->paraFloat((string) ($dados['valor_energia'] ?? '')),
                'gas' => $this->paraFloat((string) ($dados['valor_gas'] ?? '')),
                'agua' => $this->paraFloat((string) ($dados['valor_agua'] ?? '')),
                'embalagem' => $this->paraFloat((string) ($dados['valor_embalagem'] ?? '')),
                'etiqueta' => $this->paraFloat((string) ($dados['valor_etiqueta'] ?? '')),
                'mao_obra' => $this->paraFloat((string) ($dados['valor_mao_obra'] ?? '')),
                'taxa_operacional' => $this->paraFloat((string) ($dados['valor_taxa_operacional'] ?? '')),
                'desperdicio' => $this->paraFloat((string) ($dados['valor_desperdicio'] ?? '')),
            ];

            $resultado = Custeio::calcular(
                $custoIngredientesTotal,
                $rendimento,
                $overhead,
                $this->paraFloat((string) ($dados['margem_desejada'] ?? '')),
                $this->paraFloat((string) ($dados['comissao_ifood'] ?? '')),
                $this->paraFloat((string) ($dados['taxa_pagamento_online'] ?? '')),
            );
        }

        echo $this->view('dashboard.precificacao.index', [
            'pageTitle' => 'Precificação Inteligente - KADOSYS Food',
            'activeMenu' => 'precificacao',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'custeioConfig' => $custeioConfig,
            'resultado' => $resultado,
            'resultadoIfood' => null,
            'old' => $dados,
            'errors' => $errors,
            'success' => null,
        ], 'dashboard');
    }

    /**
     * Calculadora separada da taxa iFood Entrega II (comissao + taxa
     * fixa por distancia) - simula o valor liquido recebido de um
     * pedido, sem relacao com produto/ficha tecnica nenhuma.
     */
    public function simularIfood(): void
    {
        $restauranteId = $this->restauranteId();
        $custeioConfig = CusteioConfig::obterOuCriar($restauranteId);

        $valorPedido = $this->paraFloat((string) $this->request->input('valor_pedido', ''));
        $distanciaKm = $this->paraFloat((string) $this->request->input('distancia_km', ''));

        $resultadoIfood = IfoodTaxaEntrega::calcular($valorPedido, $distanciaKm) + [
            'valorPedido' => $valorPedido,
            'distanciaKm' => $distanciaKm,
        ];

        echo $this->view('dashboard.precificacao.index', [
            'pageTitle' => 'Precificação Inteligente - KADOSYS Food',
            'activeMenu' => 'precificacao',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'custeioConfig' => $custeioConfig,
            'resultado' => null,
            'resultadoIfood' => $resultadoIfood,
            'old' => [],
            'errors' => [],
            'success' => null,
        ], 'dashboard');
    }

    public function configSalvar(): void
    {
        $restauranteId = $this->restauranteId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/precificacao');
        }

        $dados = $this->request->only([
            'valor_energia', 'valor_gas', 'valor_agua', 'valor_embalagem', 'valor_etiqueta', 'valor_mao_obra',
            'valor_taxa_operacional', 'valor_desperdicio', 'margem_desejada', 'comissao_ifood', 'taxa_pagamento_online',
        ]);

        CusteioConfig::atualizar(
            $restauranteId,
            $this->paraFloat((string) ($dados['valor_energia'] ?? '')),
            $this->paraFloat((string) ($dados['valor_gas'] ?? '')),
            $this->paraFloat((string) ($dados['valor_agua'] ?? '')),
            $this->paraFloat((string) ($dados['valor_embalagem'] ?? '')),
            $this->paraFloat((string) ($dados['valor_etiqueta'] ?? '')),
            $this->paraFloat((string) ($dados['valor_mao_obra'] ?? '')),
            $this->paraFloat((string) ($dados['valor_taxa_operacional'] ?? '')),
            $this->paraFloat((string) ($dados['valor_desperdicio'] ?? '')),
            $this->paraFloat((string) ($dados['margem_desejada'] ?? '')),
            $this->paraFloat((string) ($dados['comissao_ifood'] ?? '')),
            $this->paraFloat((string) ($dados['taxa_pagamento_online'] ?? '')),
        );

        Session::flash('precificacao_success', 'Valores padrão de custeio atualizados com sucesso.');
        $this->redirect('/dashboard/precificacao');
    }

    private function paraFloat(string $valor): float
    {
        $normalizado = str_replace(',', '.', $valor);

        return is_numeric($normalizado) ? (float) $normalizado : 0.0;
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function restauranteId(): int
    {
        return $this->usuario()?->restauranteId ?? 0;
    }
}
