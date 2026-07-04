-- KADOSYS Igrejas - Migracao 029
-- Distingue, numa fatura Pix, se e uma renovacao normal (estende o
-- ciclo/proximo_vencimento por +1 mes quando paga) ou uma cobranca
-- avulsa de upgrade proporcional (so cobre a diferenca dos dias que
-- faltam no ciclo atual - nao estende nada, ver
-- Igrejas\Controllers\AssinaturaController::iniciarUpgradeProporcional()
-- e processarFaturaPix()).

ALTER TABLE plataforma_faturas
    ADD COLUMN tipo ENUM('renovacao', 'upgrade_proporcional') NOT NULL DEFAULT 'renovacao' AFTER plano;
