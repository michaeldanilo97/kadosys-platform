-- Cancelamento self-service da assinatura da plataforma (ver
-- Barbearias\Controllers\ConfiguracaoController::cancelarAssinatura).
-- NULL = assinatura ativa normalmente. Quando preenchido, a barbearia
-- continua com acesso ate proximo_vencimento (ja pago) - depois disso o
-- cron suspender_assinaturas_canceladas.php marca a barbearia como
-- suspensa.
ALTER TABLE barbearias ADD COLUMN cancelado_em DATETIME NULL AFTER status;
