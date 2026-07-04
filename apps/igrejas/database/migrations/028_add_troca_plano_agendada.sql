-- KADOSYS Igrejas - Migracao 028
-- Upgrade/downgrade de plano respeitando o ciclo ja pago (ver
-- Igrejas\Controllers\AssinaturaController).
--
-- proximo_vencimento: quando o ciclo pago atual termina - usado pra
-- calcular o valor proporcional de um upgrade (cobranca avulsa so da
-- diferenca, nao do plano inteiro de novo) e pra saber quando aplicar
-- um downgrade agendado. Preenchido a cada pagamento confirmado
-- (renovacao Pix ou assinatura de cartao autorizada).
--
-- plano_agendado: downgrade so entra em vigor no fim do ciclo ja pago
-- (sem reembolso, sem tirar acesso no meio do caminho) - fica guardado
-- aqui ate cron/aplicar_trocas_agendadas.php aplicar de fato na data de
-- proximo_vencimento.

ALTER TABLE plataforma_tenants
    ADD COLUMN proximo_vencimento DATE NULL AFTER trial_expira_em,
    ADD COLUMN plano_agendado VARCHAR(20) NULL AFTER proximo_vencimento;
