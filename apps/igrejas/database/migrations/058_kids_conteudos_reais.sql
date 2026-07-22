-- KADOSYS Igrejas - Migracao 058
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Substitui os conteudos oficiais KADOSYS que ate aqui eram so
-- "casca" (metadados cadastrados, mas sem nenhum conteudo de verdade -
-- so um texto interno tipo "Adicione o arquivo em Kids > Conteudos >
-- Editar", visivel pra crianca abrir e nao ter nada real pra fazer) por
-- conteudo de verdade, sem exigir nenhum upload manual:
--   - colorir (7 itens): desenho em SVG interativo (clica na parte,
--     escolhe a cor), embutido direto no texto_conteudo;
--   - jogo (3 itens com placeholder puro): 2 jogos da memoria (temas
--     diferentes) e 1 trivia sequencial, HTML/JS embutido;
--   - jogo (1 item hibrido - "Bingo dos Frutos do Espirito"): so
--     removida a frase de instrucao interna que vazava antes da
--     atividade real (que ja funcionava sem tela, em familia);
--   - slide (2 itens): apresentacao navegavel, HTML/JS embutido;
--   - hq (3 itens): quadrinhos em paineis, HTML puro;
--   - pdf (3 itens): agora aponta pro arquivo real, versionado em
--     apps/igrejas/public/assets/kids/pdfs/ (asset estatico do proprio
--     app - nao e upload por igreja).
-- Os 8 itens de video/audio que so tinham o mesmo texto de placeholder
-- foram removidos do catalogo oficial (sem link real de video/audio
-- licenciado pra usar) - ver DEPLOY_LOG.md Ajuste 147 pro raciocinio
-- completo.
UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 50,260 A 150,150 0 0 1 350,260 L 320,260 A 120,120 0 0 0 80,260 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 80,260 A 120,120 0 0 1 320,260 L 290,260 A 90,90 0 0 0 110,260 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 110,260 A 90,90 0 0 1 290,260 L 260,260 A 60,60 0 0 0 140,260 Z"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 40,278 Q 70,268 100,278 T 160,278 T 220,278 T 280,278 T 330,278"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 110,230 L 290,230 L 260,262 L 140,262 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="170" y="188" width="60" height="42" rx="8"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 163,190 L 200,163 L 237,190 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="200" cy="210" r="10"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="315" cy="130" rx="24" ry="16"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 296,126 L 268,108 L 300,142 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="338" cy="122" r="9"/>
<circle cx="335" cy="120" r="2" fill="#3A2E5C"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Noé e os Animais na Arca';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="330" cy="60" r="28"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 330,20 L 330,8 M 362,32 L 372,24 M 374,60 L 386,60 M 362,88 L 372,96 M 298,32 L 288,24"/>
<path fill="none" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 55,270 L 55,110 Q 55,88 78,88 Q 100,88 100,110"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 40,280 L 320,280"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="190" cy="205" rx="72" ry="46"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="268" cy="192" r="30"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="252" cy="164" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="286" cy="164" r="11"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="140" y="240" width="14" height="30" rx="5"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="175" y="245" width="14" height="30" rx="5"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="210" y="245" width="14" height="30" rx="5"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="240" y="240" width="14" height="30" rx="5"/>
<circle cx="278" cy="188" r="2.5" fill="#3A2E5C"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Jesus, o Bom Pastor';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 200,55 L 214,100 L 260,100 L 224,127 L 238,172 L 200,145 L 162,172 L 176,127 L 140,100 L 186,100 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="4" stroke-linejoin="round" d="M 305,55 L 310,70 L 325,75 L 310,80 L 305,95 L 300,80 L 285,75 L 300,70 Z"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 30,280 L 370,280"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 148,262 L 200,208 L 252,262 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="158" y="262" width="84" height="42"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="190" y="282" width="20" height="22"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'A Estrela de Belém';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 30,280 L 370,280"/>
<line x1="90" y1="215" x2="130" y2="215" stroke="#3A2E5C" stroke-width="8" stroke-linecap="round"/>
<line x1="270" y1="215" x2="310" y2="215" stroke="#3A2E5C" stroke-width="8" stroke-linecap="round"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 140,158 Q 108,120 150,108 Q 162,130 150,158 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 260,158 Q 292,120 250,108 Q 238,130 250,158 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="118" y="152" width="164" height="22" rx="6"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="130" y="172" width="140" height="92" rx="8"/>
<line x1="130" y1="205" x2="270" y2="205" stroke="#3A2E5C" stroke-width="4"/>
<line x1="130" y1="235" x2="270" y2="235" stroke="#3A2E5C" stroke-width="4"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'A Arca da Aliança';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 200,225 C 160,188 92,150 92,107 C 92,72 120,52 150,52 C 175,52 195,66 200,90 C 205,66 225,52 250,52 C 280,52 308,72 308,107 C 308,150 240,188 200,225 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="190" y="90" width="20" height="88" rx="4"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="160" y="114" width="80" height="20" rx="4"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="62" cy="245" r="27"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="338" cy="245" r="27"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="200" cy="270" r="27"/>
<circle cx="53" cy="240" r="2.4" fill="#3A2E5C"/><circle cx="71" cy="240" r="2.4" fill="#3A2E5C"/>
<circle cx="329" cy="240" r="2.4" fill="#3A2E5C"/><circle cx="347" cy="240" r="2.4" fill="#3A2E5C"/>
<circle cx="191" cy="265" r="2.4" fill="#3A2E5C"/><circle cx="209" cy="265" r="2.4" fill="#3A2E5C"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Jesus abençoa as crianças';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 30,282 L 370,282"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 150,55 L 250,55 L 250,140 Q 250,205 150,232 Q 50,205 50,140 L 50,55 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="150" cy="140" r="26"/>
<line x1="330" y1="55" x2="330" y2="85" stroke="#3A2E5C" stroke-width="6" stroke-linecap="round"/>
<line x1="330" y1="85" x2="298" y2="135" stroke="#3A2E5C" stroke-width="6" stroke-linecap="round"/>
<line x1="330" y1="85" x2="362" y2="135" stroke="#3A2E5C" stroke-width="6" stroke-linecap="round"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="330" cy="150" rx="15" ry="9"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="70" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="105" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="140" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="175" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="210" cy="262" r="11"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Davi e Golias';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 20,268 Q 50,258 80,268 T 140,268 T 200,268 T 260,268 T 320,268 T 380,268"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="210" cy="175" rx="145" ry="72"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 68,175 L 24,133 L 42,175 L 24,217 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="230" cy="93" r="9"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="247" cy="76" r="7"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="259" cy="60" r="5"/>
<circle cx="302" cy="150" r="6" fill="#3A2E5C"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 322,168 Q 335,175 322,182"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="205" cy="178" r="40"/>
<circle cx="205" cy="164" r="7" fill="none" stroke="#3A2E5C" stroke-width="3"/>
<path fill="none" stroke="#3A2E5C" stroke-width="3" stroke-linecap="round" d="M 205,171 L 205,196 M 205,177 L 191,163 M 205,177 L 219,163"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Jonas e a Baleia';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-memoria>
<p class="kids-jogo-status" data-memoria-status>Encontre os pares de animais da arca! 🐾</p>
<div class="kids-memoria-grade" data-memoria-grade>
<button type="button" class="kids-memoria-carta" data-emoji="🐘">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦒">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦁">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐯">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐻">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐵">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦓">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦌">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐘">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦒">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦁">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐯">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐻">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐵">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦓">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦌">❓</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-memoria]'').forEach(function (jogo) {
        var grade = jogo.querySelector(''[data-memoria-grade]'');
        var status = jogo.querySelector(''[data-memoria-status]'');
        var cartas = Array.prototype.slice.call(grade.children);

        for (var i = cartas.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = cartas[i]; cartas[i] = cartas[j]; cartas[j] = tmp;
        }
        cartas.forEach(function (c) { grade.appendChild(c); });

        var virada = null;
        var travado = false;
        var pares = 0;
        var totalPares = cartas.length / 2;

        cartas.forEach(function (carta) {
            carta.addEventListener(''click'', function () {
                if (travado || carta.classList.contains(''virada'') || carta.classList.contains(''encontrada'')) {
                    return;
                }

                carta.classList.add(''virada'');
                carta.textContent = carta.getAttribute(''data-emoji'');

                if (!virada) {
                    virada = carta;
                    return;
                }

                travado = true;

                if (virada.getAttribute(''data-emoji'') === carta.getAttribute(''data-emoji'')) {
                    virada.classList.add(''encontrada'');
                    carta.classList.add(''encontrada'');
                    pares++;
                    virada = null;
                    travado = false;

                    if (pares === totalPares) {
                        status.textContent = ''Você encontrou todos os pares! 🎉'';
                    }
                } else {
                    setTimeout(function () {
                        virada.classList.remove(''virada'');
                        virada.textContent = ''❓'';
                        carta.classList.remove(''virada'');
                        carta.textContent = ''❓'';
                        virada = null;
                        travado = false;
                    }, 700);
                }
            });
        });
    });
})();
</script>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Monte a Arca de Noé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-corrida" data-corrida>
<div class="kids-corrida-cabecalho">
<span>🏁 Sua trilha da fé</span>
<span class="kids-corrida-estrelas" data-corrida-estrelas>⭐ 0/8</span>
</div>
<div class="kids-quiz-pergunta">
<p>1. Quantos discípulos Jesus escolheu?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">12</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">7</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">20</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">3</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>2. Quem escreveu muitos Salmos?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Davi</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Golias</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Faraó</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Herodes</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>3. Em que cidade Jesus nasceu?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Belém</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Nazaré</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Jerusalém</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Roma</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>4. Quem foi engolido por um grande peixe?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Jonas</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Pedro</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Paulo</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Elias</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>5. Quantos dias Deus levou pra criar o mundo?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">6</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">3</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">40</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">10</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>6. Quem atravessou o Mar Vermelho com o povo de Israel?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Moisés</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Josué</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Davi</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Sansão</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>7. Qual é o primeiro livro da Bíblia?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Gênesis</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Êxodo</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Salmos</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Mateus</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>8. Quem traiu Jesus por 30 moedas de prata?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Judas</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Pedro</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Tomé</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">João</button>
</div>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-corrida]'').forEach(function (corrida) {
        var contador = corrida.querySelector(''[data-corrida-estrelas]'');
        var grupos = corrida.querySelectorAll(''[data-quiz-alternativas]'');
        var total = grupos.length;
        var acertos = 0;

        grupos.forEach(function (grupo) {
            grupo.addEventListener(''click'', function (event) {
                var escolhida = event.target.closest(''.kids-quiz-alternativa'');

                if (!escolhida || grupo.classList.contains(''respondida'')) {
                    return;
                }

                grupo.classList.add(''respondida'');
                var acertou = escolhida.getAttribute(''data-correta'') === ''1'';

                grupo.querySelectorAll(''.kids-quiz-alternativa'').forEach(function (botao) {
                    botao.disabled = true;

                    if (botao.getAttribute(''data-correta'') === ''1'') {
                        botao.classList.add(''correta'');
                    } else if (botao === escolhida) {
                        botao.classList.add(''errada'');
                    }
                });

                if (acertou) {
                    acertos++;

                    if (contador) {
                        contador.textContent = ''⭐ '' + acertos + ''/'' + total;
                    }
                }
            });
        });
    });
})();
</script>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Corrida da Fé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-memoria>
<p class="kids-jogo-status" data-memoria-status>Encontre os pares de símbolos da fé! ✨</p>
<div class="kids-memoria-grade" data-memoria-grade>
<button type="button" class="kids-memoria-carta" data-emoji="📖">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="✝️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🕊️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="⭐">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🙏">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="👑">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🌈">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐑">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="📖">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="✝️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🕊️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="⭐">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🙏">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="👑">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🌈">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐑">❓</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-memoria]'').forEach(function (jogo) {
        var grade = jogo.querySelector(''[data-memoria-grade]'');
        var status = jogo.querySelector(''[data-memoria-status]'');
        var cartas = Array.prototype.slice.call(grade.children);

        for (var i = cartas.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = cartas[i]; cartas[i] = cartas[j]; cartas[j] = tmp;
        }
        cartas.forEach(function (c) { grade.appendChild(c); });

        var virada = null;
        var travado = false;
        var pares = 0;
        var totalPares = cartas.length / 2;

        cartas.forEach(function (carta) {
            carta.addEventListener(''click'', function () {
                if (travado || carta.classList.contains(''virada'') || carta.classList.contains(''encontrada'')) {
                    return;
                }

                carta.classList.add(''virada'');
                carta.textContent = carta.getAttribute(''data-emoji'');

                if (!virada) {
                    virada = carta;
                    return;
                }

                travado = true;

                if (virada.getAttribute(''data-emoji'') === carta.getAttribute(''data-emoji'')) {
                    virada.classList.add(''encontrada'');
                    carta.classList.add(''encontrada'');
                    pares++;
                    virada = null;
                    travado = false;

                    if (pares === totalPares) {
                        status.textContent = ''Você encontrou todos os pares! 🎉'';
                    }
                } else {
                    setTimeout(function () {
                        virada.classList.remove(''virada'');
                        virada.textContent = ''❓'';
                        carta.classList.remove(''virada'');
                        carta.textContent = ''❓'';
                        virada = null;
                        travado = false;
                    }, 700);
                }
            });
        });
    });
})();
</script>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Memória Bíblica';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">🎣</span>
<h3>Pedro</h3>
<p>Era pescador antes de seguir Jesus. Se tornou um dos maiores líderes da igreja.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🐟</span>
<h3>André</h3>
<p>Irmão de Pedro - foi um dos primeiros a decidir seguir Jesus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">⚡</span>
<h3>Tiago (filho de Zebedeu)</h3>
<p>Jesus o chamava, junto com o irmão João, de "filhos do trovão".</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💌</span>
<h3>João</h3>
<p>O discípulo mais jovem do grupo. Escreveu um Evangelho e cartas cheias de amor.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🗺️</span>
<h3>Filipe</h3>
<p>Adorava apresentar outras pessoas a Jesus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌳</span>
<h3>Bartolomeu (Natanael)</h3>
<p>Jesus disse que o viu debaixo de uma figueira antes mesmo de chamá-lo.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">❓</span>
<h3>Tomé</h3>
<p>Ficou conhecido por duvidar até ver Jesus ressuscitado com os próprios olhos.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💰</span>
<h3>Mateus</h3>
<p>Era cobrador de impostos antes de largar tudo para seguir Jesus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🙏</span>
<h3>Tiago (filho de Alfeu)</h3>
<p>Um discípulo mais discreto, mas fiel até o fim.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💛</span>
<h3>Tadeu</h3>
<p>Perguntou a Jesus como Ele se mostraria ao mundo.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🔥</span>
<h3>Simão, o Zelote</h3>
<p>Lutava por seu povo antes de aprender a lutar pelo Reino de Deus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🗝️</span>
<h3>Judas Iscariotes</h3>
<p>Cuidava do dinheiro do grupo, mas depois traiu Jesus.</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 12</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-slides]'').forEach(function (container) {
        var slides = Array.prototype.slice.call(container.querySelectorAll(''[data-slide]''));
        var contador = container.querySelector(''[data-slide-contador]'');
        var indice = 0;

        function mostrar(novoIndice) {
            slides[indice].classList.remove(''is-ativo'');
            indice = novoIndice;
            slides[indice].classList.add(''is-ativo'');
            contador.textContent = (indice + 1) + '' / '' + slides.length;
        }

        var anterior = container.querySelector(''[data-slide-prev]'');
        var proxima = container.querySelector(''[data-slide-next]'');

        if (anterior) {
            anterior.addEventListener(''click'', function () {
                mostrar((indice - 1 + slides.length) % slides.length);
            });
        }

        if (proxima) {
            proxima.addEventListener(''click'', function () {
                mostrar((indice + 1) % slides.length);
            });
        }
    });
})();
</script>'
    WHERE tipo = 'slide' AND origem = 'kadosys' AND titulo = 'Os 12 Discípulos de Jesus';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">🏙️</span>
<h3>Jerusalém</h3>
<p>A cidade mais importante da Bíblia - lá ficava o Templo, o coração da fé do povo de Israel.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌊</span>
<h3>Mar da Galileia</h3>
<p>Um grande lago onde Jesus caminhou sobre as águas e chamou seus primeiros discípulos.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🐑</span>
<h3>Belém</h3>
<p>A pequena cidade onde Jesus nasceu, numa noite marcada por uma estrela.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🏡</span>
<h3>Nazaré</h3>
<p>A cidade onde Jesus cresceu, ao lado de Maria e José.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💧</span>
<h3>Rio Jordão</h3>
<p>O rio onde João Batista batizava as pessoas - e onde Jesus também foi batizado.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🐫</span>
<h3>Egito</h3>
<p>Para onde José foi levado, e para onde a família de Jesus fugiu quando Ele era bebê.</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 6</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-slides]'').forEach(function (container) {
        var slides = Array.prototype.slice.call(container.querySelectorAll(''[data-slide]''));
        var contador = container.querySelector(''[data-slide-contador]'');
        var indice = 0;

        function mostrar(novoIndice) {
            slides[indice].classList.remove(''is-ativo'');
            indice = novoIndice;
            slides[indice].classList.add(''is-ativo'');
            contador.textContent = (indice + 1) + '' / '' + slides.length;
        }

        var anterior = container.querySelector(''[data-slide-prev]'');
        var proxima = container.querySelector(''[data-slide-next]'');

        if (anterior) {
            anterior.addEventListener(''click'', function () {
                mostrar((indice - 1 + slides.length) % slides.length);
            });
        }

        if (proxima) {
            proxima.addEventListener(''click'', function () {
                mostrar((indice + 1) % slides.length);
            });
        }
    });
})();
</script>'
    WHERE tipo = 'slide' AND origem = 'kadosys' AND titulo = 'Mapa da Terra Santa para Crianças';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">👦😢</div>
<p class="kids-hq-legenda">José era o filho caçula preferido do pai, e seus irmãos ficaram cheios de inveja.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">🕳️👋</div>
<p class="kids-hq-legenda">Tomados pela raiva, os irmãos jogaram José num poço e depois o venderam como escravo.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">🐫➡️</div>
<p class="kids-hq-legenda">José foi levado para o Egito - mas Deus estava com ele em cada passo do caminho.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">⛓️😔</div>
<p class="kids-hq-legenda">Mesmo acusado injustamente e preso, José continuou confiando em Deus.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">💭✨</div>
<p class="kids-hq-legenda">José tinha um dom especial: interpretar sonhos, com a ajuda de Deus.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">👑🎉</div>
<p class="kids-hq-legenda">O Faraó ficou tão impressionado que fez José governador de todo o Egito!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">🌾</div>
<p class="kids-hq-legenda">José guardou comida durante os anos bons, salvando o Egito de uma grande fome.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">🤝😭</div>
<p class="kids-hq-legenda">Anos depois, seus irmãos foram pedir ajuda sem saber quem ele era - e José os perdoou com um abraço.</p>
</div>
</div>'
    WHERE tipo = 'hq' AND origem = 'kadosys' AND titulo = 'As Aventuras de José no Egito';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">🕌🙏</div>
<p class="kids-hq-legenda">Daniel vivia na Babilônia, mas nunca deixou de orar ao seu Deus, três vezes por dia.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">📜🚫</div>
<p class="kids-hq-legenda">Uns homens maus fizeram uma lei proibindo orar a qualquer um, menos ao rei.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">🙏😌</div>
<p class="kids-hq-legenda">Mesmo sabendo do perigo, Daniel continuou orando a Deus como sempre fazia.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">🦁🦁🦁</div>
<p class="kids-hq-legenda">Por desobedecer a lei, Daniel foi jogado numa cova cheia de leões famintos.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">👼🛡️</div>
<p class="kids-hq-legenda">Mas Deus enviou um anjo para fechar a boca dos leões a noite inteira.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">🎉🙌</div>
<p class="kids-hq-legenda">Na manhã seguinte, Daniel saiu são e salvo - e todos viram o poder de Deus!</p>
</div>
</div>'
    WHERE tipo = 'hq' AND origem = 'kadosys' AND titulo = 'Daniel, o Homem Corajoso';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">⭐🐑</div>
<p class="kids-hq-legenda">Jesus nasceu em Belém numa noite marcada por uma estrela brilhante no céu.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">💧🕊️</div>
<p class="kids-hq-legenda">Quando adulto, foi batizado por João no Rio Jordão, e o Espírito de Deus desceu sobre Ele.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">✋🍞🐟</div>
<p class="kids-hq-legenda">Jesus fazia milagres: curava doentes e alimentou 5 mil pessoas com só 5 pães e 2 peixes!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">👨‍👩‍👧‍👦❤️</div>
<p class="kids-hq-legenda">Ele ensinava sobre o amor de Deus e como cuidar uns dos outros.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">✝️😢</div>
<p class="kids-hq-legenda">Jesus foi preso injustamente e morreu na cruz para pagar pelos nossos pecados.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">🌅🎉</div>
<p class="kids-hq-legenda">Mas no terceiro dia, Ele ressuscitou! A morte não pôde detê-Lo!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">☁️🙌</div>
<p class="kids-hq-legenda">Depois, Jesus subiu ao céu, prometendo voltar um dia.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">❤️🌍</div>
<p class="kids-hq-legenda">E até hoje, Ele convida cada criança a fazer parte da sua família para sempre.</p>
</div>
</div>'
    WHERE tipo = 'hq' AND origem = 'kadosys' AND titulo = 'A Vida de Jesus em Quadrinhos';

UPDATE kids_conteudos SET texto_conteudo = 'Brinque em casa: escreva os 9 frutos do Espírito (amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio) em pedacinhos de papel e sorteie um por vez, contando uma situação em que você pode praticar aquele fruto hoje.'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Bingo dos Frutos do Espírito';

UPDATE kids_conteudos SET midia_path = 'assets/kids/pdfs/10-mandamentos.pdf', texto_conteudo = NULL
    WHERE tipo = 'pdf' AND origem = 'kadosys' AND titulo = 'Caderno de Atividades: Os 10 Mandamentos';

UPDATE kids_conteudos SET midia_path = 'assets/kids/pdfs/livros-da-biblia.pdf', texto_conteudo = NULL
    WHERE tipo = 'pdf' AND origem = 'kadosys' AND titulo = 'Cartilha: Livros da Bíblia';

UPDATE kids_conteudos SET midia_path = 'assets/kids/pdfs/diploma-kids.pdf', texto_conteudo = NULL
    WHERE tipo = 'pdf' AND origem = 'kadosys' AND titulo = 'Diploma Kids KADOSYS';

-- Remove os 8 placeholders de video/audio (sem link real disponivel).
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'Davi e Golias';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'A Parábola do Filho Pródigo';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'Daniel na Cova dos Leões';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'Davi Tocando Harpa para o Rei Saul';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'A Criação em 7 Dias';
DELETE FROM kids_conteudos WHERE tipo = 'audio' AND origem = 'kadosys' AND titulo = 'Salmo 23 narrado para crianças';
DELETE FROM kids_conteudos WHERE tipo = 'audio' AND origem = 'kadosys' AND titulo = 'Louvor Infantil: Deus é Bom';
DELETE FROM kids_conteudos WHERE tipo = 'audio' AND origem = 'kadosys' AND titulo = 'Salmo 100 narrado para crianças';
