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
