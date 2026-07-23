<?php

use Food\Core\Csrf;
use Food\Models\CusteioConfig;

/**
 * @var array $config
 * @var CusteioConfig $custeioConfig
 * @var array{custoIngredientesPorUnidade: float, custoOverheadPorUnidade: float, custoTotal: float, markup: float,
 *   margemPercentual: float, lucro: float, precoIdealBalcao: float, precoIdealWhatsapp: float, precoIdealIfood: float,
 *   precoIdealDelivery: float}|null $resultado
 * @var array{comissao: float, taxaFixa: float, valorLiquido: float, valorPedido: float, distanciaKm: float}|null $resultadoIfood
 * @var array $old
 * @var array<int, string> $errors
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');

/** Le do $old (POST anterior) se existir, senao cai no padrao da loja - assim o simulador "lembra" o que foi digitado. */
$campo = static function (string $nome, float $padrao) use ($old): string {
    if (isset($old[$nome]) && $old[$nome] !== '') {
        return (string) $old[$nome];
    }

    return number_format($padrao, 4, ',', '');
};
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Gestão</p>
            <h1 class="dashboard-title">Precificação Inteligente</h1>
            <p class="dash-page-subtitle">Simule o custo e o preço ideal de uma receita sem salvar nenhum produto.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Simulador de custo e preço ideal</h2>
            <p>Preenche com os valores padrão da loja - ajuste à vontade só pra esta simulação.</p>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/precificacao/simular" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="custo_ingredientes_total">Custo de ingredientes do lote (R$)</label>
                <input type="text" id="custo_ingredientes_total" name="custo_ingredientes_total" value="<?= htmlspecialchars((string) ($old['custo_ingredientes_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" required>
            </div>
            <div class="form-field">
                <label for="rendimento">Rendimento (unidades)</label>
                <input type="number" id="rendimento" name="rendimento" min="1" value="<?= htmlspecialchars((string) ($old['rendimento'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-field">
                <label for="margem_desejada">Margem desejada (%)</label>
                <input type="text" id="margem_desejada" name="margem_desejada" value="<?= $campo('margem_desejada', $custeioConfig->margemDesejadaPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="comissao_ifood">Comissão iFood (%)</label>
                <input type="text" id="comissao_ifood" name="comissao_ifood" value="<?= $campo('comissao_ifood', $custeioConfig->comissaoIfoodPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="taxa_pagamento_online">Taxa pagamento online (%)</label>
                <input type="text" id="taxa_pagamento_online" name="taxa_pagamento_online" value="<?= $campo('taxa_pagamento_online', $custeioConfig->taxaPagamentoOnlinePadrao) ?>">
            </div>

            <div class="form-field" style="flex: 1 1 100%; margin-top: 0.5rem;">
                <label style="font-weight: 600;">Overhead (custo estimado por unidade produzida)</label>
            </div>
            <div class="form-field">
                <label for="valor_energia">Energia</label>
                <input type="text" id="valor_energia" name="valor_energia" value="<?= $campo('valor_energia', $custeioConfig->valorEnergiaPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_gas">Gás</label>
                <input type="text" id="valor_gas" name="valor_gas" value="<?= $campo('valor_gas', $custeioConfig->valorGasPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_agua">Água</label>
                <input type="text" id="valor_agua" name="valor_agua" value="<?= $campo('valor_agua', $custeioConfig->valorAguaPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_embalagem">Embalagem</label>
                <input type="text" id="valor_embalagem" name="valor_embalagem" value="<?= $campo('valor_embalagem', $custeioConfig->valorEmbalagemPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_etiqueta">Etiqueta</label>
                <input type="text" id="valor_etiqueta" name="valor_etiqueta" value="<?= $campo('valor_etiqueta', $custeioConfig->valorEtiquetaPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_mao_obra">Mão de obra</label>
                <input type="text" id="valor_mao_obra" name="valor_mao_obra" value="<?= $campo('valor_mao_obra', $custeioConfig->valorMaoObraPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_taxa_operacional">Taxa operacional</label>
                <input type="text" id="valor_taxa_operacional" name="valor_taxa_operacional" value="<?= $campo('valor_taxa_operacional', $custeioConfig->valorTaxaOperacionalPadrao) ?>">
            </div>
            <div class="form-field">
                <label for="valor_desperdicio">Desperdício</label>
                <input type="text" id="valor_desperdicio" name="valor_desperdicio" value="<?= $campo('valor_desperdicio', $custeioConfig->valorDesperdicioPadrao) ?>">
            </div>

            <div class="form-field" style="align-self: flex-end;">
                <button type="submit" class="btn-k btn-k-grad">Calcular</button>
            </div>
        </form>

        <?php if ($resultado !== null): ?>
            <div class="crud-table-wrapper" style="margin-top: 1.5rem;">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Custo total</th>
                            <th>Markup</th>
                            <th>Margem</th>
                            <th>Lucro</th>
                            <th>Preço ideal balcão</th>
                            <th>Preço ideal WhatsApp</th>
                            <th>Preço ideal iFood</th>
                            <th>Preço ideal delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $moeda($resultado['custoTotal']) ?></td>
                            <td class="text-dim"><?= number_format($resultado['markup'], 2, ',', '.') ?>x</td>
                            <td class="text-dim"><?= number_format($resultado['margemPercentual'], 2, ',', '.') ?>%</td>
                            <td class="text-dim"><?= $moeda($resultado['lucro']) ?></td>
                            <td><strong><?= $moeda($resultado['precoIdealBalcao']) ?></strong></td>
                            <td><?= $moeda($resultado['precoIdealWhatsapp']) ?></td>
                            <td><?= $moeda($resultado['precoIdealIfood']) ?></td>
                            <td><?= $moeda($resultado['precoIdealDelivery']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Taxa iFood Entrega II</h2>
            <p>Simule o valor líquido recebido de um pedido do iFood (comissão + taxa fixa por distância).</p>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/precificacao/simular-ifood" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="valor_pedido">Valor do pedido (R$)</label>
                <input type="text" id="valor_pedido" name="valor_pedido" placeholder="0,00" required>
            </div>
            <div class="form-field">
                <label for="distancia_km">Distância da entrega (km)</label>
                <input type="text" id="distancia_km" name="distancia_km" placeholder="0" required>
            </div>
            <div class="form-field" style="align-self: flex-end;">
                <button type="submit" class="btn-k btn-k-outline">Calcular</button>
            </div>
        </form>

        <?php if ($resultadoIfood !== null): ?>
            <div class="crud-table-wrapper" style="margin-top: 1.5rem;">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Valor do pedido</th>
                            <th>Distância</th>
                            <th>Comissão (12%)</th>
                            <th>Taxa fixa de entrega</th>
                            <th>Valor líquido recebido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $moeda($resultadoIfood['valorPedido']) ?></td>
                            <td class="text-dim"><?= number_format($resultadoIfood['distanciaKm'], 1, ',', '.') ?> km</td>
                            <td class="text-dim">- <?= $moeda($resultadoIfood['comissao']) ?></td>
                            <td class="text-dim">- <?= $moeda($resultadoIfood['taxaFixa']) ?></td>
                            <td><strong><?= $moeda($resultadoIfood['valorLiquido']) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Valores padrão da loja</h2>
            <p>Usados como ponto de partida em toda ficha técnica nova e no simulador acima - editáveis a qualquer momento.</p>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/precificacao/config" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="cfg_margem_desejada">Margem desejada padrão (%)</label>
                <input type="text" id="cfg_margem_desejada" name="margem_desejada" value="<?= number_format($custeioConfig->margemDesejadaPadrao, 2, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_comissao_ifood">Comissão iFood padrão (%)</label>
                <input type="text" id="cfg_comissao_ifood" name="comissao_ifood" value="<?= number_format($custeioConfig->comissaoIfoodPadrao, 2, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_taxa_pagamento_online">Taxa pagamento online padrão (%)</label>
                <input type="text" id="cfg_taxa_pagamento_online" name="taxa_pagamento_online" value="<?= number_format($custeioConfig->taxaPagamentoOnlinePadrao, 2, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_energia">Energia padrão</label>
                <input type="text" id="cfg_valor_energia" name="valor_energia" value="<?= number_format($custeioConfig->valorEnergiaPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_gas">Gás padrão</label>
                <input type="text" id="cfg_valor_gas" name="valor_gas" value="<?= number_format($custeioConfig->valorGasPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_agua">Água padrão</label>
                <input type="text" id="cfg_valor_agua" name="valor_agua" value="<?= number_format($custeioConfig->valorAguaPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_embalagem">Embalagem padrão</label>
                <input type="text" id="cfg_valor_embalagem" name="valor_embalagem" value="<?= number_format($custeioConfig->valorEmbalagemPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_etiqueta">Etiqueta padrão</label>
                <input type="text" id="cfg_valor_etiqueta" name="valor_etiqueta" value="<?= number_format($custeioConfig->valorEtiquetaPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_mao_obra">Mão de obra padrão</label>
                <input type="text" id="cfg_valor_mao_obra" name="valor_mao_obra" value="<?= number_format($custeioConfig->valorMaoObraPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_taxa_operacional">Taxa operacional padrão</label>
                <input type="text" id="cfg_valor_taxa_operacional" name="valor_taxa_operacional" value="<?= number_format($custeioConfig->valorTaxaOperacionalPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field">
                <label for="cfg_valor_desperdicio">Desperdício padrão</label>
                <input type="text" id="cfg_valor_desperdicio" name="valor_desperdicio" value="<?= number_format($custeioConfig->valorDesperdicioPadrao, 4, ',', '') ?>">
            </div>
            <div class="form-field" style="align-self: flex-end;">
                <button type="submit" class="btn-k btn-k-outline">Salvar padrões</button>
            </div>
        </form>
    </div>
</main>
