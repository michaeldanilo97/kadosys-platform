-- Ajuste 171: "em tudo que tem no kids, preciso que exija uma ação da
-- criança no sistema para concluir e ganhar xp" - fecha os tipos que
-- ainda liberavam o Concluir sem nenhuma interação real (colorir,
-- desenhar, HQ, história/devocional/versículo ilustrado, plano de
-- leitura, PDF, vídeo/áudio) e adiciona o upload de foto da boa ação
-- como forma de concluir um desafio (ver KidsAppController::concluirDesafio).

ALTER TABLE kids_conteudo_conclusoes
    ADD COLUMN foto_path VARCHAR(255) NULL AFTER conteudo_id;

-- Planos de leitura: converte o texto corrido em checklist real (um
-- checkbox por dia - so libera o Concluir com todos os dias marcados,
-- ver [data-plano-leitura] em kids-interacoes.js).
UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1:</strong> Salmos 23:1 - "O Senhor é o meu pastor, nada me faltará."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2:</strong> Salmos 118:24 - "Este é o dia que o Senhor fez; alegremo-nos e regozijemo-nos nele."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3:</strong> Salmos 46:1 - "Deus é o nosso refúgio e fortaleza."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4:</strong> Salmos 100:5 - "O Senhor é bom; a sua misericórdia dura para sempre."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5:</strong> Salmos 121:2 - "O meu socorro vem do Senhor, que fez os céus e a terra."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 6:</strong> Salmos 34:8 - "Provai e vede que o Senhor é bom."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 7:</strong> Salmos 139:14 - "Eu te louvarei, porque de um modo assombroso e maravilhoso fui formado."</span></label>
</div>' WHERE origem = 'kadosys' AND tipo = 'plano_leitura' AND titulo = '7 dias com os Salmos';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1:</strong> Provérbios 1:7 - "O temor do Senhor é o princípio do conhecimento."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2:</strong> Provérbios 3:5 - "Confie no Senhor de todo o coração."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3:</strong> Provérbios 15:1 - "A resposta mansa desvia o furor."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4:</strong> Provérbios 17:17 - "Em todo o tempo ama o amigo."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5:</strong> Provérbios 18:10 - "O nome do Senhor é uma torre forte."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 6:</strong> Provérbios 22:6 - "Ensina a criança no caminho em que deve andar."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 7:</strong> Provérbios 31:30 - "A mulher que teme ao Senhor, essa será louvada."</span></label>
</div>' WHERE origem = 'kadosys' AND tipo = 'plano_leitura' AND titulo = '7 dias com Provérbios';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1: Amor</strong> - 1 Coríntios 13:4 - "O amor é paciente, o amor é bondoso."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2: Alegria</strong> - Neemias 8:10 - "A alegria do Senhor é a força de vocês."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3: Paz</strong> - João 14:27 - "Deixo-lhes a paz; a minha paz lhes dou."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4: Paciência</strong> - Tiago 1:4 - "Que a perseverança conclua a sua obra."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5: Domínio próprio</strong> - Provérbios 25:28 - "Como cidade derrubada, sem muro, é o homem que não sabe controlar-se."</span></label>
</div>' WHERE origem = 'kadosys' AND tipo = 'plano_leitura' AND titulo = '5 dias sobre o Fruto do Espírito';
