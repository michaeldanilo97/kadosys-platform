-- Ajuste 186: mais conteudos pro Kids reforcando os tipos com menos itens
-- (pedido do usuario, continuacao de "mais jogos, mais animacoes, mais
-- sons") - 2 HQs, 2 slides, 2 planos de leitura e 2 atividades novas
-- (reaproveitando os 3 padroes ja existentes de atividade: completar/
-- ligar), todos usando so a infraestrutura HTML/CSS/JS ja existente
-- (kids-interacoes.js ja reconhece .kids-hq, [data-slides] e
-- [data-plano-leitura] genericamente - nenhum motor novo precisou ser
-- criado).

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('hq', 'kadosys', 'Sansão, o Homem Forte', 'Em quadrinhos: a força e os erros de Sansão, e a lição que fica.', 'Velho Testamento', 'Força', 'Juízes', 'Sansão', 'facil', 6, 11, 10, 15, 8,
     '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">👶💪</div>
<p class="kids-hq-legenda">Sansão nasceu com uma missão especial de Deus: libertar seu povo dos filisteus.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">💇</div>
<p class="kids-hq-legenda">Seu cabelo nunca podia ser cortado - era o sinal da força que Deus lhe dava.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">🦁👊</div>
<p class="kids-hq-legenda">Com a força de Deus, ele enfrentou um leão feroz com as próprias mãos!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">🏛️💪</div>
<p class="kids-hq-legenda">Derrubou os portões pesados de uma cidade inimiga sozinho, carregando-os nas costas.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-vermelho);">
<div class="kids-hq-cena">💔😔</div>
<p class="kids-hq-legenda">Mas Sansão contou seu segredo pra pessoa errada, e perdeu sua força.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">🙏✨</div>
<p class="kids-hq-legenda">Numa última oração sincera, pediu perdão e forças a Deus mais uma vez.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">💛</div>
<p class="kids-hq-legenda">Aprendemos com Sansão que a verdadeira força vem de confiar em Deus, não de nós mesmos.</p>
</div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('hq', 'kadosys', 'A Coragem da Rainha Ester', 'Em quadrinhos: a coragem de Ester para salvar seu povo.', 'Velho Testamento', 'Coragem', 'Ester', 'Ester', 'medio', 6, 12, 10, 15, 8,
     '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">👸</div>
<p class="kids-hq-legenda">Ester era uma jovem judia que se tornou rainha da Pérsia, sem que ninguém soubesse de seu povo.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-vermelho);">
<div class="kids-hq-cena">😈📜</div>
<p class="kids-hq-legenda">Um homem mau chamado Hamã planejou destruir todo o povo judeu no reino.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">😟💌</div>
<p class="kids-hq-legenda">Seu primo Mardoqueu pediu que Ester falasse com o rei, mesmo sendo perigoso.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">🙏😨</div>
<p class="kids-hq-legenda">Ester pediu que todos jejuassem e orassem com ela antes de agir.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">👑🚪</div>
<p class="kids-hq-legenda">Corajosamente, ela foi até o rei sem ser chamada, algo que podia custar sua vida.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">🎉👑</div>
<p class="kids-hq-legenda">O rei a recebeu bem, e Ester revelou o plano malvado de Hamã.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">✨🙌</div>
<p class="kids-hq-legenda">Deus usou a coragem de Ester pra salvar todo o seu povo!</p>
</div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('slide', 'kadosys', 'Os Milagres de Jesus', 'Uma apresentação conhecendo os milagres mais famosos de Jesus.', 'Novo Testamento', 'Milagres', NULL, 'Jesus', 'medio', 7, 12, 8, 15, 8,
     '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">🐟🍞</span>
<h3>Multiplicação dos Pães</h3>
<p>Jesus alimentou mais de 5 mil pessoas com apenas 5 pães e 2 peixes!</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌊</span>
<h3>Andar sobre as Águas</h3>
<p>Jesus caminhou sobre o mar da Galileia durante uma tempestade.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">👁️</span>
<h3>Cura do Cego</h3>
<p>Jesus devolveu a visão a um homem que nunca tinha enxergado.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🚶</span>
<h3>Cura do Paralítico</h3>
<p>Um homem que não conseguia andar há anos foi curado e levantou-se andando.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🍷</span>
<h3>Água em Vinho</h3>
<p>No casamento em Caná, Jesus transformou água em vinho, seu primeiro milagre.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">⛈️</span>
<h3>Acalmar a Tempestade</h3>
<p>Com uma palavra, Jesus fez o vento e as ondas ficarem calmos.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🙌</span>
<h3>Ressurreição de Lázaro</h3>
<p>Jesus chamou Lázaro, que já tinha morrido há 4 dias, de volta à vida!</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🧴</span>
<h3>Cura dos Dez Leprosos</h3>
<p>Jesus curou 10 homens com uma doença grave chamada lepra.</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 8</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('slide', 'kadosys', 'Os Frutos do Espírito', 'Uma apresentação conhecendo cada um dos 9 frutos do Espírito.', 'Novo Testamento', 'Fruto do Espírito', 'Gálatas', NULL, 'facil', 6, 11, 8, 15, 8,
     '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">❤️</span>
<h3>Amor</h3>
<p>Se importar e cuidar dos outros, do jeito que Deus se importa com a gente.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">😊</span>
<h3>Alegria</h3>
<p>Uma felicidade que vem de dentro, mesmo nos dias difíceis.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🕊️</span>
<h3>Paz</h3>
<p>Um coração tranquilo, sem brigas nem preocupação demais.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">⏳</span>
<h3>Paciência</h3>
<p>Esperar sem ficar bravo, mesmo quando as coisas demoram.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🤝</span>
<h3>Amabilidade</h3>
<p>Ser gentil e ajudar quem precisa, sem esperar nada em troca.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🎁</span>
<h3>Bondade</h3>
<p>Compartilhar o que você tem com quem precisa, de coração aberto.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌟</span>
<h3>Fidelidade</h3>
<p>Ser alguém em quem os outros podem confiar sempre.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🙏</span>
<h3>Mansidão</h3>
<p>Ter um jeito calmo e gentil de tratar as pessoas.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💪</span>
<h3>Domínio Próprio</h3>
<p>Saber controlar suas vontades e não fazer bagunça com suas emoções.</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 9</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('plano_leitura', 'kadosys', '7 dias com os Evangelhos', 'Um versículo por dia conhecendo os ensinamentos de Jesus.', 'Novo Testamento', 'Ensinamentos de Jesus', NULL, 'Jesus', 'medio', 7, 12, NULL, 20, 10,
     '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1:</strong> Mateus 5:16 - "Assim brilhe a vossa luz diante dos homens."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2:</strong> Mateus 6:33 - "Buscai primeiro o Reino de Deus."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3:</strong> Marcos 10:14 - "Deixai vir a mim os pequeninos."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4:</strong> Lucas 6:31 - "Fazei aos outros o que quereis que vos façam."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5:</strong> João 3:16 - "Deus amou o mundo de tal maneira que deu o seu Filho."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 6:</strong> João 13:34 - "Amai-vos uns aos outros."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 7:</strong> João 15:5 - "Eu sou a videira, vós, os ramos."</span></label>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('plano_leitura', 'kadosys', '5 dias de Gratidão', 'Um versículo por dia para aprender a agradecer a Deus.', 'Valores', 'Gratidão', NULL, NULL, 'facil', 6, 11, NULL, 20, 10,
     '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1:</strong> Salmos 100:4 - "Entrai por suas portas com ações de graças."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2:</strong> 1 Tessalonicenses 5:18 - "Em tudo dai graças."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3:</strong> Colossenses 3:15 - "E sede agradecidos."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4:</strong> Salmos 136:1 - "Louvai ao Senhor, porque ele é bom."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5:</strong> Filipenses 4:6 - "Em tudo, pela oração e súplicas, com ações de graças."</span></label>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('atividade', 'kadosys', 'Complete o Versículo 2', 'Preencha a palavra que falta em mais 4 versículos.', 'Geral', 'Memorização', NULL, NULL, 'medio', 6, 11, 8, 15, 8,
     '<div class="kids-completar" data-completar>
<p class="kids-quiz-progresso" data-completar-progresso>0 de 4 certas</p>
<div class="kids-completar-item" data-completar-item data-resposta="salvação">
<p class="kids-completar-frase">1. "O Senhor é a minha luz e a minha <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">."</p>
<p class="kids-completar-ref">Salmos 27:1</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="corajoso">
<p class="kids-completar-frase">2. "Sê forte e <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">, porque o Senhor teu Deus está contigo."</p>
<p class="kids-completar-ref">Josué 1:9</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="luz">
<p class="kids-completar-frase">3. "Vós sois a <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false"> do mundo."</p>
<p class="kids-completar-ref">Mateus 5:14</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="remédio">
<p class="kids-completar-frase">4. "O coração alegre é bom <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">."</p>
<p class="kids-completar-ref">Provérbios 17:22</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
</div>
<script>
(function () {
    // O formulario de "Concluir" fica depois deste bloco no HTML (fora
    // do texto_conteudo), entao ainda nao existe no DOM quando este
    // <script> roda - so pode ser lido apos o documento terminar de
    // parsear (mesma correcao aplicada ao quiz, ver Ajuste 155).
    document.addEventListener(''DOMContentLoaded'', iniciar);

    function normalizar(texto) {
        return texto
            .normalize(''NFD'')
            .replace(/[\\u0300-\\u036f]/g, '''')
            .trim()
            .toLowerCase();
    }

    function iniciar() {
    document.querySelectorAll(''[data-completar]'').forEach(function (bloco) {
        var itens = bloco.querySelectorAll(''[data-completar-item]'');
        var progressoEl = bloco.querySelector(''[data-completar-progresso]'');
        var formConcluir = document.querySelector(''[data-quiz-form-concluir]'');
        var total = itens.length;
        var certas = 0;

        function atualizarProgresso() {
            if (progressoEl) {
                progressoEl.textContent = certas + '' de '' + total + '' certas'';
            }

            if (certas >= total && formConcluir) {
                formConcluir.hidden = false;
            }
        }

        itens.forEach(function (item) {
            var input = item.querySelector(''[data-completar-input]'');
            var botao = item.querySelector(''[data-completar-conferir]'');
            var feedback = item.querySelector(''[data-completar-feedback]'');
            var esperado = normalizar(item.getAttribute(''data-resposta''));
            var resolvido = false;

            function conferir() {
                if (resolvido) {
                    return;
                }

                var certo = normalizar(input.value) === esperado;
                feedback.hidden = false;
                feedback.classList.remove(''correta'', ''errada'');

                if (certo) {
                    feedback.classList.add(''correta'');
                    feedback.textContent = ''✅ Isso mesmo! A resposta é "'' + item.getAttribute(''data-resposta'') + ''".'';
                    input.disabled = true;
                    botao.disabled = true;
                    resolvido = true;
                    certas++;
                    atualizarProgresso();
                } else {
                    feedback.classList.add(''errada'');
                    feedback.textContent = ''❌ Quase! Tente de novo.'';
                }
            }

            botao.addEventListener(''click'', conferir);
            input.addEventListener(''keydown'', function (event) {
                if (event.key === ''Enter'') {
                    event.preventDefault();
                    conferir();
                }
            });
        });

        atualizarProgresso();
    });
    }
})();
</script>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('atividade', 'kadosys', 'Ligue o Profeta à sua Missão', 'Uma atividade para relembrar a missão de cada profeta.', 'Velho Testamento', 'Profetas', NULL, NULL, 'medio', 6, 11, 8, 15, 8,
     '<div class="kids-ligar" data-ligar>
<p class="kids-quiz-progresso" data-ligar-progresso>0 de 5 pares certos</p>
<div class="kids-ligar-colunas">
<div class="kids-ligar-coluna">
<button type="button" class="kids-ligar-item" data-ligar-item data-par="elias" data-lado="esq">Elias</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="eliseu" data-lado="esq">Eliseu</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="jonas" data-lado="esq">Jonas</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="isaias" data-lado="esq">Isaías</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="ezequiel" data-lado="esq">Ezequiel</button>
</div>
<div class="kids-ligar-coluna">
<button type="button" class="kids-ligar-item" data-ligar-item data-par="ezequiel" data-lado="dir">viu a visão do vale de ossos secos</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="isaias" data-lado="dir">profetizou sobre o nascimento de Jesus</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="elias" data-lado="dir">confrontou os profetas de Baal</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="jonas" data-lado="dir">pregou arrependimento em Nínive</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="eliseu" data-lado="dir">multiplicou o azeite da viúva</button>
</div>
</div>
</div>
<script>
(function () {
    // Mesma correcao do quiz (ver Ajuste 155): o form de "Concluir" vem
    // depois no HTML, entao so da pra ler ele depois do DOMContentLoaded.
    document.addEventListener(''DOMContentLoaded'', iniciar);

    function iniciar() {
    document.querySelectorAll(''[data-ligar]'').forEach(function (bloco) {
        var itens = bloco.querySelectorAll(''[data-ligar-item]'');
        var progressoEl = bloco.querySelector(''[data-ligar-progresso]'');
        var formConcluir = document.querySelector(''[data-quiz-form-concluir]'');
        var total = bloco.querySelectorAll(''[data-lado="esq"]'').length;
        var certos = 0;
        var selecionado = null;

        function atualizarProgresso() {
            if (progressoEl) {
                progressoEl.textContent = certos + '' de '' + total + '' pares certos'';
            }

            if (certos >= total && formConcluir) {
                formConcluir.hidden = false;
            }
        }

        function limparSelecao() {
            itens.forEach(function (item) {
                if (!item.classList.contains(''par-certo'')) {
                    item.classList.remove(''selecionado'');
                }
            });
            selecionado = null;
        }

        itens.forEach(function (item) {
            item.addEventListener(''click'', function () {
                if (item.classList.contains(''par-certo'')) {
                    return;
                }

                if (!selecionado) {
                    limparSelecao();
                    selecionado = item;
                    item.classList.add(''selecionado'');

                    return;
                }

                if (selecionado === item) {
                    limparSelecao();

                    return;
                }

                if (selecionado.getAttribute(''data-lado'') === item.getAttribute(''data-lado'')) {
                    limparSelecao();
                    selecionado = item;
                    item.classList.add(''selecionado'');

                    return;
                }

                if (selecionado.getAttribute(''data-par'') === item.getAttribute(''data-par'')) {
                    selecionado.classList.add(''par-certo'');
                    selecionado.classList.remove(''selecionado'');
                    item.classList.add(''par-certo'');
                    certos++;
                    atualizarProgresso();
                    selecionado = null;
                } else {
                    var errados = [selecionado, item];
                    errados.forEach(function (el) { el.classList.add(''par-errado''); });
                    setTimeout(function () {
                        errados.forEach(function (el) { el.classList.remove(''par-errado''); });
                    }, 350);
                    limparSelecao();
                }
            });
        });

        atualizarProgresso();
    });
    }
})();
</script>',
     NULL, 'publicado', NOW());
