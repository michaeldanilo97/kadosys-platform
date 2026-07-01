# KADOSYS Igrejas

Primeiro produto oficial da plataforma KADOSYS: sistema de gestao para igrejas.

URL de producao: `https://kadosys.com.br/apps/igrejas`

## Sprint 1 - Estrutura inicial

Esta etapa entrega:

- Landing Page completa (hero, sobre, recursos, funcionalidades, beneficios,
  capturas de tela, planos, FAQ, rodape).
- Autenticacao completa: login, logout, sessao, "lembrar-me", CSRF, hash de
  senha, middlewares Guest/Auth.
- Recuperacao de senha com estrutura pronta (formulario, geracao e
  armazenamento de token). O envio de e-mail sera ligado em sprint futura.
- Dashboard administrativo com sidebar, topbar, breadcrumb, cards, modo
  claro/escuro e estrutura de pagina para todos os modulos do menu lateral
  (Membros, Ministerios, Grupos, Cultos, Agenda, Financeiro, Patrimonio,
  Comunicacao, Relatorios, Usuarios, Permissoes, Configuracoes). As
  funcionalidades de cada modulo ainda nao foram implementadas, conforme
  escopo definido para esta etapa.

Modulos como Biblia, Louvores, Projecao, Streamer, CRM e ERP **nao** fazem
parte desta etapa e serao desenvolvidos em sprints futuras.

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
│   │                            DashboardController
│   └── Models/                # User
├── resources/
│   └── views/
│       ├── layouts/           # landing, auth, dashboard
│       ├── landing/, auth/, dashboard/, errors/
├── config/
│   ├── config.php             # configuracao geral (base_path automatico)
│   └── database.php           # credenciais via variaveis de ambiente
├── database/
│   ├── migrations/001_create_tables.sql
│   └── seed_admin.php         # cria/atualiza o usuario administrador
└── storage/
    └── logs/
```

## Instalacao

1. Configure as variaveis de ambiente do banco (ou edite os fallbacks em
   `config/database.php`): `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
   `DB_USERNAME`, `DB_PASSWORD`.
2. Crie o banco de dados e rode a migracao:
   ```
   mysql -u usuario -p nome_do_banco < database/migrations/001_create_tables.sql
   ```
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

| Rota                          | Descricao                       | Protecao        |
|-------------------------------|----------------------------------|------------------|
| `/`                           | Landing Page                     | Publica          |
| `/login`                      | Tela de login                    | GuestMiddleware  |
| `/esqueci-senha`              | Recuperacao de senha              | GuestMiddleware  |
| `/dashboard`                  | Painel administrativo             | AuthMiddleware   |
| `/dashboard/{slug}`           | Estrutura de cada modulo do menu  | AuthMiddleware   |
| `POST /logout`                | Encerra a sessao                  | AuthMiddleware   |

## Padroes seguidos

- PSR-4 (autoload) e PSR-12 (estilo de codigo).
- MVC: Controllers (`src/Controllers`), Models (`src/Models`), Views
  (`resources/views`).
- SOLID / Clean Code: classes com responsabilidade unica, sem duplicacao de
  views entre os modulos do menu (uma unica view `dashboard/placeholder.php`
  reaproveitada para todos os modulos ainda nao implementados).
- Banco de dados exclusivo por instalacao (sem multi-tenant).
