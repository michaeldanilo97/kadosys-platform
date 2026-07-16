-- Chave Pix propria da barbearia (recebe direto na conta dela, sem
-- gateway nenhum - mesmo padrao ja usado no Igrejas pra doacao, ver
-- Igrejas\Core\PixEstatico) - usada no PDV (concluir atendimento) pra
-- gerar um QR Code do valor exato pro cliente pagar.
ALTER TABLE barbearias
    ADD COLUMN pix_chave VARCHAR(140) NULL AFTER modo_atendimento,
    ADD COLUMN pix_nome_beneficiario VARCHAR(25) NULL AFTER pix_chave,
    ADD COLUMN pix_cidade VARCHAR(15) NULL AFTER pix_nome_beneficiario;
