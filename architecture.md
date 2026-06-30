# Arquitetura — Kadosys Platform

## Visao geral

A Kadosys Platform segue uma arquitetura de **nucleo unico compartilhado
(Core)** com **multiplos aplicativos plugaveis (apps/)**. O Core nunca
e duplicado: ele existe uma unica vez na raiz do projeto e e
referenciado por todos os aplicativos atraves do mesmo autoload do
Composer.

```
public_html/
├── core/        <- Framework proprio (existe apenas uma vez)
├── apps/        <- Aplicativos (igrejas, crm, streamer, escola, ...)
├── config/      <- Configuracoes globais (.env -> config/*.php)
├── database/    <- Migrations e Seeders compartilhados
├── resources/   <- Views/assets compartilhados (fallback do Core)
├── bootstrap/   <- Inicializacao da Application
├── storage/     <- Logs, cache, sessoes, uploads
└── index.php    <- Front Controller unico
```

Cada aplicativo dentro de `apps/{nome}/` contem **apenas** sua propria
camada de aplicacao (Controllers, Models, Services, Modules, Views,
Routes, Assets, Config, Lang). Nenhum aplicativo possui seu proprio
Composer, vendor, Router, Database, Session ou qualquer parte do
framework — toda essa infraestrutura pertence exclusivamente ao Core.

## Fluxo de uma requisicao (MVC)

1. **index.php** (Front Controller) recebe toda requisicao HTTP e
   inicializa a `Application` via `bootstrap/app.php`.
2. **Application::initialize()** carrega o `.env` (`Env`), as
   configuracoes (`Config`), registra os Service Providers do Core
   (`AppServiceProvider`, `RouteServiceProvider`,
   `DatabaseServiceProvider`, `ViewServiceProvider`), carrega a
   config especifica do aplicativo ativo e suas rotas
   (`apps/{app}/Routes/web.php`).
3. **Application::handle()** captura a requisicao atual
   (`Request::capture()`), que automaticamente remove o
   `APP_BASE_PATH` da URI.
4. **Router::dispatch()** encontra a rota correspondente (metodo +
   URI), executa a pilha de Middlewares (`MiddlewarePipeline`) e, por
   fim, invoca a **action** da rota — uma Closure ou um
   `Controller@metodo`.
5. O **Controller** (camada C do MVC) trata a requisicao, geralmente
   delegando regras de negocio a um **Service**, acessando dados via
   **Model**, e retornando uma `Response`.
6. O **Model** (camada M) encapsula o acesso a dados via
   `QueryBuilder`, aplicando automaticamente o isolamento
   multi-tenant quando aplicavel.
7. A **View** (camada V) e um template PHP puro renderizado pela
   classe `View`, que primeiro procura o arquivo em
   `apps/{app}/Views/` e, como fallback, em `resources/views/` do
   Core (usado para paginas de erro compartilhadas, por exemplo).
8. A `Response` resultante e enviada ao navegador (`Response::send()`).

## Camadas do Core

| Camada | Pasta | Responsabilidade |
|---|---|---|
| Core | `core/Core/` | Application, Container, Config, Env |
| Http | `core/Http/` | Request, Response, Controller base |
| Routing | `core/Routing/` | Router, Route |
| Middleware | `core/Middleware/` | Pipeline e middlewares (auth, guest, csrf, tenant) |
| Database | `core/Database/` | Connection, QueryBuilder, Model, Migration, Seeder |
| Security | `core/Security/` | Auth, Session, Csrf, Hash |
| View | `core/View/` | Motor de templates PHP puro |
| Support | `core/Support/` | Validator, Logger, Tenant |
| Providers | `core/Providers/` | Service Providers (registro de bindings) |
| Exceptions | `core/Exceptions/` | ExceptionHandler |
| Helpers | `core/Helpers/` | Funcoes globais (url, view, config, etc) |
| Console | `core/Console/` | CLI (migrate, make:app, make:controller, etc) |

## Container de servicos

A `Application` mantem um `Container` (injecao de dependencias) que
resolve automaticamente classes via Reflection, inspecionando o
construtor e resolvendo recursivamente cada dependencia tipada.
Servicos transversais como `Auth` e `Csrf` sao registrados como
**singletons** pelo `AppServiceProvider`, garantindo uma unica
instancia por requisicao.

```php
// Resolucao automatica via Reflection (sem binding explicito):
$controller = $container->resolve(MeuController::class);

// Binding singleton (uma instancia para toda a requisicao):
$container->singleton(Auth::class, fn () => new Auth());
```

## Roteamento

O `Router` trabalha **exclusivamente com URIs relativas**
(`/dashboard`, `/login`), nunca com caminhos absolutos incluindo o
`APP_BASE_PATH`. Isso e garantido porque o `Request::capture()`
remove o `APP_BASE_PATH` da URI antes mesmo do Router recebe-la.

```php
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->setName('dashboard')
    ->middleware(['auth', 'tenant']);

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function ($router) {
    $router->get('/relatorios', [RelatorioController::class, 'index']);
});
```

## Middlewares

Cada middleware implementa `MiddlewareInterface` (metodo
`handle(Request $request, Closure $next): Response`), seguindo o
padrao "onion" (cebola): pode atuar antes e/ou depois de chamar
`$next($request)`. Os aliases globais registrados pelo
`RouteServiceProvider` sao:

| Alias | Classe | Funcao |
|---|---|---|
| `auth` | `AuthMiddleware` | Exige usuario autenticado |
| `guest` | `GuestMiddleware` | Exige usuario NAO autenticado |
| `csrf` | `VerifyCsrfTokenMiddleware` | Valida token CSRF em POST/PUT/PATCH/DELETE |
| `tenant` | `TenantMiddleware` | Resolve o tenant atual (multi-tenant) |

### Criando um novo Middleware

```php
namespace Kadosys\Apps\Igrejas\Middleware;

use Kadosys\Core\Middleware\MiddlewareInterface;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;

final class SomenteAdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        // logica de verificacao...
        return $next($request);
    }
}
```

Registre o alias (se desejar usar por nome curto) em um Service
Provider, ou referencie diretamente a classe completa na rota:

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware(\Kadosys\Apps\Igrejas\Middleware\SomenteAdminMiddleware::class);
```

## Como criar um novo aplicativo

Use o comando CLI, que gera toda a estrutura padrao automaticamente:

```bash
php kadosys make:app crm
```

Isso cria `apps/crm/` com `Controllers/`, `Models/`, `Services/`,
`Modules/`, `Views/`, `Routes/`, `Assets/`, `Config/` e `Lang/`, alem
de um `Routes/web.php` e `Config/config.php` iniciais. Em seguida:

1. Adicione o namespace do novo app ao `composer.json` (autoload
   PSR-4), caso ainda nao exista (os apps padrao ja vem
   pre-configurados: igrejas, crm, streamer, escola, condominio, erp,
   rh).
2. Defina `APP_ACTIVE=crm` no `.env` para ativa-lo nesta instalacao
   (cada instalacao/dominio/subdiretorio roda **um** aplicativo ativo
   por vez, todos compartilhando o mesmo Core).
3. Rode `composer dump-autoload` se adicionar um namespace novo.

## Como criar um Controller

```bash
php kadosys make:controller MembrosController
```

Gera `apps/{app_ativo}/Controllers/MembrosController.php`, ja
estendendo `Kadosys\Core\Http\Controller`:

```php
namespace Kadosys\Apps\Igrejas\Controllers;

use Kadosys\Core\Http\Controller;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;

final class MembrosController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('membros.index');
    }
}
```

## Como criar um Model

```bash
php kadosys make:model Membro
```

Gera `apps/{app_ativo}/Models/Membro.php`, ja estendendo
`Kadosys\Core\Database\Model` com suporte automatico a `tenant_id`:

```php
namespace Kadosys\Apps\Igrejas\Models;

use Kadosys\Core\Database\Model;

final class Membro extends Model
{
    protected static string $table = 'membros';
    protected static bool $tenantAware = true;
}
```

Uso:

```php
$membros = Membro::all();                          // Filtrado pelo tenant atual
$membro = Membro::find(5);
$novo = Membro::create(['nome' => 'Joao']);          // tenant_id injetado automaticamente
$membro->update(['nome' => 'Joao Silva']);
$membro->delete();
```

## Como criar um Service

Services concentram regras de negocio, mantendo os Controllers finos:

```php
namespace Kadosys\Apps\Igrejas\Services;

final class MembroService
{
    public function cadastrar(array $dados): Membro
    {
        // validacoes, regras de negocio...
        return Membro::create($dados);
    }
}
```

## Como criar um Modulo

Modulos sao subdivisoes de negocio dentro de um aplicativo (ex: o app
"igrejas" podera ter os modulos Membros, Financeiro e Escalas operando
de forma desacoplada). Crie a pasta dentro de
`apps/{app}/Modules/{NomeDoModulo}/` replicando a mesma estrutura
(Controllers, Models, Services, Views), e registre suas rotas no
`Routes/web.php` do aplicativo, com um prefixo de grupo:

```php
$router->group(['prefix' => 'financeiro'], function ($router) {
    $router->get('/', [FinanceiroController::class, 'index']);
});
```

## Multi-tenant

Veja [database.md](database.md) para detalhes completos sobre o
isolamento de dados por `tenant_id`.

## Padroes aplicados

- **SOLID**: cada classe possui responsabilidade unica (Connection
  cuida apenas de conexao, QueryBuilder apenas de queries, Model
  apenas de persistencia orientada a objeto).
- **PSR-4**: autoload por namespace.
- **PSR-12**: estilo de codigo (4 espacos, chaves em linha propria
  para classes/metodos, tipagem forte em todos os parametros e
  retornos).
- **DRY**: nenhuma logica de infraestrutura e duplicada entre
  aplicativos.
- **KISS**: implementacoes simples e diretas, sem abstrações
  desnecessarias.
