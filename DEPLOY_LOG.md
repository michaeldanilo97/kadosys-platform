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
