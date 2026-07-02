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
