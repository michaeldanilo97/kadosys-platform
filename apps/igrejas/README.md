# KADOSYS Igrejas

Primeiro produto oficial da plataforma KADOSYS: sistema de gestao para igrejas.

URL de producao: `https://kadosys.com.br/apps/igrejas`

## Sprint 1 - Estrutura inicial

Esta etapa entregou:

- Landing Page completa (hero, sobre, recursos, funcionalidades, beneficios,
  capturas de tela, planos, FAQ, rodape).
- Autenticacao completa: login, logout, sessao, "lembrar-me", CSRF, hash de
  senha, middlewares Guest/Auth.
- Recuperacao de senha com estrutura pronta (formulario, geracao e
  armazenamento de token). O envio de e-mail sera ligado em sprint futura.
- Dashboard administrativo com sidebar, topbar, breadcrumb, cards, modo
  claro/escuro e estrutura de pagina para todos os modulos do menu lateral
  (Membros, Ministerios, Grupos, Cultos, Agenda, Financeiro, Patrimonio,
  Comunicacao, Relatorios, Usuarios, Permissoes, Configuracoes).

Modulos como Biblia, Louvores, Projecao, Streamer, CRM e ERP **nao** fazem
parte desta etapa e serao desenvolvidos em sprints futuras.

## Sprint 2 - Modulo Membros

Primeiro modulo de negocio implementado (v2 do roadmap):

- Cadastro completo de membros (dados pessoais, contato, endereco e
  membresia).
- Listagem com busca (nome/e-mail) e paginacao.
- Edicao e remocao, com validacao e mensagens de sucesso/erro.
- KPI "Membros ativos" no dashboard, com contagem de novos membros no mes.

## Sprint 3 - Modulo Ministerios

Segundo modulo de negocio implementado (v2 do roadmap):

- Cadastro de ministerios (nome, descricao, lider e status).
- Lider selecionado entre os membros ativos.
- Gestao de voluntarios: adicionar/remover membros vinculados a cada
  ministerio (relacao muitos-para-muitos).
- Listagem com busca por nome e paginacao.
- KPI "Ministerios" no dashboard, com contagem de ministerios ativos.

## Sprint 4 - Modulo Cultos

Terceiro modulo de negocio implementado (v2 do roadmap):

- Programacao de cultos (titulo, data, horario, local, descricao e
  status: agendado/realizado/cancelado).
- Registro de frequencia: adicionar/remover membros presentes em cada
  culto.
- Listagem com busca por titulo/local e paginacao.
- KPI "Proximo culto" no dashboard, com o proximo culto agendado.

## Sprint 5 - Modulo Projecao/Telao

Sistema de projecao ao vivo para o culto, com 3 telas sincronizadas por
polling (sem WebSocket, por rodar em hospedagem compartilhada):

- **Painel do operador** (`/dashboard/projecao`, autenticado): inicia/
  encerra a sessao de projecao, seleciona a referencia biblica, cola o
  link de um video do YouTube e controla play/pause/fadeout.
- **Telao** (`/telao/{token}`, publico): tela cheia para o projetor,
  acessada por link direto com token. Exibe biblia, video ou a logo da
  igreja (no fadeout).
- **Preletor** (`/preletor`, publico, PIN de 6 digitos): tela do tablet
  do pastor, espelha o texto do telao, permite trocar a referencia
  biblica e marcar o texto a lapis (anotacao temporaria, por versiculo).
- Schema da Biblia com os 66 livros ja cadastrados; texto dos versiculos
  importado via `database/seed_biblia.php` (formato documentado no
  proprio script).
- Upload da logo da igreja em Configuracoes (`/dashboard/configuracoes`),
  usada no fadeout do video.

## Tecnologias

- PHP 8.3+
- MySQL
- Bootstrap 5 (via CDN)
- CSS puro / JavaScript puro
- Composer (apenas para autoload PSR-4)

Nenhum framework (Laravel, Symfony, CodeIgniter, Slim) ou bibliotecas de
frontend baseadas em componentes (React, Vue, Angular, Tailwind) foram
utilizados, conforme exigido.

## Estrutura de pastas

```
apps/igrejas/
├── composer.json
├── .htaccess                 # redireciona tudo para /public
├── public/                   # document root da aplicacao
│   ├── index.php             # front controller
│   ├── .htaccess
│   └── assets/
│       ├── css/ (app, landing, auth, dashboard)
│       └── js/  (landing, dashboard)
├── routes/
│   └── web.php                # definicao das rotas
├── src/
│   ├── Core/                  # micro-framework interno (Router, Controller,
│   │                            View, Database, Session, Csrf, Auth, Request)
│   │   └── Middleware/        # GuestMiddleware, AuthMiddleware
│   ├── Controllers/           # LandingController, AuthController,
│   │                            DashboardController, MembroController,
│   │                            MinisterioController, CultoController,
│   │                            ConfiguracaoController, ProjecaoController,
│   │                            ProjecaoEstadoController, TelaoController,
│   │                            PreletorController
│   └── Models/                # User, Membro, Ministerio, Culto,
│                                 ConfiguracaoIgreja, BibliaLivro,
│                                 BibliaVersiculo, ProjecaoSessao,
│                                 ProjecaoEstado
├── resources/
│   └── views/
│       ├── layouts/           # landing, auth, dashboard, telao
│       ├── landing/, auth/, dashboard/, errors/, telao/
├── config/
│   ├── config.php             # configuracao geral (base_path automatico)
│   └── database.php           # credenciais via variaveis de ambiente
├── database/
│   ├── install.sql             # instalacao completa (todas as migracoes)
│   ├── migrations/001_create_tables.sql
│   ├── migrations/002_create_membros_table.sql
│   ├── migrations/003_create_ministerios_tables.sql
│   ├── migrations/004_create_cultos_tables.sql
│   ├── migrations/005_create_projecao_tables.sql
│   ├── seed_biblia.php        # importa o texto biblico (ver cabecalho do arquivo)
│   └── seed_admin.php         # cria/atualiza o usuario administrador
└── storage/
    └── logs/
```

## Instalacao

1. Configure as variaveis de ambiente do banco (ou edite os fallbacks em
   `config/database.php`): `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
   `DB_USERNAME`, `DB_PASSWORD`.
2. Crie o banco de dados e rode as migracoes. Duas formas equivalentes
   (use apenas uma delas):
   ```
   # a) instalacao completa em um unico arquivo (recomendado)
   mysql -u usuario -p nome_do_banco < database/install.sql

   # b) migracoes numeradas, uma a uma, em ordem
   mysql -u usuario -p nome_do_banco < database/migrations/001_create_tables.sql
   mysql -u usuario -p nome_do_banco < database/migrations/002_create_membros_table.sql
   mysql -u usuario -p nome_do_banco < database/migrations/003_create_ministerios_tables.sql
   mysql -u usuario -p nome_do_banco < database/migrations/004_create_cultos_tables.sql
   mysql -u usuario -p nome_do_banco < database/migrations/005_create_projecao_tables.sql
   ```
   A cada novo modulo implementado, uma nova migracao numerada e criada em
   `database/migrations/` e `database/install.sql` e atualizado para
   refletir o schema completo mais recente.
3. Crie o usuario administrador inicial:
   ```
   php database/seed_admin.php "Administrador" "[email protected]" "senha-forte"
   ```
4. (Opcional, recomendado) Gere o autoload via Composer:
   ```
   composer install
   ```
   Caso o Composer ainda nao tenha sido executado, `public/index.php`
   utiliza um autoload de fallback (PSR-4 manual) para que a aplicacao
   continue funcionando.
5. Aponte o document root do subdiretorio `/apps/igrejas` para a pasta
   `public/` (ou utilize o `.htaccess` da raiz, que ja redireciona as
   requisicoes internamente).

## Rotas desta etapa

| Rota                                  | Descricao                         | Protecao        |
|----------------------------------------|------------------------------------|------------------|
| `/`                                    | Landing Page                       | Publica          |
| `/login`                               | Tela de login                      | GuestMiddleware  |
| `/esqueci-senha`                       | Recuperacao de senha                | GuestMiddleware  |
| `/dashboard`                           | Painel administrativo               | AuthMiddleware   |
| `/dashboard/membros`                   | Listagem de membros (busca/paginacao) | AuthMiddleware |
| `/dashboard/membros/novo`              | Formulario de novo membro           | AuthMiddleware   |
| `POST /dashboard/membros`              | Cadastra membro                     | AuthMiddleware   |
| `/dashboard/membros/{id}/editar`       | Formulario de edicao                | AuthMiddleware   |
| `POST /dashboard/membros/{id}`         | Atualiza membro                     | AuthMiddleware   |
| `POST /dashboard/membros/{id}/excluir` | Remove membro                       | AuthMiddleware   |
| `/dashboard/ministerios`               | Listagem de ministerios (busca/paginacao) | AuthMiddleware |
| `/dashboard/ministerios/novo`          | Formulario de novo ministerio       | AuthMiddleware   |
| `POST /dashboard/ministerios`          | Cadastra ministerio                 | AuthMiddleware   |
| `/dashboard/ministerios/{id}/editar`   | Formulario de edicao (e voluntarios) | AuthMiddleware  |
| `POST /dashboard/ministerios/{id}`     | Atualiza ministerio                 | AuthMiddleware   |
| `POST /dashboard/ministerios/{id}/excluir` | Remove ministerio                | AuthMiddleware   |
| `POST /dashboard/ministerios/{id}/voluntarios` | Adiciona voluntario         | AuthMiddleware   |
| `POST /dashboard/ministerios/{id}/voluntarios/{membroId}/remover` | Remove voluntario | AuthMiddleware |
| `/dashboard/cultos`                    | Listagem de cultos (busca/paginacao) | AuthMiddleware  |
| `/dashboard/cultos/novo`               | Formulario de novo culto            | AuthMiddleware   |
| `POST /dashboard/cultos`               | Cadastra culto                      | AuthMiddleware   |
| `/dashboard/cultos/{id}/editar`        | Formulario de edicao (e frequencia) | AuthMiddleware   |
| `POST /dashboard/cultos/{id}`          | Atualiza culto                      | AuthMiddleware   |
| `POST /dashboard/cultos/{id}/excluir`  | Remove culto                        | AuthMiddleware   |
| `POST /dashboard/cultos/{id}/presencas` | Registra presenca                  | AuthMiddleware   |
| `POST /dashboard/cultos/{id}/presencas/{membroId}/remover` | Remove presenca     | AuthMiddleware   |
| `/dashboard/projecao`                  | Painel do operador (biblia/video)   | AuthMiddleware   |
| `POST /dashboard/projecao/iniciar`     | Inicia sessao de projecao           | AuthMiddleware   |
| `POST /dashboard/projecao/encerrar`    | Encerra sessao de projecao          | AuthMiddleware   |
| `/dashboard/configuracoes`             | Configuracoes (logo da igreja)      | AuthMiddleware   |
| `POST /dashboard/configuracoes/logo`   | Envia/atualiza a logo               | AuthMiddleware   |
| `POST /dashboard/configuracoes/logo/remover` | Remove a logo                 | AuthMiddleware   |
| `/dashboard/{slug}`                    | Estrutura dos demais modulos do menu | AuthMiddleware  |
| `POST /logout`                         | Encerra a sessao                    | AuthMiddleware   |
| `/telao/{token}`                       | Telao (tela cheia do projetor)      | Publica (token)  |
| `/preletor`                            | Entrada por PIN (tablet do pastor)  | Publica          |
| `POST /preletor`                       | Autentica o PIN                     | Publica          |
| `/preletor/painel`                     | Painel do preletor                  | Dispositivo autorizado |
| `POST /preletor/sair`                  | Encerra o acesso do dispositivo     | Publica          |
| `GET /projecao/{token}/estado`         | Estado atual (JSON, polling)        | Publica (token)  |
| `POST /projecao/{token}/biblia`        | Define a referencia biblica exibida | Publica (token)  |
| `POST /projecao/{token}/video`         | Define o video exibido              | Publica (token)  |
| `POST /projecao/{token}/video/estado`  | Play/pausa/fadeout do video         | Publica (token)  |
| `POST /projecao/{token}/logo`          | Exibe a logo da igreja              | Publica (token)  |
| `POST /projecao/{token}/limpar`        | Limpa a tela (fica em branco)       | Publica (token)  |

## Padroes seguidos

- PSR-4 (autoload) e PSR-12 (estilo de codigo).
- MVC: Controllers (`src/Controllers`), Models (`src/Models`), Views
  (`resources/views`).
- SOLID / Clean Code: classes com responsabilidade unica, sem duplicacao de
  views entre os modulos do menu (uma unica view `dashboard/placeholder.php`
  reaproveitada para todos os modulos ainda nao implementados).
- Banco de dados exclusivo por instalacao (sem multi-tenant).
