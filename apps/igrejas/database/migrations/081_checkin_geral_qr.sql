-- Ajuste 189: Check-in geral por QR fixo - complementa o check-in do
-- Kids (que ja existe, operado pela equipe) com um QR fixo na entrada
-- da igreja pra qualquer membro confirmar presenca no culto do dia
-- sozinho, pelo proprio celular, sem precisar de login (a congregacao
-- nao tem conta/senha, so os Usuarios da equipe tem). O QR fica
-- guardado em configuracoes_igreja (linha unica) por ser fixo -
-- independente de qual culto esta rolando, ele sempre resolve pro(s)
-- culto(s) agendado(s) para o dia atual (ver Igrejas\Models\Culto::deHoje()).

ALTER TABLE configuracoes_igreja
    ADD COLUMN checkin_qr_token VARCHAR(40) NULL AFTER cadastro_membros_habilitado;
