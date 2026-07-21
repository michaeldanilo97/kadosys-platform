-- Segundo lembrete, de "ultima hora" - alem do aviso do dia anterior
-- (agendamentos.lembrete_enviado_em), agora tambem avisa perto do
-- proprio horario (ver cron/enviar_lembretes_proximos.php, pensado pra
-- rodar a cada 20min). Coluna separada porque os dois lembretes sao
-- independentes: um cliente pode receber os dois, cada um com seu
-- proprio controle de "ja enviado".
ALTER TABLE agendamentos ADD COLUMN lembrete_proximo_enviado_em DATETIME NULL AFTER lembrete_enviado_em;
