# Banco de Dados — Kadosys Platform

## Conexao

A plataforma utiliza **PDO puro** com prepared statements em todas as
operacoes, sem nenhum ORM de terceiros. A classe `Connection`
(`core/Database/Connection.php`) implementa o padrao **Singleton**,
garantindo uma unica conexao ativa por requisicao, compartilhada por
todos os Models e Query Builders.

As credenciais sao lidas de `config/database.php`, que por sua vez le
do `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kadosys
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

## QueryBuilder

O `QueryBuilder` (`core/Database/QueryBuilder.php`) oferece uma API
fluente para `SELECT`, `INSERT`, `UPDATE` e `DELETE`, sempre via
prepared statements (bindings nomeados), prevenindo SQL Injection.

```php
use Kadosys\Core\Database\QueryBuilder;

// SELECT
$membros = (new QueryBuilder('membros'))
    ->where('tenant_id', '=', 1)
    ->where('ativo', '=', true)
    ->orderBy('nome')
    ->limit(10)
    ->get();

// SELECT primeiro resultado
$membro = (new QueryBuilder('membros'))->where('id', '=', 5)->first();

// COUNT
$total = (new QueryBuilder('membros'))->where('ativo', '=', true)->count();

// INSERT (retorna o ID inserido)
$id = (new QueryBuilder('membros'))->insert([
    'nome' => 'Joao',
    'tenant_id' => 1,
]);

// UPDATE (retorna numero de linhas afetadas)
(new QueryBuilder('membros'))->where('id', '=', 5)->update(['nome' => 'Joao Silva']);

// DELETE
(new QueryBuilder('membros'))->where('id', '=', 5)->delete();
```

## Model (ActiveRecord)

A classe `Model` (`core/Database/Model.php`) e a base abstrata para
todos os Models da plataforma, oferecendo uma API ActiveRecord simples
sobre o `QueryBuilder`, com **suporte nativo a multi-tenant**.

```php
namespace Kadosys\Apps\Igrejas\Models;

use Kadosys\Core\Database\Model;

final class Membro extends Model
{
    protected static string $table = 'membros';
    protected static bool $tenantAware = true; // padrao
}
```

Operacoes disponiveis:

```php
Membro::all();                  // Todos os registros (filtrados por tenant)
Membro::find(5);                // Busca por chave primaria
Membro::where('nome', 'LIKE', '%joao%')->get();
Membro::create(['nome' => 'Joao']); // tenant_id injetado automaticamente
$membro->update(['nome' => 'Joao Silva']);
$membro->delete();
$membro->toArray();
```

## Transacoes

```php
use Kadosys\Core\Database\Connection;

Connection::getInstance()->transaction(function () {
    // operacoes...
    // rollback automatico em caso de excecao
});
```

## Multi-tenant

A Kadosys Platform foi projetada desde o inicio para suportar
**multiplos tenants** (igrejas, empresas, organizacoes) na mesma
instalacao e no mesmo banco de dados, atraves de uma coluna
`tenant_id` presente em todas as tabelas de negocio.

### Como funciona

1. O `TenantMiddleware` (alias `tenant`) resolve o tenant do usuario
   autenticado (campo `tenant_id` da tabela de usuarios) e o
   disponibiliza globalmente via `Kadosys\Core\Support\Tenant::current()`.
2. Todo `Model` com `$tenantAware = true` (padrao) filtra
   automaticamente **todas** as consultas (`all()`, `find()`,
   `where()`) adicionando `WHERE tenant_id = :tenant_atual`.
3. Ao criar um registro via `Model::create()`, o `tenant_id` e
   injetado automaticamente a partir do contexto atual, sem
   necessidade de informa-lo manualmente.

### Habilitando/desabilitando

Controlado em `config/tenant.php` / `.env`:

```env
TENANT_ENABLED=true
TENANT_DEFAULT_ID=1
```

### Desabilitando o isolamento em um Model especifico

Models que armazenam dados globais (nao especificos de um tenant,
como tabelas de configuracao do sistema) podem desativar o
comportamento:

```php
final class ConfiguracaoGlobal extends Model
{
    protected static string $table = 'configuracoes_globais';
    protected static bool $tenantAware = false;
}
```

## Migrations

Migrations sao classes que estendem `Kadosys\Core\Database\Migration`,
implementando `up()` (aplica a alteracao) e `down()` (reverte).

```bash
php kadosys make:migration create_membros_table
```

Gera um arquivo em `database/migrations/` prefixado com timestamp
(garante ordem de execucao):

```php
final class CreateMembrosTable extends Migration
{
    public function up(): void
    {
        $this->connection()->statement("
            CREATE TABLE membros (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        $this->connection()->statement('DROP TABLE IF EXISTS membros');
    }
}
```

Execute as migrations pendentes:

```bash
php kadosys migrate
```

O `MigrationRunner` mantem uma tabela de controle
(`kadosys_migrations`) registrando quais migrations ja foram
executadas, evitando reexecucao.

## Seeders

Seeders populam tabelas com dados iniciais/teste, estendendo
`Kadosys\Core\Database\Seeder`:

```php
final class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        (new QueryBuilder('tenants'))->insert(['nome' => 'Igreja Exemplo']);
    }
}
```

A plataforma ja inclui:

- `database/migrations/2026_06_26_000000_create_tenants_table.php`
- `database/migrations/2026_06_26_000001_create_users_table.php`
- `database/seeders/DefaultDataSeeder.php` (cria um tenant e um
  usuario administrador padrao: `admin@kadosys.local` / `password`)

## Tabelas base incluidas

### `tenants`

| Coluna | Tipo | Descricao |
|---|---|---|
| id | INT | Chave primaria |
| nome | VARCHAR(255) | Nome do tenant (igreja/empresa) |
| slug | VARCHAR(255) | Identificador unico amigavel |
| app | VARCHAR(100) | Aplicativo associado (igrejas, crm, etc) |
| ativo | TINYINT(1) | Status do tenant |

### `users`

| Coluna | Tipo | Descricao |
|---|---|---|
| id | INT | Chave primaria |
| tenant_id | INT | Isolamento multi-tenant |
| name | VARCHAR(255) | Nome do usuario |
| email | VARCHAR(255) | E-mail (unico), usado no login |
| password | VARCHAR(255) | Hash bcrypt da senha |
| role | VARCHAR(50) | Papel/funcao (ACL simples) |
| remember_token | VARCHAR(100) | Token do "lembrar-me" |
