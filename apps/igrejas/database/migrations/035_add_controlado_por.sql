-- KADOSYS Igrejas - Migracao 035
-- "Quem esta no comando" da projecao: o operador (painel do dashboard)
-- e o preletor (tablet do pastor) podem controlar o mesmo telao ao
-- mesmo tempo, sem nenhuma coordenacao entre eles ate aqui - um lado
-- podia sobrescrever silenciosamente o que o outro estava exibindo.
-- Esta coluna guarda quem fez a ULTIMA acao que definiu o conteudo
-- principal (biblia/video/logo/pix/imagem/blank), pra que o outro
-- lado saiba que precisa "assumir o comando" antes de agir por cima.

ALTER TABLE projecao_estados
    ADD COLUMN controlado_por ENUM('operador', 'preletor') NULL AFTER modo;
