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
