# Log de Deploy

Toda vez que uma alteracao e enviada para o `main`, uma entrada nova e
adicionada aqui no topo (numero sequencial "Ajuste N").

Como usar para conferir se o deploy no cPanel funcionou: depois de dar
"Pull" no Git Version Control (ou subir os arquivos por FTP), abra este
arquivo direto no navegador -

```
https://SEUDOMINIO/DEPLOY_LOG.md
```

- se o numero do ultimo ajuste bater com o que foi avisado no chat, o
  deploy funcionou;
- se aparecer um ajuste mais antigo, ainda falta atualizar o servidor.

---

## Ajuste 174 - 2026-07-24

**Igrejas Kids: "Jogar com amigo" - duelo de quiz online 1x1 entre crianças da mesma igreja**

Resposta a "e possível permitir que as crianças jogem jogos juntos
online?" - duelo turn-based, sem WebSocket (a hospedagem não tem
processo persistente), atualizado por polling a cada 1,5s, igual ao
padrão já usado no Food (tela da cozinha).

- Uma criança desafia outra da mesma igreja pra um quiz publicado; a
  convidada recebe o convite na tela "Jogar com amigo" e pode
  aceitar/recusar.
- Sala do duelo: cada criança responde no seu próprio ritmo, vendo o
  placar (acertos/total) do adversário se atualizar ao vivo por
  polling - sem chat de texto livre, só 6 reações de emoji
  pré-definidas (👍😂🎉😮❤️💪), decisão deliberada pra não abrir
  nenhuma superfície de mensagem entre crianças.
- Quem termina primeiro (todas as perguntas certas) vence: +20 XP e
  +12 moedas pro vencedor, +5 XP e +3 moedas de participação pro
  outro - concedido uma única vez, protegido contra requisições
  concorrentes.
- Isolamento entre igrejas é automático (cada banco é de uma igreja
  só, nunca cruza).
- Corrigido de quebra: banners com `hidden` que usavam a classe
  `.kids-premio-banner` continuavam visíveis, porque essa classe já
  define `display: flex` e vence o atributo `hidden` no CSS de origem
  do autor (mesmo bug de especificidade do Ajuste 249, agora com um
  seletor `[hidden]` dedicado pra essa classe).



**Igrejas Kids: missão do dia, sequência de acesso e ranking (igreja + entre igrejas)**

Segunda e terceira parte do pedido do usuário (a primeira, o boneco do
avatar, já tinha saído no Ajuste 172): "faz algo bacana envolvente" pra
fazer a criança querer voltar todo dia + "ranking geral das crianças e
igrejas na tela".

- **Missão do dia**: 2 conteúdos sorteados por criança, todo dia
  (prioriza o que ela ainda não concluiu), com bônus de +15 XP e +8
  moedas só nesse dia - aparece na home da Biblioteca com um selo
  "Feito!" pra cada uma já concluída.
- **Sequência de acesso à Biblioteca**: separada da sequência de
  presença física na igreja (que só conta em check-in) - conta dias
  seguidos abrindo o app em casa, com bônus de marco em 3/7/14/30 dias
  (+15 a +100 XP, +10 a +70 moedas).
- **Ranking dentro da igreja**: top 10 crianças por XP, com a posição
  da própria criança sempre visível mesmo fora do top 10.
- **Ranking entre igrejas KADOSYS**: top 10 igrejas pelo XP total das
  crianças (só o nome da igreja aparece, nenhuma criança de uma igreja
  é exposta pra outra) - alimentado por uma tabela nova no banco
  central (`plataforma_kids_ranking`), atualizada automaticamente toda
  vez que uma criança ganha XP em qualquer igreja provisionada. Só
  aparece em instalações que fazem parte da frota multi-tenant (uma
  instalação avulsa/de desenvolvimento simplesmente não mostra essa
  seção, sem erro).
- Migração 068 (por igreja): `kids_criancas.sequencia_app_dias`/
  `ultima_visita_app_em` + tabela `kids_missoes_diarias`.
- Migração 069 (só banco central, fora do `install.sql` - mesmo padrão
  de `plataforma_tenants`/`plataforma_avisos`): tabela
  `plataforma_kids_ranking`.
- Testado via Playwright: missão do dia sorteada e persistente no
  mesmo dia, bônus concedido só na primeira conclusão de cada missão,
  sequência de acesso incrementando corretamente (inclusive marco de 3
  dias simulado) e sem duplicar bônus numa segunda visita no mesmo dia,
  ranking da igreja mostrando a criança de teste, ranking entre
  igrejas corretamente ausente numa instalação sem tenant resolvido.

---

## Ajuste 172 - 2026-07-24

**Igrejas Kids: avatar vira um "boneco" de verdade (corpo + roupas em camadas)**

Pedido do usuário: "coloca tambem um ranking geral das crianças e
igrejas na tela... crie novas funções e etc para uso de moedas e quanto
maior nivel mais coisas, ainda aquele avatar esta feio, tente criar um
boneco, com roupas e etc tipo um roblox sabe?". Esta entrega cobre a
parte do boneco/roupas + mais itens pra gastar moeda e subir de nível
(o ranking entre crianças/igrejas fica pra uma próxima entrega, já que
depende de agregar dado entre os bancos separados de cada igreja).

- O avatar deixou de ser um círculo com a inicial do nome + emojis
  soltos (chapéu/acessório flutuando) e virou um boneco de verdade,
  desenhado em SVG por camadas: corpo (com tom de pele escolhível) +
  roupa (silhueta própria por cima) + chapéu/acessório (iguais a antes).
- **Tom de pele**: 6 opções, todas liberadas desde o nível 1 - é
  representação/escolha, não recompensa.
- **Roupa**: categoria nova com 12 itens (10 por nível + 2 na loja de
  moedas), cada uma com uma cor própria sobre um dos 5 "moldes" de
  roupa (camiseta e bermuda, vestido, moletom com capuz, uniforme com
  capa/emblema, manto longo) - dá pra reconhecer o visual de longe sem
  precisar desenhar uma peça nova do zero pra cada item.
- Loja de moedas ganhou 2 roupas exclusivas (Roupa Arco-íris e Traje
  Espacial), junto com os itens que já existiam de chapéu/acessório/
  fundo/título - mais opções de "quanto maior o nível (ou mais moedas),
  mais coisas pra desbloquear".
- Migração 067: colunas `avatar_pele`/`avatar_roupa` em `kids_criancas`.
- Testado via Playwright: boneco renderiza com a roupa/pele padrão,
  troca de roupa/pele/chapéu/acessório atualiza a prévia na hora sem
  recarregar, item bloqueado por nível continua impedindo a troca (fica
  cinza com o cadeado, exatamente como as demais categorias).

---

## Ajuste 171 - 2026-07-24

**Igrejas Kids: exigir ação real da criança em TODO conteúdo pra concluir + foto do desafio**

Pedido do usuário: "os desafios kids, permita tirar foto da boa ação e
fazer upload para poder concluir a tarefa. em tudo que tem no kids,
preciso que exija uma ação da criança no sistema para concluir e ganhar
xp". Antes deste ajuste só quiz, jogos com fases, slideshow e estudo
tinham gate de verdade - colorir, desenho, HQ, história/devocional/
versículo ilustrado, plano de leitura, PDF e desafio liberavam o botão
"Concluir" direto, sem exigir nenhuma interação real.

- **Desafio**: novo fluxo de upload de foto da boa ação
  (`KidsAppController::concluirDesafio`, rota
  `POST /kids/conteudo/{id}/concluir-desafio`) - a criança tira/escolhe
  uma foto (com preview instantâneo), e o próprio envio da foto (validada
  como imagem JPG/PNG/WEBP, até 8MB, salva em
  `uploads/kids-desafios/{tenant}/`) é a ação que libera XP/moedas via
  `KidsConteudo::registrarConclusaoPor`, que agora também guarda
  `foto_path` (nova coluna em `kids_conteudo_conclusoes`) como evidência.
- **Colorir**: só libera o Concluir depois que a criança pinta (clica)
  cada região pintável do desenho pelo menos uma vez.
- **Desenho livre** (ex: "Desenhe a sua Oração"): exige um traço de
  verdade no canvas (contagem mínima de movimento com o dedo/mouse
  pressionado), não só um toque.
- **HQ**: a criança precisa tocar em cada quadrinho pra "virar a página"
  antes de poder concluir.
- **Plano de leitura**: convertido de texto corrido pra um checklist real
  (um checkbox por dia) - só libera com todos os dias marcados.
- **História/devocional/versículo ilustrado**: novo widget de reação
  (escolher um emoji de como o conteúdo fez a criança se sentir) antes de
  concluir.
- **PDF**: só libera depois de realmente abrir o arquivo.
- **Vídeo/áudio**: preparado pra só liberar quando a mídia terminar de
  tocar (evento `ended`) - proativo, já que hoje não há nenhum conteúdo
  desses tipos no catálogo oficial.
- Migração 066: nova coluna `kids_conteudo_conclusoes.foto_path` e
  conversão dos 3 planos de leitura existentes em checklist.
- Testado fim a fim via Playwright: colorir, plano de leitura, HQ,
  história (reação), PDF e desafio (upload de foto real, com preview e
  concessão de XP) - todos bloqueando o Concluir até a ação certa e
  liberando depois. Reconfirmado que quiz, slide e jogo (memória)
  continuam funcionando normalmente (sem regressão).

---

## Ajuste 170 - 2026-07-24

**Igrejas Kids: jogo da memória deixava concluir sem terminar de verdade**

Bug real no motor genérico do jogo da memória (`kids-jogo-memoria.js`,
usado por Monte a Arca de Noé, Memória Bíblica e Memória: Milagres de
Jesus): a trava de "não deixa clicar de novo numa carta já virada"
checava só a classe `virada`, mas essa classe só é adicionada 150ms
depois do clique (tempo da animação de virar). Clicando duas vezes bem
rápido na mesma carta (comum numa criança impaciente batendo na tela),
a segunda batida passava pela trava, a carta entrava duas vezes na
comparação de par - e como é a mesma carta, ela "combinava com ela
mesma" e contava como par encontrado sem a criança ter achado par
nenhum. Repetindo isso dava pra terminar a fase (e o jogo inteiro) sem
jogar de verdade.

- Corrigido bloqueando também a classe `virando` (que é adicionada na
  hora, sem atraso) na trava de clique - agora uma segunda batida na
  mesma carta, por mais rápida que seja, não passa.
- Testado simulando o exploit (dois cliques seguidos na mesma carta):
  antes marcava como encontrada sozinha, agora não marca mais. Testado
  de novo o fluxo legítimo completo (3 fases, pares de verdade) pra
  garantir que continua funcionando normalmente.
- Revisados os motores de trivia e caça-palavras em busca do mesmo tipo
  de brecha (clique duplo/rápido registrando progresso indevido) - não
  encontrado nenhum problema equivalente neles (o clique de resposta
  certa já desabilita os botões na hora, e a seleção do caça-palavras é
  sempre um gesto síncrono).

## Ajuste 169 - 2026-07-24

**Igrejas Kids: correções de qualidade encontradas depois do Ajuste 168**

Revisão de conteúdo por conteúdo depois do ar do modo Kids com sons/
níveis (Ajuste 168) encontrou vários problemas reais, alguns bugs de
verdade e alguns conteúdos que nunca tinham virado widget de fato:

- **Quiz entregando a resposta de graça**: a explicação de cada
  pergunta (`.kids-quiz-explicacao`) tinha `hidden` no HTML, mas o CSS
  (`display: flex`) sobrescrevia esse atributo - a explicação (que cita
  a resposta certa) ficava visível pra TODAS as perguntas antes mesmo
  de responder. Corrigido com `.kids-quiz-explicacao[hidden] { display:
  none; }`.
- **Caça-Nomes: Personagens do Novo Testamento** (4º caça-palavras) tinha
  ficado de fora da consolidação do Ajuste 168 - ainda carregava o
  algoritmo antigo embutido, rodando em paralelo com o motor global e
  duplicando o processamento de cada seleção. Consolidado.
- **"Slides" com conteúdo que nunca virou slideshow**: "Como a Bíblia
  chegou até nós" era `tipo=slide` mas o texto nunca tinha sido
  convertido pro widget `kids-slides` - aparecia como um parágrafo cru
  sem nenhum estilo (era exatamente o "slides não tem nada" reportado).
  Convertido em 4 slides de verdade. Aproveitado pra também consolidar a
  navegação (‹ ›) dos 3 slideshows no `kids-interacoes.js` global, em vez
  de duplicar o script de novo, e a mesma passada adicionou a trava de
  conclusão nos slides: só libera o Concluir ao chegar no último.
- **"Verdadeiro ou Falso" e "Adivinhe o Personagem"**: dois "jogos" que
  eram só texto puro com a resposta escrita entre parênteses (nem
  interativo, nem escondia a resposta). Convertidos pro motor de trivia
  em rodadas.
- **"Bingo dos Frutos do Espírito" e "Caça ao Tesouro Bíblico em Casa"**:
  reclassificados de `jogo` pra `desafio` - são atividades pra fazer em
  casa/offline, não jogos digitais, e o tipo errado criava expectativa
  de widget que nunca existiu.
- **Tipo "estudo"**: não exigia nenhuma ação da criança (só ler texto).
  Adicionado ao allowlist de HTML confiável e incluída 1 pergunta rápida
  de fixação (motor de trivia) no fim de cada um dos 5 itens, com a
  mesma trava de conclusão dos outros widgets.
- Testado tudo de novo com Playwright (login de criança via PIN):
  explicação do quiz escondida até responder, caça-palavras 97 sem
  duplicação, os 2 jogos novos com o Concluir liberado só no fim, os 3
  slideshows com navegação e trava funcionando, e as 5 telas de estudo
  com a pergunta de fixação. Instalação limpa do `install.sql` (com a
  migração 065 já mesclada) testada do zero, sem erros.

## Ajuste 168 - 2026-07-23

**Igrejas: modo Kids com sons, níveis e conclusão forçada nos jogos**

O módulo Kids tinha um problema concreto: os conteúdos do tipo "jogo"
(memória, trivia, caça-palavras) não tinham nenhuma trava de conclusão -
dava pra clicar em "Concluir e ganhar XP" sem jogar nada, e cada jogo
tinha o algoritmo inteiro copiado e colado dentro do próprio conteúdo
(o caça-palavras, por exemplo, tinha o mesmo script de ~230 linhas
triplicado). Também não existia nenhum som na Biblioteca Kids.

- **`kids-sons.js`** (novo): efeitos sonoros sintetizados via Web Audio
  API (osciladores, sem arquivo de áudio nenhum pra hospedar) - clique,
  virar carta, acerto, erro, moeda, fase concluída, vitória. Botão de
  mudo flutuante (🔊/🔇) injetado automaticamente em toda página Kids,
  com preferência salva no `localStorage`.
- **`kids-interacoes.js`** (novo): hook universal de som/animação - um
  `MutationObserver` detecta as classes de estado que os jogos já
  usavam antes (`virada`, `correta`, `errada`, `errada-tmp`,
  `encontrada`) e dispara o som certo automaticamente, sem precisar
  editar nenhum dos 97 conteúdos já existentes. Também expõe
  `KidsProgresso` (banner de "fase concluída" + liberar o botão
  Concluir), usado pelos motores de jogo abaixo.
- **`kids-jogo-memoria.js`**, **`kids-jogo-trivia.js`** (novos): motores
  genéricos de jogo da memória (com fases progressivas de pares) e
  trivia (com rodadas) - o conteúdo só declara os dados
  (`data-fases`/`data-rodadas` em JSON), e só libera o Concluir depois
  da última fase/rodada 100% certa. Errar não trava - dá pra tentar de
  novo.
- **`kids-jogo-cacapalavras.js`** (novo): o algoritmo de
  arrastar-para-selecionar do caça-palavras, que estava triplicado
  inline em 3 conteúdos diferentes, virou um arquivo só, carregado uma
  vez - com a mesma trava de só liberar o Concluir quando todas as
  palavras forem encontradas.
- **CSS** (`kids-biblioteca.css`): animação de "virar carta", tremida
  nas respostas erradas, banner de fase concluída, estilo do botão de
  mudo - tudo com `prefers-reduced-motion` respeitado.
- **`kids/show.php`**: o gate de "só libera Concluir com o jogo
  terminado" (que já existia pro quiz) passou a reconhecer também os
  novos marcadores de memória/trivia/caça-palavras. Preview do admin
  (`dashboard/kids/biblioteca/show.php`) sincronizado pra carregar os
  mesmos scripts.
- **Migração 064**: os 2 jogos da memória e a trivia existentes
  (Monte a Arca de Noé, Memória Bíblica, Corrida da Fé) ganharam fases/
  rodadas de verdade; os 3 caça-palavras existentes perderam o script
  duplicado (agora usam o motor global); conteúdo novo - "Memória:
  Milagres de Jesus" (3 fases), "Trilha da Criação" (trivia em 2
  rodadas), "Caça-Nomes: Mulheres da Bíblia" (grade gerada
  proceduralmente) e mais 2 quizzes ("Mulheres da Bíblia",
  "Oração e Mandamentos").
- Testado com Playwright fazendo login de criança de verdade (PIN):
  jogo da memória, trivia e caça-palavras confirmados com o Concluir
  escondido até o fim e liberado só depois de terminar; quiz já
  existente testado de novo pra garantir que o hook universal de som
  não quebrou nada. Instalação limpa do `install.sql` com a migração
  064 já mesclada testada do zero, sem erros.

## Ajuste 167 - 2026-07-23

**Barbearias e Food: aviso de contagem regressiva do teste grátis**

Até agora, só o Igrejas mostrava um aviso proativo de quando o teste
grátis termina - no Barbearias e no Food, quem estava em trial só
descobria que o prazo tinha acabado quando já era bloqueado e
redirecionado pra tela de assinatura, sem nenhum aviso antes disso.

- Adicionado o mesmo banner do Igrejas ("Seu teste grátis termina em X
  dia(s) (DD/MM/AAAA). Clique aqui para escolher um plano.") no topo do
  painel dos dois apps - só aparece pra quem ainda está em trial e não
  venceu, e não aparece na própria tela de assinatura (senão ficaria
  redundante).
- Estilo novo `.dash-pix-aviso` (mesma paleta âmbar do aviso
  equivalente no Igrejas) adicionado no `app.css` dos dois apps - link
  clicável que leva direto pra `/dashboard/assinatura`.
- Testado localmente nos dois apps: banner aparece com a contagem
  certa, some quando a pessoa já está na tela de assinatura, e o link
  funciona.

## Ajuste 166 - 2026-07-23

**KADOSYS Food: Fase 9 (final) - Configurações + integração com o Super Admin**

Nona e última fase do plano original do app Food: tela de Configurações
completa e integração no painel `/sites` e `/avisos` do Super Admin,
junto com Igrejas e Barbearias.

- **Configurações**: perfil (nome/telefone/logo/cor de destaque,
  reaproveitando os campos que já existiam em `restaurantes`), dados
  fiscais informativos (CPF/CNPJ, razão social - sem emissão de NF-e,
  que exigiria certificado digital + integração com a SEFAZ), chave Pix
  própria, equipe (admin/operador, com a mesma trava de "sempre precisa
  sobrar 1 admin ativo" já usada no Barbearias), impressoras (cadastro
  só informativo de nome/IP - o comprovante do PDV continua saindo pela
  impressão do navegador) e backup (exporta um JSON com todos os dados
  do restaurante - produtos, pedidos, financeiro, clientes etc. - para
  download sob demanda).
- **`Food\Models\Impressora`** e **`Food\Models\RestauranteAviso`**:
  models novos, seguindo o mesmo padrão estático já usado no resto do
  app.
- **Integração com o Super Admin**: clonados `SiteBarbearia` →
  `SiteFood`, `DatabaseBarbearias` → `DatabaseFood`,
  `config/database_barbearias.php` → `database_food.php`,
  `AvisoBarbearia` → `AvisoFood` - o Food passa a aparecer lado a lado
  com Igrejas/Barbearias na listagem unificada de sites (suspender/
  reativar/estender acesso/excluir) e no painel de avisos da
  plataforma, com o aviso aparecendo no sino de notificações do próprio
  painel do restaurante (`Food\Models\RestauranteAviso::ativo()`).
- Testado localmente ponta a ponta: todas as ações de Configurações
  (perfil, dados fiscais, Pix, criar/editar/excluir usuário da equipe,
  cadastrar/excluir impressora, baixar o backup e conferir o JSON),
  mais o fluxo completo do Super Admin (o restaurante aparece em
  `/sites`, suspender/reativar/estender funcionam, publicar um aviso só
  para "Food" aparece no sino do painel do restaurante, excluir apaga
  em cascata).

## Ajuste 165 - 2026-07-23

**KADOSYS Food: Fase 8 - Dashboard rico + Gráficos + Relatórios/DRE**

Oitava fase do app Food: painel principal com KPIs de verdade (não mais
placeholders), gráfico de fluxo de caixa e uma tela de Relatórios com
DRE do período e ranking de produtos.

- **`Food\Models\Relatorio`**: motor único de agregação (mesma
  filosofia do `Food\Core\Custeio`) usado tanto pelo Dashboard quanto
  pela nova tela de Relatórios, pra nunca ter duas telas somando a
  mesma coisa de formas ligeiramente diferentes. Toda query que cruza
  `financeiro_lancamentos` com `pedido_itens`/`produtos` passa antes
  por uma subquery `DISTINCT pedido_id`, porque uma venda com
  pagamento dividido (Fase 6) gera várias linhas de lançamento pro
  mesmo pedido - sem isso o custo/quantidade vendida seria multiplicado
  pela quantidade de formas de pagamento usadas na venda.
- **DRE do período**: receita → custo direto vendido (CMV, calculado a
  partir do custo em cache de cada produto) → lucro bruto → despesas
  (lançamentos manuais + contas a pagar marcadas como pagas dentro do
  período) → lucro líquido.
- **Comissão iFood estimada**: só o percentual (12%) sobre pedidos de
  origem iFood no período - a taxa fixa de entrega por distância não
  entra porque o pedido não guarda a distância percorrida (aproximação
  avisada na própria tela).
- **Gráfico de fluxo de caixa**: vendorizado o Chart.js (MIT, arquivo
  único em `public/assets/js/vendor/`, sem CDN) - mesmo padrão já usado
  pro `qrcode-generator.js`. O gráfico lê as cores do tema atual
  (claro/escuro) direto do CSS, em vez de cor fixa.
- **Nova tela `/dashboard/relatorios`**: seletor de mês/ano, DRE,
  produtos mais vendidos (por quantidade) e mais lucrativos (por
  margem), clientes ativos/novos e estoque baixo - reaproveita as
  mesmas queries do `Relatorio`.
- Testado localmente com uma venda real no PDV (3x Bolo de Chocolate +
  5x Brigadeiro Gourmet = R$ 57,50) conferindo cada KPI contra o
  cálculo manual, mais uma despesa paga e um pedido iFood extras pra
  validar a despesa no DRE e a comissão estimada com valor diferente de
  zero.

## Ajuste 164 - 2026-07-23

**KADOSYS Food: Fase 7 - Financeiro completo + Precificação Inteligente**

Sétima fase do app Food: contas a pagar/receber (com despesas
recorrentes/parceladas geradas automaticamente), centros de custo, e o
simulador de Precificação Inteligente com a fórmula exata da taxa
iFood Entrega II.

- **`contas_a_pagar` / `contas_a_receber`**: status só guarda
  pendente/paga(recebida)/cancelada de propósito - "vencida" é
  **calculado na hora** (`estaVencida()`: pendente + vencimento
  passado) em vez de persistido, pra nunca ficar desatualizado se um
  cron atrasar.
- **Despesas recorrentes**: `serie_id` agrupa as parcelas de uma mesma
  despesa (a primeira linha aponta pra si mesma). Novo cron
  `gerar_despesas_recorrentes.php` (mensal): pra cada série ativa, se a
  parcela mais recente já foi paga e não atingiu o limite de parcelas
  (`parcela_total` nulo = sem fim, ex. aluguel), gera a próxima com
  vencimento um mês depois - idempotente (só gera de novo se a atual já
  foi resolvida).
- **`centros_custo`**: agrupamento opcional (Cozinha, Delivery,
  Administrativo...) vinculável a qualquer conta a pagar/receber.
- **`Food\Core\IfoodTaxaEntrega`**: função pura (comissão 12% do valor
  + taxa fixa por faixa de distância: ≤3km R$3,99 / 3-5km R$5,99 /
  5-7km R$7,99 / >7km R$9,99), retorna comissão/taxa fixa/valor líquido
  recebido.
- **Precificação Inteligente**: simulador avulso (não salva produto
  nenhum) que reaproveita o **mesmo** `Food\Core\Custeio` já usado por
  `Produto::recalcularCusto()` - o número mostrado aqui é sempre
  idêntico ao que um produto real usaria com os mesmos parâmetros.
  Calculadora separada pra simular o valor líquido de um pedido iFood
  pela distância. A mesma tela também edita os valores padrão de
  overhead/margem/taxas (`CusteioConfig::atualizar()`, antes só lidos,
  nunca editáveis).
- **`FinanceiroLancamento`** ganhou tela própria de listagem (resumo do
  dia/mês, filtro por tipo, paginação) - upgrade de model "só grava"
  pra um Model completo (`paginate()`, `resumoDoPeriodo()`, `delete()`),
  mantendo a assinatura de `create()` intacta (Pedido::finalizar() e o
  Caixa continuam funcionando sem alteração nenhuma).
- 2 novos itens no menu lateral: "Financeiro" e "Precificação".
- Testado fim a fim localmente (MariaDB + `php -S` + curl): simulador
  de custeio conferido matemática exata (custo total, markup, margem,
  preços ideais por canal incluindo o "engordamento" de iFood/
  delivery), calculadora iFood Entrega II conferida (R$50 a 4km →
  comissão R$6,00 + taxa R$5,99 → líquido R$38,01), salvamento dos
  valores padrão da loja, CRUD de centros de custo, conta a pagar
  recorrente de 12x gerando a parcela seguinte via cron **só** depois
  de paga a atual (rodar de novo sem gerar duplicata), conta a receber
  vencida exibindo o badge correto, KPI de vencidas atualizando em
  tempo real, e nenhuma regressão nas telas de Caixa/PDV/Produção da
  Fase 6.

## Ajuste 163 - 2026-07-23

**KADOSYS Food: Fase 6 - Produção (cozinha/TV) + Caixa + PDV**

Sexta fase do app Food: a tela de cozinha em tempo real, abertura/
fechamento de caixa com sangria/suprimento e o PDV completo com venda
touch, split payment e Pix dinâmico.

- **Correção de semântica do status do pedido**: a Fase 5 usava
  "recebido" pra dizer "ainda em montagem, sem estoque baixado" - mas o
  spec original usa "Recebido" como a primeira coluna do kanban da
  cozinha (pedido confirmado, cozinha recebeu). Entrou um novo valor
  `montagem` pra fase de montagem (pré-confirmação), liberando
  "recebido" pra voltar a significar "confirmado". Migration faz
  `ALTER` acrescentando `montagem` ao ENUM antes de migrar as linhas
  antigas com status `recebido` (que sob a semântica velha eram sempre
  rascunhos) pra `montagem`.
- **Produção**: `/dashboard/producao` (kanban Recebido → Em preparo →
  Finalizado → Saiu para entrega → Entregue hoje), com timer de tempo
  decorrido calculado no navegador e alerta sonoro de pedido novo via
  polling (`/dashboard/producao/dados`) - o som é sintetizado no
  próprio navegador (Web Audio API, 3 beeps ascendentes), sem precisar
  de nenhum arquivo de áudio. Variante `/dashboard/producao/tv`, tela
  cheia sem sidebar, com botão "Habilitar som" (desbloqueio de áudio
  exige um gesto do usuário no navegador).
- **`caixas`**: abertura/fechamento de turno, clone quase direto do
  model já usado em Barbearias. Sangria/suprimento **não** viraram uma
  tabela própria - são `financeiro_lancamentos` comuns (despesa/
  categoria "Sangria" ou receita/categoria "Suprimento") vinculados via
  `caixa_id`, mesmo padrão de Barbearias. O saldo esperado do caixa é
  `valor_abertura` + soma dos lançamentos vinculados a ele.
- **`pedido_pagamentos`**: permite mais de uma forma de pagamento por
  venda (split) - se um pedido não tiver nenhuma linha aqui,
  `Pedido::finalizar()` cai no comportamento antigo da Fase 5 (um único
  lançamento com `pedidos.forma_pagamento` pro valor total), então
  pedidos criados pela tela normal de Pedidos continuam intactos. Pra
  isso, a `UNIQUE` em `financeiro_lancamentos.pedido_id` (Fase 5) virou
  um índice comum.
- **PDV** (`/dashboard/pdv`): o "carrinho" é sempre um Pedido de
  verdade (origem balcão, status `montagem`), guardado na sessão -
  reaproveita 100% a lógica de itens/estoque já existente, sem
  duplicar regra de negócio. Grid de produtos tocável + campo de
  código de barras (autofocado, sem SDK - qualquer leitor USB digita
  como teclado); tela de pagamento aceita dinheiro/Pix/cartão/vale
  combinados na mesma venda, calcula troco automaticamente (dinheiro:
  aplica `min(recebido, restante)`, troco = `recebido - aplicado`) e
  gera QR Pix dinâmico com o valor exato do restante (reaproveita
  `Food\Core\PixEstatico`, confirmação ainda manual). Exige caixa
  aberto pra vender. Recibo final é uma página simples pra impressão
  pelo próprio navegador (`window.print()`, sem driver ESC-POS).
- 3 novos itens no menu lateral: "PDV", "Produção", "Caixa".
- Testado fim a fim localmente (MariaDB + `php -S` + curl): venda
  completa no PDV com 2 produtos (R$37,00) split em dinheiro (R$20
  recebidos, sem troco) + Pix (R$17,00), baixa de estoque conferida
  (9,600kg restantes, exato), 2 lançamentos financeiros vinculados ao
  caixa (saldo esperado R$100 abertura + R$37 vendas = R$137,00
  confirmado na tela), sangria de R$50 e suprimento de R$30 (saldo
  R$117,00 confirmado), fechamento de caixa, avanço completo do pedido
  pelas 4 etapas do kanban de Produção, bloqueio de venda sem caixa
  aberto, e rollback total ao tentar confirmar um pedido clássico sem
  estoque suficiente (nada alterado, mensagem aponta o ingrediente).

## Ajuste 162 - 2026-07-23

**KADOSYS Food: Fase 5 - Clientes + Pedidos + baixa automática de estoque**

Quinta fase do app Food: cadastro de clientes e o módulo de Pedidos
(Balcão/WhatsApp/iFood manual/Delivery próprio), com a peça mais nova
até aqui - baixa automática de estoque em cascata ao confirmar um
pedido, usando a ficha técnica de cada produto vendido.

- **`clientes`**: cadastro simples (nome, telefone, WhatsApp,
  aniversário, endereço, observações). Ticket médio, total gasto,
  frequência e último pedido **não ficam guardados** - são calculados
  na hora via query sobre `pedidos` (`Cliente::estatisticas()`), só
  contando pedidos já confirmados.
- **`pedidos` + `pedido_itens`**: pedido nasce com status "recebido"
  (ainda em montagem, sem efeito real) e itens são adicionados um a um
  - mesmo padrão já usado em Ficha Técnica/Compras. A coluna `status`
  já nasce com os 6 valores do fluxo de produção completo do spec
  original (Recebido/Em preparo/Finalizado/Saiu para entrega/Entregue/
  Cancelado), mas só "Confirmar" e "Cancelar" são expostos nesta fase -
  a tela de Produção (TV da cozinha) que vai expor o resto do fluxo
  chega na Fase 6.
- **`Pedido::finalizar()`**: dentro de **uma única transação**,
  percorre cada item → ficha técnica do produto → desconta o estoque
  de cada ingrediente proporcionalmente (quantidade vendida × consumo
  da receita ÷ rendimento), com o mesmo UPDATE condicional atômico já
  usado no resto da plataforma. **Se faltar estoque de qualquer
  ingrediente, a transação inteira é revertida** e o pedido continua
  "recebido" - a mensagem de erro aponta exatamente qual ingrediente
  faltou. Com sucesso: loga a movimentação de saída de cada
  ingrediente, cria um lançamento financeiro de receita e avança o
  pedido pra "em preparo".
- **`financeiro_lancamentos`** (schema mínimo): criado automaticamente
  por `Pedido::finalizar()` - ainda sem tela própria (dashboard/CRUD/
  relatórios completos ficam pra Fase 7).
- Cancelar um pedido só é permitido enquanto ainda está "recebido"
  (antes da baixa de estoque) - desfazer um pedido já confirmado
  exigiria reverter o estoque com segurança, fora de escopo aqui (mesma
  lógica conservadora já usada nas Compras).
- 2 novos itens no menu lateral: "Pedidos" e "Clientes".
- Testado fim a fim localmente (MariaDB + `php -S` + curl + Playwright):
  criação de pedido + itens, confirmação com baixa de estoque
  conferida manualmente item a item (matemática exata), lançamento
  financeiro automático correto, bloqueio por estoque insuficiente com
  rollback total (nada alterado), cancelamento, e estatísticas de
  cliente contando só o pedido confirmado.

## Ajuste 161 - 2026-07-23

**KADOSYS Food: Fase 4 - Estoque + Compras**

Quarta fase do app Food: registro de compras de ingredientes com
entrada automática no estoque, e um log auditável de toda
movimentação (compra, ajuste manual, perda, inventário).

- **`estoque_movimentos`**: log de toda entrada/saída/perda/ajuste de
  inventário, com o ingrediente afetado e o motivo/referência. O cache
  rápido (`ingrediente.estoque_atual`) continua sendo a fonte usada no
  dia a dia - esta tabela é só o histórico.
- **`compras` + `compra_itens`**: cabeçalho (fornecedor opcional, data,
  frete, observação) + itens (ingrediente, quantidade, preço unitário,
  validade opcional). Ao adicionar um item: soma o estoque do
  ingrediente, atualiza `preco_atual` pro valor pago e recalcula
  `preco_medio` (custo médio ponderado entre o estoque que já tinha e
  a quantidade nova), loga a movimentação e recalcula o valor total da
  compra. Uma compra é um registro **apêndice-só** nesta entrega -
  editar/excluir um item já lançado exigiria reverter estoque e preço
  com segurança mesmo que o estoque já tenha sido parcialmente
  consumido por vendas (Fase 5), o que fica fora de escopo aqui (mesma
  simplificação já assumida pra vencimento/FEFO no plano original).
- **Recálculo de custo em cascata**: ao adicionar um item de compra, o
  preço do ingrediente pode mudar - `CompraController::itemAdicionar()`
  dispara `Produto::recalcularCustoDeProdutosComIngrediente()` logo em
  seguida, mesmo padrão já usado em `IngredienteController::update()`.
- **Tela de Estoque**: painel de estoque baixo (reaproveita a mesma
  query de Ingredientes), painel de itens vencendo nos próximos 7 dias
  (a partir de `compra_itens.validade` - alerta simples, sem
  rastreamento de lote/FEFO), histórico de movimentações paginado, e
  formulário de ajuste manual (entrada/saída/perda somam ou descontam
  do estoque com o mesmo UPDATE condicional atômico já usado no
  restante da plataforma pra nunca deixar o estoque negativo;
  inventário define a contagem exata, não um delta).
- Excluir um fornecedor que já tem compra registrada agora é bloqueado
  (protegeria o histórico de custo de compra).
- 2 novos itens no menu lateral: "Estoque" e "Compras".
- Testado fim a fim localmente (MariaDB + `php -S` + curl + Playwright):
  criação de compra + adição de itens conferindo manualmente a
  matemática do preço médio ponderado e do valor total, os 4 tipos de
  movimentação manual (entrada, saída, perda bloqueada por estoque
  insuficiente, inventário), alerta de vencimento próximo aparecendo
  só dentro da janela de 7 dias, e bloqueio de exclusão de fornecedor
  com compra registrada.

## Ajuste 160 - 2026-07-23

**KADOSYS Food: Fase 3 - Produtos + Ficha Técnica + motor de custeio automático**

Terceira fase do app Food, com a parte mais nova da plataforma: o
motor de custeio que calcula automaticamente o custo/margem/preço
ideal de cada produto a partir da própria receita.

- **`Food\Core\Custeio`**: classe estática, sem dependência de banco,
  única com a fórmula de markup/margem/lucro/preço ideal - recebe o
  custo de ingredientes já somado + os 8 valores de overhead (energia,
  gás, água, embalagem, etiqueta, mão de obra, taxa operacional,
  desperdício) + margem desejada, e devolve custo total, markup,
  margem, lucro e o preço ideal para balcão/WhatsApp/iFood/delivery
  próprio (os dois últimos "engordados" pela comissão/taxa configurada,
  pra sobrar a mesma margem líquida depois do desconto da plataforma).
- **`custeio_config`**: 1 linha por restaurante com os valores padrão
  de overhead + margem desejada (30% de fábrica) + comissão iFood
  (12%, a mesma da Entrega II) + taxa de pagamento online (3,49%) -
  criada automaticamente na primeira vez que a Fase 3 precisar dela
  (`CusteioConfig::obterOuCriar()`). A tela de edição desses valores
  fica pra Fase 7 (Precificação Inteligente).
- **`produtos`**: cadastro completo (categoria, código/SKU, código de
  barras, descrição, tags, foto, tempo de preparo, peso, rendimento da
  receita, status ativo/pausado/inativo) + preços por canal (balcão,
  WhatsApp, iFood, promoção, delivery próprio) + override opcional de
  cada um dos 8 valores de overhead (sobrescreve o padrão da loja só
  pra aquele produto) + os campos de cache calculados automaticamente.
- **`ficha_tecnica_itens`**: a receita de cada produto - ingrediente +
  quantidade (no rendimento total da receita, não por unidade) + perda
  percentual daquele item no preparo. Tela dedicada
  (`/dashboard/produtos/{id}/ficha-tecnica`) pra adicionar/remover
  ingredientes, com o custeio recalculado e exibido na hora.
- **Recálculo automático em cascata**: `Produto::recalcularCusto()`
  roda sempre que a ficha técnica muda ou o produto é salvo;
  `Produto::recalcularCustoDeProdutosComIngrediente()` roda dentro do
  mesmo request que atualiza o preço de um ingrediente (já ligado em
  `IngredienteController::update()`) - qualquer produto que usa aquele
  ingrediente tem o custo/preço ideal atualizado na hora, sem fila.
- Excluir um ingrediente que está em uso em alguma ficha técnica agora
  é bloqueado com uma mensagem clara (antes seria um erro de FK cru).
- Item no menu lateral "Produtos".
- Testado fim a fim localmente (MariaDB + `php -S` + curl + Playwright):
  cadastro de produto, montagem de ficha técnica com 3 ingredientes,
  conferência manual da matemática do custeio (custo, markup, margem,
  preço ideal por canal todos batendo com o cálculo esperado),
  recálculo automático do custo do produto ao editar o preço de um
  ingrediente usado nele, edição de rendimento recalculando o custo por
  unidade, bloqueio de exclusão de ingrediente em uso, exclusão de
  produto removendo a ficha técnica em cascata.

## Ajuste 159 - 2026-07-23

**KADOSYS Food: Fase 2 - Categorias, Ingredientes, Fornecedores**

Segunda fase do novo app Food, com os 3 primeiros módulos de catálogo
(CRUD completo, seguindo o mesmo padrão de `Barbearias\Controllers\
ProdutoController`, mas já com ícones Bootstrap Icons e `data-confirm`
em vez do emoji/`confirm()` nativo usado originalmente em Barbearias -
Food nasceu moderno na Fase 1 e continua assim em cada módulo novo).

- **`categorias`**: nome + ativo/inativo. Toda loja nova recebe
  automaticamente as 8 categorias padrão (Doces, Bolos, Salgados,
  Bebidas, Combos, Tortas, Cafés, Outros) via `Categoria::seedPadrao()`,
  chamado por `CadastroController::enviar()` logo após criar o usuário
  admin - a lista fica livremente editável dali em diante, não é um
  ENUM fixo. Vai ser usada em `produtos.categoria_id` na Fase 3.
- **`fornecedores`**: nome, contato, telefone/WhatsApp, e-mail, prazo de
  entrega (dias) e forma de pagamento combinada (texto livre, já que
  prazos variam demais pra travar num conjunto fixo de opções).
- **`ingredientes`**: nome, categoria (texto livre do próprio usuário,
  ex. "Laticínios" - diferente da tabela `categorias`, que categoriza o
  produto vendido, não o ingrediente), fornecedor (opcional), código/SKU,
  unidade de medida, preço atual, estoque atual/mínimo (em `DECIMAL`,
  já que ingrediente se compra e usa fracionado, ex. 2,5kg), localização
  no estoque, observação e foto (upload PNG/JPG/WEBP até 5MB, mesmo
  padrão de `ProfissionalController::processarUploadFoto()` do
  Barbearias). Tela de listagem mostra um painel de "Estoque baixo"
  (ingredientes com estoque no ou abaixo do mínimo cadastrado), igual ao
  já usado em Produtos/Barbearias.
- Migration `001_categorias_ingredientes_fornecedores.sql` +
  `install.sql` atualizado (dual-write, mesmo padrão já usado em
  Academias) para que instalações novas recebam tudo de uma vez.
- 3 novos itens no menu lateral do dashboard (Categorias, Ingredientes,
  Fornecedores), entre "Painel" e "Faturas".
- Testado fim a fim localmente (MariaDB + `php -S` + seed + curl +
  Playwright): login, CRUD completo (criar/editar/excluir) dos 3
  módulos, seed automático das 8 categorias no cadastro, alerta de
  estoque baixo, busca com paginação e vínculo ingrediente→fornecedor
  exibido corretamente na listagem.

## Ajuste 158 - 2026-07-23

**KADOSYS Food: novo app (Fase 1 - esqueleto + billing + landing + dashboard base)**

Início de um novo produto na plataforma, **Food** (`food.kadosys.com.br`),
voltado a confeitarias/restaurantes/delivery, com o spec completo (ficha
técnica com custeio automático, estoque de ingredientes, PDV, pedidos
multi-canal, produção, financeiro completo, precificação inteligente
com a fórmula do iFood Entrega II) planejado em 9 fases. Esta é a
Fase 1, seguindo literalmente a mesma arquitetura de Igrejas/Barbearias/
Academias: app autocontido `apps/food` (namespace `Food\`), banco único
`kadosys1_food` (usuário `kadosys1_michael`) com isolamento lógico por
`restaurante_id`.

- **Core clonado do Barbearias** (Auth, Csrf, Database, Documento,
  Mailer, MercadoPagoClient, Middleware, PixEstatico, Request, Router,
  Session, View) - sem `ClienteAuth`/`Disponibilidade` (nenhum portal
  público de autoatendimento nesta entrega).
- **`install.sql`**: `restaurantes` (tenant + billing), `restaurante_faturas`
  (cobrança Pix avulsa), `users`, `password_resets`.
- **`Food\Models\Plano`**: 3 planos (Essencial R$49,90 / Plus R$74,90 /
  Premium R$99,99, faixa combinada com o usuário), trial de 7 dias.
- **Billing completo**: `CadastroController` (trial síncrono / Pix
  avulso com QR / assinatura recorrente via Checkout Pro),
  `WebhookController` (confirmação assíncrona Mercado Pago),
  `AssinaturaController` (tela de bloqueio quando trial vence ou fatura
  atrasa), `FaturaController` (histórico), crons
  `gerar_faturas_pix.php` e `suspender_assinaturas_canceladas.php`.
- **Landing pública rica** com copy específico do domínio (ficha
  técnica automática, taxa do iFood embutida, PDV com Pix, produção em
  tempo real) e os 3 planos.
- **Dashboard já moderno desde o início** (diferente de Barbearias/
  Academias, que ganharam isso depois em retrofit): Bootstrap Icons,
  `kadosys-modal.js`, sidebar colapsável, tema claro/escuro, hover/
  efeitos nos cards, PWA (manifest/service worker/ícones) - painel
  ainda simples (boas-vindas), os módulos de verdade entram nas
  próximas fases.
- `seed_admin.php` pra popular um restaurante + admin de teste.

Testado localmente (MariaDB + PHP embutido + Playwright/Chromium real):
`composer dump-autoload`, landing completa (todas as seções, incluindo
scroll-reveal), cadastro, login → painel → faturas, alternância de
tema, logout - tudo funcionando, `php -l` limpo em todos os arquivos
novos. Ícones aparecem em branco nos screenshots de teste porque o
proxy do sandbox bloqueia o CDN do Bootstrap Icons; em produção
carregam normalmente, do mesmo jeito que já funcionam hoje no Igrejas/
Barbearias/Academias.

As próximas fases (Categorias/Ingredientes/Fornecedores, Produtos+Ficha
Técnica, Estoque+Compras, Clientes+Pedidos, Produção+PDV, Financeiro
completo+Precificação, Dashboard rico+Relatórios, Configurações+
Superadmin) seguem o plano já definido, cada uma virando um PR próprio.

---

## Ajuste 157 - 2026-07-23

**KADOSYS Academias: dashboard modernizado (icones, efeitos, painel rico)**

Pedido do usuário: "academias, modernize dashboard e funções, está muito
simples quero algo tipo igrejas com efeitos e etc". Academias herdou o
tratamento visual do Barbearias (emoji cru, sem hover/transicao, popup
nativo de confirmação) - este ajuste porta pro Academias o mesmo
tratamento que o Igrejas já tinha:

- **Bootstrap Icons no lugar de emoji**: menu lateral, topbar, sino de
  avisos, toggle de tema, menu do usuário e os botões de editar/excluir
  de 9 telas de CRUD (Alunos, Professores, Planos de Matrícula,
  Avaliação Física, Fichas de Treino, Financeiro, Configurações,
  Check-in) agora usam `<i class="bi bi-...">` (CDN, mesmo padrão do
  Igrejas) em vez de caracteres de emoji.
- **`kadosys-modal.js` (popup de confirmação estilizado)**: copiado
  verbatim do Igrejas (é 100% genérico, sem nada específico daquele
  app) e ligado no `dashboard.js` via `initConfirmForms()` - todo
  `onsubmit="return confirm(...)"` nativo virou `data-confirm="..."`
  nas 9 telas de CRUD (exclusão de aluno/professor/plano/avaliação/
  ficha/exercício/lançamento, troca de plano da assinatura, cancelar
  assinatura, fechar caixa, regenerar QR de check-in).
- **Efeitos visuais**: `.kpi-card` e `.modulo-card` ganharam hover
  (lift + sombra + transição), ícone colorido em badge (`.kpi-icon`
  azul/violeta/ciano/verde), seta que aparece no hover dos cards de
  módulo, pulso suave (`prefers-reduced-motion`-aware) no ícone de
  "check-ins agora".
- **Painel enriquecido**: KPIs viraram links coloridos com ícone e
  trend (Check-ins agora - com indicador "ao vivo", Receita de hoje,
  Alunos ativos, Professores ativos); grid de módulos expandido de 4
  pra 10 (todos exceto o próprio Painel); painel de avisos da
  plataforma + top frequência do mês direto na tela inicial (antes só
  dava pra ver o aviso no sininho da sidebar); painel de ações rápidas
  (Novo aluno, Abrir QR de check-in, Nova avaliação física, Lançar no
  financeiro).
- `DashboardController` passou a agregar check-ins em aberto
  (`AcademiaCheckin::presentesAgora`), receita do dia
  (`FinanceiroLancamento::resumoDoPeriodo`), ranking do mês
  (`AcademiaCheckin::rankingDoMes`) e o aviso ativo da plataforma
  (`AcademiaAviso::ativo`) pra alimentar o painel.

Testado localmente (MariaDB + PHP embutido + Playwright/Chromium real):
login, painel com KPIs/módulos/quick actions, popup de confirmação
substituindo o `confirm()` nativo na exclusão de aluno, alternância de
tema claro/escuro, sidebar em modo mobile - tudo funcionando. Os ícones
aparecem em branco nos screenshots de teste porque o proxy do sandbox
bloqueia o CDN do Bootstrap Icons (`jsdelivr.net`); em produção
carregam normalmente, do mesmo jeito que já funcionam hoje no Igrejas.

---

## Ajuste 156 - 2026-07-23

**KADOSYS Kids: atividades "so texto" viram widgets interativos + loja de moedas do Avatar**

Segunda leva pedida pelo usuário depois do Ajuste 155, respondendo a
"os demais módulos infantis estão completos com interação direto no
sistema?" e "implantou a utilização das moedas?":

- **4 atividades que eram só texto viraram widgets de verdade**:
  - "Complete o Versículo": 4 lacunas pra preencher, com correção na
    hora (aceita sem acento/maiúscula) e permite tentar de novo até
    acertar - mesmo padrão de retry do quiz (Ajuste 155).
  - "Ligue o Personagem à sua História": jogo de ligar pares (clicar
    no personagem, depois na história correspondente), com feedback
    de erro sem travar a tentativa.
  - "Desenhe a sua Oração": tela de desenho livre de verdade (canvas
    HTML5, paleta de 8 cores + botão limpar) - antes só pedia pra
    pegar papel e lápis fora do app.
  - O antigo "Caça-palavras: Personagens do Novo Testamento" (que era
    só texto e já tinha ficado redundante desde que os caça-palavras
    de verdade existem como tipo `jogo`) virou um 4º puzzle de
    caça-palavras real, com os mesmos 6 nomes.
  - As 3 primeiras atividades só liberam "Concluir e ganhar XP" quando
    tudo está certo (mesmo gate do quiz); o desenho não tem gate, é só
    uma atividade livre.
  - Nova migration `063_kids_atividades_interativas.sql`.
- **Loja de moedas no Avatar**: além do "Pedir ajuda" do quiz (única
  forma de gastar moedas até aqui), agora dá pra comprar 8 itens
  exclusivos do Avatar (2 por categoria: chapéu, acessório, fundo,
  título) só com moedas, independente do nível - uma segunda via de
  progressão que não compete com o desbloqueio por XP. Nova tabela
  `kids_avatar_compras` (migration `062_kids_avatar_compras.sql`)
  registra a compra permanentemente; `KidsCrianca::gastarMoedas()`
  (Ajuste 155) é reaproveitado pro desconto.

**Testado**: banco local MariaDB do zero (`install.sql` completo,
carregado com `--default-character-set=utf8mb4`) + `php -S` com uma
criança de teste, via Playwright com Chromium real: "Complete o
Versículo" (erro mantém o campo editável, acertar as 4 libera
Concluir), "Ligue o Personagem" (par errado não trava, ligar os 5
pares libera Concluir), "Desenhe a sua Oração" (traço real desenhado
no canvas, sem gate), novo caça-palavras (arraste funcionando), e a
loja do Avatar (compra desconta moedas certinho e o item comprado
passa a aparecer equipável). Regressão: reexecutei os testes do
Ajuste 155 (quiz com explicação/retry, caça-palavras por arraste) e
nada quebrou. Um bug real foi encontrado e corrigido no teste: os
botões de "Comprar" da loja estavam dentro do `<form>` principal de
"Salvar visual" (form aninhado, inválido em HTML) - o clique acabava
enviando o formulário errado. Corrigido movendo a loja pra uma seção
com formulários próprios, fora do form de equipar. Nenhum
erro/warning no log do servidor PHP.

## Ajuste 155 - 2026-07-23

**KADOSYS Kids: caça-palavras por arraste, quiz com explicação bíblica + tentativas ilimitadas, e moedas gastas pra pedir ajuda**

Três correções/melhorias pedidas juntas para o módulo Kids, todas no
mecanismo compartilhado de jogos/quiz (afeta os 3 puzzles de
caça-palavras e os 11 quizzes oficiais de uma vez só):

- **Caça-palavras: seleção por arraste.** O mecanismo antigo (clicar
  na primeira letra, depois na última) reancorava em silêncio sempre
  que o segundo clique não formava uma linha reta válida - o que
  parecia "apagar" a letra já escolhida. Agora dá pra arrastar o
  dedo/mouse letra por letra (pointer events), com destaque ao vivo
  do caminho percorrido; e pra quem prefere tocar em vez de arrastar,
  um toque fora da linha simplesmente é ignorado (a âncora continua
  destacada, sem sumir) - tocar na própria letra âncora de novo
  cancela a seleção. Nova migration `061_kids_caca_palavras_arraste.sql`
  substitui o script de interação dos 3 puzzles existentes.
- **Quiz: retry + explicação bíblica + conclusão só com tudo certo.**
  Antes, a primeira resposta (certa ou errada) travava a pergunta
  para sempre. Agora, errar só mostra feedback e uma explicação
  bíblica curta - a criança pode tentar de novo até acertar. O botão
  "Concluir e ganhar XP" fica escondido até todas as perguntas do
  quiz serem respondidas certas (com uma barra de progresso "X de Y
  respondidas certas"). Nova migration `060_kids_quiz_explicacoes.sql`
  acrescenta o campo `explicacao` em cada uma das perguntas dos 11
  quizzes oficiais.
- **Moedas: pedir ajuda no quiz.** Novo botão "🪙 Pedir ajuda" em cada
  pergunta, que desconta 5 moedas da criança (saldo visível no topo
  da tela) e esconde 2 alternativas erradas (efeito "cartas na
  manga"), via novo endpoint `POST /kids/conteudo/{id}/quiz-ajuda`
  (AJAX, sem recarregar a página). Bloqueia se o saldo for
  insuficiente. Novo método `KidsCrianca::gastarMoedas()` (UPDATE
  condicional, nunca deixa o saldo negativo mesmo em cliques
  concorrentes).

**Testado**: banco local MariaDB do zero (`install.sql`, carregado com
`--default-character-set=utf8mb4` - confirmado que os JSONs dos 11
quizzes com `explicacao` são válidos e sem corrupção de acentuação) +
`php -S` com uma criança de teste (PIN); fluxo completo via curl
(login, endpoint de ajuda descontando moedas corretamente e recusando
quando o saldo acaba) e via Playwright com Chromium real: no quiz,
clicar errado mostra explicação e permite tentar de novo sem travar,
acertar as 4 perguntas revela o botão de concluir (antes escondido) e
concluir avança pro próximo quiz não feito; no caça-palavras, um toque
fora da linha não apaga mais a âncora selecionada, e um arraste
completo pela palavra "PEDRO" marca a palavra como encontrada
corretamente. Um bug real foi encontrado e corrigido durante o teste
(o script do quiz lia o formulário de "Concluir" antes dele existir no
DOM, por vir depois no HTML - corrigido rodando a lógica só após
`DOMContentLoaded`) e outro no `KidsCrianca::gastarMoedas()` (mesmo
parâmetro nomeado `:custo` usado duas vezes na mesma query, inválido
com prepared statements nativos - corrigido com dois parâmetros
distintos). Nenhum erro/warning no log do servidor PHP.

## Ajuste 154 - 2026-07-23

**KADOSYS Academias (Fase 5 - Avaliação física + gráfico de evolução)**

Quinta fase do novo produto Academias (ver Ajuste 152 pra Fase 4 -
ficha de treino). Entra a avaliação física (bioimpedância
simplificada), último diferencial inovador pedido pelo usuário:

- **Avaliação física** (`/dashboard/avaliacoes-fisicas`): a
  equipe/professor registra periodicamente o peso (obrigatório) e,
  opcionalmente, o percentual de gordura e medidas (peito, cintura,
  quadril, braço, coxa) de um aluno, com observação livre.
- **Minha avaliação física** (`/minha-conta/{slug}/avaliacao`): o
  aluno acompanha a própria evolução - gráficos de peso e percentual
  de gordura em SVG simples (mesmo padrão da evolução de carga da
  Fase 4, sem lib externa) mais o histórico completo em tabela. É só
  leitura pro aluno - quem registra é sempre a equipe.
- Painel do aluno ganhou um botão "Minha avaliação física" quando há
  pelo menos uma avaliação registrada.
- Nova migration (`004_avaliacao_fisica.sql`) + `install.sql`
  atualizado com a tabela `avaliacoes_fisicas`.

Com essa fase, os quatro diferenciais inovadores pedidos pelo usuário
na entrega inicial (check-in por QR fixo, ficha de treino com
evolução de carga, ranking/gamificação de frequência, avaliação
física) já estão todos implementados.

**Testado**: banco local MariaDB do zero (`install.sql`), fluxo
completo via curl com sessões reais de equipe e aluno - registro de 3
avaliações físicas em datas diferentes pra um aluno, edição e exclusão
de uma delas, aluno reivindicando o próprio acesso e vendo os gráficos
de evolução (peso caindo de 78,5kg pra 75,0kg renderizado como linha
descendente correta, mesmo com uma avaliação excluída no meio) e o
histórico em tabela, controle de acesso (rota da equipe exige login,
rota do aluno com slug inválido retorna 404). Nenhum erro/warning no
log do servidor PHP.

## Ajuste 153 - 2026-07-23

**KADOSYS Kids: novo jogo "Caça-Nomes" (achar nomes bíblicos na grade de letras) + animações de celebração**

Reportado: faltava um tipo de atividade "pra achar os nomes" na
Biblioteca (um caça-palavras de verdade) e as telas de conclusão
estavam paradas demais pra prender a atenção da criança.

- **Caça-Nomes (3 puzzles novos, tipo "jogo")**: "Os 12 Discípulos",
  "Heróis do Velho Testamento" e "Frutos do Espírito" - uma grade de
  letras (10x10/11x11) com os nomes escondidos na horizontal, vertical
  ou diagonal. A criança toca na primeira letra da palavra e depois na
  última - se formar uma linha reta que bate com algum nome da lista,
  ele é marcado como encontrado (fica verde, risca na lista). Cada
  grade foi gerada por um script auxiliar (garante que as palavras
  cabem sem conflito de letra entre elas), mas a interação em si roda
  100% no navegador, sem chamada nenhuma ao servidor - mesmo padrão já
  usado no jogo da memória e na trivia.
- **Animações de celebração**: o banner "Você ganhou +X XP" que aparece
  ao concluir qualquer atividade agora entra com uma animação de
  "pulo" e solta confetes caindo, com o emoji balançando; acertar uma
  alternativa do quiz ou achar um par no jogo da memória agora dá um
  "pulinho" de destaque na hora. Tudo respeita
  `prefers-reduced-motion` (desliga a animação pra quem pediu menos
  movimento no sistema).
- Nova migration (`059_kids_caca_palavras.sql`) + `install.sql`
  atualizado com os 3 puzzles novos.

**Testado localmente** (MariaDB + servidor PHP + Playwright real):
`install.sql` sozinho já carrega os 3 puzzles novos sem erro; testado
com Chromium de verdade - tocar na primeira e na última letra de uma
palavra válida marca ela como encontrada (grade E lista de palavras),
uma seleção que não forma nenhuma palavra da lista não marca nada por
engano, e o banner de conclusão renderiza os confetes corretamente.

## Ajuste 152 - 2026-07-23

**KADOSYS Academias (Fase 4 - Ficha de treino + evolução de carga)**

Quarta fase do novo produto Academias (ver Ajuste 150 pra Fase 3 -
check-in/checkout). Entra a ficha de treino, o segundo diferencial
pedido pelo usuário:

- **Ficha de treino** (`/dashboard/fichas-treino`): a equipe/professor
  cria uma ficha pra um aluno (nome, objetivo, validade) e monta a lista
  de exercícios direto na tela de edição (nome, grupo muscular, séries,
  repetições, carga sugerida, descanso, observação) - adicionar, editar
  e remover exercício tudo na mesma página, sem telas separadas. Uma
  academia pode ter várias fichas ativas ao mesmo tempo pro mesmo aluno
  (ex.: "Treino A" e "Treino B" alternados), então "ativa" não é
  exclusiva - o toggle só controla se aquela ficha aparece no painel do
  aluno.
- **Treino do dia** (`/minha-conta/{slug}/treino`): o aluno vê as
  fichas ativas dele, entra em uma e marca cada exercício como feito
  informando a carga usada e séries completas. Marcar de novo no mesmo
  dia atualiza o registro em vez de duplicar (`UNIQUE` em
  `treino_execucoes` por exercício+aluno+dia).
- **Evolução de carga**: cada marcação vira um ponto no gráfico de
  evolução daquele exercício, renderizado como um SVG simples (linha +
  ponto no último registro, sem nenhuma biblioteca externa) direto
  abaixo do exercício na tela do aluno.
- Painel do aluno (`/minha-conta/{slug}`) ganhou um botão "Meu treino"
  quando ele tem pelo menos uma ficha ativa.
- Nova migration (`003_ficha_treino.sql`) + `install.sql` atualizado com
  as tabelas `fichas_treino`, `ficha_exercicios` e `treino_execucoes`.

**Testado**: banco local MariaDB do zero (`install.sql`), fluxo
completo via curl com sessões reais de equipe e aluno - criação de
professor/aluno, criação da ficha, adição/edição/remoção de exercícios,
aluno reivindicando o próprio acesso, marcando um exercício como feito
(e confirmando que marcar de novo no mesmo dia atualiza em vez de
duplicar), gráfico SVG de evolução renderizando corretamente com dados
inseridos em datas diferentes, toggle de ficha ativa/inativa refletindo
no painel do aluno, e controle de acesso confirmando que um aluno não
consegue ver a ficha de outro (404). Nenhum erro/warning no log do
servidor PHP.

## Ajuste 151 - 2026-07-23

**KADOSYS Kids: quiz mostra o que já foi feito e avança sozinho pro próximo; corrige avatar sem reação ao clique**

Dois problemas reportados no "modo criança" de Igrejas (`/kids/*`):

- **Quiz pouco profissional**: na lista de quizzes (`/kids/tipo/quiz`) não
  dava pra saber quais a criança já tinha feito, e ao concluir um quiz
  ela caía de volta na mesma tela (`Já concluído`) em vez de seguir pro
  próximo. Agora a listagem mostra um selo "✓" em cada card já
  concluído e uma pílula de progresso ("6 de 11 concluídos"); ao
  concluir um quiz, `KidsAppController::concluir` busca o próximo quiz
  publicado ainda não feito (`KidsConteudo::proximoNaoConcluidoPorTipo`)
  e manda a criança direto pra lá - só volta pro mesmo quiz quando não
  sobra nenhum outro pra fazer. Esse "mostrar concluído" e progresso
  também aparece nos outros tipos de conteúdo (colorir, jogo, etc.),
  não só quiz, reaproveitando a mesma tela.
- **Avatar não reagia a clique**: a causa era puramente visual - o CSS
  do card de cada item (chapéu/acessório/fundo/título) só ficava com a
  borda destacada via uma classe `.selecionado` calculada no servidor
  na hora de renderizar a página, e o rádio interno tem
  `pointer-events: none` (era assim de propósito, só o card em volta é
  clicável) - só que **nenhuma regra CSS reagia ao clique**, então a
  criança clicava, o rádio ficava marcado por baixo dos panos, mas
  nada mudava na tela até apertar "Salvar visual" e a página recarregar
  - parecia simplesmente quebrado. Corrigido com duas partes: (1) CSS
  `.kids-item-card:has(input:checked)` pra destacar a borda na hora,
  sem esperar salvar (mesmo padrão `:has()` já usado em Barbearias/
  Academias); (2) um JS pequeno que atualiza a pré-visualização do
  avatar no topo da tela (boné, acessório, cor de fundo, título) assim
  que a criança clica numa opção, também antes de salvar - "Salvar
  visual" continua sendo o único momento que grava no banco.

**Testado localmente** (MariaDB + servidor PHP com sessão real de
criança via PIN): completar quizzes em sequência confirmando o avanço
automático pro próximo não concluído, o selo de concluído e a pílula de
progresso na listagem, e o fallback (fica na mesma tela) quando todos os
quizzes já foram feitos. Testado o avatar com Playwright (Chromium real,
clicando nos rádios): confirmado que a pré-visualização do fundo muda de
gradiente e o emoji do chapéu aparece imediatamente ao clicar, e que a
regra `:has(input:checked)` do CSS realmente aplica a borda destacada
(verificado isolado, já que o servidor PHP embutido usado no teste local
não serve arquivos `.css` estáticos - limitação conhecida e já
documentada desta sessão, não afeta o Apache real de produção).

## Ajuste 150 - 2026-07-23

**KADOSYS Academias (Fase 3 - Check-in/checkout por QR fixo + gamificação/ranking)**

Terceira fase do novo produto Academias (ver Ajuste 149 pra Fase 2 - CRUD
backbone). Entra a mecânica de presença, o diferencial principal pedido
pelo usuário:

- **QR fixo na entrada**: a academia tem UM único QR
  (`/dashboard/checkin/qr`), pensado pra ficar num tablet ou impresso na
  recepção - não é um QR por aluno. Token regenerável a qualquer momento
  em "Gerar novo QR", invalidando o QR antigo na hora.
- **Check-in/checkout com o mesmo QR**: o aluno, já logado no painel dele
  pelo celular, escaneia esse QR pra entrar; escaneia de novo pra sair. A
  câmera nativa do celular já basta - é só uma URL
  (`/checkin/{slug}/{token}`). Cada leitura fecha ou abre um registro em
  `academia_checkins` (entrada/saída), com a duração do treino mostrada
  no check-out. Se o aluno ainda não estiver logado no navegador, cai
  numa tela de entrar/criar-senha com retorno automático pro mesmo check-in
  assim que autenticar - nenhum passo extra.
- **Área do aluno** (`/minha-conta/{slug}`): login próprio, separado da
  equipe (`Academias\Core\AlunoAuth`, sessão própria). Aluno "reivindica"
  o cadastro que a equipe já criou pra ele (confirma telefone/e-mail,
  cria senha) - ninguém se cadastra do zero por essa tela. Painel mostra
  status atual (dentro/fora), sequência de dias, pontos e histórico
  recente - **sem botão manual de check-in**, de propósito: a prova de
  presença é escanear o QR físico no local.
- **Gamificação**: sequência de dias consecutivos (`streak`) incrementa
  só se o check-in anterior contabilizado foi ontem; check-ins repetidos
  no mesmo dia não inflam a sequência; qualquer intervalo maior reseta
  pra 1. Recorde de sequência nunca diminui. Cada check-in soma 10
  pontos de frequência.
- **Ranking** (`/dashboard/ranking`, espelhado no painel do aluno):
  número de check-ins do mês corrente, calculado ao vivo (sem coluna de
  contagem pra não precisar de reset mensal).
- **Painel da equipe** (`/dashboard/checkin`): quem está na academia
  agora (check-ins em aberto) + histórico paginado.
- Nova migration (`002_checkins.sql`) + `install.sql` atualizado com a
  tabela `academia_checkins`.

**Testado**: banco local MariaDB do zero (`install.sql`), fluxo completo
via curl com sessões reais de equipe e aluno - login da equipe, geração
do QR, cadastro de aluno pela equipe, aluno reivindicando o próprio
acesso, "escaneando" o QR (check-in, depois check-out com duração),
segundo check-in no mesmo dia confirmando que a sequência não infla,
conferência direta no banco de `academia_checkins` e das colunas de
gamificação em `alunos`, painéis "quem está dentro"/histórico/ranking da
equipe, regeneração de QR invalidando o token antigo, e o fluxo de
"escanear sem estar logado" completando o check-in pendente
automaticamente após o login. Nenhum erro/warning no log do servidor
PHP.

## Ajuste 149 - 2026-07-22

**KADOSYS Academias (Fase 2 - CRUD backbone: Alunos, Professores, Planos de Matrícula, Financeiro, Configurações)**

Segunda fase do novo produto Academias (ver Ajuste 148 pra Fase 1 -
esqueleto/cobrança/site público). Entra o backbone operacional:

- **Alunos**: CRUD completo (nome, telefone, e-mail, CPF, data de
  nascimento, plano de matrícula, início/vencimento da matrícula,
  status, objetivo, restrições de saúde). Já com as colunas de
  gamificação/check-in no model (`streakAtual`, `pontosFrequencia`
  etc.) que a Fase 3 vai passar a escrever.
- **Professores**: CRUD (nome, especialidade, contato, ativo/inativo).
- **Planos de Matrícula**: CRUD (nome, preço, duração em dias,
  descrição) - o catálogo que a academia oferece pros próprios alunos,
  sem relação com o plano de assinatura da academia com a Kadosys.
- **Financeiro**: caixa (abrir/fechar) + lançamentos de receita/despesa,
  mesmo padrão já usado em Barbearias, com um campo extra opcional
  `aluno_id` pra marcar quando o lançamento é o pagamento de uma
  mensalidade.
- **Configurações**: perfil da academia (nome, telefone, cor de
  destaque, logo), chave Pix própria (pra receber mensalidade na
  recepção), troca de plano/cancelamento de assinatura com a Kadosys,
  equipe (criar/editar/excluir acesso). Removida a seção "modo de
  atendimento" (fila/agendamento) do template original de Barbearias -
  não existe esse conceito em Academias.
- Sidebar do dashboard atualizada com os 5 novos itens de menu.
- Painel inicial (`/dashboard`) agora mostra números reais (alunos
  ativos, total de alunos, professores ativos, planos de matrícula)
  em vez do texto de boas-vindas genérico da Fase 1.
- Nova migration (`001_caixas_financeiro.sql`) + `install.sql`
  atualizado com as tabelas `caixas` e `financeiro_lancamentos`.

**Testado**: banco local MariaDB do zero (`install.sql`), fluxo
completo via curl com sessão real (login, CSRF, criar plano de
matrícula, professor, aluno vinculado ao plano, lançamento financeiro
vinculado ao aluno, atualizar perfil da academia com cor de destaque) -
cada escrita confirmada direto no banco, sem nenhum erro/warning no
log do servidor PHP.

## Ajuste 148 - 2026-07-22

**Novo produto: KADOSYS Academias (Fase 1 - esqueleto, cobrança e site público)**

Início de um novo app na plataforma, `apps/academias`, gestão completa
para academias (check-in por QR Code, ficha de treino, avaliação
física, ranking de frequência - módulos completos vêm nas próximas
fases). Segue exatamente a mesma receita já usada em Barbearias: app
100% autocontido (`Academias\*`, próprio `composer.json`/`vendor`,
próprio `src/Core/*`), banco único compartilhado `kadosys1_academias`
com isolamento lógico por `academia_id` em cada tabela (sem banco por
cliente, ao contrário do Igrejas).

**O que entra nesta Fase 1:**
- Core completo clonado de Barbearias (Auth, Csrf, Database, Documento,
  Mailer, MercadoPagoClient, Middlewares, PixEstatico, Request, Router,
  Session, View) + `AlunoAuth` (sessão separada pro futuro painel do
  aluno, mesmo padrão do `ClienteAuth` de Barbearias).
- Schema inicial (`install.sql`): `academias` (tenant), `academia_faturas`,
  `academia_avisos` (já preparado pra integração futura com o Super
  Admin), `users`, `password_resets`, `unidades`, `planos_matricula`,
  `professores`, `alunos` (já com as colunas de gamificação/check-in
  que a Fase 3 vai usar, pra não precisar de outra migration só por
  isso).
- Cobrança da própria academia com a Kadosys: mesmo motor de Barbearias
  - trial grátis (5 dias) ou Pix/cartão recorrente via Mercado Pago,
  cadastro público, webhook, tela de bloqueio por pagamento pendente,
  histórico de faturas, crons de renovação Pix e suspensão por
  cancelamento.
- Site público rico (hero, seção "por que usar", recursos, funcionalidades,
  planos, FAQ, footer) com conteúdo genuíno de academia (não é o texto
  de Barbearias com find-and-replace) - já reaproveitando o mesmo
  tratamento visual reforçado que Barbearias ganhou no ajuste anterior.
- Dashboard já moderno desde o início (sidebar colapsável, glass
  effects, white-label por logo/cor, tema claro/escuro) - em vez de
  ganhar isso depois, como aconteceu com Barbearias.
- PWA (`manifest.json` + `sw.js` + ícones) e `seed_admin.php` pra criar
  uma academia de teste via linha de comando.

**Fora de escopo nesta Fase 1** (chega nas próximas): CRUD de Alunos/
Professores/Planos de Matrícula, o check-in/checkout por QR Code fixo
(mecânica: a academia deixa um QR fixo na entrada, o aluno já logado no
painel dele escaneia pra entrar e escaneia de novo pra sair), ficha de
treino com evolução de carga, avaliação física, painel do aluno e
integração com o Super Admin (listagem unificada de sites + avisos da
plataforma).

**Testado**: `php -l` em todos os arquivos PHP, banco local MariaDB com
`install.sql` + `seed_admin.php`, fluxo completo via curl (landing,
cadastro, login, dashboard, faturas, tela de assinatura) sem nenhum
erro/warning no log do servidor PHP.

**Deploy do novo app (passo novo, igual já foi feito pro Igrejas/
Barbearias/Super Admin)**: criar um subdomínio novo no cPanel (ex.:
`academias.kadosys.com.br`) apontando pra `apps/academias/public`,
rodar `composer install` (ou confiar no autoload mínimo já commitado),
configurar as variáveis de ambiente (banco `kadosys1_academias` +
credenciais do Mercado Pago, mesma conta já usada em Igrejas/
Barbearias) e rodar `apps/academias/database/install.sql` uma única
vez no banco novo.

## Ajuste 147 - 2026-07-22

**KADOSYS Kids: substitui conteudos "casca" da biblioteca oficial por conteudo de verdade (sem exigir upload)**

Reportado: varios conteudos oficiais KADOSYS na Biblioteca (colorir,
jogo, slide, hq, video, audio, pdf) mostravam so um texto interno tipo
*"Adicione o link do vídeo em Kids > Conteúdos > Editar."* como se
fosse o conteúdo - a criança abria, lia essa instrução sem sentido pra
ela, e clicava em "Concluir" ganhando XP de graça, sem fazer nada de
verdade.

- **Colorir (7 itens)**: cada um agora e um SVG interativo de verdade -
  a criança clica numa área do desenho e escolhe a cor numa paleta,
  tudo embutido direto no `texto_conteudo` (sem nenhum arquivo de
  imagem). Novo item: "Jonas e a Baleia" (faltava no lote anterior).
- **Jogo (4 itens)**: "Monte a Arca de Noé" e "Memória Bíblica" viraram
  jogos da memória reais (8 pares, embaralha a cada partida); "Corrida
  da Fé" virou uma trivia de 8 perguntas com contador de estrelas;
  "Bingo dos Frutos do Espírito" já tinha uma atividade real pra
  brincar em família - só tirou a frase de instrução interna que vazava
  antes dela.
- **Slide (2 itens)**: "Os 12 Discípulos de Jesus" e "Mapa da Terra
  Santa" viram apresentações navegáveis (anterior/próxima + contador).
- **HQ (3 itens)**: "José no Egito", "Daniel" e "A Vida de Jesus" viram
  quadrinhos reais em painéis (cena + legenda), lidos direto na tela.
- **PDF (3 itens)**: "Caderno de Atividades: Os 10 Mandamentos",
  "Cartilha: Livros da Bíblia" e "Diploma Kids KADOSYS" agora são
  arquivos PDF reais, gerados e versionados em
  `apps/igrejas/public/assets/kids/pdfs/` - **asset estático do
  próprio app, não é upload por igreja** (não requer nenhuma ação
  manual da equipe).
- **Vídeo/áudio (8 itens removidos)**: sem nenhum link real de
  vídeo/áudio licenciado pra usar, a alternativa honesta era remover
  esses itens do catálogo oficial em vez de deixar a instrução interna
  visível pra criança. Ficam disponíveis pra reintroduzir no futuro
  quando houver conteúdo de verdade pra linkar.
- **Views**: `kids/show.php` e `dashboard/kids/biblioteca/show.php`
  agora renderizam `texto_conteudo` como HTML confiável **somente**
  quando `origem = 'kadosys'` e `tipo` é colorir/jogo/slide/hq (conteúdo
  só editável via migração, nunca pelo formulário da igreja - sem
  abrir brecha de XSS pra conteúdo cadastrado pela própria igreja, que
  continua sempre escapado).
- Migração 058 (`kids_conteudos_reais.sql`) atualiza igrejas já
  instaladas; o mesmo conteúdo também foi anexado ao final do
  `install.sql` pra instalações novas já nascerem certas.

**Testado localmente** (MariaDB + servidor PHP + Playwright real):
`install.sql` sozinho já produz o catálogo final correto (85 itens, 0
placeholders remanescentes, 0 vídeo/áudio kadosys); testado clicar
numa parte do desenho de colorir (preenche a cor), jogo da memória
(carta vira ao clicar), trivia (marca acerto e soma estrela),
slideshow (avança e atualiza contador) e o link do PDF apontando pro
arquivo certo.

---

## Ajuste 146 - 2026-07-22

**Telao: mostra "Carregando vídeo..." em vez de tela muda enquanto o video nao inicia**

- Reportado ao vivo: telao ficava preto/mudo por 20-30s+ sem NENHUMA
  pista visivel de que algo estava acontecendo, so um F5 manual
  resolvia. O mecanismo de auto-recuperacao (reload automatico apos
  timeout, ja existente ha varios ajustes) continua funcionando igual -
  so que agora, assim que a vigia de reproducao comeca (ver
  `agendarChecagemReproducao` em `telao.js`), a tela mostra
  "Carregando vídeo..." (aviso neutro, cinza, distinto da mensagem de
  erro em branco/negrito) em vez de ficar 100% muda enquanto espera.
- **Investigacao**: nao foi possivel reproduzir com um vídeo real do
  YouTube (este ambiente de teste nao tem acesso a youtube.com,
  bloqueado por politica de rede) nem confirmar a causa raiz exata sem
  o log do console do navegador no momento do travamento (ainda nao
  recebido). O codigo ja tem bastante logica de auto-recuperacao
  acumulada de ajustes anteriores (timeout de buffering, timeout de
  "sem resposta", tratamento de excecao, reload automatico unico por
  video/sessao) - os limites de tempo foram mantidos como estao
  (comentarios no codigo explicam que reduzi-los ja causou regressao
  antes: video que ia comecar sendo interrompido no meio). Testado
  localmente (Playwright + servidor real) com o cenario de YouTube
  genuinamente bloqueado: confirmado que o reload automatico existente
  ainda dispara normalmente (nao foi quebrado), e que o novo aviso
  "Carregando vídeo..." renderiza certinho, visivel e legivel.
- **Proximo passo se persistir**: com o log do console (F12 > Console)
  no exato momento do travamento, da pra identificar com certeza qual
  caminho de recuperacao esta (ou nao esta) disparando.

---

## Ajuste 145 - 2026-07-22

**KADOSYS Kids: corrige quiz revelando a resposta certa antes da crianca responder**

- `resources/views/kids/show.php`: a tela de quiz do modo crianca
  (`/kids/conteudo/{id}`) mostrava um ✅ fixo do lado da alternativa
  correta assim que a pagina carregava - a crianca via a resposta antes
  de tentar. Trocado por alternativas clicaveis (`<button>`), sem
  nenhuma marca visual antes do clique; so depois que a crianca escolhe
  uma alternativa o JS revela ✅ na certa e ❌ na escolhida (se errada) e
  trava o restante das opcoes daquela pergunta.
- CSS novo em `kids-biblioteca.css` pros estados do botao (hover antes
  de responder, `.correta`/`.errada` depois).
- A tela de preview do quiz no painel administrativo
  (`dashboard/kids/biblioteca/show.php`, vista pela equipe/professor)
  continua mostrando a resposta certa de propósito - não foi alterada.

**Testado localmente** (MariaDB + servidor PHP embutido): conferido no
HTML retornado por `/kids/conteudo/{id}` que nenhuma alternativa vem
marcada como certa antes do clique (so o atributo `data-correta`, usado
pelo JS, sem nenhum indicio visual), e que apos clicar numa alternativa
a certa fica verde e a errada (se for o caso) fica vermelha.

---

## Ajuste 144 - 2026-07-22

**KADOSYS Kids: Avatar da Crianca (nivel, cosmeticos e titulos ganhos por participacao)**

- Migracao 144 (nova, `apps/igrejas/database/migrations/057_kids_avatar.sql`,
  ja replicada em `install.sql`): 4 colunas novas em `kids_criancas`
  (`avatar_chapeu`, `avatar_acessorio`, `avatar_fundo`, `avatar_titulo`)
  guardando so o slug do item ATUALMENTE equipado em cada categoria -
  o xp que ja existia (ganho a cada check-in ou conteudo concluido)
  continua sendo a unica fonte de progresso, sem tabela nova de
  "desbloqueios".
- Novo model `Igrejas\Models\KidsAvatar`: catalogo estatico (emoji +
  nome + nivel minimo) de 10 chapeus, 10 acessorios, 6 fundos e 8
  titulos (incluindo "Pequeno Missionário" e "Guardião dos Versículos"),
  alem da formula de nivel a partir do xp acumulado (20 niveis, curva
  pensada pra o primeiro nivel vir em 1-2 check-ins e os seguintes
  exigirem semanas de participacao constante).
- `KidsCrianca::atualizarAvatar()`: salva os itens escolhidos, mas
  sempre reconferindo no servidor se o nivel atual realmente desbloqueou
  aquele slug antes de gravar - um POST forjado tentando equipar um
  item ainda bloqueado e silenciosamente ignorado (volta pra "nenhum"
  naquela categoria), nunca aceito.
- Nova tela **/kids/avatar** (mesmo "modo crianca" por PIN do resto do
  modulo Kids): palco com o avatar (chapeu + boneco + acessorio,
  fundo em gradiente conforme o item equipado), barra de XP/nivel, e
  uma galeria por categoria - itens bloqueados aparecem escurecidos com
  cadeado e o nivel necessario. Acesso pelo botao "Meu Avatar" no topo
  da home do modo crianca.

**Testado localmente** (MariaDB + servidor PHP embutido, install.sql
completo): login por PIN, tela do avatar mostrando nivel/itens corretos
pro xp da crianca de teste, equipar um item desbloqueado (salva
certinho), tentativa forjada de equipar um item de nivel superior ao
atual (rejeitada no servidor, campo volta pra vazio), e um check-in
novo continuando a somar xp e recalcular o nivel normalmente.

---

## Ajuste 143 - 2026-07-16

**Novo app: KADOSYS Super Admin (painel unico cruzando Igrejas + Barbearias)**

- Novo app standalone **`apps/superadmin`** (mesmo padrao sem framework
  dos outros dois - Core proprio, sem nenhuma dependencia direta de
  `apps/igrejas` ou `apps/barbearias`), com login por chave mestra
  (bcrypt), igual ao painel `/plataforma` do Igrejas.
- **`/sites`**: lista TODAS as igrejas e barbearias cadastradas numa
  tabela unica (produto, nome, plano, status, criado em, ultimo
  acesso), com busca por nome/slug. Cada linha pode ser:
  - **Suspensa/Reativada**: so muda o `status` no banco de cada
    produto (bloqueia o acesso, sem apagar nada).
  - **Excluida (permanente, irreversivel)**: exige digitar o nome
    exato do site numa tela de confirmacao antes de liberar o botao.
    Barbearia: `DELETE` simples (toda a cascata de agendamentos,
    clientes, financeiro etc. e resolvida pelas FKs `ON DELETE CASCADE`
    ja existentes). Igreja: exclui banco de dados e usuario MySQL no
    cPanel (melhor esforco, reaproveitando as mesmas credenciais/token
    ja configurados pro provisionamento do Igrejas) e sempre remove o
    registro central no final, mesmo se o cPanel falhar - so avisa que
    o subdominio precisa ser removido manualmente depois (essa
    hospedagem nao expoe exclusao de subdominio via API).
- **`/avisos`**: publica uma mensagem no sino de notificacoes de
  Igrejas, Barbearias ou Todos - escreve direto em `plataforma_avisos`
  (Igrejas) e/ou na tabela nova `barbearia_avisos` (Barbearias, ver
  abaixo), reaproveitando a mesma logica de "um aviso ativo por vez"
  ja usada no Igrejas.

**Novo no Barbearias, como pre-requisito**: o Barbearias nao tinha
nenhum sistema de aviso/notificacao ainda - foi adicionada a tabela
`barbearia_avisos` (migration 014, ja incluida tambem em
`install.sql`) e um sino no rodape da sidebar do painel, mostrando o
aviso ativo (se houver) publicado pelo Super Admin.

**Acao manual pendente no banco `kadosys1_barbearias`** (se
`install.sql` ja rodou antes): rodar
`apps/barbearias/database/migrations/014_barbearia_avisos.sql` uma
unica vez.

**Deploy do novo app (unico passo realmente novo neste ajuste)**:
1. Criar um subdominio novo no cPanel (ex.: `admin.kadosys.com.br`)
   apontando pra `apps/superadmin/public` (mesmo esquema ja usado pro
   Igrejas/Barbearias - subdominio aponta pra dentro da pasta
   `public/` do app).
2. Configurar as variaveis de ambiente desse subdominio (MultiPHP INI
   Editor, ou `config/*.local.php` direto no servidor):
   - `SUPERADMIN_SENHA_HASH` - hash bcrypt de uma chave mestra nova,
     gerado com
     `php -r "echo password_hash('sua-chave-aqui', PASSWORD_BCRYPT);"`.
   - `SUPERADMIN_IGREJAS_DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` -
     mesmas credenciais do banco central do Igrejas
     (`kadosys1_igrejas`), so pra leitura/escrita de
     `plataforma_tenants`/`plataforma_avisos`.
   - `SUPERADMIN_BARBEARIAS_DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` -
     mesmas credenciais do banco do Barbearias
     (`kadosys1_barbearias`).
   - `CPANEL_HOST/PORT/USERNAME/API_TOKEN/ROOT_DOMAIN` - as MESMAS
     variaveis ja configuradas pro Igrejas (reaproveitadas, nenhum
     token novo precisa ser gerado).
3. Rodar `composer install` na pasta `apps/superadmin` se o servidor
   suportar Composer (o app ja sobe com um `vendor/` minimo commitado,
   igual aos outros dois, pra funcionar mesmo sem isso).

**Testado localmente** (MariaDB + servidor PHP embutido, bancos
espelhando os schemas de producao): login com chave mestra, listagem
unificada mostrando igrejas e barbearias juntas, suspender/reativar
dos dois lados, exclusao com nome errado (rejeitada) e nome certo
(efetivada - barbearia com cascata confirmada no banco, igreja com
aviso correto de cPanel nao configurado), publicacao de aviso "Todos"
gravando nos dois bancos, aviso "Somente Igrejas" sem afetar
Barbearias, encerrar aviso manualmente, e o sino do painel Barbearias
mostrando o aviso publicado (com acentuacao correta).

---

## Ajuste 142 - 2026-07-15

**Barbearias: assinaturas de cliente (pacotes pré-pagos de atendimentos por mês)**

- Nova tela **`/dashboard/assinaturas-clientes`**:
  - **Planos**: cadastro simples (nome, preço mensal, quantos
    atendimentos por mês o pacote inclui) e exclusão.
  - **Assinar cliente**: busca por nome/telefone e assina num plano -
    cada cliente só pode ter **uma assinatura ativa por vez**.
  - **Assinantes ativos**: lista com o uso do ciclo atual (ex.: "1/4")
    e botão de cancelar.
- **Sem cron nenhum**: o ciclo mensal é ancorado na data em que o
  cliente assinou (não no dia 1), e o "início do ciclo atual" é
  sempre recalculado na hora com base na data de hoje - não existe
  job nenhum "resetando" saldo no fim do mês.
- **Consumo integrado ao fluxo de pagamento existente**: na tela de
  concluir atendimento (`/dashboard/agendamentos/{id}/pagamento`), se
  o cliente tiver assinatura ativa com saldo no ciclo, aparece um
  aviso com o saldo e um botão **"Concluir usando a assinatura (sem
  cobrar)"** como alternativa ao pagamento avulso de sempre - esse
  caminho conclui o atendimento e registra o consumo, mas **não gera
  lançamento financeiro** (a mensalidade já foi cobrada fora do
  sistema, mesma lógica manual do resto do financeiro/fidelidade).
- Sem saldo suficiente no ciclo, a opção simplesmente não aparece (o
  cliente segue pagando avulso normalmente).

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/013_assinaturas_clientes.sql`
uma única vez - cria as tabelas `assinatura_planos`,
`assinaturas_clientes` e `assinatura_consumos`, sem afetar dados
existentes.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): plano "4 atendimentos por R$120" cadastrado;
cliente assinado (bloqueado corretamente ao tentar assinar de novo
enquanto já tinha assinatura ativa); novo agendamento do cliente
mostrou "4 de 4 restantes" na tela de pagamento; ao concluir usando a
assinatura, o agendamento foi marcado concluído, um consumo foi
registrado e **nenhum** lançamento financeiro foi criado (confirmado
via consulta direta); listagem de assinantes ativos passou a mostrar
"1/4"; cancelamento de assinatura funcionou e mudou o status pra
"cancelada". Nenhum warning/erro no log do PHP durante os testes.

---

## Ajuste 141 - 2026-07-15

**Barbearias: white-label (logo e cor de destaque por barbearia)**

- Em **Configurações → Dados da barbearia**: novo campo de **logo**
  (upload PNG/JPG/WEBP, até 5MB, mesmo padrão de validação usado pra
  foto de profissional) e um seletor de **cor de destaque**
  (`<input type="color">`).
- Quando cadastrados, logo e cor substituem a marca genérica "KADOSYS
  Barbearias" em três lugares:
  - **Dashboard** (sidebar da equipe);
  - **Página pública de agendamento** (`/agendar/{slug}`);
  - **Área do cliente** (`/minha-conta/{slug}/*`).
  - A cor é aplicada via `<style>:root{--primary:...}</style>`
    injetado no `<head>`, reaproveitando o mesmo token de cor já usado
    em todo o CSS (sem precisar duplicar nenhuma regra).
- De brinde: como as páginas do cliente (login, cadastro, agendamento,
  painel) agora mostram a marca da própria barbearia, os links de
  marketing "Planos / Entrar / Testar grátis" (que eram voltados a
  donos de barbearia interessados em assinar, e apareciam sem sentido
  na tela de um cliente final) só aparecem mais na landing page
  pública de vendas - nas páginas de uma barbearia específica, o menu
  de marketing simplesmente não é mais renderizado.
- Sem logo/cor cadastrados, tudo continua exatamente como antes
  (marca genérica KADOSYS).

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/012_white_label.sql` uma única
vez - adiciona `barbearias.logo_path` e `barbearias.cor_primaria`
(ambos `NULL` por padrão), sem afetar dados existentes.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): logo enviado e cor `#E11D48` salva - confirmado
que `<style>:root{--primary:#E11D48}</style>` e a tag `<img>` do logo
aparecem corretamente nas três telas (dashboard, agendamento público,
login da área do cliente); a landing page pública (sem barbearia no
contexto) continuou com a marca genérica e os links de marketing
intactos; cor inválida (`nao-e-cor`) foi rejeitada com mensagem de
erro e não alterou o valor salvo. Nenhum warning/erro no log do PHP
durante os testes.

---

## Ajuste 140 - 2026-07-15

**Barbearias: PWA (painel instalável, ícone na tela inicial)**

- O **dashboard** (`/dashboard/*`) agora é um PWA instalável: manifesto
  (`public/manifest.json`), ícone próprio (gerado em azul/roxo com um
  "K" estilizado, nas variações 192px/512px/512px maskable/apple-touch-icon)
  e `theme-color` combinando com o visual escuro do app - em
  celular/tablet o navegador oferece "Adicionar à tela inicial", e o
  painel abre em tela cheia como um app nativo, sem barra de
  endereços.
- Service worker (`public/sw.js`) registrado só no dashboard: cacheia
  **apenas assets estáticos** (CSS/JS/ícones), nunca páginas HTML -
  como o app é multi-tenant e cada página depende da sessão/barbearia
  logada, cachear HTML serviria dados errados pra quem abrisse o app
  offline depois trocando de conta. O ganho real é abrir mais rápido
  em conexão ruim (o "esqueleto visual" já vem do cache) - os dados em
  si sempre vêm da rede.
- Escopo desta entrega: só o dashboard (uso interno da equipe). A
  página pública de agendamento não virou PWA porque cada barbearia
  teria que ter nome/ícone próprios num manifesto por tenant, o que é
  um escopo bem maior - fica pra uma entrega futura se fizer sentido.

**Sem migração pendente** - só arquivos estáticos novos + um `<link>`/
registro de JS no layout do dashboard.

**Testado localmente**: como o servidor embutido do PHP (`php -S`) não
reproduz sozinho o comportamento do `.htaccess` do Apache (servir
arquivo estático existente sem passar pelo roteador da aplicação), o
teste usou um router script temporário só de desenvolvimento
(descartado depois, não faz parte do commit) que replica exatamente a
regra do `.htaccess` já em produção. Com isso confirmado: `manifest.json`
retorna JSON válido com `Content-Type: application/json`; `sw.js`
retorna `Content-Type: application/javascript`; os 4 ícones carregam
como PNG válido; a página `/dashboard` inclui as tags `<link rel="manifest">`,
`<link rel="apple-touch-icon">` e `<meta name="theme-color">`
corretamente. Nenhum erro no log do PHP durante os testes.

---

## Ajuste 139 - 2026-07-15

**Barbearias: painel de recepção (tela cheia pra TV/tablet do salão)**

- Nova tela **`/dashboard/recepcao`** (item "Recepção (TV)" no menu,
  abre em nova aba): tela em **tela cheia**, sem a barra lateral do
  dashboard, pensada pra ficar aberta continuamente numa TV/tablet na
  recepção da barbearia.
- Mostra a **fila de atendimentos do dia** (não-cancelados), ordenada
  por horário, com status inferido a partir dos dados existentes (a
  aplicação não tem um status de "em atendimento"):
  - **Concluído** (verde) - atendimento já finalizado;
  - **Atrasado** (vermelho) - ainda agendado, mas o horário já passou;
  - **Aguardando** (neutro) - agendado pro futuro.
  - O próximo atendimento aguardando ganha um destaque visual (borda
    colorida).
- Relógio no topo atualiza sozinho a cada segundo (só o texto, via
  JS) e a página inteira recarrega sozinha a cada 60s (meta refresh
  simples, sem JS de polling) pra manter a fila em dia.
- Se a barbearia tiver mais de uma unidade ativa, aparecem abas pra
  filtrar a fila por unidade.

**Sem migração pendente** - reaproveita a tabela `agendamentos` já
existente.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): três agendamentos no dia (um concluído, um no
passado ainda como "agendado", um no futuro) resultaram exatamente
nos três status esperados (Concluído/Atrasado/Aguardando), na ordem
certa por horário, com o atendimento futuro mais próximo destacado
como "próximo". Nenhum warning/erro no log do PHP durante os testes.

---

## Ajuste 138 - 2026-07-15

**Barbearias: programa de fidelidade (pontos por atendimento + resgate de recompensas)**

- Nova tela **`/dashboard/fidelidade`**:
  - **Configuração**: liga/desliga o programa e define quantos pontos
    o cliente ganha por real gasto (desativado por padrão -
    `fidelidade_pontos_por_real = NULL`).
  - **Recompensas**: cadastro simples (nome + pontos necessários) e
    exclusão.
  - **Resgate**: busca o cliente por nome/telefone, mostra o saldo de
    pontos e as recompensas que ele já pode trocar - o débito de
    pontos é **atômico** (mesmo padrão do estoque de produtos: o
    próprio `UPDATE` já checa se ainda há saldo suficiente, evitando
    saldo negativo em resgates simultâneos) e fica registrado no
    **histórico** (extrato de ganhos/resgates) logo abaixo.
- **Pontos concedidos automaticamente**: ao registrar o pagamento de
  um atendimento (`AgendamentoController::pagamento`), se o programa
  estiver ativo, o cliente ganha `floor(valor_pago × pontos_por_real)`
  pontos, com um lançamento correspondente no extrato.
- **Área do cliente** (`/minha-conta/{slug}`): mostra o saldo de
  pontos no topo do painel, quando o programa está ativo.

**Bug real encontrado e corrigido durante o teste** (pré-existente,
não introduzido por este ajuste, mas descoberto ao reaproveitar o
mesmo padrão de busca): o banco roda com `PDO::ATTR_EMULATE_PREPARES
= false` (prepares nativos do MySQL), e nesse modo **reutilizar o
mesmo parâmetro nomeado mais de uma vez na mesma query
(`:busca` repetido em `OR`) quebra com
`SQLSTATE[HY093]: Invalid parameter number`**. Isso já afetava em
produção qualquer busca por texto nas telas de **Clientes**,
**Profissionais** e **Agendamentos** (a busca sempre dava erro 500
assim que alguém digitava um termo) - corrigido nos três lugares
(`Cliente::paginate`, `Profissional::paginate`, `Agendamento::paginate`)
trocando pra um placeholder nomeado distinto por ocorrência
(`:busca`, `:busca2`, `:busca3`), todos apontando pro mesmo valor.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/011_fidelidade.sql` uma única
vez - adiciona `barbearias.fidelidade_pontos_por_real` e
`clientes.pontos_fidelidade`, e cria as tabelas
`fidelidade_recompensas` e `fidelidade_movimentos`, sem afetar dados
existentes.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): ativado o programa (1 ponto por real), pagamento
de R$87,00 concedeu 87 pontos ao cliente; recompensa de 50 pontos
cadastrada e resgatada com sucesso (saldo caiu pra 37, extrato
registrou ganho e resgate corretamente); nova tentativa de resgate da
mesma recompensa (37 < 50) foi bloqueada tanto na interface (botão
desabilitado) quanto no servidor (requisição direta não debitou
nada); saldo de pontos apareceu corretamente na área do cliente após
login. Busca nas telas de Clientes, Profissionais e Agendamentos
retestada depois da correção do bug de parâmetro duplicado - as três
voltaram a status 200. Nenhum warning/erro remanescente no log do PHP.

---

## Ajuste 137 - 2026-07-15

**Barbearias: relatórios consolidados (faturamento, agendamentos, ticket médio, ocupação)**

- Nova tela **`/dashboard/relatorios`**, com filtro de período (padrão
  o mês atual):
  - **Receitas/despesas/saldo** do período (reaproveitando o mesmo
    resumo já usado no Financeiro);
  - **Agendamentos por status** (total, concluídos, agendados,
    cancelados);
  - **Ticket médio**: faturamento dos atendimentos concluídos dividido
    pela quantidade (mesma base de cálculo usada na comissão - valor
    pago no PDV quando existe, senão o preço atual do serviço);
  - **Taxa de ocupação por profissional**: horas ocupadas (soma da
    duração dos atendimentos concluídos) contra uma **estimativa** de
    horas disponíveis no período, calculada a partir do
    expediente cadastrado (dias/horário de atendimento) - não desconta
    férias/folgas pontuais (bloqueios de agenda), então é uma
    aproximação de capacidade, não um cálculo exato; isso está
    explicado na própria tela.
- Sem gráficos - só números e uma tabela, para manter simples e rápido
  de carregar mesmo num período longo.

**Sem migração pendente** - reaproveita tabelas já existentes
(`agendamentos`, `financeiro_lancamentos`, `profissionais`).

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): com 2 atendimentos concluídos no mês (R$55 pago
no PDV + R$30 no preço de tabela) e uma venda de produto de R$90,00,
o relatório mostrou receitas R$145,00, ticket médio R$42,50 (85/2) e 2
agendamentos concluídos - todos batendo com o cálculo manual esperado;
tabela de ocupação mostrou os dois profissionais ativos com horas
disponíveis coerentes com os dias/horário cadastrados de cada um;
filtro pra um período sem nenhum dado (mês anterior) retornou tudo
zerado sem erro. Nenhum warning/erro no log do PHP durante os testes.

---

## Ajuste 136 - 2026-07-15

**Barbearias: produtos e estoque (venda avulsa + baixa automática)**

- Nova tela **`/dashboard/produtos`**: cadastro de produtos (nome,
  preço, estoque atual, estoque mínimo), com alerta de **estoque
  baixo** no topo da tela (produtos com estoque no ou abaixo do
  mínimo cadastrado).
- **Venda avulsa** direto na listagem: escolhe quantidade e forma de
  pagamento, sem precisar de agendamento - a baixa no estoque é
  **atômica** (o próprio `UPDATE` já checa se ainda há estoque
  suficiente, evitando estoque negativo mesmo em duas vendas
  simultâneas do mesmo produto) e, se a baixa funcionar, gera
  automaticamente um lançamento de receita no financeiro (dentro do
  caixa aberto, se houver um) - aparece normalmente na tela
  `/dashboard/financeiro` como qualquer outro lançamento.
- `financeiro_lancamentos` ganhou `produto_id`/`quantidade` (mesmo
  padrão já usado com `agendamento_id` pro pagamento de atendimento),
  permitindo rastrear qual venda de produto gerou qual lançamento.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/010_produtos.sql` uma única vez
- cria a tabela `produtos` e adiciona `financeiro_lancamentos.produto_id`/`quantidade`,
sem afetar dados existentes.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): produto cadastrado (estoque 5, mínimo 3, ainda
não aparece no alerta); venda de 2 unidades - estoque caiu pra 3,
lançamento de receita criado com valor R$90,00 (2x R$45,00), produto
passou a aparecer no alerta de estoque baixo (3 = mínimo); tentativa de
vender 100 unidades (mais do que o estoque disponível) foi bloqueada
com mensagem de erro e **não alterou o estoque**; edição de
nome/preço/estoque salvou corretamente. Nenhum warning/erro no log do
PHP durante os testes.

---

## Ajuste 135 - 2026-07-15

**Barbearias: comissão de profissionais (fechamento por período)**

- Cada profissional agora tem um **percentual de comissão** (0-100%,
  novo campo no cadastro de profissional).
- Nova tela `/dashboard/comissoes`: fechamento por período (padrão o
  mês atual, com filtro de data e de profissional), mostrando por
  profissional a quantidade de atendimentos concluídos, o valor
  faturado e o valor de comissão a receber (faturado × percentual).
  Clicar em um profissional abre o detalhe com a lista de atendimentos
  que entraram na conta.
- **Base de cálculo do valor faturado**: usa o valor realmente cobrado
  no PDV (`financeiro_lancamentos.valor`, ligado ao agendamento) quando
  existe; se o atendimento foi concluído sem registrar pagamento, usa o
  preço atual do serviço como aproximação.
- Sem geração automática de pagamento/repasse - é só o relatório de
  fechamento, o pagamento em si continua fora do sistema (mesma lógica
  de escopo das outras telas manuais deste módulo).

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/009_comissoes.sql` uma única vez
- adiciona `profissionais.percentual_comissao` (`DECIMAL(5,2)`, padrão
`0`), sem afetar dados existentes.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): cadastrado profissional com 40% de comissão, dois
agendamentos concluídos no período (um com pagamento manual de R$55
registrado no PDV, divergente do preço de tabela de R$50; outro sem
pagamento registrado, caindo pro preço de tabela de R$30) - relatório
mostrou faturado R$85,00 e comissão R$34,00 (40% de 85), batendo com o
cálculo esperado; detalhe por profissional listou os dois atendimentos
com os valores corretos (R$55 e R$30); edição do percentual de
comissão salvou corretamente (40% → 45,5%); validação de percentual
fora da faixa (150%) bloqueou o cadastro sem gravar nada no banco;
cadastro de novo profissional com percentual válido (25%) funcionou
normalmente. Nenhum warning/erro no log do PHP durante os testes.

---

## Ajuste 134 - 2026-07-15

**Barbearias: CRM básico (aniversariantes/inativos) + portfólio de fotos dos profissionais**

- **CRM** (`/dashboard/crm`): duas listas pra saber com quem vale a
  pena entrar em contato -
  - **Aniversariantes do mês** (precisa cadastrar a data de nascimento
    do cliente - novo campo opcional no cadastro de cliente);
  - **Clientes inativos**: quem já teve pelo menos um atendimento mas
    não aparece há X dias (30/60/90/180, configurável na própria
    tela) - um cliente com um agendamento **futuro** marcado nunca
    aparece aqui.
  - **Sem nenhum envio automático** - a aplicação não tem canal de
    e-mail/WhatsApp configurado (mesma observação do Ajuste 133): é
    só uma lista com nome/telefone/e-mail pra equipe entrar em
    contato por fora.
- **Portfólio de profissionais**: cada profissional ganha uma galeria
  de fotos de trabalhos (upload/legenda/exclusão na tela de edição do
  profissional), mostrada como miniaturas clicáveis no card de cada
  profissional na página pública de agendamento.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/008_crm_e_portfolio.sql` uma
única vez - adiciona `clientes.data_nascimento` (nullable) e cria a
tabela `portfolio_fotos`, sem afetar dados existentes.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): cliente cadastrado com aniversário em julho
aparece no CRM, cliente com aniversário em dezembro não aparece;
agendamento antigo (simulado via SQL) faz o cliente aparecer como
inativo com filtro de 60 dias; um agendamento futuro tira o cliente da
lista de inativos; upload de foto de portfólio - encontrado e
corrigido um bug real no meio do teste (o método do controller usava
o nome de parâmetro errado pro placeholder de rota `{id}`, causando
erro 500 - o roteador da aplicação passa os parâmetros de rota como
argumentos nomeados do PHP, então o nome do parâmetro do método
precisa bater exatamente com o nome usado na rota); depois do ajuste,
upload/exclusão testados de ponta a ponta (aparece no dashboard e na
página pública, some dos dois ao excluir, arquivo realmente removido
do disco); confirmado que editar um cliente sem tocar no campo de
nascimento preserva o valor, inclusive quando o mesmo telefone agenda
de novo pela página pública. Nenhum warning/notice remanescente no log
do PHP após a correção.

---

## Ajuste 133 - 2026-07-15

**Barbearias: lista de espera + cancelamento/reagendamento pelo cliente (fecha o módulo de agenda avançada)**

- **Lista de espera**: quando não há nenhum horário livre no dia
  escolhido, a página pública de agendamento oferece "Entrar na lista
  de espera desse dia" (reaproveita nome/telefone/email já digitados).
  Nova tela `/dashboard/lista-espera` mostra quem está esperando
  (cliente, contato, profissional, serviço, data desejada), com ação
  pra marcar "Atendido" ou remover - **sem notificação automática**: a
  equipe confere a fila e entra em contato por fora (a aplicação não
  tem nenhum canal de e-mail/WhatsApp configurado, ver observação
  abaixo).
- **Cancelamento pelo cliente**: na área do cliente, cada próximo
  agendamento ganha um botão "Cancelar".
- **Reagendamento pelo cliente**: também ganha "Reagendar", levando a
  uma tela de escolha de nova data/horário (mesmo profissional e
  serviço) - a disponibilidade é recalculada excluindo o próprio
  agendamento do cálculo de conflito (senão ele apareceria como
  ocupado consigo mesmo), e revalidada no servidor no momento de
  confirmar.
- Extraído `Barbearias\Core\Disponibilidade::horariosLivres()` -
  centraliza o cálculo de horários livres (antes duplicado só no
  agendamento público) e agora é compartilhado entre o agendamento
  público e o reagendamento da área do cliente.
- **Confirmação/lembrete automático não foi implementado nesta etapa**:
  a aplicação não tem nenhuma infraestrutura de e-mail, SMS ou
  WhatsApp configurada (nenhum SMTP, nenhuma API de mensageria) - isso
  exige uma decisão de canal e credenciais antes de poder ser
  construído de verdade, em vez de simular algo que não funciona.
  Fica como próximo passo quando houver decisão sobre qual canal usar.
- Isso fecha o módulo de "recursos avançados de agenda" (bloqueios do
  Ajuste 132 + lista de espera/reagendamento/cancelamento aqui).

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/007_lista_espera.sql` uma única
vez - cria só a tabela nova `lista_espera`, sem alterar nenhuma tabela
existente.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): bloqueio de dia inteiro criado → horários vazios
confirmados → botão de lista de espera aparece na página pública com o
`formaction` correto → entrada na lista de espera criada e cliente
reaproveitado/criado por telefone → mensagem de sucesso exibida →
entrada aparece no painel `/dashboard/lista-espera` com todos os dados
→ marcar como atendido remove da lista → agendamento normal criado
noutro dia aberto → cliente cria conta reivindicando o cadastro
existente (mesmo telefone) → botões "Reagendar"/"Cancelar" aparecem no
painel → endpoint de horários do reagendamento confirmado excluindo o
próprio agendamento do conflito (mostrou o horário original como
disponível) → reagendamento confirmado, `data_hora` atualizada mantendo
profissional/serviço/status → cancelamento testado, status muda e some
dos "próximos" → tentativa de reagendar/consultar horários de um
agendamento já cancelado bloqueada (guarda de posse + status). Nenhum
warning/notice no log do PHP durante os testes.

---

## Ajuste 132 - 2026-07-15

**Barbearias: bloqueios de agenda (férias, folgas e compromissos pontuais)**

- Nova tela `/dashboard/bloqueios` - registra qualquer período em que um
  profissional NÃO atende, além do expediente normal já configurado
  (dias/horário de atendimento): bloqueio pontual (reunião, compromisso),
  férias ou folga - com motivo opcional.
- O bloqueio é sempre respeitado no cálculo de horários disponíveis do
  agendamento público - um bloqueio de dia inteiro (férias/folga) some
  com todos os horários daquele dia; um bloqueio de algumas horas só
  fecha o intervalo correspondente (inclusive horários que
  começariam antes do bloqueio mas invadiriam ele, do mesmo jeito que já
  acontece com outro agendamento marcado).
- Também é validado ao criar/editar um agendamento manualmente pelo
  painel - a equipe não consegue marcar por cima de umas férias ou
  bloqueio sem querer, com uma mensagem de erro clara.
- Esta é a primeira fatia dos "recursos avançados de agenda" do prompt
  original - o restante (lista de espera, reagendamento pelo cliente na
  área do cliente, confirmação/lembrete automático) fica pra próximas
  etapas.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/006_bloqueios_agenda.sql` uma
única vez - cria só a tabela nova `bloqueios_agenda`, sem alterar
nenhuma tabela existente.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): horários livres confirmados antes de qualquer
bloqueio → bloqueio de dia inteiro (férias) criado → todos os horários
daquele dia somem, dias vizinhos continuam livres → bloqueio pontual de
1h criado num outro dia → só o intervalo correspondente (e os slots que
invadiriam ele) somem, o resto do dia continua disponível → tentativa
de marcar manualmente pelo painel em cima do bloqueio pontual bloqueada
com a mensagem correta, nenhum agendamento criado → marcar num horário
livre do mesmo dia funciona normalmente → exclusão do bloqueio pontual
testada → horário volta a aparecer disponível imediatamente. Nenhum
warning/notice no log do PHP durante os testes.

---

## Ajuste 131 - 2026-07-15

**Barbearias: multi-unidade (filiais)**

- **Unidades**: nova tela `/dashboard/unidades` (CRUD) - nome, endereço,
  cidade/UF, CEP, telefone, WhatsApp. Toda barbearia (nova ou já
  existente) já nasce com uma "Unidade Principal" automática - quem
  nunca cadastra uma segunda unidade **não vê nenhuma tela nova**: sem
  seletor de unidade no cadastro de profissional, sem campo de unidade
  no agendamento (painel ou público), sem filtro na listagem. Tudo isso
  só aparece a partir do momento em que existe uma segunda unidade
  ativa.
- **Profissionais**: podem atender em uma ou mais unidades (vínculo
  muitos-pra-muitos) - o formulário ganha os checkboxes de unidade
  assim que a barbearia tem 2+ unidades ativas.
- **Agendamento no painel**: ganha um campo de unidade (obrigatório
  quando há 2+ unidades) e a listagem ganha filtro por unidade + coluna
  correspondente.
- **Agendamento público** (`/agendar/{slug}`): quando a barbearia tem
  mais de uma unidade ativa, aparece um passo de escolha de unidade
  ANTES da escolha de profissional, e a lista de profissionais é
  filtrada (no navegador) pra mostrar só quem atende naquela unidade -
  revalidado no servidor no envio (rejeita profissional que não
  pertence à unidade escolhida, mesmo que o JS seja contornado).
- Toda barbearia precisa ter sempre pelo menos uma unidade: excluir ou
  desativar a última unidade ativa é bloqueado com uma mensagem clara.
- Fora de escopo por ora: geolocalização, redes sociais e
  logo/imagens por unidade, caixa/financeiro por unidade (o módulo
  Financeiro shipado no Ajuste 130 continua consolidado por barbearia,
  não por unidade), visão comparativa entre unidades no painel -
  ficam pra uma próxima etapa.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/005_multi_unidade.sql` uma única
vez - cria as tabelas `unidades` e `profissional_unidades`, adiciona
`agendamentos.unidade_id` (nullable) e faz o backfill automático: toda
barbearia existente ganha uma "Unidade Principal" e todo profissional
existente é vinculado a ela. Testado aplicando sobre uma cópia do
schema anterior (com dados reais de barbearia/profissional/agendamento
já cadastrados) - o resultado bate exatamente com o schema de uma
instalação nova via `install.sql`.

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada), nos dois cenários:
- **1 unidade (caso comum)**: confirmado que nenhuma tela nova aparece
  em lugar nenhum (profissional, agendamento do painel, agendamento
  público) - um agendamento público completo foi feito e o
  `unidade_id` foi preenchido automaticamente com a unidade principal,
  sem o cliente perceber nada diferente.
- **2 unidades**: criação da segunda unidade; profissional existente
  aparece automaticamente marcado na unidade 1 (do backfill); novo
  profissional vinculado só à unidade 2; formulário de agendamento do
  painel exige a unidade; filtro por unidade na listagem confirmado
  batendo exatamente com os agendamentos de cada unidade; agendamento
  público mostra o passo de escolha de unidade e os profissionais
  certos aparecem tagueados por unidade; tentativa de agendar com
  profissional que não pertence à unidade escolhida bloqueada no
  servidor (nenhuma linha criada); exclusão da unidade não-principal
  funcionou (agendamento antigo vinculado a ela virou `unidade_id NULL`
  via `ON DELETE SET NULL`, vínculo do profissional removido em
  cascata); tentativa de excluir a última unidade restante bloqueada
  com mensagem clara; voltando a 1 unidade, toda a UI condicional
  desapareceu de novo. Nenhum warning/notice no log do PHP durante os
  testes.

---

## Ajuste 130 - 2026-07-15

**Barbearias: módulo Financeiro (caixa diário + lançamentos + PDV rápido)**

- **Caixa diário**: abertura (com valor inicial e observações) e
  fechamento (com valor contado e cálculo automático do valor
  esperado = abertura + receitas − despesas do caixa). Só pode existir
  um caixa aberto por vez por barbearia.
- **Lançamentos manuais** de receita/despesa, com categoria (texto
  livre com sugestões), forma de pagamento (dinheiro, Pix, cartão de
  crédito/débito, outro), valor e data - listados com filtro por tipo
  e paginação, e vinculados ao caixa aberto no momento (se houver).
- **KPIs do dia e do mês**: receitas, despesas e saldo, no topo da
  tela de Financeiro.
- **PDV rápido integrado à agenda**: ao marcar um agendamento como
  concluído (`/dashboard/agendamentos/{id}/pagamento`), a forma de
  pagamento e o valor recebido (pré-preenchido com o preço do serviço,
  editável) são registrados no mesmo passo como um lançamento de
  receita vinculado ao agendamento e ao caixa aberto - no máximo um
  lançamento por agendamento, e só é possível para agendamentos ainda
  com status "agendado".
- Editar/cancelar um agendamento pela tela de edição continua sem
  gerar lançamento automático (só a tela de "concluir com pagamento" o
  faz) - evita criar cobrança sem o usuário perceber.
- Esta é a segunda etapa da sequência combinada no chat (área do
  cliente → financeiro → multi-unidades). Ainda ficam de fora deste
  módulo (fora de escopo por ora): contas a pagar/receber recorrentes,
  fechamento com relatório em PDF, comissão por serviço/produto e
  vínculo com o módulo de assinaturas de cliente - dependem de módulos
  futuros ou de mais detalhamento.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/004_financeiro.sql` uma única vez
(testado localmente aplicando sobre uma cópia do schema anterior -
chega ao mesmo resultado do `install.sql` atual).

**Testado localmente** (MariaDB + servidor PHP embutido, via curl com
sessão autenticada): abrir caixa → registrar despesa manual → concluir
um agendamento registrando o pagamento (Pix) → lançamento de receita
criado automaticamente com o valor do serviço e vinculado ao caixa →
KPIs do dia/mês batendo com os lançamentos → valor esperado do caixa
calculado corretamente (abertura + receitas − despesas) → fechar caixa
→ tela volta a mostrar "Fechado" e oferece abrir um novo. Tentativa de
registrar pagamento de novo num agendamento já concluído é bloqueada
(redireciona sem duplicar lançamento, protegido também por índice
único em `agendamento_id`). Exclusão de lançamento testada. Nenhum
warning/notice no log do PHP durante os testes.

---

## Ajuste 129 - 2026-07-15

**Barbearias: área do cliente (login próprio, agendamentos e avaliação de atendimento)**

- **Login próprio do cliente final** (`/minha-conta/{slug}`), separado
  do login da equipe - o cliente cria uma conta com telefone + senha;
  se esse telefone já tinha um cadastro (de um agendamento anterior
  sem conta), a senha é adicionada a ele em vez de duplicar - o
  histórico de agendamentos anterior aparece automaticamente.
- **Painel do cliente**: próximos agendamentos, histórico de
  atendimentos e atalho pra agendar de novo (`/agendar/{slug}`).
- **Avaliação de atendimento**: depois de um corte marcado como
  concluído, o cliente pode dar uma nota de 1 a 5 estrelas com
  comentário opcional, direto no próprio painel - no máximo uma
  avaliação por agendamento.
- Link "Já tem conta? Entrar" no agendamento público, e "Criar conta"
  na tela de confirmação, pra quem acabou de agendar sem conta
  descobrir a área do cliente.
- Esta é a primeira fatia da "área do cliente" pedida (próximos
  agendamentos, histórico, avaliação, agendamento rápido) - o restante
  do que foi pedido (histórico de pagamentos, plano contratado,
  fidelidade/cashback/carteira virtual, fotos dos cortes, produtos
  favoritos) depende de módulos que ainda não existem (financeiro,
  assinaturas de cliente, fidelidade, produtos/estoque) e ficam pra
  próximas etapas, na ordem combinada no chat.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/003_area_do_cliente.sql` uma
única vez (testado localmente aplicando sobre uma cópia do schema
anterior - chega ao mesmo resultado do `install.sql` atual).

**Testado localmente** (MariaDB + servidor PHP embutido): cliente
agenda sem conta (guest) → cria conta depois com o mesmo telefone →
reivindica o cadastro existente sem duplicar → vê o agendamento no
painel → agendamento marcado como concluído → avalia com nota e
comentário → avaliação registrada e formulário substituído por "já
avaliou" → segunda tentativa de avaliar o mesmo agendamento bloqueada.
Isolamento entre barbearias testado (mesmo telefone com senha em uma
barbearia não loga em outra). Senha errada rejeitada corretamente.

---

## Ajuste 128 - 2026-07-15

**Barbearias: modo claro (padrão do painel), perfil completo do profissional e agendamento público pro cliente final**

- **Modo claro/escuro**: o painel (dashboard) agora tem um botão "Tema"
  na barra lateral pra alternar entre claro e escuro - o claro é o
  padrão (mesma convenção já usada no KADOSYS Igrejas), com a escolha
  salva no navegador. O site público/institucional continua sempre
  escuro de propósito.
- **Perfil completo do profissional**: além de nome/especialidade/
  telefone, agora dá pra cadastrar e-mail, foto (upload PNG/JPG/WEBP,
  até 5MB), dias da semana que atende e horário de expediente -
  necessário pro agendamento público calcular horários livres.
- **Agendamento público** (`/agendar/{slug-da-barbearia}`): link que a
  barbearia compartilha com os próprios clientes (tela de
  Configurações tem um botão "Copiar" pra esse link) - o cliente final
  escolhe o profissional, o serviço e vê os horários realmente livres
  daquele dia (calculado a partir do expediente do profissional menos
  os agendamentos que já existem, com granularidade de 15 minutos e
  checagem de sobreposição por duração do serviço), preenche nome e
  telefone e confirma - sem precisar criar conta. Um cliente que já
  agendou antes com o mesmo telefone é reconhecido automaticamente
  (não duplica cadastro). A disponibilidade é sempre revalidada no
  servidor no momento da confirmação, pra evitar dois clientes
  reservando o mesmo horário ao mesmo tempo.
- `config/database.php` agora aceita um arquivo
  `config/database.local.php` (gitignored) como alternativa a variável
  de ambiente, mesmo padrão já usado em `config/mercadopago.php` - útil
  quando o painel de hospedagem não sustenta variável de ambiente
  customizada entre deploys.

**Ação manual pendente no banco `kadosys1_barbearias`** (só se
`install.sql` já rodou antes): rodar
`apps/barbearias/database/migrations/002_profissional_completo.sql`
uma única vez (testado localmente aplicando sobre uma cópia do schema
anterior - chega ao mesmo resultado do `install.sql` atual).

**Testado localmente** (MariaDB + servidor PHP embutido): alternância
de tema com persistência após reload, cadastro de profissional com
dias/horário de expediente, geração de horários livres pra um dia da
semana configurado (visualmente conferido: início/fim do expediente,
granularidade de 15 min), reserva de um horário e confirmação de que
ele - e os horários que se sobrepõem a ele por causa da duração do
serviço - somem da lista de disponíveis, cliente reconhecido pelo
telefone numa segunda visita, e as páginas de erro (barbearia
inexistente, dia sem expediente, data no passado) devolvendo lista
vazia/404 em vez de quebrar.

---

## Ajuste 127 - 2026-07-15

**Barbearias: módulos do sistema (Profissionais, Serviços, Clientes, Agendamentos, Faturas, Configurações)**

- Shell do painel com barra lateral (sidebar) navegável entre todos os
  módulos, com versão colapsável/gaveta pra celular - substitui a
  topbar simples de "em breve" da fase inicial.
- **Profissionais**, **Serviços** e **Clientes**: cadastro completo
  (listar com busca e paginação, criar, editar, excluir), sempre
  isolado por `barbearia_id`.
- **Agendamentos**: cria um horário ligando cliente + profissional +
  serviço + data/hora, com atalho pra marcar como concluído/cancelado
  direto na listagem, além de editar/excluir. O painel inicial passa a
  mostrar números reais (agendamentos hoje, profissionais/serviços
  ativos, clientes) e os próximos agendamentos.
- **Faturas**: histórico de cobrança (Pix - vem do banco; cartão -
  buscado ao vivo na API do Mercado Pago, já que a cobrança recorrente
  é debitada automaticamente sem passar pelo nosso webhook de novo).
  Fica acessível mesmo com trial vencido/pagamento pendente, pra quem
  está bloqueado conseguir ver a própria fatura pendente e pagar.
- **Configurações**: dados da barbearia (nome/telefone) e gestão de
  equipe (adicionar acesso, editar nome/e-mail/papel/senha, ativar ou
  desativar, remover) - só administradores acessam, e nunca deixa a
  barbearia sem nenhum admin ativo.

**Testado localmente** (MariaDB + servidor PHP embutido, 2 barbearias
simultâneas): criação/edição/exclusão nos 4 módulos de cadastro,
agendamento citando profissional/serviço/cliente reais, atalho de
concluir/cancelar, isolamento completo entre barbearias (uma não
enxerga nem consegue acessar por URL direta os dados da outra), bloqueio
de "usuario" (equipe) nas Configurações, regra de "sempre precisa
sobrar 1 admin ativo", e a barra lateral testada em desktop e mobile
(inclusive o menu gaveta) via screenshot.

---

## Ajuste 126 - 2026-07-15

**Barbearias: site institucional, cadastro público com cobrança automática (Pix/cartão) e trial de 5 dias**

- Site público de vendas (`/`) com planos e preços - Essencial R$29,90,
  Plus R$49,90, Premium R$69,90 por mês, mesmos nomes de exibição do
  Igrejas.
- Cadastro público (`/cadastro`): barbearia + admin + escolha de plano
  e forma de pagamento (cartão, Pix ou teste grátis de 5 dias). Como o
  Barbearias usa um banco único (multi-tenant lógico), o fluxo é bem
  mais simples que o do Igrejas - não existe "provisionar banco/
  subdomínio", então a barbearia já nasce com `status = 'pendente'`
  (Pix/cartão aguardando confirmação) ou `'ativo'` (trial, sem
  cobrança), e o teste grátis loga direto no painel sem tela de espera.
- Cobrança automática via Mercado Pago (mesma conta/credenciais já
  configuradas pro Igrejas) - assinatura recorrente por cartão
  (Checkout Pro/preapproval) ou cobrança Pix avulsa renovada todo mês.
- Webhook (`POST /webhooks/mercadopago`) confirma pagamento e ativa a
  barbearia automaticamente.
- Bloqueio de acesso (`Barbearias\Core\Middleware\AuthMiddleware`):
  redireciona pra `/dashboard/assinatura` quando o trial vence, a
  fatura Pix vence sem pagamento, ou o primeiro pagamento (Pix/cartão)
  ainda não foi confirmado - dessa tela dá pra pagar (gerar novo QR Pix
  ou assinar por cartão) sem perder acesso aos dados já cadastrados.
- Cron `apps/barbearias/cron/gerar_faturas_pix.php` gera a fatura Pix
  do próximo ciclo automaticamente (mesmo padrão do Igrejas, sem
  precisar conectar num banco por tenant já que é tudo a mesma tabela).
- Novas tabelas/colunas: `barbearias.documento_tipo/documento/
  razao_social/plano/metodo_pagamento/mp_preapproval_id/
  trial_expira_em/proximo_vencimento/status`, e a tabela
  `barbearia_faturas` (unifica primeiro pagamento e renovação numa
  linha só - diferente do Igrejas, que separa isso em duas tabelas).
- **Ação manual pendente no banco `kadosys1_barbearias`**: se o
  `install.sql` original (Ajuste 124) ainda **não** foi rodado no
  banco, só rodar o `install.sql` atual já resolve (já inclui tudo).
  Se ele **já** foi rodado antes, rodar
  `apps/barbearias/database/migrations/001_add_billing.sql` uma única
  vez pra adicionar as colunas/tabela novas (testado localmente
  aplicando em cima do schema antigo, chega no mesmo resultado do
  install.sql atual). Também configurar as variáveis de ambiente
  `MP_ACCESS_TOKEN`/`MP_PUBLIC_KEY`/`MP_WEBHOOK_SECRET`/`APP_URL` (mesma
  conta do Igrejas) e cadastrar o webhook
  `https://SEUDOMINIO/webhooks/mercadopago` no painel do Mercado Pago,
  além do Cron Job diário do `apps/barbearias/cron/gerar_faturas_pix.php`
  (mesmo padrão já usado no Igrejas).

**Testado localmente** (MariaDB `barbearias_teste` + servidor PHP
embutido): cadastro por teste grátis cria a barbearia (`status =
'ativo'`, `trial_expira_em` 5 dias à frente) e o admin, loga
automaticamente e chega no painel; cadastro por Pix/cartão cria a
barbearia em `status = 'pendente'` e falha de forma controlada quando
o Mercado Pago não está configurado (sem crash); trial vencido e
pagamento pendente bloqueiam `/dashboard` e redirecionam pra
`/dashboard/assinatura`, com `/logout` e a própria tela de assinatura
liberados da regra pra não travar o usuário. As chamadas reais à API
do Mercado Pago (criar assinatura/cobrança Pix) não são testáveis
neste ambiente (proxy de sandbox bloqueia `api.mercadopago.com`) - só
os caminhos que não dependem da API foram verificados de ponta a
ponta.

---

## Ajuste 125 - 2026-07-15

**Barbearias: adiciona vendor/ e composer.lock (deploy por git pull)**

- Como o deploy da hospedagem faz "pull" automático da raiz do
  `public_html`, o `apps/barbearias/vendor/` (autoload gerado pelo
  Composer) precisa estar versionado no Git - sem isso, o app quebraria
  assim que o pull acontecesse, já que não há passo manual de
  `composer install` no servidor (mesmo padrão já usado em
  `apps/igrejas/vendor/`). Sem dependências de terceiros (o
  `composer.json` do Barbearias não declara nenhuma), então é só o
  autoloader mesmo.

---

## Ajuste 124 - 2026-07-15

**Novo app: KADOSYS Barbearias (estrutura inicial + login)**

- Primeira fatia do novo sistema de gestão para barbearias
  (`apps/barbearias`), seguindo a mesma linha do KADOSYS Igrejas (PHP
  puro, sem framework, MVC próprio), mas com uma arquitetura de dados
  diferente: em vez de um banco isolado por cliente, o Barbearias usa
  **um banco único e compartilhado** (`kadosys1_barbearias`), com uma
  coluna `barbearia_id` em cada tabela isolando os dados de cada
  barbearia - evita o limite de quantidade de bancos MySQL por conta
  de hospedagem compartilhada, e simplifica bastante a operação (uma
  migração só, uma conexão só).
- Entregue nesta etapa: infraestrutura completa (roteador, sessão,
  CSRF, views, middlewares), schema inicial (`barbearias`, `users`,
  `profissionais`, `serviços`, `clientes`, `agendamentos`), e o fluxo
  de login/logout funcionando de ponta a ponta - com mensagens de erro
  específicas (e-mail não cadastrado / senha incorreta / usuário
  desativado). O e-mail de login é único **globalmente** (não por
  barbearia), então não precisa de uma etapa de "qual barbearia" como
  o Igrejas tem hoje.
- Painel inicial mostra os módulos que ainda serão construídos
  (Profissionais, Serviços, Clientes, Agendamentos), todos marcados
  "Em breve".
- Testado com 2 barbearias de teste simultâneas, confirmando que uma
  não vê nem um pouco dos dados da outra.
- **Pendente antes do primeiro deploy real**: rodar
  `apps/barbearias/database/install.sql` uma única vez no banco
  `kadosys1_barbearias`, configurar as variáveis de ambiente
  `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` no servidor (a
  senha NÃO fica no código, por segurança), rodar `composer install`
  dentro de `apps/barbearias/`, e criar a primeira barbearia+admin com
  `php database/seed_admin.php "Nome da Barbearia" "Nome do Admin" "email" "senha"`.

---

## Ajuste 123 - 2026-07-15

**Site institucional: adiciona Kadosys™ Sites (construtor de sites)**

- Novo sistema "em desenvolvimento" no site institucional: Kadosys™
  Sites, um construtor de sites arrasta e solta com domínio e
  hospedagem inclusos (editor visual, modelos prontos por segmento,
  domínio próprio, publicação em poucos cliques).

---

## Ajuste 122 - 2026-07-15

**Site institucional: novos sistemas em desenvolvimento, remove streaming, WhatsApp real**

- Site institucional (`index.php` na raiz do repositório - o site
  "guarda-chuva" da marca KADOSYS, diferente do `apps/igrejas`) atualizado:
  - **"Acessar Sistema"** (botão do menu) agora só oferece acesso de
    verdade ao Kadosys™ Igrejas - os demais sistemas aparecem como
    "Em desenvolvimento" ou "Em breve", sem link quebrado (`href="#"`)
    fingindo ser clicável.
  - **3 novos sistemas** adicionados como "em desenvolvimento": Kadosys™
    Barbearias, Kadosys™ Creches e Kadosys™ Condomínios - cada um com
    prévia das funcionalidades planejadas.
  - **Kadosys™ Igrejas** ganhou uma prévia do que já tem de verdade hoje
    (membros/ministérios, check-in infantil com PIN, projeção e Pix).
  - **Removida** a seção inteira de "Streaming de Jogos" (card, seção
    dedicada, item no formulário de contato e imagem
    `assets/gaming-streaming.jpg`) - não é mais um produto da empresa.
  - **WhatsApp real**: botão flutuante, ícone social e contato do
    rodapé agora apontam para (11) 93325-2478, no lugar dos números
    de exemplo.

---

## Ajuste 121 - 2026-07-15

**Seleção de igreja: inclui a instalação atual + mensagens de erro mais claras no login**

- A tela "Qual igreja?" (Ajuste 119) só considerava igrejas de
  subdomínio - se o e-mail também tivesse conta na própria instalação
  atual (o domínio raiz, que também pode ter usuários de verdade),
  essa opção ficava de fora da lista, ou pior: com só uma igreja de
  subdomínio encontrada, mandava direto pra ela e ignorava que a
  instalação atual também era uma opção válida. Agora a instalação
  atual entra na busca e na lista de opções como qualquer outra
  igreja.
- Mensagem de erro do login ficou mais clara: em vez de sempre
  "E-mail ou senha inválidos" (pensado pra não revelar se um e-mail
  existe), agora diferencia **"Esse e-mail não está cadastrado."**,
  **"Senha incorreta."** e **"Esse usuário está desativado."**,
  conforme o caso.

---

## Ajuste 120 - 2026-07-15

**Correção: link da seleção de igreja duplicava o domínio**

- Bug do Ajuste 119: os links da tela "Qual igreja?" (e o redirecionamento
  automático quando só uma igreja é encontrada) apontavam para um
  endereço quebrado, tipo `ijpm.kadosys.com.br.kadosys.com.br` - o
  domínio raiz aparecia duplicado.
- Causa: `Tenant::subdominio` já guarda o host completo da igreja (ex.:
  `ijpm.kadosys.com.br`), não só o pedaço antes do domínio raiz (ver
  `Provisionador::provisionar()`) - o código novo montava o link
  colando `.` + domínio raiz de novo em cima disso.
- Corrigido para usar `subdominio` direto, sem concatenar o domínio
  raiz por cima.

---

## Ajuste 119 - 2026-07-15

**Login no domínio raiz: seleciona a igreja quando o e-mail está em mais de uma**

- Até agora, a tela de login do domínio raiz da plataforma
  (`kadosys.com.br`, fora de qualquer subdomínio de igreja) pedia
  e-mail e senha juntos, igual a qualquer tela de login de igreja
  específica - mas não existe uma tabela central de usuários (cada
  igreja tem seu próprio banco isolado), então não tinha como saber
  antes pra qual banco mandar a senha.
- Agora essa tela pede só o e-mail primeiro. Ao continuar, o sistema
  procura em todas as igrejas ativas quais têm um usuário com esse
  e-mail cadastrado:
  - **nenhuma encontrada** - segue pro formulário normal (e-mail +
    senha) na própria instalação atual, sem revelar se o e-mail existe
    ou não em outro lugar;
  - **uma encontrada** - manda direto pro subdomínio daquela igreja,
    sem fricção extra;
  - **duas ou mais encontradas** - mostra a nova tela **"Qual
    igreja?"**, com um cartão por igreja (nome + subdomínio) pra
    escolher pra onde ir digitar a senha.
- Dentro do subdomínio de uma igreja específica, o login continua
  exatamente como sempre foi (e-mail + senha juntos, sem esse passo
  extra) - a mudança só existe no domínio raiz, onde a ambiguidade de
  fato existe.
- Nova consulta `Tenant::ativasComEmailCadastrado()` conecta,
  temporariamente, no banco de cada igreja ativa (via
  `Database::conexaoAvulsa()`, com timeout curto pra não travar a
  busca se alguma estiver fora do ar) só pra checar se o e-mail existe
  ali - sem nunca conferir a senha nessa etapa.

---

## Ajuste 118 - 2026-07-15

**Segundo lote grande da biblioteca oficial KADOSYS (historias, quiz, jogos e mais)**

- Biblioteca oficial ampliada de 30 para **93 conteúdos**, com foco em
  histórias, quiz e jogos (como pedido), além de reforçar todos os
  outros tipos: 18 histórias, 11 quiz (cada um com 4 perguntas de
  múltipla escolha), 7 jogos, 9 versículos ilustrados, 7 devocionais,
  7 desenhos para colorir, 5 estudos, 5 desafios, 5 vídeos, 4
  atividades, 3 áudios, 3 slides, 3 HQs, 3 planos de leitura e 3 PDFs.
- Histórias novas cobrindo do Gênesis a Atos (Criação, Torre de Babel,
  Abraão, Rute, Ester, Sansão, Gideão, multiplicação dos pães, Zaqueu,
  tempestade acalmada, Lázaro, ressurreição, conversão de Paulo).
- Quiz novos sobre heróis do Velho Testamento, parábolas, os 12
  discípulos, criação, fruto do Espírito, igreja primitiva, Provérbios
  e Natal/Páscoa.
- Jogos novos jogáveis direto no texto (verdadeiro ou falso relâmpago,
  adivinhe o personagem, caça ao tesouro em casa) e outros com
  instruções para imprimir em família (bingo, corrida da fé, memória).
- Os novos conteúdos já fazem parte do `install.sql`, então toda
  igreja nova criada a partir de agora já nasce com a biblioteca
  completa.

---

## Ajuste 117 - 2026-07-15

**Responsável gera o próprio PIN + biblioteca oficial KADOSYS ampliada**

- Nova área de autoatendimento **"Meus filhos"** (Kids > Meus filhos,
  com atalho em "Meu perfil"): o próprio responsável, logado com o
  usuário dele, agora consegue gerar/renovar/remover o PIN de acesso
  dos filhos vinculados a ele, sem depender da equipe da igreja. Cada
  ação confere que a criança realmente pertence ao responsável logado
  antes de agir - ninguém consegue gerar PIN de uma criança que não é
  sua, mesmo sabendo o ID dela.
- Essa área fica acessível a qualquer usuário autenticado, mesmo sem
  permissão administrativa no módulo Kids (mesmo tratamento que "Meu
  perfil" já recebia) - afinal, gerenciar o acesso do próprio filho é
  autoatendimento, não uma tarefa de equipe.
- **Biblioteca oficial KADOSYS ampliada**: de 8 para 30 conteúdos,
  agora cobrindo os 15 tipos da biblioteca (incluindo slides, HQ,
  plano de leitura, PDF, atividades e jogos, que ainda não tinham
  nenhum exemplo), com mais histórias, vídeos, devocionais, quiz,
  desenhos para colorir e versículos ilustrados.
- Os novos conteúdos já fazem parte do `install.sql`, então toda
  igreja nova criada a partir de agora já nasce com a biblioteca
  completa.

---

## Ajuste 116 - 2026-07-15

**Login próprio da criança na Biblioteca Kids, por PIN**

- Cada criança pode ganhar um PIN de 4 dígitos, gerado pela equipe no
  perfil dela (Kids > Crianças) - **só é possível se a criança já tiver
  um responsável (Membro) vinculado**, o que funciona como o
  consentimento mínimo antes de liberar esse acesso independente. O
  PIN é mostrado uma única vez (a equipe entrega ao responsável) e
  guardado sempre com hash, nunca em texto puro.
- Nova tela pública `/kids/entrar`: a criança escolhe o próprio perfil
  (foto/nome) e digita o PIN - sem e-mail, sem senha, sem precisar de
  um adulto logado no sistema administrativo. Mesmo padrão de acesso
  por PIN já usado no Preletor/Telão.
- Depois de 5 tentativas erradas seguidas, o PIN fica bloqueado por 15
  minutos - proteção contra tentativa por força bruta, já que 4 dígitos
  é um espaço de busca pequeno.
- A Biblioteca (`/kids`) passa a ser servida também nesse "modo
  criança" de verdade: uma sessão própria da criança (independente do
  login administrativo), num layout novo sem sidebar/topbar - só o
  mundo colorido, pensado pra criança usar sozinha num tablet ou
  celular. O acesso via painel (Kids > Biblioteca, com o seletor de
  criança) continua existindo como uma prévia pra equipe.

---

## Ajuste 115 - 2026-07-14

**KADOSYS Kids (Fase 2): biblioteca de conteúdo + tela colorida "modo criança"**

- Nova estrutura de conteúdo do módulo Kids: histórias, vídeos,
  áudios, devocionais, estudos, quiz, atividades para colorir,
  desafios, versículos ilustrados e mais - com idade recomendada,
  categoria, tema, livro bíblico, personagem e recompensa (XP/moedas)
  configuráveis por conteúdo.
- Cada conteúdo tem uma origem: **⭐ oficial KADOSYS** (semeada
  automaticamente em `install.sql` - já vem com 8 conteúdos de
  exemplo variados) ou **🏠 da própria igreja** (cadastrada pela
  equipe em Kids > Conteúdos). O conteúdo oficial é somente leitura -
  a igreja não pode editar nem excluir.
- CMS de conteúdo (Kids > Conteúdos) no mesmo padrão visual do
  restante do painel: listagem com busca/filtro por tipo e origem,
  formulário completo (incluindo upload de capa/mídia e um construtor
  simples de perguntas de quiz).
- **Biblioteca (modo criança)**: nova tela com visual completamente
  diferente do painel administrativo - colorida, com cards grandes
  arredondados, gradientes vibrantes e emojis, no estilo
  Duolingo/Khan Academy Kids pedido. Mostra os conteúdos agrupados por
  tipo, com um seletor de "quem está navegando" (ainda não existe
  login próprio da criança, é um placeholder até essa fase futura) e
  um botão "Concluir" que concede XP/moedas automaticamente - só uma
  vez por conteúdo, mesmo se a criança repetir.
- As tabelas novas (`kids_conteudos`, `kids_conteudo_conclusoes`) e os
  conteúdos de exemplo já fazem parte do `install.sql`, então toda
  igreja nova criada a partir de agora já nasce com a biblioteca
  populada.

---

## Ajuste 114 - 2026-07-14

**Novo módulo: KADOSYS Kids (Fase 1 - Turmas, Crianças e Check-in)**

- Primeira fase do módulo KADOSYS Kids, focada no lado operacional da
  equipe/professores do ministério infantil: cadastro de Turmas (por
  faixa etária, com professor responsável), cadastro de Crianças
  (foto, turma, responsável vinculado a um Membro ou nome/telefone
  avulso, outras pessoas autorizadas a retirar, alergias e observações
  médicas) e uma tela de Check-in/Check-out na porta da sala.
- Cada check-in gera um código de segurança de 4 dígitos entregue ao
  responsável na entrada - a saída só é liberada quando esse código é
  informado corretamente, evitando que a criança seja retirada por
  quem não tem o código.
- Gamificação inicial: cada check-in concede XP e moedas à criança e
  atualiza a sequência de presença, exibidos no perfil e na grade de
  Crianças. As demais partes do módulo (histórias, jogos, avatares,
  mapa bíblico, IA infantil, área dos pais etc., do escopo completo do
  KADOSYS Kids) ficam para as próximas fases.
- Módulo "Kids" adicionado ao menu lateral, com o mesmo plano mínimo
  de Ministérios/Grupos/Comunicação (Plus). As tabelas novas
  (`kids_turmas`, `kids_criancas`, `kids_checkins`) já fazem parte do
  `install.sql`, então toda igreja nova criada a partir de agora já
  nasce com o módulo pronto para uso - sem passo extra de provisionamento.

---

## Ajuste 113 - 2026-07-14

**Equipe e Membros ganham o mesmo padrão visual de card**

- Equipe agora agrupa a galeria por departamento (Músicos, Mídia,
  Equipamento), cada um com seu título e contagem de pessoas.
- Os cards de Equipe e Membros passam a seguir o mesmo modelo: foto
  com emblema de cargo, nome, cargo, status "Ativo/Inativo" e
  contato - antes só o card de Membros tinha esse nível de detalhe.
- Em Membros, quem não tem uma função de verdade na equipe (a
  maioria da congregação) passa a mostrar "Membro" com um ícone de
  pessoa no emblema, em vez de ficar sem nenhum rótulo de cargo -
  mesmo padrão de quem é "Músico - Teclado", por exemplo.

---

## Ajuste 112 - 2026-07-14

**"Ao vivo" no menu só quando tem sessão de projeção de verdade**

- Projeção e Louvores ficavam sempre marcados como "Ao vivo" (selo
  verde pulsando) no topo do menu, mesmo fora de culto. Agora esse
  selo só aparece quando existe mesmo uma sessão de projeção em
  andamento (a mesma usada pelo telão e pelo tablet do preletor) -
  fora de culto, os dois módulos continuam destacados no topo do
  menu pra achar rápido, só que sob o rótulo "Destaque", sem alegar
  que tem algo ao vivo acontecendo.

---

## Ajuste 111 - 2026-07-14

**Membros vira uma grade de cards moderna, e corrige quem não aparecia**

- Corrigido: o vínculo automático entre login e Membros (Ajuste 109)
  só acontecia quando a pessoa abria "Meu perfil" - agora acontece
  assim que ela faz qualquer coisa logada no painel, então
  administradores e qualquer conta antiga aparecem em Membros desde
  o primeiro acesso, sem precisar visitar "Meu perfil" antes.
- Tela de Membros trocou a tabela por uma grade de cards, no mesmo
  estilo da tela Equipe: foto de perfil (a mesma de quem também tem
  acesso ao sistema) ou inicial, emblema de cargo pra quem tem uma
  função de verdade (músico, mídia, equipamento), status, contato,
  data de entrada/idade e os botões de ver perfil/excluir.
- Busca passa a encontrar também por cargo (ex.: "músico"), além de
  nome e e-mail.
- A foto enviada em "Meu perfil" agora aparece em todo lugar que
  mostra a pessoa: cabeçalho do painel, Equipe, Membros, Usuários e
  Permissões - antes só aparecia na galeria da Equipe.

---

## Ajuste 110 - 2026-07-14

**Login de cada igreja exibe o logo, o nome e a URL própria**

- A tela de login agora mostra o logo e o nome da igreja (os mesmos
  cadastrados em Configurações), no lugar do título genérico
  "Entrar" - só quando acessada pelo subdomínio da igreja.
- O rodapé lateral, que sempre mostrou o texto fixo
  "kadosys.com.br/apps/igrejas", agora mostra a URL de verdade da
  igreja (ex.: suaigreja.kadosys.com.br). Nas páginas centrais (ex.:
  cadastro de uma igreja nova), continua mostrando o texto genérico.

---

## Ajuste 109 - 2026-07-14

**Meu perfil ganha endereço e dados pessoais, vinculado ao cadastro em Membros**

- Até agora, quem tinha acesso ao sistema (usuário/login) e quem
  aparecia em Membros eram dois cadastros separados, sem nenhuma
  ligação - "Meu perfil" só editava foto/cargo/instrumento, sem
  endereço nem telefone.
- Cada usuário passa a ficar vinculado ao seu registro em Membros
  (`users.membro_id`, migração 048) - contas antigas se vinculam
  automaticamente na primeira vez que a pessoa abrir "Meu perfil"
  (reaproveita um Membro já existente com o mesmo e-mail, ou cria um
  novo se não achar nenhum).
- "Meu perfil" ganhou as seções "Dados pessoais" (telefone, data de
  nascimento, sexo, estado civil, CPF, RG, naturalidade) e "Endereço"
  (com o mesmo autopreenchimento por CEP usado em Membros) - editando
  o MESMO cadastro que aparece pra secretaria em Membros, sem
  duplicar dado.
- Como consequência, a tela Membros agora mostra todo mundo que tem
  acesso ao sistema, inclusive administradores.

---

## Ajuste 108 - 2026-07-14

**Equipe: só entra na galeria quem tem um cargo de verdade**

- Quem está com o cargo padrão "membro" (sem função definida na
  equipe, normalmente um admin que só usa o sistema) deixa de
  aparecer na galeria e no perfil de Equipe - essa pessoa não tem um
  cargo pra mostrar ali; continua tendo acesso normal ao sistema,
  só não entra mais nessa galeria específica.

---

## Ajuste 107 - 2026-07-14

**Agenda: compromisso pessoal liberado mesmo sem edição no módulo**

- Quem só tem "visualizar" liberado no módulo Agenda (ver
  Permissões) agora consegue cadastrar, editar e excluir um
  compromisso marcado como "Só eu" (privado) - continua não podendo
  criar/editar evento "Todo mundo" (público), que segue exigindo
  nível "editar" como antes.
- No formulário, quem não tem edição liberada já abre com "Só eu"
  selecionado e a opção "Todo mundo" desabilitada, em vez de só
  descobrir o bloqueio depois de tentar salvar.
- Reforço de segurança: mesmo editando o próprio compromisso privado
  por essa brecha, não dá pra promovê-lo pra público sem nível
  "editar" de verdade - o backend força de volta pra privado.

---

## Ajuste 106 - 2026-07-14

**Perfil da Equipe (aberto ao clicar no nome/foto na galeria)**

- Clicar em alguém na galeria de Equipe agora abre um perfil com
  status, cargo/instrumento, data de entrada na equipe, e-mail e um
  resumo de quais módulos essa pessoa acessa (e com qual nível -
  Visualizar ou Editar), reunindo numa tela só uma informação que
  hoje só aparecia espalhada em Permissões.
- Administradores veem, além disso, atalhos para "Editar usuário" e
  "Editar permissões" direto do perfil.
- Model `User` passou a expor a data de criação da conta
  (`created_at`), usada no "Na equipe desde".

---

## Ajuste 105 - 2026-07-14

**Nova tela de perfil do membro (aberta ao clicar no nome na listagem)**

- Ao clicar no nome de um membro na listagem, agora abre uma tela de
  perfil completa (`/dashboard/membros/{id}`) no lugar da antiga tela
  de edição simples - cabeçalho com avatar, status, "membro desde",
  idade e contatos (e-mail e WhatsApp clicáveis); emblemas dos
  ministérios/grupos que a pessoa participa; abas Dados, Contato,
  Endereço, Ministérios, Participações, Histórico e Documentos.
- Endereço ganhou busca automática por CEP (campos separados de
  logradouro/bairro/cidade/estado) e novos campos CPF, RG e
  naturalidade (migrações 046 e 047, aplicar em cada banco de igreja).
- Nova aba de Documentos permite anexar e remover arquivos do membro
  (PDF/JPG/PNG/WEBP, até 10MB), seguindo o mesmo padrão de upload já
  usado no módulo de Louvores.
- Ícone de "editar" na listagem foi substituído por "ver perfil"; a
  antiga rota `/editar` foi removida.
- Preço do plano Premium ajustado de R$ 179,90 para R$ 139,90/mês.

---

## Ajuste 104 - 2026-07-14

**Permissões liberado em todos os planos, e botão flutuante de suporte via WhatsApp**

- Módulo Permissões deixa de ser exclusivo do plano Premium e passa a
  estar disponível desde o Essencial - removido de
  `Plano::MODULO_MINIMO`. Cards de plano, tabela de comparação
  (gerada automaticamente) e a pergunta do FAQ sobre níveis de
  permissão foram corrigidos pra refletir isso.
- Nova botão flutuante fixo no canto inferior direito de toda a área
  logada (`layouts/dashboard.php`, usado por todos os módulos de
  gestão), com link direto pro WhatsApp de suporte da KADOSYS
  (+55 11 93325-2478).

---

## Ajuste 103 - 2026-07-14

**Nova seção "Como funciona" (linha do tempo) em cada página de recurso**

- Cada uma das 12 páginas `/recursos/{modulo}` ganhou uma seção com o
  passo a passo real de uso daquele módulo, numa linha do tempo
  vertical (número, título curto e descrição), entre o screenshot e
  os diferenciais.
- `RecursoController::MODULOS` ganhou o campo `passos` em cada módulo,
  com 4 etapas cada, descrevendo o fluxo real de cadastro/uso.

---

## Ajuste 102 - 2026-07-14

**Corrige textos de Projeção que davam a entender que o cargo de operador foi eliminado**

- Os textos sobre a Projeção (home e página `/recursos/projecao`)
  enfatizavam demais "sem operador", "sem precisar de alguém dedicado
  no computador" - dando a entender que essa função da equipe deixou
  de existir. Reescritos pra deixar claro que é uma opção a mais, não
  uma substituição: a igreja continua podendo operar com um operador
  dedicado no computador, com o preletor controlando direto do
  tablet, ou os dois ao mesmo tempo, cada um na sua tela.

---

## Ajuste 101 - 2026-07-14

**Louvores/Modo Culto também passa a usar imagem própria fora da pasta recursos/**

- Card de Louvores e Modo Culto na home (destaques e capturas de tela)
  e a página `/recursos/louvores` passam a usar `assets/img/modo_culto.png`
  em vez de `assets/img/recursos/louvores.png`, a pedido do usuário.

---

## Ajuste 100 - 2026-07-14

**Página /recursos/projecao também passa a usar assets/img/telao.png**

- A página dedicada de Projeção e Telão usava `projecao.png` +
  `preletor.png` (screenshot composta gerada via Playwright). Trocado
  pra usar a mesma imagem `assets/img/telao.png` já referenciada na
  home (Ajuste 99), a pedido do usuário.
- `RecursoController::MODULOS` reorganizado: cada módulo agora guarda
  o caminho completo da imagem (a maioria com prefixo `recursos/`,
  já que ficam em `assets/img/recursos/`), em vez de assumir sempre
  essa subpasta - permite que o módulo de Projeção aponte pra
  `assets/img/telao.png`, fora da subpasta `recursos/`.

---

## Ajuste 99 - 2026-07-14

**Selo do menu Recursos ainda sobrepunha texto em telas mais estreitas; troca da imagem de Projeção**

- O Ajuste 98 tentou corrigir o selo "Destaque" reservando um espaço
  fixo em pixels ao lado do texto - funcionava em algumas larguras de
  tela e sobrepunha em outras, porque o cálculo dependia do tamanho
  exato do texto do navegador. Trocado por uma solução que não depende
  de conta de pixel: o selo agora fica numa linha própria, embaixo do
  nome do módulo, só nos dois itens marcados como destaque - garante
  que nunca mais sobrepõe o texto, em qualquer largura de tela.
- Card de "Projeção e Telão" na home passou a usar uma imagem única
  (`assets/img/telao.png`) em vez da composição telão+tablet gerada
  antes - arquivo ainda precisa ser adicionado ao repositório pra
  aparecer (fica como imagem quebrada até lá).

---

## Ajuste 98 - 2026-07-14

**Corrige selo "Ao vivo" cortando/sobrepondo texto no menu Recursos e na home**

- O selo ao lado de "Louvores e Modo Culto" e "Projeção e Telão" (menu
  Recursos da navbar e cards de destaque da home) estava sobrepondo o
  próprio texto do link, com a coluna do menu cortando o conteúdo -
  bug de CSS: a largura reservada pro selo era menor que o selo
  realmente ocupava. Corrigido reservando espaço suficiente e tirando
  o selo do fluxo do texto (posicionado à parte, não mais competindo
  por espaço com o rótulo).
- Trocado o texto do selo de "Ao vivo" pra "Destaque" nos dois lugares
  (menu Recursos e cards da home) - o rótulo anterior não fazia
  sentido pro contexto.

---

## Ajuste 97 - 2026-07-14

**Site institucional: destaque pra Louvores/Modo Culto e Projeção, e endereço próprio da igreja**

- Nova seção "Os maiores diferenciais" logo após o hero da home, com um
  card grande pra Louvores/Modo Culto (cifra e tom ao vivo) e outro pra
  Projeção/Telão - este último com uma composição real (telão + tablet
  do preletor flutuando por cima) mostrando o preletor circulando um
  versículo no tablet e a marcação aparecendo no telão na mesma hora.
  As duas eram os maiores diferenciais do sistema e ficavam perdidas
  no meio da grade de recursos, sem destaque nenhum.
- Descoberto no processo que a marcação ao vivo (o preletor desenha -
  círculo, sublinhado - sobre o versículo no tablet, e a marcação
  sincroniza pro telão) já existe de verdade no sistema (ver
  `ProjecaoEstadoController::definirMarcacao`), mas não estava descrita
  em lugar nenhum do site - virou um novo diferencial na página
  `/recursos/projecao`.
- Menu "Recursos" da navbar ganhou um selo "Ao vivo" em Louvores e
  Projeção, destacando os dois entre os demais módulos da lista.
- Novo item na seção Benefícios explicando que cada igreja ganha um
  endereço próprio no sistema (ex: suaigreja.kadosys.com.br), separado
  de todas as outras - por onde os membros acessam o login, se
  cadastram e fazem doações.

---

## Ajuste 96 - 2026-07-14

**Site institucional: correção de textos desatualizados na home e no FAQ**

- Removidas todas as menções a "IA"/"inteligência artificial" como
  recurso do produto (hero, meta description, rodapé) - esse recurso
  nunca existiu de verdade (o antigo painel "Insights da IA" era texto
  fixo, substituído pelo painel "Novidades" no Ajuste 28) e ainda
  aparecia como diferencial de venda.
- Card "Inteligente" da seção Sobre trocado por "Em tempo real",
  destacando a sincronização ao vivo entre Modo Culto, telão e
  preletor (o diferencial real do sistema).
- Seção Recursos ganhou o card "Louvores e Modo Culto" que faltava -
  era um dos maiores diferenciais do sistema e não tinha card nenhum.
- Seção Funcionalidades e Benefícios reescritas com o que o sistema
  entrega hoje (dashboard, autocadastro de membros, permissões,
  relatórios, doação via Pix direto pra conta da igreja), sem falar
  mais em IA.
- Cards de plano (Essencial/Plus/Premium) e tabela de comparação
  corrigidos módulo a módulo conforme `Plano::MODULO_MINIMO` - removida
  a linha "Usuários administradores" (1 / ilimitados), que não é uma
  regra realmente aplicada no sistema, e a linha de troca de tom nos
  Playbacks, recurso removido do produto (Ajuste 45).
- FAQ reescrito: a pergunta sobre "troca de tom nos Playbacks" descrevia
  como recurso "em desenvolvimento", mas na verdade foi implementado e
  depois removido (Ajuste 43-45) por não ficar bom o suficiente; e a
  pergunta "quais módulos estarão disponíveis em seguida" listava
  Grupos, Agenda, Financeiro e Comunicação como futuros, sendo que os
  quatro já estão no ar. Foram trocadas por perguntas reais sobre como
  funciona o Modo Culto (tom ao vivo), os Playbacks (biblioteca de
  áudio), os níveis de permissão por usuário e as doações via Pix.

---

## Ajuste 95 - 2026-07-14

**Site institucional: página dedicada por módulo, com screenshot real do sistema**

- Cada módulo do sistema (Louvores/Modo Culto, Projeção/Telão, Agenda,
  Financeiro, Membros, Equipe, Ministérios, Grupos, Playbacks,
  Comunicação, Patrimônio e Relatórios) ganhou sua própria página
  pública em `/recursos/{modulo}` - hero com o diferencial principal,
  uma captura de tela real do sistema rodando, a lista de
  diferenciais daquele módulo, e "conheça também" pro próximo módulo.
  Antes só existia a home única com cards resumidos.
- Destaque pro Modo Culto (transposição automática de tom ao vivo,
  sincronizado com todo o time) e pra Projeção (telão sincronizado com
  o tablet do preletor em tempo real, com uma imagem mostrando os
  dois lado a lado) - os dois maiores diferenciais do sistema, que
  antes não apareciam em lugar nenhum do site.
- A seção "Capturas de tela" da home, que até então era só um
  placeholder ("imagens reais serão adicionadas..."), agora mostra
  3 screenshots reais (Louvores, Projeção, Agenda) linkando pras
  páginas completas.
- Cada card da seção "Recursos" da home ganhou um link "Saiba mais"
  pra página dedicada do módulo correspondente, e a navbar ganhou um
  menu suspenso "Recursos" listando todos os módulos (com equivalente
  em lista simples no menu mobile).
- O scroll-reveal que já existia na home (efeito de entrada conforme
  desce a tela) foi reaproveitado em todas as páginas novas - mesma
  animação, sem JS adicional.
- Ajuste técnico: os links da navbar/rodapé da home usavam âncoras
  relativas (`#recursos`) - funcionavam só na própria home. Agora
  sempre apontam pra `/#recursos`, funcionando a partir de qualquer
  página nova.

## Ajuste 94 - 2026-07-14

**Permissões: card não pré-marca mais "Sem acesso" quando o usuário não tem restrição nenhuma**

- Quando um usuário não tem nenhuma linha em Permissões (acesso total
  ao que o plano libera, como sempre foi o padrão de contas antigas),
  a tela mostrava cada módulo com "Sem acesso" pré-marcado - dava a
  entender visualmente o oposto do que o aviso azul acima já dizia
  (que o acesso é total). Agora, nesse estado, nenhuma opção fica
  marcada em nenhum card até o admin decidir restringir algum módulo.

## Ajuste 93 - 2026-07-14

**Corrige cartões de Permissões quase ilegíveis no tema claro**

- O cartão de cada módulo (Permissões e Configurações > Permissões
  padrão) usa um fundo escuro semi-transparente e título em branco
  pensados pro tema escuro - no tema claro isso deixava o nome do
  módulo (ex.: "Membros", "Financeiro") com contraste muito baixo,
  quase ilegível, tanto no cartão normal quanto no selecionado.
- Adicionado fundo e cor de texto próprios pro tema claro (mesmo
  problema do toggle switch no Ajuste 89 e dos rótulos "Sem
  acesso/Só visualizar/Editar" no Ajuste 92 - variáveis de cor
  pensadas só pro tema escuro sendo usadas sem revisar o claro).

## Ajuste 92 - 2026-07-14

**Permissões: nível "só visualizar" x "editar" por módulo, e perfil padrão pra novos acessos**

- Cada módulo liberado pra um usuário em Permissões agora tem um
  nível: "Sem acesso", "Só visualizar" (vê a tela, mas qualquer
  tentativa de salvar/excluir algo é bloqueada) ou "Editar" (acesso
  completo, como sempre foi). Antes era tudo ou nada - se o módulo
  estivesse liberado, dava pra editar; agora dá pra deixar alguém ver
  uma tela sem poder mexer nela.
- Nova seção em Configurações > "Permissões padrão para novos
  acessos": a igreja escolhe o nível de cada módulo que todo NOVO
  acesso de usuário já nasce com - seja criado no cadastro combinado
  de membro (Ajuste 88), pelo autocadastro público, ou manualmente em
  Usuários. Antes, um acesso criado manualmente nascia sem nenhuma
  restrição (via tudo que o plano libera) e o autocadastro público
  nascia sem acesso nenhum (o admin tinha que liberar cada módulo na
  mão) - agora os dois usam o mesmo perfil configurável.
- Perfil padrão de fábrica (antes de qualquer personalização): Agenda,
  Equipe, Cultos, Ministérios, Grupos, Membros e Playbacks liberados
  como "só visualizar"; Financeiro, Projeção, Patrimônio, Comunicação
  e Relatórios ficam de fora (sem acesso) até o admin liberar na mão -
  um começo que não expõe informação sensível por acidente.
- Migração 045 (rodar no banco de cada igreja): adiciona a coluna
  `nivel` em `user_modulos` e cria a tabela `permissoes_padrao`, já
  com o perfil de fábrica acima.
- Contas existentes não mudam de comportamento: usuário sem nenhuma
  restrição em Permissões continua acessando tudo que o plano libera,
  com edição completa, como sempre foi.

## Ajuste 91 - 2026-07-14

**Novo módulo Agenda: calendário com cultos, eventos e aniversariantes**

- A tela de Agenda agora abre num calendário mensal (antiga listagem
  continua disponível na aba "Lista") mostrando, no mesmo lugar: os
  cultos cadastrados, os eventos/reuniões/reservas do módulo Agenda e
  quem faz aniversário naquele mês - cada tipo com uma cor própria e
  legenda no rodapé.
- Evento da Agenda agora tem visibilidade: "Todo mundo" (como sempre
  foi - aparece pra qualquer usuário, ex.: ensaio do grupo marcado
  pelo admin/líder) ou "Só eu" (compromisso pessoal, ex.: uma visita -
  só quem cadastrou vê, tanto no calendário quanto na lista; tentar
  abrir a URL de edição de um evento privado de outra pessoa dá 404,
  mesmo pra outro usuário logado).
- Cadastro de culto ganhou opção de recorrência: marcando "Toda semana"
  e escolhendo até quando repetir, o sistema já cria um culto
  independente pra cada semana automaticamente (limite de segurança de
  60 ocorrências), evitando cadastrar manualmente toda semana um culto
  fixo (ex.: domingo às 18h).
- Aniversariantes deixam de ser só uma lista: todo dia, quem faz
  aniversário e tem e-mail cadastrado recebe automaticamente um e-mail
  de parabéns (novo cron `enviar_parabens_aniversario.php`, mesmo
  padrão dos crons de Pix já existentes - varre toda igreja
  provisionada automaticamente). A mensagem é personalizável em
  Configurações > Aniversariantes, com os marcadores `{nome}` e
  `{igreja}`; sem personalização, usa uma mensagem padrão. Uma tabela
  de controle (`aniversario_envios`) garante no máximo um e-mail por
  membro por ano, mesmo se o cron rodar mais de uma vez no mesmo dia -
  e só marca como enviado quando o envio realmente funciona (senão
  tenta de novo no dia seguinte).
- **Importante**: cadastrar o novo cron no "Cron Jobs" do cPanel, ex.:
  `php /home/kadosys1/apps/igrejas/cron/enviar_parabens_aniversario.php`
  rodando uma vez por dia (sugestão: de manhã, ex. 7h).
- Migração 044 (rodar no banco de cada igreja): adiciona
  `visibilidade`/`criado_por_user_id` em `agenda_eventos`,
  `mensagem_aniversario` em `configuracoes_igreja` e cria a tabela
  `aniversario_envios`.

## Ajuste 90 - 2026-07-14

**Modo Culto continua funcionando se a internet cair (modo offline de emergência)**

- Se o aparelho perder a internet durante o culto, o Modo Culto e a
  tela cheia de louvor não quebram mais - continuam mostrando a
  última cifra/tom/repertório recebidos em vez de dar erro ao
  recarregar a página. Assim que a conexão volta, a sincronização
  automática (polling) retoma sozinha, sem precisar recarregar nada.
- Feito com um Service Worker (`public/service-worker.js`) que guarda
  em cache as páginas e dados mais recentes; a rede é sempre tentada
  primeiro (quem está online não vê nada em cache), e o cache só
  entra em ação quando a rede falha de verdade.
- Um aviso aparece no topo da tela quando o app detecta que está
  offline, deixando claro que o aparelho está sem internet e o que
  fica indisponível nessa hora: no Modo Culto, avançar/voltar
  música, mudar tom e o chat exigem internet e ficam inativos; na
  tela cheia de louvor (menos dependente de rede) o aviso só avisa
  que está mostrando a última versão salva.
- Ações do líder (avançar música, mudar tom, mensagens) não são
  enfileiradas para reenvio depois - continuam exigindo conexão de
  verdade, pra evitar dessincronizar quem está vendo a projeção.
- Limitação: a primeiríssima visita a uma página, se já for feita
  offline (nunca carregou antes com internet), não tem o que mostrar
  do cache.

## Ajuste 89 - 2026-07-14

**Toggle switch quase invisível no tema claro**

- O fundo do switch desligado usava uma variável pensada pro tema
  escuro (branco bem transparente), que sobre um card branco do tema
  claro ficava quase invisível - não parecia um botão. Adicionado um
  cinza de verdade pro tema claro (mesmo tom já usado em outros
  controles, ex.: `.crud-icon-btn`), com a bolinha branca por cima pra
  manter contraste.

## Ajuste 88 - 2026-07-14

**Cadastrar membro já com acesso ao sistema, num passo só**

- Na tela de "Novo membro", nova seção "Acesso ao sistema" com um
  toggle "Criar acesso ao sistema para este membro" - antes disso era
  preciso cadastrar o membro, depois ir em Usuários e cadastrar de
  novo (nome/e-mail repetidos) só pra criar o login.
- Ligando o toggle aparecem os campos de senha e os toggles de
  "Músico"/"Líder de louvor" - o e-mail e o nome usados são os mesmos
  já preenchidos em cima, sem duplicar campo. Cargo na Equipe é
  ajustado automaticamente (Músico se marcado, senão Membro).
- Se o e-mail já estiver em uso por outro usuário, ou a senha não
  conferir, nada é criado (nem o membro, nem o usuário) - o formulário
  volta com os erros e mantém a seção aberta.
- Só disponível no cadastro (não na edição de um membro já existente).

## Ajuste 87 - 2026-07-14

**Toggle switch (Músico, Líder de louvor, etc.) em cima do texto em
vez de do lado**

- Quando um toggle switch (`.toggle-switch-field`) fica dentro de um
  campo `.crud-field` (padrão usado nos formulários de cadastro), uma
  regra de CSS mais genérica (`.crud-field label`) tinha mais peso e
  forçava `display: block`, cancelando o `display: flex` que
  posiciona o switch ao lado do texto - o switch encolhia e ficava em
  cima da primeira letra do rótulo.
- Afetava o cadastro/edição de usuário ("Músico", "Líder de louvor")
  e o de louvor ("Remover anexo atual"). Corrigido aumentando a
  especificidade da regra do toggle.
- Testado visualmente com Playwright antes/depois em ambas as telas.

## Ajuste 86 - 2026-07-14

**Louvores: anotações pessoais (privadas) em cada música**

- Nova seção "Minhas anotações" na página de detalhe do louvor: cada
  músico pode escrever lembretes pessoais (ex.: "usar capotraste na 2ª
  casa", "trocar pra guitarra limpa", "solo começa no segundo
  refrão"), visíveis SÓ pra quem escreveu - nunca compartilhado com o
  resto do time.
- Migração 043: nova tabela `louvor_anotacoes` (uma anotação por
  usuário por louvor), já refletida em `install.sql`.
- Deixar o texto em branco e salvar apaga a anotação.

## Ajuste 85 - 2026-07-14

**Novo módulo Equipe: galeria estilo rede social (foto + cargo +
instrumento) e autoatendimento de perfil**

- Novo módulo "Equipe" (`/dashboard/equipe`), aberto pra qualquer
  usuário com login: uma galeria em cards com foto, nome e um badge de
  cargo - Músico (com ícone do instrumento: bateria, guitarra, baixo,
  violão, teclado, vocal), Mídia, Equipamento (mesa de som) ou Membro
  (mostra o logo da igreja como ícone).
- Cada pessoa edita o PRÓPRIO perfil (foto, cargo, instrumento) em
  "Meu perfil" (`/dashboard/perfil`, atalho no topo do menu lateral,
  com a própria foto) - separado da tela de Usuários (que continua só
  pra admin gerenciar login/senha/papel/permissões). O admin também
  pode definir cargo/instrumento de qualquer usuário direto no
  cadastro dele.
- Foto: PNG/JPG/WEBP até 5MB, mesmo padrão de upload já usado em
  Playbacks/Configurações (pasta por igreja, nome de arquivo
  aleatório, foto antiga apagada ao trocar).
- Migração 042: `users` ganha `cargo`, `instrumento` e `foto_path` -
  já incluída no `install.sql` (aprendemos com o Ajuste 75: sem isso,
  igrejas novas ficariam sem o módulo até rodar a migração à mão).
- Corrigido de brinde: `/dashboard/perfil` precisava ficar acessível
  pra QUALQUER usuário mesmo com acesso restrito em Permissões (é
  autoatendimento, não um módulo de verdade) - adicionado à lista de
  exceções do `AuthMiddleware`, testado com um usuário restrito
  confirmando acesso liberado ao perfil e bloqueado no restante.

## Ajuste 84 - 2026-07-14

**Projeção e Louvores destacados no topo do menu ("Ao vivo")**

- Esses dois módulos são usados AO VIVO durante o culto (telão pro
  operador, cifras pro time de louvor) - antes ficavam misturados no
  meio da lista alfabética/lógica de módulos administrativos, difícil
  de achar rápido em cima da hora.
- Criado um grupo novo "Ao vivo" no topo do menu lateral, logo abaixo
  de "Dashboard", com Projeção e Louvores destacados: borda de acento
  verde e um pontinho pulsando (só quando o módulo está liberado pro
  usuário, sem cadeado). O restante dos módulos continua na lista
  "Módulos" normal, sem duplicar os dois que subiram.

## Ajuste 83 - 2026-07-14

**Icone de Modo Culto na listagem de repertorios nao abria em nova
janela**

- O botao "Abrir Modo Culto" na tela do editor ja abre em nova aba
  (`target="_blank"`), mas o icone equivalente na listagem de
  repertorios ficou faltando esse atributo - clicar nele navegava na
  MESMA aba, tirando o lider da tela de gerenciamento. Corrigido pra
  abrir em nova janela/aba, igual ao resto do modulo.

## Ajuste 82 - 2026-07-14

**Sugestão automática de tom tinha o mesmo bug de detecção de acorde do
Ajuste 81 (fix ficou incompleto)**

- O Ajuste 81 corrigiu o detector de "linha de acordes" no transpositor
  do cadastro e na mudança de tom ao vivo do Modo Culto, mas esqueceu de
  uma terceira cópia da mesma lógica: a sugestão automática de tom ao
  colar a cifra (`louvor-sugestao-tom.js`) - que ainda aceitava qualquer
  letra depois da nota como qualidade de acorde válida.
- Corrigido usando a mesma lista fechada de qualidades/extensões dos
  outros dois lugares.

## Ajuste 81 - 2026-07-14

**Corrigido bug serio: transpor tom as vezes corrompia palavras da
letra**

- O detector de "linha de acordes" (que decide se transpõe uma linha
  ou deixa ela como letra) aceitava QUALQUER combinacao de letras/
  numeros depois da nota como se fosse uma qualidade de acorde valida
  (m, 7, sus4 etc.). Isso significa que uma linha de letra composta so
  por palavras que comecam com A-G (bem comum em português: "Deus Fala
  Comigo", "Graça e Amor", "Bendito", "Coração"...) era interpretada
  errado como "linha de acordes" e tinha suas palavras mutiladas na
  transposicao (ex.: "Deus" virando "C#eus").
- Corrigido nos dois lugares que fazem essa deteccao (o botao "Transpor"
  do cadastro E a mudanca de tom ao vivo no Modo Culto, que usa a mesma
  logica portada pro PHP): agora só uma lista fechada de qualidades/
  extensões de acorde reais (m, 7, 9, dim, sus2, sus4, add9, maj7, 7M,
  m7b5 etc.) é aceita - qualquer outra coisa é tratada como letra normal
  e nunca é transposta.
- Testado com varias linhas de acordes reais (incluindo baixo com
  inversão, extensões entre parênteses, sustenido/bemol) pra garantir
  que continuam transpondo certinho, e com linhas de letra em português
  que antes quebravam, confirmando que agora ficam intactas.

## Ajuste 80 - 2026-07-14

**Modo Culto: botao X do chat "Avisos rapidos" nao fechava o painel**

- O painel de chat (`.mc-chat`) e os controles de tom do lider
  (`.mc-tom-controles`) tinham `display: flex` fixo no CSS, sem
  excecao pro atributo `hidden` - como uma regra de CSS do proprio site
  sempre tem prioridade sobre o estilo padrao do navegador pro atributo
  `hidden`, marcar o elemento como escondido no JavaScript (clicar no X)
  nao tinha efeito nenhum visualmente, mesmo o codigo rodando certo por
  baixo dos panos.
- Corrigido adicionando `.mc-chat[hidden] { display: none; }` e
  `.mc-tom-controles[hidden] { display: none; }` - agora o X do chat (e
  o painel de "primeira vez", antes de escolher a musica) escondem de
  verdade.

## Ajuste 79 - 2026-07-14

**Modo Culto: mudar o tom ao vivo agora transpõe a letra/cifra de
verdade, e tons fora da lista não quebram mais a exibição**

- Não precisa importar nada no banco pra isso - a funcionalidade usa só
  tabelas que já existiam.
- Corrigido: mudar o tom ao vivo no Modo Culto só trocava o rótulo do
  tom (registrava no histórico), mas os acordes escritos na letra/cifra
  continuavam no tom antigo. Agora a letra/cifra são transpostas de
  verdade pro novo tom, igual ao botão "Transpor" do cadastro (mesmo
  cálculo, portado pro PHP em `Igrejas\Core\Transpositor`, já que o Modo
  Culto só exibe o que está salvo no banco).
- Corrigido: quando a música atual estava num tom que não é uma das
  opções fixas do seletor (grafia antiga, ex.: cadastrada antes do
  Ajuste 76), o seletor ficava com o valor "desmarcado" por baixo dos
  panos - fazendo os botões de meio tom (+/-) calcularem a partir do
  tom errado e não mudarem nada visível. Agora o tom atual sempre
  aparece certo no seletor, mesmo quando é uma grafia fora da lista
  padrão.

## Ajuste 78 - 2026-07-13

**Aviso visual quando o tom muda (transposição e Modo Culto)**

- Ao clicar em "Transpor Letra/Cifra automaticamente" no cadastro do
  louvor, a letra/cifra mudavam de tom silenciosamente - sem nenhuma
  confirmação visível de que a transposição realmente aconteceu. Agora
  aparece um aviso flutuante ("Tom alterado para X - letra e cifra
  transpostas.") chamando atenção.
- No Modo Culto, quando o líder muda o tom da música ao vivo, além do
  aviso no chat (ver Ajuste 77), agora aparece o mesmo tipo de aviso
  flutuante na tela de TODOS os músicos assim que a mudança chega pelo
  polling - reforça visualmente que o tom mudou, mesmo pra quem não
  está de olho no chat.

## Ajuste 77 - 2026-07-13

**Modo Culto: líder muda o tom da música ao vivo, sincronizado pra todos**

- No Modo Culto, o líder agora consegue mudar o tom da música que está
  tocando na hora (dropdown com todos os tons, ou botões rápidos de
  meio tom pra cima/baixo) sem precisar sair da tela e ir editar o
  louvor.
- A mudança grava de verdade no cadastro do louvor (mesma tabela usada
  em qualquer outro lugar do sistema) e entra no histórico de tons,
  igual a qualquer outra alteração de tom.
- Todos os músicos no Modo Culto recebem a mudança automaticamente no
  próximo poll (mesmo mecanismo de sincronização já usado pra
  avançar/voltar a música), e um aviso automático aparece no chat
  ("Fulano mudou o tom de 'Música' para X"), pra quem não estiver de
  olho no tom exibido perceber.

## Ajuste 76 - 2026-07-13

**Tom "Db" (e outras grafias antigas) sumia do formulario do louvor**

- A lista fixa de "Tom atual" (`Louvor::TONS_MAIORES`/`TONS_MENORES`) so
  tem uma grafia por nota (ex.: `C#`, nao `Db`; `F#`, nao `Gb`). Louvores
  cadastrados antes desse `<select>` existir (quando o campo era texto
  livre) podiam ter salvo `tom_atual` numa grafia que nao esta nessa
  lista - o `<select>` nao achava a opcao correspondente e mostrava
  silenciosamente "Nenhum" em vez do tom real, arriscando sobrescrever o
  tom certo caso o formulario fosse salvo sem o usuario notar.
- Corrigido: se o tom salvo nao estiver nas listas padrao, o formulario
  agora mostra ele mesmo assim, num grupo separado "Outro (grafia
  antiga, ajuste se puder)", selecionado - nada e perdido ou trocado sem
  o usuario escolher outro tom de propósito.

## Ajuste 75 - 2026-07-13

**install.sql desatualizado: igrejas novas nao ganhavam o modulo de
Louvores/Programacao de Culto nem os controles de volume do video do
telao**

- `database/install.sql` e o schema usado para provisionar o banco de
  QUALQUER igreja nova (ver `Provisionador.php`) - e diferente das
  migracoes numeradas em `database/migrations/`, que rodam manualmente
  banco a banco. Ele estava desatualizado desde a migracao 038: faltavam
  as colunas `musico`/`lider_louvor` em `users`, as colunas de volume/mudo/
  reiniciar do video em `projecao_estados`, e as tabelas inteiras
  `louvores`, `louvor_tons_historico`, `repertorios`, `repertorio_itens` e
  `repertorio_mensagens`.
- Ou seja: toda igreja cadastrada depois que essas funcionalidades foram
  lancadas ficou sem o modulo Louvores e sem Programacao de Culto (Modo
  Culto) ate rodar as migracoes manualmente no banco dela.
- Corrigido: `install.sql` atualizado com todo o schema das migracoes
  038 a 041, testado do zero contra um banco vazio pra garantir que roda
  sem erro (incluindo a ordem correta da FK `repertorios.atual_item_id`,
  que so pode ser criada depois de `repertorio_itens` existir).
- Igrejas ja provisionadas nesse intervalo continuam precisando rodar as
  migracoes 038-041 manualmente - esse ajuste so vale pra igrejas novas
  daqui pra frente.

## Ajuste 74 - 2026-07-13

**Louvores: sugestão automática de tom + "tocado por último"**

- Ao colar a letra (com cifra junto) ou preencher a Cifra, o campo
  "Tom atual" é sugerido automaticamente a partir do primeiro acorde
  reconhecido no texto - economiza um clique, já que a informação já
  estava no que foi colado. Só sugere enquanto o campo estiver vazio;
  se a pessoa escolher um tom na mão, a sugestão automática para (não
  fica brigando com a escolha).
- Nova coluna "Tocado por último" na listagem de Louvores (ex.: "Há 3
  dias", "Nunca tocado") - toda vez que um louvor vira a música "atual"
  no Modo Culto, fica registrado quando tocou de verdade, ajudando o
  time a variar o repertório em vez de repetir sempre os mesmos.

Arquivos novos: `apps/igrejas/database/migrations/041_add_ultima_execucao_louvor.sql`,
`apps/igrejas/public/assets/js/louvor-sugestao-tom.js`.

Arquivos alterados: `apps/igrejas/src/Models/Louvor.php` (campo
`ultima_execucao` + `marcarExecutado()`),
`apps/igrejas/src/Controllers/RepertorioController.php` (marca o louvor
como executado ao avançar a música atual),
`apps/igrejas/resources/views/dashboard/louvores/{index,form}.php`.

**IMPORTANTE:** rodar a migração 041 no banco de cada igreja depois do
deploy (mesma regra das outras migrações de módulo).

## Ajuste 73 - 2026-07-13

**Novo submódulo: Programação de Culto (repertório) + Modo Culto ao vivo**

Novo submódulo dentro de Louvores: o líder de louvor (nova flag
"Líder de louvor" na tela de Usuários, ao lado de "Músico") monta e
arrasta a ordem dos louvores de um culto - a mudança é sincronizada em
tempo real (por polling a cada ~1.2s, mesmo mecanismo já usado em
Projeção/Telão - sem WebSocket, funciona na hospedagem compartilhada)
pra todo o time no **Modo Culto**: uma tela dedicada, sem menu do
painel, mostrando só a música atual (letra/cifra com abas, tom e
andamento em BPM). O líder avança/volta a música direto por lá, e todo
mundo acompanha instantaneamente.

Incluído também um canal discreto de avisos rápidos entre os músicos
(ex.: "abaixa meio tom") - fica escondido até ser aberto de propósito
(ícone de chat no topo, com contador de não lidas), sem poluir a tela
principal, e guarda histórico entre cultos.

Novo campo "Andamento (BPM)" no cadastro do louvor (aparece ao lado do
tom no Modo Culto e na tela cheia).

Arquivos novos: `apps/igrejas/database/migrations/040_create_repertorio_culto.sql`,
`apps/igrejas/src/Models/{Repertorio,RepertorioItem,RepertorioMensagem}.php`,
`apps/igrejas/src/Controllers/RepertorioController.php`,
`apps/igrejas/resources/views/layouts/modo-culto.php`,
`apps/igrejas/resources/views/dashboard/louvores/repertorios/{index,form,editor,culto}.php`,
`apps/igrejas/public/assets/css/repertorio-culto.css`,
`apps/igrejas/public/assets/js/{repertorio-editor,repertorio-culto}.js`.

Arquivos alterados: `apps/igrejas/src/Models/User.php` (campo
`lider_louvor`), `apps/igrejas/src/Models/Louvor.php` (campo
`andamento_bpm` + lista pro seletor de repertório),
`apps/igrejas/src/Controllers/{UsuarioController,LouvorController}.php`,
`apps/igrejas/routes/web.php`,
`apps/igrejas/resources/views/dashboard/usuarios/{form,index}.php`,
`apps/igrejas/resources/views/dashboard/louvores/{index,form,show,tela-cheia}.php`,
`apps/igrejas/public/assets/css/crud.css`.

**IMPORTANTE:** rodar a migração 040 no banco de cada igreja depois do
deploy (mesma regra das outras migrações de módulo).

## Ajuste 72 - 2026-07-13

**Louvores: modo de cadastro (letra+cifra juntas ou separadas) e
correção do PDF**

- Corrigido: a tela cheia/PDF só imprimia o campo Cifra, mesmo quando o
  louvor tinha a cifra colada JUNTO da letra (comum ao colar direto do
  Cifra Club) - agora letra e cifra saem as duas no PDF, cada uma com
  seu próprio título de seção, independente de qual aba estava
  selecionada na tela.
- Novo cadastro pergunta primeiro "como você vai cadastrar esse
  louvor": letra e cifra juntas (modo padrão, pensado pra colar direto
  do Cifra Club) ou em campos separados - escolher a segunda opção
  revela o campo Cifra dedicado. Facilita pro músico que só quer colar
  de um jeito ou de outro, sem ficar com os dois campos preenchidos com
  informação redundante/confusa.

Arquivos novos: `apps/igrejas/public/assets/js/louvor-form.js`.

Arquivos alterados: `apps/igrejas/resources/views/dashboard/louvores/{form,tela-cheia}.php`,
`apps/igrejas/public/assets/css/{louvor-tela-cheia,crud}.css`.

## Ajuste 71 - 2026-07-13

**Louvores: tela cheia (auto-scroll + PDF), transposição automática de
tom e ícone de visualizar**

- Ícone de "olho" na listagem de louvores, ao lado de Editar, levando
  direto pra página de detalhe (letra, cifra e histórico de tons).
- Nova tela cheia (`/dashboard/louvores/{id}/tela-cheia`), sem menu do
  painel, pensada pra abrir numa segunda tela (tablet do músico, monitor
  no palco): letra/cifra com fonte ajustável, auto-scroll com controle
  de velocidade (pra rolar sozinho sem tirar a mão do instrumento) e
  botão de tela cheia de verdade (Fullscreen API do navegador). O botão
  "Baixar PDF" usa o diálogo de impressão nativo do navegador (sem
  precisar de nenhuma biblioteca de PDF no servidor, que costuma exigir
  dependências que a hospedagem compartilhada não tem).
- "Tom atual" virou um `<select>` com os 24 tons (12 maiores + 12
  menores), em vez de campo de texto livre.
- Transposição automática de cifra: reconhece as linhas que são só
  acordes (tanto na Cifra quanto na Letra colada direto do Cifra Club,
  que já vem com os acordes juntos) e desloca cada nota
  proporcionalmente ao trocar de tom - mesmo princípio de um
  transpositor de cifra club, calculado no navegador (sem precisar de
  nada no servidor).

Arquivos novos: `apps/igrejas/resources/views/layouts/tela-cheia.php`,
`apps/igrejas/resources/views/dashboard/louvores/tela-cheia.php`,
`apps/igrejas/public/assets/css/louvor-tela-cheia.css`,
`apps/igrejas/public/assets/js/louvor-tela-cheia.js`,
`apps/igrejas/public/assets/js/louvor-transpositor.js`.

Arquivos alterados: `apps/igrejas/src/Models/Louvor.php` (lista de tons),
`apps/igrejas/src/Controllers/LouvorController.php` (ação `telaCheia`),
`apps/igrejas/routes/web.php`,
`apps/igrejas/resources/views/dashboard/louvores/{index,form,show}.php`.

## Ajuste 70 - 2026-07-13

**Novo módulo: Louvores (letras, cifras e tons) + tag de músico**

Novo módulo pro time de louvor: cadastro de louvores com letra, cifra,
tom atual e um anexo opcional (PDF/imagem da cifra escrita à mão),
podendo linkar um áudio já cadastrado em Playbacks. Toda vez que o tom
de um louvor muda, fica registrado um histórico (quem mudou, quando e de
qual tom pra qual) - resolve a bagunça de cada departamento mudar o tom
e ninguém saber qual é o "oficial".

Acesso ao módulo é liberado automaticamente pra usuários marcados como
"Músico" na tela de Usuários (novo campo, ao lado de Papel) - sem
precisar mexer em Permissões pra cada pessoa do time. Um usuário comum
(sem essa marcação) continua sem acesso, mesmo que não tenha nenhuma
restrição em Permissões (diferente dos outros módulos, que ficam
liberados por padrão pra qualquer 'usuario' do plano contratado).

Arquivos novos: `apps/igrejas/database/migrations/039_create_louvores_musico.sql`,
`apps/igrejas/src/Models/Louvor.php`, `apps/igrejas/src/Models/LouvorTomHistorico.php`,
`apps/igrejas/src/Controllers/LouvorController.php`,
`apps/igrejas/resources/views/dashboard/louvores/{index,form,show}.php`.

Arquivos alterados: `apps/igrejas/src/Models/User.php` (campo `musico`
+ regra de acesso), `apps/igrejas/src/Models/Playback.php` (lista
enxuta pro combo de vincular áudio), `apps/igrejas/src/Controllers/UsuarioController.php`,
`apps/igrejas/src/Controllers/DashboardController.php`,
`apps/igrejas/src/Controllers/PermissaoController.php`,
`apps/igrejas/routes/web.php`,
`apps/igrejas/resources/views/dashboard/usuarios/{form,index}.php`,
`apps/igrejas/public/assets/css/crud.css`.

**IMPORTANTE:** rodar a migração 039 no banco de cada igreja depois do
deploy (mesma regra das outras migrações de módulo).

## Ajuste 69 - 2026-07-13

**Painel do operador: comandos de volume, mudo e reiniciar no vídeo**

Além de Play/Pausar/Fadeout, o painel agora tem um slider de volume
(0-100), um botão de mudo/desmudo e um botão de reiniciar (volta o vídeo
pro segundo 0 sem precisar recarregar o link). Os três comandos são
persistidos no servidor (nova migração 038) e aplicados no telão via
polling, do mesmo jeito que os outros comandos de vídeo - inclusive
funcionam vindos de outro dispositivo (ex.: o preletor, se algum dia
ganhar os mesmos controles).

Aproveitado também para trocar `/preletor` e `/telao` (mostrados como
texto simples na tela do operador) pela URL completa (com domínio) -
sem isso, quem não é da área técnica não sabia que precisava completar
o endereço com o domínio da igreja antes de digitar no navegador do
tablet do pastor ou da Smart TV.

Arquivos alterados: `apps/igrejas/database/migrations/038_add_video_volume_mudo_reiniciar.sql`
(nova), `apps/igrejas/src/Models/ProjecaoEstado.php`,
`apps/igrejas/src/Controllers/ProjecaoEstadoController.php`,
`apps/igrejas/src/Controllers/ProjecaoController.php`,
`apps/igrejas/src/Core/Controller.php`, `apps/igrejas/routes/web.php`,
`apps/igrejas/resources/views/dashboard/projecao/index.php`,
`apps/igrejas/public/assets/css/biblia-picker.css`,
`apps/igrejas/public/assets/js/projecao-admin.js`,
`apps/igrejas/public/assets/js/telao.js`.

**IMPORTANTE:** rodar a migração 038 no banco de cada igreja depois do
deploy (mesma regra das outras migrações de módulo).

## Ajuste 68 - 2026-07-13

**Painel do operador: barrinha de progresso do video "voltando" no meio da
contagem**

O telao reporta o tempo do video pro servidor a cada 2 segundos, mas o
painel do operador consulta o servidor a cada 1.5 segundos - como os dois
ciclos nao sao sincronizados, boa parte das consultas cai bem no meio de
duas atualizacoes reais e volta um valor levemente atrasado. Antes, o
painel jogava esse valor direto na tela, dando a impressao de que o video
tinha voltado do comeco (mesmo ele continuando tocando normalmente).

Agora o painel mantem uma contagem local que avanca sozinha 1s por vez (um
cronometro de verdade), so aceitando o valor do servidor quando ele esta
igual ou a frente do que ja esta sendo exibido - ou quando a duracao muda
(sinal de que e um video novo, aceita o valor mesmo que seja menor que o
anterior). Confirmado com teste automatizado simulando leituras
"atrasadas" do servidor (barra nunca mais recua) e simulando a troca real
de video (novo tempo e aceito normalmente).

Arquivo alterado: `apps/igrejas/public/assets/js/projecao-admin.js`.

## Ajuste 67 - 2026-07-13

**YouTube: achada a causa real da tela preta muda que so um F5 resolvia**

- Bug reportado ao vivo com print do Console do navegador (obrigado pela
  paciencia em coletar isso!): o video ficava com tela preta e SEM
  audio nenhum, esperando 30+ segundos sem nenhuma recuperacao
  automatica - so um F5 manual resolvia. Confirmado tambem numa aba
  anonima (zerada), descartando qualquer acumulo de estado do
  navegador.
- Causa raiz: o Console mostrava `DOMException: An invalid or illegal
  string was specified` repetindo sem parar dentro do proprio script
  do YouTube (`www-widgetapi.js`, funcao interna `sendMessage`) - um
  bug conhecido de comunicacao entre o iframe do YouTube e a pagina,
  que pode acontecer no primeiro carregamento (ligado a protecoes de
  rastreamento/particionamento de cookies de terceiros de alguns
  navegadores). Isso fazia `player.getPlayerState()` (usado pelo vigia
  de recuperacao automatica) LANCAR EXCECAO em vez de so devolver "nao
  iniciado" - e o codigo, ao capturar esse erro, desistia da
  recuperacao SILENCIOSAMENTE, sem tentar nada. Por isso nunca
  recarregava sozinho, mesmo esperando bastante tempo.
- Corrigido: uma excecao repetida (~3s) ao consultar o player agora e
  tratada como um sinal de travamento tambem, disparando a mesma
  recuperacao automatica (reload unico, depois aviso na tela) ja usada
  nos outros casos.
- Validado com testes automatizados reproduzindo exatamente esse
  DOMException reportado - confirmado que o reload automatico agora
  dispara em poucos segundos, e que uma segunda falha (reload ja
  usado) mostra o aviso de erro em vez de ficar num loop.

## Ajuste 66 - 2026-07-13

**Revisao geral de acentuacao PT-BR em todo o sistema**

- Pedido: revisar os textos do site (pagina publica e sistema interno)
  incluindo acentuacao correta em portugues, ex.: "gratis" -> "Grátis".
- Todo o texto visivel ao usuario foi revisado - landing page, tela de
  cadastro, doacao/Pix publico, login, todas as telas do dashboard
  (membros, ministerios, grupos, cultos, agenda, financeiro,
  patrimonio, comunicacao, relatorios, usuarios, permissoes,
  configuracoes, faturas, playbacks, projecao), telao/preletor, painel
  da plataforma, mensagens de erro/sucesso e dialogos de confirmacao.
- NAO foram alterados: nomes de variaveis/funcoes/classes, chaves de
  array, nomes de rotas/URLs, classes CSS, nomes de colunas do banco e
  comentarios de codigo (esses continuam sem acento, que e so
  documentacao interna pra quem mexe no codigo, nao texto do site).
- Validado com `php -l`/`node --check` em todos os arquivos alterados
  (93 arquivos) e testado visualmente no navegador (landing page,
  login e varias telas do dashboard) - tudo carregando normalmente,
  sem erros e com os acentos aparecendo corretamente.

## Ajuste 65 - 2026-07-10

**YouTube: video ficava com tela preta sem audio pra sempre, mesmo esperando bastante tempo**

- Bug reportado ao vivo (mesmo depois dos Ajustes 39/58/59 ja terem
  corrigido outros casos de tela preta): quando o telao ja estava
  aberto ha um tempo (parado em texto/branco) e um video era projetado
  pela primeira vez, a tela ficava preta e MUDA, sem o mecanismo de
  reload automatico nunca disparar - so um F5 manual resolvia.
- Causa raiz encontrada: o telao reaplica o video a cada consulta ao
  servidor (~1.5s) enquanto o modo for video, de proposito, pra dar
  outra chance caso a primeira tentativa de carregar falhe. O problema
  e que `getVideoData()` do player do YouTube pode demorar mais que
  1.5s pra refletir o video recem-carregado (atraso normal da propria
  API) - entao o codigo concluia (errado) que precisava carregar de
  novo, chamando `loadVideoById()` repetidas vezes seguidas. Cada
  chamada nova reiniciava o carregamento do zero, entao o video nunca
  tinha os poucos segundos continuos que precisa pra realmente comecar
  - e como o estado ficava sendo reiniciado o tempo todo (nunca
  "travado" por tempo suficiente), nem o proprio vigia de recuperacao
  automatica conseguia detectar o problema e disparar o reload sozinho.
- Corrigido: enquanto ja existe uma checagem de reproducao ativa pra um
  video (ver `agendarChecagemReproducao`), o telao para de tentar
  recarregar esse mesmo video a cada consulta - deixa o vigia observar
  o player por um tempo continuo de verdade (alguns segundos), tempo
  suficiente pra reproducao normal comecar OU pro reload automatico
  disparar de verdade se estiver mesmo travado.
- Validado com testes automatizados simulando exatamente o atraso real
  do `getVideoData()` (confirmado que `loadVideoById()` agora e chamado
  so 1 vez, e o video toca normalmente sem reload nenhum) e o caso de
  travamento genuino (confirmado que o reload automatico continua
  funcionando normalmente, sem regressao).

## Ajuste 64 - 2026-07-10

**IMPORTANTE: rode a migracao 037 no banco CENTRAL antes de acessar/publicar avisos**

- Nova coluna `publico_alvo` em `plataforma_avisos` (banco central).
  Sem rodar `database/migrations/037_add_publico_alvo_plataforma_avisos.sql`
  no banco central, a pagina `/plataforma/avisos` da erro.

**Avisos da plataforma agora escolhem o publico: admins, membros ou todos**

- Ao publicar um aviso em `/plataforma/avisos`, agora da pra escolher
  quem ve: so admins de cada igreja, so membros comuns, ou todo mundo
  (continua sendo o padrao, igual antes). Avisos ja publicados antes
  desta mudanca continuam visiveis pra todo mundo (nao muda nada pra
  quem ja existia).
- O filtro se aplica tanto no sino de notificacoes quanto no painel
  "Atualizacoes do sistema" (Ajuste 63) da dashboard de cada igreja -
  um aviso "so admins" nao aparece mais pra membros comuns, e um aviso
  "so membros" nao aparece mais pra admins.

## Ajuste 63 - 2026-07-10

**Dashboard do cliente: painel "Novidades" virou "Atualizacoes do sistema" (so pra admins) e para de acumular avisos ja encerrados**

- Duas coisas diferentes estavam misturadas: o aviso que o DONO da
  plataforma publica pra todas as igrejas (`/plataforma/avisos`) e o
  aviso que CADA IGREJA cria pros seus proprios membros (modulo
  Comunicacao) - o segundo ja funciona certo (aparece pra quem tem
  acesso ao modulo, admin ou nao, e ja tem opcao de excluir).
- O problema era so no primeiro: o painel principal do dashboard
  ("Novidades") mostrava os ultimos 5 avisos da plataforma pra
  QUALQUER usuario da igreja, mesmo os ja encerrados pelo dono da
  plataforma - ficava acumulando aviso velho sem nunca sumir.
- Corrigido: o painel (renomeado "Atualizacoes do sistema") agora so
  aparece pra admins, e mostra so o aviso ATIVO no momento (some
  assim que o dono da plataforma clica em "Encerrar"). O sino de
  notificacoes no topo (visivel pra todo mundo) nao mudou - ja
  filtrava certo antes.

## Ajuste 62 - 2026-07-10

**IMPORTANTE: rode a migracao 036 no banco CENTRAL antes de acessar o painel da plataforma**

- Nova coluna `ultimo_acesso_em` em `plataforma_tenants` (banco central,
  o mesmo que recebe os cadastros publicos - NAO e no banco de cada
  igreja). Sem rodar `database/migrations/036_add_ultimo_acesso_tenants.sql`
  no banco central, a pagina `/plataforma/igrejas` da erro.

**Painel da plataforma (`/plataforma/igrejas`): ultimo acesso e situacao de pagamento de cada igreja**

- Pedido: informacoes importantes pra controlar melhor as igrejas
  provisionadas, tipo ultimo acesso e se o pagamento esta em dia.
- Adicionada a coluna **Ultimo acesso**: registra o momento do ultimo
  login com sucesso de qualquer usuario daquela igreja (gravado direto
  no banco central em `AuthController::login()`), mostrando "Nunca
  acessou" pra quem ainda nao entrou.
- Adicionada a coluna **Situacao do pagamento**: resume de relance se
  esta em dia, atrasado, em teste gratis ou pendente de confirmacao -
  mesma logica de bloqueio ja usada em AuthMiddleware (trial vencido,
  fatura Pix vencida, assinatura de cartao nao autorizada), so que
  resumida numa badge por igreja em vez de decidir se bloqueia ou nao
  o acesso.
- Coluna antiga "Pagamento" virou "Metodo" (Pix/Cartao/Teste gratis),
  mantendo so o metodo escolhido, separado da situacao.

## Ajuste 61 - 2026-07-10

**Faturas: extrato de cobrancas de cartao direto da API do Mercado Pago**

- Pedido: quem paga por cartao so via um badge de status na tela de
  Faturas, sem ver as cobrancas de fato (o texto so mandava conferir
  direto no Mercado Pago). O Mercado Pago tem uma API pra listar os
  pagamentos ja debitados de uma assinatura recorrente
  (`/authorized_payments/search`) - antes nao usada em lugar nenhum do
  sistema.
- Adicionado `MercadoPagoClient::buscarPagamentosAssinatura()` (busca
  esse extrato) e `FaturaController::buscarPagamentosCartao()` (chama a
  API e traduz pro formato da tela). A tela agora mostra data, valor e
  status (Pago/Pendente/Recusado/Cancelado) de cada cobranca, com
  fallback pro aviso antigo se a consulta falhar ou o pagamento online
  nao estiver configurado.

## Ajuste 60 - 2026-07-10

**Nova tela de Faturas no menu lateral**

- Pedido do admin: uma tela pra ver o historico de cobrancas da igreja -
  vencimento, se foi paga e opcao de pagar agora.
- Novo item "Faturas" no menu lateral (`/dashboard/faturas`), visivel
  pra admins e pra quem o admin liberar via Permissoes (mesmo padrao dos
  outros modulos opcionais, ex.: Financeiro) - disponivel em todos os
  planos, sem bloqueio mesmo com plano contratado limitado.
- Mostra: status atual (teste gratis, assinatura por cartao com o status
  da cobranca recorrente, ou historico completo de cobrancas Pix com
  vencimento/valor/status de cada uma) e um botao "Pagar agora" na
  cobranca Pix pendente mais recente (reaproveita a mesma tela de QR code
  ja usada quando uma fatura vence).

## Ajuste 59 - 2026-07-10

**So clicar em "Assinar" (mesmo sem terminar o pagamento) ja derrubava o teste gratis**

- Bug reportado ao vivo: com o teste gratis ainda valido, o admin clicou
  em "Assinar" no plano Essencial so pra ver a tela do Mercado Pago (sem
  chegar a pagar) - e o painel de Configuracoes ja mostrava o plano como
  contratado, com "Pagamento pendente", perdendo o aviso de dias restantes
  do teste gratis. Causa: o metodo de pagamento (cartao/Pix) era marcado
  no banco assim que a assinatura/cobranca era CRIADA, e nao quando o
  pagamento de fato era CONFIRMADO pelo Mercado Pago (webhook) - a
  intencao original era so liberar quem ja estava com o teste VENCIDO e
  bloqueado, mas a marcacao acontecia sempre, mesmo com teste ainda
  valido.
- Corrigido: a marcacao imediata (antes da confirmacao) so acontece
  agora quando o teste gratis ja estava realmente vencido (pra nao deixar
  quem ja estava bloqueado esperando o webhook). Fora esse caso, o metodo
  de pagamento so muda de fato quando o Mercado Pago confirma o
  pagamento via webhook - clicar em "Assinar" e nao terminar o pagamento
  agora nao afeta o teste gratis em andamento.

## Ajuste 58 - 2026-07-10

**Telao ainda ficava com tela preta no YouTube - agora tenta recarregar sozinho antes de so avisar**

- Bug reportado ao vivo mesmo depois do Ajuste 56 corrigir o caso do
  video "travado" (estado -1/5): em outro caso a tela ficava preta e
  so um F5 manual resolvia. Investigando, existiam DOIS outros
  caminhos de falha do YouTube que nunca tentavam recarregar sozinhos
  - so mostravam o aviso de erro direto: (1) quando o player nunca
    responde NENHUM estado (nem "travado", literalmente nao responde
    nada) e (2) quando a propria API do YouTube nunca termina de
    carregar (nem o player chega a ser criado). A suposicao original
    era que recarregar nao ajudaria nesses casos (bloqueio
    permanente), mas o F5 manual do usuario provou o contrario na
    pratica.
- Corrigido: os dois casos agora tentam um reload automatico da
  pagina (uma unica vez por sessao do navegador, assim como o caso ja
  corrigido antes) antes de desistir e mostrar o aviso estatico de
  erro. Validado com testes automatizados simulando cada um dos dois
  cenarios (player que nunca responde e API do YouTube totalmente
  bloqueada) - em ambos o reload automatico aconteceu no tempo
  esperado.

## Ajuste 57 - 2026-07-10

**Preletor: campo "Ate" (versiculo final) agora pode ser limpo e nao aceita valor menor que o inicial**

- Bug reportado ao vivo: uma vez escolhido um numero no "Ate", nao
  tinha como voltar atras (o dropdown nao oferecia opcao de limpar) -
  e era possivel escolher um "Ate" MENOR que o versiculo inicial (ex.:
  inicio 5, ate 3), o que o servidor entao reordenava sozinho
  (min/max), gerando um intervalo diferente do que a pessoa queria.
- Corrigido: clicar de novo no mesmo numero ja selecionado no "Ate"
  limpa a selecao (volta a "Opcional"). Numeros menores que o
  versiculo inicial agora ficam desabilitados (visualmente apagados)
  na grade do "Ate" - impossivel escolher uma combinacao invertida. Se
  o inicio mudar pra um numero maior que o "Ate" ja escolhido, o "Ate"
  e limpo automaticamente (ficaria invalido).

**Video: audio continuava tocando por baixo do texto biblico ao trocar de modo**

- Bug reportado ao vivo: com um video em exibicao, ao trocar pra
  Biblia (inclusive pelo fluxo de "assumir comando" do preletor), o
  telao mostrava o texto biblico normalmente, mas o AUDIO do video
  continuava tocando por baixo - a troca de camada so escondia o
  video visualmente (CSS), sem pausar o player de verdade.
- Corrigido: toda vez que o modo deixa de ser "video" (biblia, logo,
  Pix, imagem ou tela em branco), o telao agora pausa o player do
  YouTube de verdade. Validado com um mock do player confirmando a
  chamada a `pauseVideo()` exatamente no momento da troca de modo.

**Aviso de teste gratis mostrava "2 dias" quando faltava so 1**

- Bug reportado ao vivo: com o teste vencendo amanha, o aviso dizia
  "termina em 2 dias" - o calculo usava a diferenca EXATA de
  timestamps (incluindo a hora do dia) com `ceil()`, entao qualquer
  sobra de horas alem de 24h arredondava pra cima (ex.: 10/07 as 13h55
  ate 11/07 as 23h59 sao ~34h, que è mais de 1 dia mas menos de 2,
  arredondando errado pra "2"). Corrigido calculando a diferenca por
  DATA de calendario (ignorando a hora), que da o resultado esperado:
  1 dia de diferenca entre 10/07 e 11/07.

## Ajuste 56 - 2026-07-10

**"Quem esta no comando": operador e preletor param de sobrescrever um ao outro silenciosamente**

- O painel do operador e o tablet do preletor podem controlar o mesmo
  telao ao mesmo tempo, sem nenhuma coordenacao entre eles ate aqui -
  um lado podia trocar o versiculo/video/logo em exibicao sem nenhum
  aviso de que o outro estava com aquele conteudo ativo.
- Adicionada uma nova coluna `controlado_por` (operador/preletor) em
  `projecao_estados`, atualizada a cada acao que muda o conteudo
  principal (biblia, video, logo, pix, imagem, limpar). Agora, quando
  o OUTRO lado esta no comando, aparece uma confirmacao "Assumir
  comando?" antes de agir por cima - mesmo se o modo nao estiver
  mudando (ex.: os dois em "biblia", so trocando de versiculo). Cada
  painel tambem mostra uma badge ("Preletor no comando" / "Operador no
  comando") avisando ANTES mesmo de tentar fazer alguma coisa.

**Texto biblico com intervalo longo ("Ate") nao corta mais no telao/preletor**

- Selecionar um intervalo grande de versiculos (campo "Ate" da busca)
  podia fazer o texto estourar o palco 16:9 e cortar o final fora da
  tela, sem nenhum aviso. Corrigido com auto-ajuste de tamanho de
  fonte (`KadosysBiblia.ajustarTamanhoTexto`, nova variavel CSS
  `--biblia-escala`): o texto e medido apos renderizar e a fonte
  diminui em passos ate caber inteiro no palco (com um piso minimo de
  legibilidade). Validado com um intervalo de 7 versiculos que antes
  estouraria a tela - agora cabe todo, com fonte menor. Um versiculo
  unico continua no tamanho normal (sem reducao desnecessaria).
- Adicionado tambem um aviso textual perto do campo "Ate" (dashboard e
  preletor) explicando que intervalos longos reduzem a fonte
  automaticamente, pra quem prefere manter os intervalos curtos por
  legibilidade.

## Ajuste 55 - 2026-07-10

**Preletor: troca os selects nativos de Versao/Capitulo/Versiculo/Ate por widgets customizados escuros**

- Bug reportado ao vivo (com screenshots): no tablet do preletor, ao
  abrir os campos Versao/Cap./Vers./Ate, a lista de opcoes aparecia
  branca, sem nenhum estilo, sobrepondo o texto biblico e a previa do
  telao de forma confusa - mesmo com `color-scheme: dark` ja aplicado
  na caixa fechada do `<select>`. Causa: o POPUP de um `<select>`
  nativo e renderizado pelo proprio navegador/SO em varios casos
  (principalmente fora do Chromium), ignorando o tema definido via
  CSS - uma limitacao conhecida da plataforma web, nao um bug de
  codigo corrigivel so com mais CSS.
- Corrigido substituindo os 4 campos por componentes 100% customizados
  (mesma linguagem visual do resto do app):
  - **Versao**: pills (NVI/ACF/AA), reaproveitando o mesmo componente
    `montarVersaoPills` ja usado no painel do operador.
  - **Capitulo/Versiculo/Ate**: novo dropdown numerico
    (`montarNumeroCombo` em `biblia-picker.js`) - um botao que abre uma
    grade de numeros clicaveis, com a mesma casca escura ja usada pelo
    combo de busca de livro (fundo solido, borda, sombra).
- Ao trocar a versao/traducao, o texto no preletor agora reprojeta
  automaticamente no telao (mesmo comportamento ja existente no painel
  do operador) - antes exigia reclicar num versiculo pra ver o efeito.
- Validado o fluxo completo (livro -> capitulo -> versiculo -> texto
  projetado -> troca de versao -> texto atualizado automaticamente) e
  o fechamento do dropdown ao clicar fora.

## Ajuste 54 - 2026-07-10

**Corrige lista de livros da Biblia sobrepondo o painel de Video de forma confusa**

- Bug reportado ao vivo: ao abrir a busca de livro no painel Biblia
  (Projecao), a lista de resultados aparecia colada/misturada com o
  painel de Video logo abaixo, em vez de flutuar claramente por cima.
- Causa raiz: tanto o painel Biblia quanto o painel de Video usam
  `backdrop-filter` (efeito vidro fosco) - isso cria um "stacking
  context" PROPRIO pra cada painel. Um z-index alto so no campo de
  busca (dentro do painel Biblia) nao adianta: a disputa de camadas
  entre os DOIS PAINEIS inteiros e decidida pela ordem no HTML, nao
  pelo z-index de algo dentro de um deles - por isso a lista, mesmo
  com z-index alto, ficava "atras" do painel seguinte.
- Corrigido subindo o z-index do painel Biblia INTEIRO (nao so do
  campo) enquanto a lista estiver aberta - agora ela flutua
  claramente por cima do painel de Video, com sombra e borda nitidas,
  sem nenhuma mistura visual.

## Ajuste 53 - 2026-07-10

**Corrige regressao do Ajuste 52: video que estava tocando (com som prestes a comecar) sendo interrompido/recarregado**

- O Ajuste 52 corrigiu a tela preta permanente, mas foi longe demais:
  passou a tratar `getPlayerState() === undefined` com o MESMO limite
  curto (6s) usado pro cenario de autoplay bloqueado - so que
  `undefined` tambem acontece de forma NORMAL enquanto o iframe do
  player ainda esta conectando (handshake), o que em rede mais lenta
  (bem comum em wifi de igreja) pode levar mais que 6s mesmo em video
  que ia funcionar perfeitamente. Resultado: video comecava a tocar
  (som prestes a engatar) e era interrompido por um reload automatico
  disparado achando que estava travado - reportado ao vivo pelo
  usuario ("pareceu que ia sair som mas parou").
- Corrigido dando a `undefined`/`null` um contador PROPRIO, bem mais
  generoso (~20s em vez de 6s) e sem forcar reload algum (reload nao
  ajudaria um iframe genuinamente bloqueado, e atrapalha um handshake
  lento mas saudavel) - so mostra a mensagem de erro apos esse tempo
  todo sem nenhuma resposta do player. Os estados -1/5 (o player JA
  respondeu e confirmou que nao comecou - autoplay bloqueado de
  verdade) continuam com o limite curto de 6s + 1 reload automatico,
  como sempre funcionou.
- Validado com 3 cenarios via mock do player: (1) travado pra sempre
  (`undefined` eterno) - mostra erro em ~22s, sem reload; (2) handshake
  lento de 10s antes de comecar a tocar - NAO e mais interrompido,
  toca normalmente; (3) autoplay bloqueado classico (-1 fixo, player
  respondendo) - continua recarregando uma vez em ~6-7s como antes.

## Ajuste 52 - 2026-07-10

**Corrige tela preta permanente no video quando o embed do YouTube nao carrega**

- Bug real reportado: video carregado no operador, telao fica com tela
  preta pra sempre, sem nenhuma mensagem de erro (mesmo esperando).
  Causa raiz: o vigia de "video travado" (`agendarChecagemReproducao`
  em `telao.js`) consultava `player.getPlayerState()` a cada segundo,
  mas quando o objeto do player e criado com sucesso (o script da API
  do YouTube carrega) e o IFRAME do embed em si nunca termina de
  conectar (dominio do embed bloqueado por firewall/rede da igreja,
  por exemplo), esse metodo passa a retornar `undefined` para sempre -
  e o codigo tratava qualquer valor desconhecido como "o player esta
  funcionando normalmente", zerando os contadores de falha a cada
  tick e nunca deixando o timeout de travamento disparar.
- Corrigido tratando `undefined`/`null` como os demais estados
  "travados" (nao iniciado/na fila) - agora, depois de ~6s parado
  assim, tenta um reload automatico (unico por video) e, se persistir,
  mostra a mensagem clara "Nao foi possivel carregar o player do
  YouTube..." em vez de deixar a tela preta sem nenhuma explicacao.
- Validado com um mock do player do YouTube reproduzindo exatamente o
  cenario do bug (construtor funciona, metodos nunca respondem) -
  confirmado que a mensagem aparece corretamente - e com um mock de
  player saudavel, confirmando que nenhum reload/erro falso e
  disparado quando o video carrega normalmente.

## Ajuste 51 - 2026-07-10

**Descricao "Dizimo"/"Oferta" visivel no app do banco, QR nas pontas da tela, logo e mensagem/versiculo no centro, botoes de exibicao com estado ativo, URL completa no telao, menu lateral corrigido**

- Bug real encontrado por validacao pedida: os QR de Dizimo e Oferta so
  gravavam o txid (campo 62/05 do BR Code) diferenciando um do outro -
  esse campo e uso interno/extrato e a MAIORIA dos apps de banco NAO
  mostra ele com destaque pro pagador na hora de confirmar. Adicionado
  o campo correto pra isso, o 26/02 (`PixEstatico::montarPayload()`
  ganhou um parametro `$descricao` opcional), agora preenchido com
  "Dízimo"/"Oferta" em `ProjecaoEstado::montarPixJson()` - validado
  gerando os payloads reais da aplicacao e decodificando com uma
  biblioteca Pix independente (`pix-utils`), confirmando
  `infoAdicional: "Dizimo"` / `"Oferta"` distintos. O mesmo parametro
  foi aplicado tambem na doacao publica (`/doar`), usando a categoria
  escolhida pelo doador como descricao.
- Tela de Pix do telao redesenhada: os dois QR agora ficam nas pontas
  OPOSTAS da tela (antes so tinham um espacamento grande no meio) -
  perto demais, a camera do celular as vezes le o QR errado com a
  congregacao de pe. Entre eles, uma coluna central mostra a logo da
  igreja e uma mensagem opcional configuravel em Configuracoes: texto
  livre ou um versiculo biblico (resolvido na hora, buscando o texto
  atual da tabela `biblia_versiculos` - migracao 034 adiciona os
  campos `pix_mensagem_*` em `configuracoes_igreja`).
- A instrucao "prefere fazer depois" no telao agora mostra a URL
  publica COMPLETA (com dominio da igreja), nao so o caminho `/doar` -
  facil de digitar/lembrar quando alguem le de longe.
- Os botoes "Logo"/"Dizimo e Oferta"/imagens no painel de Projecao
  agora ficam azuis (estado ativo) refletindo o que realmente esta no
  telao no momento, sincronizado por polling - igual ja acontecia com
  os botoes de video.
- Corrigido bug de layout do dashboard: havia DUAS regras `.dash-main`
  em `dashboard.css`, a segunda sobrescrevendo a `margin-left`
  reativa da primeira com um valor fixo - por isso o conteudo nao
  ocupava o espaco liberado ao recolher o menu lateral.

## Ajuste 50 - 2026-07-10

**Dizimo e oferta agora exibem os 2 QR Pix juntos, bem afastados na tela**

- No Ajuste 49, Dizimo e Oferta eram botoes separados que trocavam um
  QR pelo outro no telao. Como os dois usam a MESMA chave Pix da
  igreja e normalmente sao recolhidos no mesmo momento do culto,
  agora um unico botao "Dizimo e Oferta" exibe os DOIS QR ao mesmo
  tempo, lado a lado - cada um com seu titulo (Dízimo/Oferta) e o
  proprio txid (pra identificar cada um depois no extrato), mas a
  mesma chave por baixo.
- Os dois QR ficam propositalmente bem espacados um do outro: perto
  demais, a camera do celular de quem esta escaneando (principalmente
  em pe, meio de longe) as vezes focava ou lia o QR vizinho por
  engano.

## Ajuste 49 - 2026-07-10

**Exibicoes rapidas no telao: Pix de dizimo/oferta + galeria de imagens favoritas**

- **Pix de dizimo/oferta com um clique**: novo painel "Exibicoes rapidas"
  em Projecao com botoes Logo/Dizimo/Oferta - clicar em Dizimo ou Oferta
  ja mostra o QR code Pix da igreja em tela cheia no telao (sem valor
  fixo, cada pessoa digita o proprio valor no banco), com o titulo
  "Dízimo"/"Oferta" em destaque e uma instrucao embaixo com o link
  publico (`/doar`) pra quem preferir doar depois do proprio celular.
  Os botoes ficam desabilitados se a igreja ainda nao cadastrou uma
  chave Pix em Configuracoes.
- **Galeria de imagens**: novo painel "Imagens" em Projecao - a igreja
  sobe cartazes/avisos (PNG, JPG, WEBP ou GIF, ate 8MB), marca os que
  usa com mais frequencia como favoritos (estrela) e exibe qualquer um
  deles em tela cheia no telao com um clique.
- Nova migracao 033 (`pix`/`imagem` no modo de `projecao_estados`,
  tabela `projecao_imagens`).

## Ajuste 48 - 2026-07-09

**Doacao via Pix estatico (novo) + loop de reload do video corrigido + menu lateral recolhivel + ajustes de UI**

- **Doacao via Pix (nova funcionalidade)**: a igreja agora pode
  cadastrar a propria chave Pix em Configuracoes e ganhar uma pagina
  publica (`/doar`, sem login, pra compartilhar no WhatsApp/redes) onde
  qualquer pessoa escolhe um valor, categoria (dizimo/oferta/etc.) e
  recebe um QR code Pix pra pagar direto pelo app do banco - o dinheiro
  cai direto na conta da igreja, sem passar pela plataforma nem por
  nenhum gateway de pagamento. Como e Pix por chave (sem gateway), o
  Banco Central nao avisa ninguem quando o pagamento e feito - o doador
  confirma manualmente ("Ja fiz o Pix"), o que ja cria o lancamento
  correspondente no Financeiro automaticamente. O payload do QR
  (padrao BR Code do Banco Central) e montado no servidor
  (`Igrejas\Core\PixEstatico`, com CRC16 validado contra uma
  implementacao independente) e renderizado como imagem inteiramente no
  navegador (biblioteca `qrcode-generator` de Kazuhiko Arase,
  vendorizada em `assets/js/vendor/`, MIT) - nenhum servico externo
  envolvido. Nova migracao 032 (chave Pix em `configuracoes_igreja` +
  tabela `financeiro_doacoes`).
- **Video do telao preso num loop de reload**: uma correcao anterior
  (Ajuste 47) fazia o telao assumir "precisa recarregar o video" sempre
  que nao conseguia confirmar 100% que o video certo estava carregado -
  como essa checagem roda a cada poll (~1,5s) enquanto o modo for
  video, isso podia forcar o video a recomecar do zero repetidamente,
  mesmo quando ja estava tocando direitinho, e ele nunca chegava a
  reproduzir de verdade. Revertido para o comportamento conservador
  (so recarrega quando ha certeza real de que o video errado - ou
  nenhum - esta carregado).
- **Menu lateral recolhivel**: novo botao no topo da sidebar do painel
  pra recolher/expandir o menu (preferencia salva no navegador). O
  mobile continua funcionando como overlay de tela cheia, ignorando
  esse estado.
- **Dropdown de busca de livro da Biblia**: tinha um overlay escuro
  cobrindo a pagina inteira (sidebar, topbar, outros paineis) ao abrir
  a busca - trocado por um esmaecimento bem mais sutil, so o
  suficiente pra separar visualmente o dropdown do painel de video
  logo abaixo.
- **Botoes Play/Pausar/Fadeout (painel de Projecao)**: agora refletem o
  estado real reportado pelo telao a cada poll, nao so o ultimo clique
  local - carregar um video novo ja marca "Play" sozinho, e mudancas
  de outra sessao/operador tambem sincronizam.

## Ajuste 47 - 2026-07-05

**Tela preta do video corrigida + voz masculina na leitura + popups modernos**

- **Tela preta do video no telao**: a checagem de "video travado" tratava
  um video so BUFFERIZANDO (carregando) como travado e recarregava a
  pagina no meio do carregamento - e depois do unico reload permitido
  por video, ficava preso numa tela preta ate um F5 manual. Agora so
  conta como travado o video parado em "nao iniciado" (o sintoma real
  de autoplay bloqueado); bufferizando/pausado nunca recarrega. Se
  travar de verdade com o reload ja gasto, aparece o aviso de "toque na
  tela" como saida (o toque destrava o play na hora). A checagem tambem
  passou a cobrir o video carregado logo na abertura da pagina (antes
  esse caminho ficava sem vigia nenhum).
- **Leitura em voz alta ("Ler agora")**: agora prefere voz MASCULINA em
  portugues quando o aparelho tiver uma instalada (Daniel, Antonio,
  Felipe, Ricardo, etc.) - a voz exata depende do que existe no
  aparelho/navegador do telao.
- **Popups modernos**: todas as confirmacoes que usavam a janelinha
  nativa do navegador (excluir membro/culto/grupo/etc., trocar o que
  esta em exibicao no telao, encerrar sessao de projecao) agora usam um
  popup proprio do sistema, no estilo do painel (novo
  assets/js/kadosys-modal.js).
- Nenhuma migracao nova.

## Ajuste 46 - 2026-07-05

**Corrige video que travava no PC (recarregava cedo demais) + retorno visual dos botoes do video**

- O mecanismo que recarrega o telao sozinho quando o video trava
  (Ajuste 42) estava dando so 1,8s pro video comecar antes de decidir
  que "travou" e recarregar - no PC, um video que so demora um pouco
  mais pra bufferizar (conexao mais lenta, anuncio do YouTube antes do
  video) era recarregado a toa, e como so recarregava UMA vez por
  video, se a lentidao se repetisse ele ficava travado ate um F5
  manual. Agora ele verifica ao longo de ~6s antes de desistir, e nao
  recarrega se o video comecar nesse meio tempo.
- Os botoes Play / Pausar / Fadeout no painel de Projecao agora
  respondem visualmente na hora do clique (antes o destaque so aparecia
  depois da resposta do servidor, dando a sensacao de que "nao faziam
  nada").
- Nenhuma migracao nova.

## Ajuste 45 - 2026-07-05

**Removido o controle de tom do Playbacks**

- Mesmo com a correcao do Ajuste 44 (tom sem mudar velocidade), a
  qualidade do resultado em musicas reais nao ficou boa o suficiente -
  removido o recurso a pedido do usuario. O modulo Playbacks continua
  normal (upload, player, busca, edicao, exclusao), so sem o "Tom" no
  player. Nenhuma migracao nova.

## Ajuste 44 - 2026-07-05

**Corrige o controle de tom do Playbacks (mudava velocidade tambem) e o select transparente**

- O controle de tom (Ajuste 43) mudava o `playbackRate` do audio - isso
  altera o tom, mas tambem acelera/desacelera a musica junto (efeito de
  toca-discos). Trocado por um algoritmo de verdade (time-stretch +
  reamostragem, tecnica WSOLA) que muda so o tom, mantendo a musica na
  MESMA velocidade - processado num Web Worker (nao trava a tela) e
  verificado matematicamente (frequencia resultante bate com o
  esperado, duracao continua identica).
- Corrigido tambem o select do tom que ficava praticamente transparente
  no tema claro (usava uma cor pensada so pro tema escuro) - agora
  segue o mesmo padrao ja usado nos outros campos de formulario do
  sistema.

## Ajuste 43 - 2026-07-04

**Novo modulo Playbacks (upload de audios + controle de tom)**

- **Novo modulo "Playbacks"** no menu (ja existia so como pagina "em
  construcao") - biblioteca de audios do ministerio de louvor: enviar
  (MP3, WAV, OGG, M4A ou AAC, ate 25MB), buscar, tocar, editar
  titulo/artista e excluir. Liberado em todos os planos.
- **Controle de tom** (subir/abaixar semitons, de -6 a +6) no player de
  cada playback - muda a velocidade de reproducao e desliga a
  preservacao automatica de tom do navegador, como o pitch control de
  um toca-discos. Disponivel a partir do plano Plus (quem esta no
  Essencial ve o player normal, sem o controle).
- Os arquivos ficam separados por igreja dentro de
  `public/uploads/playbacks/{subdominio}/` com nome aleatorio - como
  todos os subdominios compartilham o mesmo servidor de arquivos (so o
  banco de dados muda por igreja), isso evita que uma igreja
  sobrescreva ou acesse o audio de outra pelo nome do arquivo.
- Aumentado o limite de upload do PHP via `public/.user.ini` (30MB) -
  o padrao do cPanel costuma ser bem menor que isso.
- **Rode a migracao nova no banco de CADA igreja ja criada** (nao e no
  banco central):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/031_create_playbacks.sql
  ```

## Ajuste 42 - 2026-07-04

**Audio automatico no telao (TV sem toque na tela) + "Ler agora" (leitura em voz alta da biblia)**

- Corrigido: no telao (TV/projetor), o video do YouTube tocava mas o
  som ficava mudo, e nao tinha como "tocar na tela" pra liberar o
  audio (o telao normalmente fica numa TV sem ninguem por perto). O
  player agora comeca mudo de proposito (navegadores sempre permitem
  isso sem gesto do usuario) e desmuta sozinho logo em seguida - esse
  segundo passo (desmutar algo que ja esta tocando) e permitido sem
  gesto na maioria dos navegadores, diferente de comecar tocando com
  som direto. O aviso de "toque a tela" so aparece agora se, mesmo
  assim, o navegador continuar mudo (fica so como reforco pra quem usa
  o telao num notebook/tablet com toque).
- **Novo: botao "Ler agora no telao"** no painel de Projecao (perto de
  Anterior/Proximo) - le em voz alta o texto biblico que esta em
  projecao, usando o sintetizador de voz do proprio navegador (sem
  custo, sem depender de nenhum servico externo). So aparece quando ha
  uma referencia biblica em projecao no momento.
- **Rode a migracao nova no banco de CADA igreja ja criada** (nao e no
  banco central):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/030_add_leitura_projecao.sql
  ```

## Ajuste 41 - 2026-07-04

**Upgrade/downgrade de plano respeitando o ciclo ja pago (sem cobrar tudo de novo)**

- Antes, trocar de plano em Configuracoes sempre gerava uma cobranca
  nova em valor cheio, mesmo faltando dias pro ciclo atual vencer.
  Agora:
  - **Upgrade** (plano mais caro): cobra so a diferenca proporcional
    aos dias que faltam (ex.: 16 dias de 30 restantes = ~53% da
    diferenca), sempre via Pix avulso (mesmo pra quem assina por
    cartao - o Checkout Pro nao permite cobranca avulsa numa
    assinatura recorrente ja ativa). Assim que o Pix cai, o plano
    maior e liberado na hora; quem paga por cartao tem o valor da
    assinatura recorrente atualizado pro valor cheio a partir do
    proximo ciclo automaticamente.
  - **Downgrade** (plano mais barato): nao cobra nada agora - fica
    agendado pra entrar em vigor so quando o ciclo atual (ja pago)
    vencer. Ate la, os modulos do plano atual continuam liberados
    normalmente.
- Essa regra so vale pra quem ja tem um ciclo pago em andamento (nao
  vale pra quem esta no teste gratis nem pra primeira assinatura -
  esses continuam cobrando o valor cheio, sem ciclo anterior pra
  aproveitar).
- **Rode as migracoes novas no banco CENTRAL** (nao e no banco de cada
  igreja):
  ```
  mysql -u seu_usuario -p seu_banco_central < apps/igrejas/database/migrations/028_add_troca_plano_agendada.sql
  mysql -u seu_usuario -p seu_banco_central < apps/igrejas/database/migrations/029_add_tipo_fatura_pix.sql
  ```
- **Novo Cron Job** a configurar no cPanel (roda 1x por dia), aplicando
  os downgrades agendados que ja venceram:
  ```
  php /home/kadosys1/public_html/apps/igrejas/cron/aplicar_trocas_agendadas.php
  ```
  **Importante:** esse cron precisa rodar ANTES do cron ja existente
  `cron/gerar_faturas_pix.php` no mesmo dia (ex.: 5:00 pra este, 5:10
  pro outro) - senao uma igreja Pix com downgrade agendado pra hoje
  ainda receberia a proxima fatura no valor do plano antigo (mais
  caro).
- Limitacao conhecida: pra quem paga por cartao, a data exata do
  proximo ciclo e uma aproximacao (+30 dias a partir da ultima
  confirmacao), ja que o Mercado Pago nao expoe essa data via webhook -
  na pratica, isso so afeta o calculo do valor proporcional em poucos
  dias de diferenca, nunca o valor cobrado no cartao em si.

## Ajuste 40 - 2026-07-06

**Correcao: video do YouTube no telao so aparecia apos recarregar a pagina + fadeout de verdade**

- Achado o bug: o telao so reaplicava o comando de video quando a
  "versao" do estado mudava - se a primeira tentativa de carregar um
  video novo falhasse silenciosamente (o player as vezes ainda esta
  terminando de inicializar, principalmente quando nenhum video foi
  carregado antes na sessao), nada tentava de novo, e o telao ficava
  preso sem video ate alguem recarregar a pagina manualmente.
  Corrigido: agora, sempre que o telao esta em modo video, cada ciclo
  de verificacao (a cada 1.5s) confirma se o player realmente esta com
  o video certo carregado e tenta de novo se nao estiver - autocorrige
  sozinho, sem precisar de reload.
- **Fadeout de verdade**: o botao "Fadeout" do operador antes so
  pausava o video na hora e sobrepunha a logo. Agora baixa o volume
  aos poucos (ao longo de ~2 segundos), so depois encerra o video de
  verdade e mostra a logo - e o volume volta a 100% automaticamente
  assim que um novo video for tocado depois.
- Adicionado um aviso no painel de Projecao recomendando o uso de uma
  conta com YouTube Premium, ja que sem ela o video pode exibir
  anuncios durante a exibicao no telao.
- Testado com Playwright simulando a API do YouTube (sem depender de
  rede externa): confirma que uma falha simulada na primeira carga do
  video e corrigida sozinha nos ciclos seguintes, que o fadeout baixa o
  volume gradualmente ate 0 antes de encerrar, e que retomar o video
  depois restaura o volume a 100%.
- Nenhuma migracao de banco nesta mudanca.

## Ajuste 39 - 2026-07-06

**Correcao: troca de plano por cartao nao atualizava o plano de igrejas de subdominio**

- Achado revisando o fluxo de pagamentos: quando uma igreja de
  subdominio ja existente trocava de plano por **cartao** (Checkout
  Pro, dentro da propria Configuracoes), o webhook do Mercado Pago
  confirmava o pagamento mas **nunca atualizava o plano de verdade**
  daquela igreja. Causa: o registro da assinatura fica gravado no
  banco isolado de cada igreja, mas o webhook sempre roda na
  instalacao central (o Mercado Pago chama uma unica URL fixa, nunca
  o subdominio de uma igreja especifica) - sem uma ponte central, ele
  nunca conseguia achar de qual igreja era aquele pagamento. O mesmo
  problema **nao acontecia com Pix**, que ja usava uma tabela central
  (`plataforma_faturas`) desde o inicio.
- Corrigido com uma nova tabela central (`plataforma_assinaturas`,
  nao confundir com a tabela `assinaturas` que ja existia dentro de
  cada igreja) que guarda so o necessario (tenant + plano +
  preapproval_id) pra o webhook achar a igreja certa e aplicar a troca
  de plano no banco isolado dela - mesmo padrao ja usado pelo Pix.
- **So afeta troca de plano por cartao de igrejas de subdominio ja
  existentes.** Cadastro de igreja nova por cartao e toda a parte de
  Pix (cadastro novo e renovacao mensal) ja funcionavam certo antes e
  continuam sem alteracao.
- Rode a migracao nova SO no banco CENTRAL (o que recebe os cadastros
  publicos e os webhooks) - nao precisa rodar no banco de nenhuma
  igreja individual:
  ```
  mysql -u seu_usuario -p banco_central < apps/igrejas/database/migrations/027_create_plataforma_assinaturas.sql
  ```

## Ajuste 38 - 2026-07-06

**Auto-cadastro de membro cria login; endereco+numero no cadastro da igreja; notificacoes com data e ordenadas**

- **Auto-cadastro de membro agora cria um login**: quando o cadastro de
  membros esta habilitado (Configuracoes) e alguem se cadastra pelo
  site da igreja, alem de virar um Membro a pessoa tambem ganha uma
  conta de acesso (e-mail e senha viram o login). Por seguranca, essa
  conta comeca **sem nenhum modulo liberado** - diferente de um usuario
  de equipe (que por padrao acessa tudo que o plano libera), o admin
  precisa liberar manualmente em Permissoes o que o membro pode
  acessar. E-mail e senha (minimo 8 caracteres) passam a ser
  obrigatorios nesse formulario.
- **Endereco no cadastro da igreja nova** (dominio principal,
  `kadosys.com.br/apps/igrejas/cadastro`): agora tem CEP com
  autopreenchimento (ViaCEP), numero, endereco, cidade e estado - antes
  so pedia documento (CPF/CNPJ). O endereco enviado fica registrado no
  cadastro central e e copiado automaticamente pro banco da igreja
  (Configuracoes) assim que ela e provisionada.
- **Notificacoes do sino do painel**: cada notificacao agora mostra sua
  data (publicacao, vencimento ou data do evento, conforme o caso), e a
  lista inteira fica ordenada por essa data (mais recente/proxima
  primeiro) em vez da ordem fixa de antes.
- Rode as migracoes novas no banco de CADA igreja ja criada e no banco
  CENTRAL (o que recebe os cadastros publicos):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/026_add_endereco_configuracoes_igreja.sql
  mysql -u seu_usuario -p banco_central < apps/igrejas/database/migrations/025_add_endereco_provisionamento.sql
  ```
  Igrejas novas ja recebem a coluna de endereco automaticamente
  (`database/install.sql` atualizado) - so a migracao 026 precisa
  rodar manualmente nas igrejas que ja existiam antes deste ajuste; a
  025 roda uma unica vez, no banco central.

## Ajuste 37 - 2026-07-06

**Plano contratado visivel tambem pra quem tem acesso ao Financeiro**

- Antes, a secao "Plano contratado" (plano atual, status da assinatura,
  botao Assinar) so existia dentro de Configuracoes - tela 100% restrita
  ao admin. Agora quem tem acesso ao modulo Financeiro (mas nao e admin)
  tambem consegue **ver** o plano contratado e o status da assinatura,
  numa tela nova (`/dashboard/financeiro/plano`, com link "Plano
  contratado" no menu do usuario).
- Essa tela e so de consulta: quem nao e admin ve o plano e o status,
  mas nao pode assinar/trocar de plano por ali (aparece um aviso "Fale
  com o administrador da igreja"). Alterar o plano continua sendo so
  pelo admin, em Configuracoes.
- O aviso de contagem regressiva do teste gratis (banner no topo do
  painel e no sino de notificacoes) agora so aparece pra quem realmente
  pode fazer algo a respeito (admin ou Financeiro) - o resto da equipe
  nao ve mais um aviso de cobranca que nao consegue resolver.
- Nenhuma migracao de banco nesta mudanca.

## Ajuste 36 - 2026-07-06

**Correcao do Ajuste 35: plano/pagamento volta pro cadastro do dominio principal**

- O Ajuste 35 removeu plano e forma de pagamento da tela `/cadastro`
  do dominio principal (`kadosys.com.br/apps/igrejas`) por engano -
  essa e a tela onde uma igreja **nova** contrata a KADOSYS de
  verdade, entao ela precisa continuar pedindo plano e forma de
  pagamento (cartao, Pix ou teste gratis), exatamente como antes.
- O que continua certo do Ajuste 35 (nao foi mexido): dentro do
  subdominio de uma igreja **ja existente**, a mesma URL `/cadastro`
  continua sem nenhuma opcao de plano/pagamento - la e so o
  auto-cadastro de membros (com CEP), porque a igreja ja contratou o
  plano dela com a gente e escolher/trocar plano so faz sentido de
  dentro do painel (Configuracoes), nao numa tela publica.
- Voltaram: os campos de plano e forma de pagamento no formulario de
  cadastro do dominio principal, as telas de Pix e de retorno do
  Checkout Pro, e os links "Comecar agora"/"Testar gratis" da landing
  page com o plano/metodo pre-selecionado.

## Ajuste 35 - 2026-07-06

**Cadastro publico simplificado (sem plano/pagamento) + auto-cadastro de membros + novidades no Dashboard**

- **Cadastro de igreja nova, sem plano/pagamento**: a tela publica de
  cadastro (`/cadastro` no dominio principal) nao pede mais plano nem
  forma de pagamento - toda igreja nova comeca direto com 7 dias de
  teste gratis. Escolher e pagar um plano agora e feito so depois, de
  dentro do proprio painel (Configuracoes), pra nao passar a impressao
  de que a KADOSYS esta revendendo planos na propria tela de criacao
  da conta. As telas antigas de Pix/retorno do cadastro foram removidas
  (ficaram sem uso).
- **Auto-cadastro de membros**: nova opcao em Configuracoes pra ligar/
  desligar um link publico de cadastro de membros. Quando ligado, a
  mesma URL `/cadastro` (agora dentro do subdominio da igreja, nao no
  dominio principal) mostra um formulario onde qualquer pessoa se
  cadastra como membro sozinha - com autopreenchimento de endereco a
  partir do CEP (API do ViaCEP). Quando desligado (padrao), nada muda:
  a secretaria continua cadastrando cada membro manualmente pelo
  modulo Membros. O link "Cadastre-se" ja existente na tela de login
  agora aponta pra esse formulario quando acessado dentro do
  subdominio de uma igreja.
- **Novidades no Dashboard**: o painel "Insights da IA" do conteudo
  principal do Dashboard (nao o da barra lateral, ja trocado no ajuste
  34) agora mostra os avisos mais recentes do dono da plataforma (os
  mesmos publicados em `/plataforma/avisos`) em vez do texto fixo
  sobre recursos futuros de IA.
- Rode as migracoes novas no banco de CADA igreja ja criada (nao e o
  banco central da plataforma). Igrejas novas ja recebem as tabelas/
  colunas automaticamente (`database/install.sql` atualizado):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/023_add_cadastro_membros_configuracoes_igreja.sql
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/024_add_cep_membros.sql
  ```

## Ajuste 34 - 2026-07-06

**Avisos na barra lateral (no lugar do card "Insights da IA") + pagina de detalhe com controle de lido**

- O card "Insights da IA" da barra lateral (so um teaser de recurso
  futuro) foi substituido por uma lista com os avisos mais recentes do
  modulo Comunicacao. So aparece pra quem tem acesso ao modulo.
- Cada aviso agora tem uma pagina de detalhe propria
  (`/dashboard/comunicacao/{id}`) - clicar num aviso na barra lateral
  ou nas notificacoes abre essa pagina.
- Abrir a pagina de detalhe marca o aviso como lido **por usuario**
  (cada pessoa tem seu proprio controle de leitura) - o ponto azul de
  "novo" na barra lateral some depois disso, mesmo se outro usuario da
  igreja ainda nao tiver lido.
- Rode a migracao nova no banco de CADA igreja ja criada (nao e o
  banco central da plataforma). Igrejas novas ja recebem a tabela
  automaticamente (`database/install.sql` atualizado):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/022_create_comunicacao_aviso_leituras.sql
  ```

## Ajuste 33 - 2026-07-06

**Correcao: tela de Permissoes escondia modulos durante o teste gratis**

- Em Permissoes, a lista de modulos que da pra marcar pra um usuario
  usava sempre o plano contratado (Essencial, Plus, Premium) pra
  decidir o que mostrar - mas durante o teste gratis de 7 dias, o
  sistema libera TODOS os modulos temporariamente (mesma regra usada
  no resto do painel). Essa tela nao sabia disso, entao so aparecia
  Membros/Cultos/Projecao/Playbacks/Agenda, mesmo com a igreja em
  trial e o restante liberado de verdade.
- Agora a tela de Permissoes mostra todos os modulos liberados no
  momento (respeitando o trial), igual o resto do sistema ja fazia.
- Sem migracao - e so codigo.

## Ajuste 32 - 2026-07-06

**Quadro de avisos publico da igreja + aviso do dono da plataforma pra todas as igrejas**

- Novo link publico `/avisos` (sem login) em cada igreja: mostra os
  avisos do modulo Comunicacao publicados com publico "todos" - da pra
  compartilhar com a congregacao (grupo do WhatsApp, QR code no
  templo, etc.). Avisos marcados "so lideranca" continuam restritos ao
  painel administrativo, nunca aparecem nesse link.
- Novo painel "Avisos" dentro do painel da plataforma (o mesmo onde as
  igrejas sao excluidas) - o dono do sistema pode publicar um aviso que
  aparece automaticamente no sino de notificacoes do painel de
  **todas** as igrejas cadastradas (ex.: manutencao programada, novo
  recurso disponivel). So um aviso fica ativo por vez.
- **Rode a migracao nova, mas so no banco CENTRAL** (o mesmo banco
  onde ficam as tabelas `plataforma_tenants`/`plataforma_provisionamentos`
  - normalmente o banco desta instalacao principal, nao o de cada
  igreja individual):
  ```
  mysql -u seu_usuario -p banco_central < apps/igrejas/database/migrations/021_create_plataforma_avisos.sql
  ```

## Ajuste 31 - 2026-07-06

**Sino de notificacoes e menu do usuario funcionais + novo texto do card de IA**

- O sino de notificacoes no topo do painel era so decorativo. Agora
  mostra de verdade: fatura Pix perto de vencer, contagem do teste
  gratis, ultimos avisos publicados em Comunicacao e o proximo culto
  agendado - cada um com link direto pro modulo. O ponto vermelho so
  aparece quando ha algo de fato.
- Clicar no nome do usuario, no topo, agora abre um menu com
  "Configuracoes" e "Sair" (antes era so um texto sem acao).
- O card "Assistente IA" da barra lateral ganhou um texto novo,
  detalhando o que vem por ai (resumos de frequencia, alertas
  financeiros, sugestoes de comunicacao).
- Sem migracao de banco - e so codigo (view, CSS, JS e um metodo novo
  no model de Comunicacao).

## Ajuste 30 - 2026-07-06

**Correcao: redirecionamento prematuro pro subdominio novo (tela "preparando tudo")**

- Depois de criar uma conta de teste gratis, a tela "Estamos preparando
  tudo" verificava se o subdominio novo ja estava pronto com um
  `fetch` no modo `no-cors` direto do navegador. Esse modo nunca
  consegue ler o status/corpo da resposta (bloqueado por CORS) - ai
  qualquer resposta do servidor contava como "pronto", inclusive uma
  pagina de erro (subdominio com vhost ainda nao carregado, banco
  daquela igreja ainda inacessivel, etc.). Resultado: o usuario as
  vezes era mandado pro login com o site ainda quebrado.
- Agora essa checagem roda no servidor (`CadastroController::prontoStatus()`),
  que faz a requisicao de verdade pro subdominio e so confirma
  "pronto" quando a pagina de login carregou por completo (status 200
  e o formulario presente no corpo) - nao so quando o servidor
  respondeu alguma coisa.
- Sem migracao de banco - e so codigo (controller, rota, view e JS).

## Ajuste 29 - 2026-07-06

**Ultimos 3 modulos: Patrimonio, Comunicacao e Relatorios (fim dos "em construcao")**

- **Patrimonio**: cadastro de bens, imoveis, veiculos e equipamentos da
  igreja - categoria, numero de patrimonio, valor estimado, data de
  aquisicao, local e status (ativo/em manutencao/baixado). Exclusivo do
  plano Premium (topo).
- **Comunicacao**: mural de avisos e comunicados para "todos os
  membros" ou "so lideranca", com rascunho/publicado/arquivado. Por
  enquanto e um mural dentro do proprio painel (envio por e-mail/SMS
  fica pra uma proxima etapa). Disponivel a partir do plano Plus.
- **Relatorios**: painel consolidado, por mes, com membros ativos,
  novos membros, cultos e media de presenca, ministerios/grupos
  ativos, entradas/saidas/saldo financeiro (com quebra por categoria) e
  valor total do patrimonio ativo. Somente leitura, exclusivo do plano
  Premium (topo).
- Com isso, todos os modulos do menu lateral tem uma tela de verdade -
  nao sobrou nenhum "em construcao".
- Rode as migracoes novas no banco de CADA igreja ja criada (nao e no
  banco central da plataforma). Igrejas novas ja recebem as tabelas
  automaticamente (`database/install.sql` atualizado). Relatorios nao
  precisa de migracao (so consulta dados que ja existem):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/019_create_patrimonio_tables.sql
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/020_create_comunicacao_tables.sql
  ```

## Ajuste 27 - 2026-07-06

**Novo modulo: Agenda + correcao critica na busca de Membros e Cultos**

- Modulo Agenda completo: eventos, reunioes e reservas de espaco da
  igreja, com tipo, data, horario de inicio/termino, local e
  responsavel (um membro) opcional. Disponivel em todos os planos.
- **Correcao critica**: a busca por texto em Membros (por nome OU
  e-mail) e em Cultos (por titulo OU local) estava **derrubando a
  pagina com erro** desde sempre - o banco de dados nao aceita usar o
  mesmo parametro (":search") duas vezes na mesma consulta. Se algum
  usuario ja tentou buscar algo em Membros ou Cultos e a pagina deu
  erro, era isso.
- Rode a migracao nova no banco de CADA igreja ja criada (nao e no
  banco central da plataforma). Igrejas novas ja recebem a tabela
  automaticamente (`database/install.sql` atualizado):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/017_create_agenda_tables.sql
  ```

## Ajuste 28 - 2026-07-06

**Novo modulo: Usuarios e Permissoes**

- Ate aqui so existia 1 usuario administrador por igreja. Agora um
  admin pode convidar mais gente em Usuarios, cada um com um papel:
  - **Administrador**: acesso total, inclusive gerenciar usuarios,
    permissoes e o plano/faturamento da igreja.
  - **Usuario**: acesso a todos os modulos do plano contratado (igual
    a um admin), exceto Usuarios, Permissoes e Configuracoes.
- No plano Premium (o mais completo), o admin pode ir em Permissoes e
  restringir ainda mais um usuario especifico, liberando so os
  modulos escolhidos (ex.: um voluntario que so deveria ver Membros e
  Cultos).
- Quando alguem tenta acessar um modulo sem permissao, aparece uma
  tela explicando o bloqueio (em vez de deixar a pagina carregar pela
  metade) - e o menu lateral ja mostra um cadeado nos modulos fora do
  alcance daquele usuario.
- Protecoes contra erro: nao da pra se auto-rebaixar, se auto-desativar
  ou excluir a propria conta, nem remover/desativar o ultimo
  administrador ativo da igreja - sempre precisa sobrar pelo menos um
  admin.
- Rode a migracao nova no banco de CADA igreja ja criada (nao e no
  banco central da plataforma). Igrejas novas ja recebem a estrutura
  automaticamente (`database/install.sql` atualizado):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/018_create_usuarios_permissoes.sql
  ```

## Ajuste 26 - 2026-07-06

**Novo modulo: Grupos (pequenos grupos, celulas e classes)**

- Modulo completo de gestao de grupos, disponivel a partir do plano
  Plus (mesmo nivel de Ministerios): cada grupo pode ter um lider
  (membro) e uma lista de participantes, alem de tipo (Grupo, Celula
  ou Classe), dia da semana, horario e local do encontro.
- Mesma estrutura de tela do modulo Ministerios (lista com busca e
  paginacao, formulario com secao de participantes).
- **Rode a migracao nova no banco de CADA igreja ja criada** (nao e no
  banco central da plataforma). Igrejas novas ja recebem a tabela
  automaticamente (`database/install.sql` atualizado):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/016_create_grupos_tables.sql
  ```

## Ajuste 25 - 2026-07-06

**Novo modulo: Financeiro (dizimos, ofertas e despesas)**

- Modulo completo de controle financeiro, disponivel a partir do plano
  Plus: lancamentos de entrada (dizimo, oferta, doacao...) e saida
  (despesa), cada um com categoria, forma de pagamento, data e vinculo
  opcional com um membro (pra registrar de quem foi um dizimo/oferta
  especifico).
- Tela principal com filtro por mes, tipo (entrada/saida), categoria e
  busca por descricao - e totais de entradas, saidas e saldo do
  periodo filtrado.
- Categorias sao totalmente customizaveis (`/dashboard/financeiro/categorias`)
  - a igreja pode criar, desativar ou remover as suas proprias, alem
    das 11 categorias padrao ja cadastradas (Dizimo, Oferta, Aluguel,
    Salarios, etc.).
- O card "Financeiro do mes" no Dashboard agora mostra o saldo real do
  mes corrente (antes era so um "--" de espera).
- **Rode a migracao nova no banco de CADA igreja ja criada** (nao e no
  banco central da plataforma - e o banco de cada igreja, inclusive a
  instalacao original). Igrejas novas criadas a partir de agora ja
  recebem essas tabelas automaticamente (`database/install.sql` foi
  atualizado):
  ```
  mysql -u seu_usuario -p banco_da_igreja < apps/igrejas/database/migrations/015_create_financeiro_tables.sql
  ```

## Ajuste 24 - 2026-07-06

**Tela de espera no teste gratis (subdominio recem-criado pode demorar a responder)**

- Depois de criar a conta no teste gratis de 7 dias, o sistema
  redirecionava direto pro `https://{subdominio}/login` recem-criado -
  mas o subdominio, mesmo ja existindo no painel do cPanel, pode
  demorar um pouco pro DNS propagar, dando "pagina nao existe" nesse
  primeiro acesso.
- Agora aparece uma tela "Estamos preparando tudo" logo depois do
  cadastro, que fica verificando em segundo plano (a cada poucos
  segundos) se o subdominio ja esta respondendo, e so entao
  redireciona pra tela de login de verdade - sem risco de cair numa
  pagina inexistente. Se demorar demais (mais de 1 minuto), aparece um
  link pra tentar entrar manualmente.
- Sem migracao de banco nesta entrega.

## Ajuste 23 - 2026-07-06

**Teste gratis com acesso total + remocao do ajuste manual de plano**

- Durante o teste gratis de 7 dias, todos os modulos do sistema ficam
  liberados agora, independente do plano escolhido no cadastro -
  antes, quem tinha selecionado Essencial no cadastro so via os
  modulos do Essencial durante o teste, o que nao fazia sentido (o
  objetivo do teste e conhecer o sistema completo antes de decidir o
  plano).
- Em Configuracoes, enquanto o teste gratis esta ativo, os 3 planos
  aparecem com o botao "Assinar" disponivel (nenhum fica marcado como
  "Plano atual", ja que nada foi de fato contratado ainda) - e o texto
  da secao passa a explicar que e um teste gratis, com a data em que
  termina.
- Removido o "Ajuste manual do plano (uso interno/suporte)" que
  aparecia em Configuracoes - alem de nao ser mais necessario (a
  assinatura via cartao/Pix ja e automatica), ele ficava visivel pra
  qualquer administrador de qualquer igreja, que podia trocar o
  proprio plano pra Premium sem pagar nada.
- Sem migracao de banco nesta entrega.

## Ajuste 22 - 2026-07-06

**Teste gratis na pagina institucional + novo modulo Playbacks**

- O botao "Teste gratis" agora aparece no topo do site (ao lado de
  "Acessar o sistema") e tambem em um banner destacado na secao de
  Planos - os dois levam direto pro cadastro ja com a opcao de teste
  gratis pre-selecionada.
- Novo modulo **Playbacks** (biblioteca de faixas para o ministerio de
  louvor) liberado em todos os planos, com card proprio na secao de
  Recursos e entrada no menu do painel (estrutura "em construcao" por
  enquanto - o conteudo em si sera publicado depois).
- A secao de Recursos tambem ganhou um card explicando a Projecao/Telao
  (ja existente, mas que nao estava descrita ali).
- No plano Premium (o mais completo), foi adicionado o recurso "mudar o
  tom da musica em tempo real" dos Playbacks - **funcionalidade ainda
  nao implementada**, so a divulgacao na pagina de vendas por enquanto
  (o desenvolvimento em si sera feito numa proxima etapa).
- Sem migracao de banco nesta entrega.

## Ajuste 21 - 2026-07-06

**CPF/CNPJ obrigatorio no cadastro + teste gratis de 7 dias**

- O formulario publico de cadastro (`/cadastro`) agora exige CPF ou
  CNPJ - escolhendo CNPJ, a razao social tambem passa a ser
  obrigatoria. O documento e validado de verdade (digito verificador),
  nao so o formato.
- Nova opcao de forma de pagamento: "Teste gratis" - cria a conta na
  hora (sem passar pelo Mercado Pago) com 7 dias de acesso completo. O
  mesmo CPF/CNPJ so pode usar o teste gratis uma vez - se tentar de
  novo com outro e-mail/igreja, o cadastro e recusado.
- Passado os 7 dias, se a igreja nao tiver assinado um plano (cartao ou
  Pix), o painel fica bloqueado com uma tela avisando que o teste
  acabou - so "Configuracoes" continua acessivel, pra dar pra assinar e
  liberar o acesso de novo. Enquanto o teste ainda esta rodando,
  aparece um aviso no topo do painel com a contagem regressiva de dias.
- Rode a migracao nova no banco de producao (adiciona as colunas de
  documento/razao social/trial nas tabelas centrais da plataforma):
  ```
  mysql -u seu_usuario -p seu_banco < apps/igrejas/database/migrations/014_add_documento_e_trial.sql
  ```

## Ajuste 20 - 2026-07-06

**Painel administrativo da plataforma - excluir igrejas**

- Nova tela `/plataforma/igrejas` pra voce (dono do sistema) ver todas
  as igrejas provisionadas e excluir uma quando precisar - apaga o
  banco de dados, o usuario do banco e o subdominio no cPanel, alem do
  registro no sistema. Acao irreversivel, com confirmacao antes de
  executar.
- Login totalmente separado do login normal de cada igreja - uma unica
  "chave mestra" configurada so no servidor.
- **Configuracao necessaria antes de usar** - gere o hash da sua chave
  (troque o texto entre aspas por uma chave bem longa e unica sua):

  ```
  php -r "echo password_hash('sua-chave-bem-longa-aqui', PASSWORD_BCRYPT), PHP_EOL;"
  ```

  E defina a variavel de ambiente `PLATAFORMA_ADMIN_SENHA_HASH` no
  cPanel com o resultado (mesmo esquema das outras variaveis, ex.:
  `MP_ACCESS_TOKEN`) - ou crie `config/plataforma.local.php` com
  `['senha_hash' => '...']` se o servidor nao suportar variavel de
  ambiente customizada. Sem isso configurado, a tela fica bloqueada
  (login sempre recusado, por seguranca).
- Acesse em `https://kadosys.com.br/plataforma/entrar` (ou o caminho
  equivalente com o BASE_PATH da instalacao).

## Ajuste 19 - 2026-07-06

**Ultimos ajustes da rodada de testes reais de Pix + provisionamento**

1. Corrigido o nome da funcao do cPanel que concede acesso ao banco de
   dados (`set_privileges_on_user` nao existe nesta versao de cPanel -
   o certo e `set_privileges_on_database`) - esse era o ultimo passo do
   provisionamento automatico que ainda estava falhando.
2. O texto completo da Biblia (NVI, ACF e AA) agora e importado
   automaticamente pra toda igreja nova - antes so a instalacao
   original tinha isso, e cada igreja criada pelo cadastro ficava so
   com os livros/capitulos, sem o texto dos versiculos.
3. Ao abrir o subdominio de uma igreja ja criada (ex.:
   `suaigreja.kadosys.com.br`), a pagina inicial agora vai direto pra
   tela de login, em vez de mostrar o site institucional de vendas.
4. Aviso de fatura Pix no painel ficou mais claro, diferenciando
   renovacao do plano atual de um upgrade pendente pra outro plano.
5. Campo "Endereco da sua igreja" no cadastro publico renomeado pra
   "URL de acesso ao painel".

Com isso, o fluxo completo de uma igreja nova - cadastro, pagamento
(cartao ou Pix), criacao automatica do banco/subdominio, Biblia
importada e e-mail de boas-vindas - deve funcionar de ponta a ponta
sem nenhuma intervencao manual.

## Ajuste 18 - 2026-07-06

**E-mail automatico de boas-vindas ao criar uma igreja nova**

- Assim que o provisionamento automatico de uma igreja termina (banco +
  subdominio prontos, cartao ou Pix), o administrador recebe um e-mail
  de `igrejas@kadosys.com.br` com o link do painel da igreja.
- Usa o envio nativo do PHP (sem servico externo) - funciona porque
  `igrejas@kadosys.com.br` e uma caixa de fato hospedada no mesmo
  dominio do servidor. Se o envio falhar por qualquer motivo, isso NAO
  atrapalha o provisionamento (a igreja continua criada normalmente) -
  so fica um aviso registrado pra conferencia.

## Ajuste 17 - 2026-07-06

**3 correcoes encontradas testando pagamento Pix real em producao**

1. A data de vencimento da cobranca Pix enviada ao Mercado Pago estava
   num formato que a API rejeitava (faltavam os milissegundos) - toda
   cobranca Pix nova estava sendo recusada com erro 400.
2. A tela de QR code do Pix (`/cadastro/pix/...`) dava erro 500 ao
   abrir, por causa de um parametro de rota com o tipo errado.
3. Depois do pagamento confirmado, a criacao automatica do banco da
   igreja no cPanel falhava com "nome nao comeca com o prefixo
   obrigatorio" - esse servidor exige o nome do banco ja com o prefixo
   da conta cPanel incluido, diferente do que o codigo assumia.

Com essas 3 correcoes, o fluxo completo de Pix (cadastro -> pagamento
-> confirmacao -> banco/subdominio criados automaticamente) devia
funcionar de ponta a ponta.

## Ajuste 16 - 2026-07-03

**Pagamento via Pix (fatura mensal) - cadastro novo e renovacao de igrejas ja ativas**

- Agora, alem do cartao (assinatura recorrente automatica), quem se
  cadastra pode escolher pagar por Pix. Nesse caso:
  - No cadastro publico, gera na hora um QR code + codigo "copia e
    cola" com 3 dias de prazo; a tela fica atualizando sozinha e, assim
    que o Pix cai, o provisionamento automatico da igreja (banco,
    subdominio, etc. - Ajuste 15) comeca do mesmo jeito que no cartao.
  - Igrejas que pagam por Pix recebem uma fatura nova todo mes (nao e
    debito automatico) - um novo job (`cron/gerar_faturas_pix.php`,
    precisa ser agendado no "Cron Jobs" do cPanel pra rodar 1x por dia)
    gera a proxima cobranca com folga antes do vencimento.
  - O painel mostra um aviso (com link pra pagar) alguns dias antes do
    vencimento. Se vencer sem pagar, o acesso ao painel fica bloqueado
    (com uma tela propria mostrando o QR code pra regularizar) ate o
    pagamento cair - confirmado automaticamente pelo mesmo webhook do
    Mercado Pago.
- **Nenhuma configuracao nova e necessaria** alem do que ja estava
  configurado pro cartao (mesmas credenciais do Mercado Pago) - so
  precisa cadastrar o cron job mencionado acima no cPanel pra renovacao
  automatica funcionar (o cadastro/pagamento inicial via Pix ja funciona
  sem isso).
- No painel do Mercado Pago, e preciso garantir que a notificacao de
  webhook do tipo "Pagamentos" (`payment`) esteja habilitada, alem da
  de assinaturas - sem isso a confirmacao automatica do Pix nao chega.

## Ajuste 15 - 2026-07-03

**Provisionamento 100% automatico da nova igreja (Fases 2 e 3) - cadastro completo funcionando sozinho**

- Quando o pagamento de um cadastro publico (Ajuste 14) e aprovado, o
  sistema agora cria tudo sozinho, sem ninguem mexer na mao:
  1. Banco de dados e usuario MySQL novos, exclusivos daquela igreja
     (via API do cPanel).
  2. Subdominio proprio (ex.: `igrejaabc.kadosys.com.br`), apontando pro
     mesmo codigo do sistema.
  3. As tabelas do sistema instaladas nesse banco novo (o mesmo
     `install.sql` de qualquer instalacao).
  4. A igreja e o primeiro usuario administrador (com o nome/e-mail/
     senha que a pessoa preencheu no cadastro) ja criados e prontos pra
     usar.
- O sistema passa a reconhecer sozinho qual igreja e qual pelo
  subdominio de cada requisicao - cada igreja continua com seu banco
  isolado (nao e um banco compartilhado entre todas), so que agora essa
  troca de banco acontece automaticamente. Testado extensivamente
  (inclusive os cenarios de "nada configurado ainda" e "subdominio
  desconhecido") pra garantir que a instalacao atual (kadosys.com.br)
  continua funcionando exatamente igual, sem nenhum efeito colateral.
- **Configuracao necessaria no servidor** (novas variaveis de ambiente,
  mesmo esquema do Mercado Pago - via cPanel ou arquivo
  `config/cpanel.local.php`, fora do Git):
  - `CPANEL_HOST` - host do servidor (ex.: server.vipreseller25ssd.com)
  - `CPANEL_PORT` - normalmente 2083
  - `CPANEL_USERNAME` - usuario da conta cPanel (ex.: kadosys1)
  - `CPANEL_API_TOKEN` - token gerado em "Manage API Tokens" no cPanel
  - `CPANEL_ROOT_DOMAIN` - dominio raiz dos subdominios (ex.:
    kadosys.com.br) - **esse e o interruptor geral**: enquanto ele nao
    for definido, nada muda no funcionamento atual.
  - `CPANEL_SUBDOMAIN_DOCROOT` - pasta (relativa ao home da conta) que
    cada subdominio novo deve apontar - a mesma pasta onde o sistema ja
    esta publicado hoje (ex.: public_html/apps/igrejas/public).
- Sem essas variaveis configuradas, o cadastro publico continua
  funcionando normalmente ate a etapa de pagamento, so a criacao
  automatica do banco fica pausada (fica registrada como erro no
  provisionamento, pra retomar depois).

## Ajuste 14 - 2026-07-03

**Cadastro publico (igreja + administrador + plano) - Fase 1 de 3**

- Nova tela publica em `/cadastro`: nome da igreja, subdominio (sugerido
  automaticamente pelo nome, editavel), nome/e-mail/senha do
  administrador, e escolha do plano (Essencial ou Premium - Enterprise
  continua "fale com o suporte"). Ao enviar, cria a assinatura no
  Mercado Pago e redireciona pro pagamento, igual ao fluxo que ja existe
  em Configuracoes.
- Os botoes "Comecar agora" da pagina de planos agora levam pra essa
  tela (com o plano ja pre-selecionado), em vez de irem direto pro
  login.
- **Isso e a Fase 1 de um projeto maior**: por enquanto, o cadastro fica
  registrado numa fila (tabela `plataforma_provisionamentos`) depois que
  o pagamento e aprovado - a criacao automatica do banco de dados da
  nova igreja (Fase 2) e a resolucao por subdominio (Fase 3) ainda vao
  ser implementadas nas proximas entregas. Ate la, um pagamento aprovado
  fica "aguardando provisionamento" e precisa ser finalizado na mao.
- Rode a migracao nova no banco de producao (cria as tabelas
  `plataforma_provisionamentos` e `plataforma_tenants` - registro
  central de igrejas, separado dos dados desta igreja):
  ```
  mysql -u seu_usuario -p seu_banco < database/migrations/011_create_plataforma_tenants.sql
  ```

## Ajuste 13 - 2026-07-03

**Alternativa para configurar o Mercado Pago sem variavel de ambiente**

- Alguns provedores de hospedagem (inclusive o servidor atual) nao
  deixam cadastrar variavel de ambiente customizada pelo MultiPHP INI
  Editor do cPanel (so os campos fixos tipo display_errors).
- Agora tambem funciona criar um arquivo
  `apps/igrejas/config/mercadopago.local.php` direto no servidor (por
  FTP ou pelo Gerenciador de Arquivos do cPanel - nunca pelo Git) com as
  credenciais. Esse arquivo esta no `.gitignore`, entao nunca vai
  aparecer no repositorio.

## Ajuste 12 - 2026-07-03

**Assinatura recorrente do plano via Mercado Pago (Checkout Pro)**

- Em Configuracoes, os planos Essencial e Premium agora tem um botao
  "Assinar" que leva pro checkout do Mercado Pago (cartao, PIX, etc.).
  Enterprise continua "sob consulta" (fale com o suporte).
- Quando o pagamento e aprovado, o Mercado Pago avisa o sistema por
  webhook e o plano contratado da igreja e atualizado sozinho - sem
  precisar trocar nada manualmente. O status da assinatura (pendente,
  ativa, pausada, cancelada) aparece do lado do nome do plano atual.
- Seguranca: toda notificacao recebida e validada (assinatura HMAC do
  Mercado Pago) antes de mexer em qualquer coisa - notificacao sem
  assinatura valida e rejeitada. O plano so muda de fato depois de
  confirmar o status direto na API do Mercado Pago (nunca so pelo que
  chega na notificacao).
- **IMPORTANTE - requer configuracao no servidor antes de funcionar**:
  precisa cadastrar 4 variáveis de ambiente no cPanel (MultiPHP INI
  Editor ou equivalente) com as credenciais do Mercado Pago:
  `MP_ACCESS_TOKEN`, `MP_PUBLIC_KEY`, `MP_WEBHOOK_SECRET` e `APP_URL`
  (URL publica completa do site). Sem isso, o botao "Assinar" mostra um
  aviso e a troca de plano continua disponivel manualmente (like antes,
  em "Ajuste manual do plano").
- Rode a migracao nova no banco de producao (cria as tabelas
  `assinaturas` e `assinatura_eventos`, essa ultima guarda um historico
  de cada notificacao recebida, pra ajudar a diagnosticar problema de
  pagamento se acontecer):
  ```
  mysql -u seu_usuario -p seu_banco < database/migrations/010_create_assinaturas.sql
  ```
- Corrigido de brinde: o botao "outline" (ex.: "Fale com o suporte",
  "Remover logo") estava com cor quase invisivel no tema claro (que e o
  padrao do painel) - agora com contraste adequado.

## Ajuste 11 - 2026-07-03

**Landing page: secao de Planos modernizada, com tabela comparativa completa**

- Cada card de plano (Essencial/Premium/Enterprise) ganhou um icone no
  topo e mais itens na lista de recursos (Projecao/Telao no Essencial,
  Comunicacao no Premium).
- Novo botao "Comparar todos os recursos" abaixo dos cards: abre uma
  tabela completa com cada modulo do sistema e em qual plano ele esta
  incluso (check verde) ou nao (traco cinza), alem de linhas extras pra
  numero de usuarios e nivel de suporte.
- Essa tabela e gerada automaticamente a partir do mesmo mapeamento
  modulo -> plano usado de verdade no controle de acesso (Ajuste 10) -
  ou seja, a pagina de vendas nunca vai ficar desatualizada em relacao
  ao que o sistema realmente libera em cada plano.

## Ajuste 10 - 2026-07-03

**Novo modulo: acesso aos modulos do menu conforme o plano contratado (Essencial/Premium/Enterprise)**

- Em Configuracoes, nova secao "Plano contratado" onde da pra escolher o
  plano da igreja (Essencial, Premium ou Enterprise) - o mesmo esquema
  anunciado na pagina de vendas.
- Cada modulo do menu agora tem um plano minimo:
  - Essencial: Membros, Agenda, Cultos, Projecao/Telao, Usuarios,
    Configuracoes.
  - Premium: tudo do Essencial + Ministerios, Grupos, Financeiro,
    Comunicacao.
  - Enterprise: tudo do Premium + Patrimonio, Relatorios, Permissoes.
- Modulo fora do plano contratado aparece apagado e com um cadeado no
  menu lateral e nos cartoes do dashboard. Se tentar entrar mesmo assim
  (clicando ou digitando o link direto), aparece uma tela explicando que
  aquele recurso e de um plano superior, com um botao direto pra trocar
  o plano em Configuracoes.
- Configuracoes continua sempre acessivel, mesmo em planos mais baixos -
  senao nao teria como ver/trocar o plano pela propria tela.
- Testado de ponta a ponta com banco de dados real: login, menu com
  cadeados, bloqueio ao tentar acessar Ministerios/Patrimonio, troca de
  plano salvando corretamente, e liberacao imediata dos modulos do novo
  plano.

## Ajuste 9 - 2026-07-03

**Cache do navegador podia esconder ajustes ja enviados (CSS/JS com versionamento automatico)**

- Causa provavel do "lapis nao fica ativo, fica a mesma cor" mesmo depois
  do Ajuste 8: o navegador do tablet guarda o `telao.css`/`preletor.js`
  em cache por um tempo, entao um ajuste ja enviado (git push) podia
  continuar parecendo "antigo" no aparelho ate o cache vencer sozinho.
  Isso pode ter acontecido em mais telas ao longo do projeto, nao so no
  preletor.
- Agora todo CSS/JS carregado pelas paginas (preletor, telao, dashboard,
  landing, login) leva um `?v=` na URL com a data de modificacao real do
  arquivo no servidor - a cada novo deploy, o navegador e obrigado a
  buscar a versao nova, sem precisar o usuario limpar cache manualmente.
- Para este deploy especifico (o mecanismo de versionamento em si e
  novo), pode ser necessario um "puxar para atualizar" ou fechar/abrir a
  aba uma vez - dos proximos ajustes em diante, isso deixa de ser
  necessario.

## Ajuste 8 - 2026-07-02

**Tela cheia com modo foco de reserva, estado da caneta mais visivel, botao de ajuda e palco redesenhado no preletor**

- Tela cheia: alem de tentar a Fullscreen API nativa do navegador (que em
  alguns tablets/navegadores e bloqueada silenciosamente, fazendo o botao
  "nao fazer nada"), agora existe tambem um "modo foco" por CSS que
  esconde a barra de topo e o aviso "A seguir", sobrando mais espaco pro
  palco - esse modo sempre funciona, independente do navegador permitir
  ou nao a API nativa.
- Caneta: o botao ativo agora ganhou uma bolinha extra (igual o
  indicador "ao vivo" do topo) que acende em verde quando a marcacao
  esta ligada e fica cinza quando desligada, alem da cor de fundo que ja
  existia - fica bem mais dificil de nao perceber o estado.
- Novo botao de ajuda ("?") ao lado dos outros: abre um quadro no canto
  da tela explicando o que cada icone faz (Projetar, setas, caneta,
  apagar, tela cheia).
- O quadro onde o texto da biblia aparece (previa do que esta no telao)
  ganhou um fundo levemente diferente do resto da pagina, borda mais
  visivel e uma etiqueta "PREVIA DO TELAO" no canto, pra ficar claro que
  aquele quadrado e uma previa e nao so o texto solto na tela.

## Ajuste 7 - 2026-07-02

**Botoes de caneta/apagar movidos para o topo, botao de tela cheia, versao auto-reprojeta no preletor**

- Caneta/Apagar marcacao e um novo botao de Tela cheia foram movidos
  para a barra de cima do preletor, ao lado de "Projetar" e das setas
  de navegacao - a barra de baixo foi removida de vez, ja que dependia
  de espaco disponivel na tela (o que causou o problema da barra de
  sistema do tablet cobrindo os botoes). No topo, isso nunca acontece.
- Botao de Tela cheia: entra/sai do modo tela cheia do navegador, o que
  tambem ajuda a ganhar espaco extra (esconde a barra de enderecos e,
  em alguns tablets, ate a barra de sistema do Android).
- Corrigido tambem no preletor: trocar a versao/traducao da biblia
  agora reprojeta automaticamente com o mesmo capitulo/versiculo, sem
  precisar reclicar em nada (mesmo bug corrigido antes so no painel do
  operador).

## Ajuste 6 - 2026-07-02

**Causa real identificada: barra de sistema (Taskbar) do tablet cobrindo os botoes**

- Com o print enviado, ficou claro: nao era bug de rolagem nem de CSS -
  era a barra de sistema do tablet (tipo a "Taskbar" dos tablets
  Samsung/One UI, com os icones de apps e navegacao) sobrepondo a parte
  de baixo da pagina, exatamente onde ficavam os botoes "Caneta"/
  "Apagar marcacao". A pagina renderizava tudo certo, so que por baixo
  dessa barra do sistema, sem o navegador avisar isso via 100vh/100dvh.
- Adicionada uma margem de seguranca no rodape da tela do preletor
  (`padding-bottom`, usando `env(safe-area-inset-bottom)` quando o
  navegador reporta certo, com um valor minimo fixo por garantia), para
  o conteudo nunca ficar colado na borda real da tela.
- O zoom (belisca-tela) tambem estava desativado de proposito no
  codigo (pensado para uma tela fixa tipo app) - isso impedia o usuario
  de ter qualquer jeito de contornar problemas assim manualmente.
  Zoom liberado.

## Ajuste 5 - 2026-07-02

**Tentativa adicional para o problema do tablet (ainda investigando)**

- Trocado `100vh` por `100dvh` (com fallback) no preletor: `100vh` no
  Chrome/Safari mobile nao desconta a barra de enderecos do navegador,
  entao a pagina podia ficar "maior" que a area realmente visivel -
  `100dvh` (altura dinamica de viewport) e o jeito correto/moderno de
  medir isso.
- Area do palco (`.preletor-canvas-wrap`) ganhou uma altura minima de
  160px, pra nunca colapsar a zero em telas muito curtas.
- Esse problema especifico (botoes sumidos so nesse tablet, em mais de
  um navegador) ainda esta em investigacao - testei em varios tamanhos
  de tela realistas e nao consegui reproduzir, entao pode ser algo
  especifico desse aparelho/versao de Android.

## Ajuste 4 - 2026-07-02

**Causa real dos botoes sumidos: canvas bloqueava todo toque por padrao**

- A causa raiz nao era rolagem nem cache: o canvas de desenho do
  preletor (`.preletor-canvas`) tem `touch-action: none` no CSS, mas o
  `pointer-events` que deveria bloquea-lo quando a caneta esta
  desligada so era definido pelo JavaScript ao clicar no botao
  "Caneta". Antes do primeiro clique, o canvas ficava com
  `pointer-events: auto` (padrao do navegador) e capturava TODO toque
  sobre ele - como ele cobre quase a tela toda (ainda mais em telas
  largas como tablets), um arraste de dedo ali nao desenhava nada (a
  caneta estava desligada) nem rolava a pagina (touch-action:none) - so
  travava, sem nenhum efeito visivel. Corrigido: o canvas agora comeca
  com `pointer-events: none` por padrao, batendo com o estado inicial
  real da caneta (desligada).

## Ajuste 3 - 2026-07-02

**Botoes de caneta/apagar inacessiveis no preletor em telas curtas**

- Causa raiz encontrada: `overflow: hidden` estava aplicado em
  `html, body`, regra compartilhada entre o telao (projetor) e o
  preletor (tablet). Isso e correto para o telao (nunca deve rolar),
  mas no preletor, se o conteudo (formulario + previa + palco + barra
  de ferramentas) ficasse mais alto que a tela do tablet, a barra com
  os botoes "Caneta"/"Apagar marcacao" ficava fora da area visivel
  **sem nenhum jeito de rolar ate ela** - nao era cache, o navegador
  simplesmente nao deixava rolar a pagina. Corrigido: o bloqueio de
  rolagem agora fica só no palco do telao, e o preletor pode rolar
  normalmente quando precisar.

## Ajuste 2 - 2026-07-02

**Video do YouTube nao tocava, cores invisiveis no tema claro**

- Video as vezes carregava mas o Play do operador nao tinha efeito: o
  comando chega no telao via polling, sem gesto direto do usuario
  naquela pagina, e o navegador pode bloquear silenciosamente o play()
  com som nesse caso. Agora o telao mostra um aviso "Toque na tela uma
  vez para habilitar o audio automatico" ate o primeiro toque - depois
  disso o video volta a responder aos comandos do operador
  normalmente.
- Corrigidas varias cores de texto que ficavam quase invisiveis no tema
  claro (numeros de capitulo/versiculo, pills de versao inativas,
  tempo do video) - agora usam cinza escuro em vez do cinza claro
  pensado pra fundo escuro.

## Ajuste 1 - 2026-07-02

**Criacao do log de deploy**

- Criado este arquivo na raiz do repositorio para servir como
  conferencia rapida de deploy a cada envio de alteracoes.
