-- Ajuste 179: mascote nao-IA da Biblioteca Kids (pedido do usuario,
-- Prioridade 6 da lista colada no chat) - a crianca escolhe um bichinho
-- (Leao/Ovelha/Pomba) que aparece como um widget flutuante em toda
-- pagina do modo Kids, reagindo com frases prontas (sem chamada de IA
-- nenhuma) a conquistas/niveis - ver Igrejas\Models\KidsAvatar::
-- catalogoMascotes() e public/assets/js/kids-mascote.js.

ALTER TABLE kids_criancas
    ADD COLUMN avatar_mascote VARCHAR(20) NOT NULL DEFAULT 'leao' AFTER avatar_roupa;
