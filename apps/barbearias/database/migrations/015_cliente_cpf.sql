-- CPF do cliente (opcional, junto com telefone/e-mail) - usado como mais
-- um identificador possivel pra vincular o cliente aos seus dados
-- (pontos de fidelidade, assinaturas, historico), ja que nem todo
-- cliente tem telefone ou e-mail preenchido no cadastro manual da
-- equipe. O cadastro exige pelo menos um dos tres (ver
-- Barbearias\Controllers\ClienteController::validar).
ALTER TABLE clientes
    ADD COLUMN cpf VARCHAR(14) NULL AFTER email;
