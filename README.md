# Kadosys Platform

Plataforma SaaS proprietaria construida em **PHP 8.1+ puro** (compativel
com PHP 8.3), sem nenhum framework de terceiros (sem Laravel, Symfony,
CodeIgniter, Yii ou Slim). A Kadosys Platform e um framework completo e
reutilizavel — semelhante em filosofia ao Laravel, porem 100% proprio —
projetado para hospedar **multiplos aplicativos** sobre o mesmo nucleo
(Core), compartilhando toda a infraestrutura: roteamento, banco de
dados, autenticacao, sessao, views, validacao, logs e tratamento de
erros.

## Aplicativos suportados nesta arquitetura

- **Igrejas** (implementado nesta v1, apenas infraestrutura)
- CRM
- Streamer
- Escola
- Condominio
- ERP
- RH

Todos os aplicativos futuros utilizam exatamente o mesmo Core, sem
duplicacao de framework.

## Requisitos

- PHP >= 8.1 (recomendado 8.3)
- Extensao PDO (`pdo_mysql` ou equivalente)
- Composer
- Servidor Apache com `mod_rewrite` (ou Nginx equivalente) para
  hospedagem compartilhada

## Instalacao

```bash
# 1. Faca upload de todo o conteudo de public_html/ para a raiz da hospedagem.

# 2. Instale as dependencias (gera o autoloader PSR-4 oficial do Composer).
composer install

# 3. Copie o arquivo de ambiente e configure suas credenciais.
cp .env.example .env

# 4. Edite o .env com os dados do seu banco e da sua URL:
#    APP_URL=https://seudominio.com
#    APP_BASE_PATH=            (ou /app/igrejas, /apps/crm, etc)
#    APP_ACTIVE=igrejas
#    DB_DATABASE=...
#    DB_USERNAME=...
#    DB_PASSWORD=...

# 5. Execute as migrations.
php kadosys migrate
```

> **Nota sobre o autoloader:** este pacote ja inclui um
> `vendor/autoload.php` minimo (PSR-4) para que a plataforma funcione
> imediatamente apos o upload, mesmo antes de rodar `composer install`.
> Ao executar `composer install`, o Composer ira gerar o autoloader
> oficial e sobrescrever esse arquivo automaticamente — nenhuma acao
> adicional e necessaria.

## Subdiretorios (APP_BASE_PATH)

A plataforma suporta nativamente instalacao em subdiretorios, apenas
alterando duas variaveis no `.env`:

```env
APP_URL=https://dominio.com
APP_BASE_PATH=/app/igrejas
```

ou

```env
APP_URL=https://dominio.com
APP_BASE_PATH=/apps/crm
```

O `Request` remove automaticamente o `APP_BASE_PATH` da URI antes de
entregar ao `Router`, que sempre trabalha com URIs relativas
(`/login`, `/dashboard`), nunca com caminhos absolutos como
`/app/igrejas/dashboard`.

## Documentacao completa

- [architecture.md](architecture.md) — Arquitetura, fluxo MVC e como
  criar novos aplicativos, modulos, Controllers, Models, Services e
  Middlewares.
- [database.md](database.md) — Banco de dados, QueryBuilder,
  Migrations, Seeders e arquitetura multi-tenant.
- [roadmap.md](roadmap.md) — Roteiro de evolucao da plataforma.

## Comandos disponiveis (CLI)

```bash
php kadosys migrate                          # Executa migrations pendentes
php kadosys make:app crm                     # Cria a estrutura de um novo aplicativo
php kadosys make:controller MembrosController
php kadosys make:model Membro
php kadosys make:migration create_membros_table
```

## Licenca

Software proprietario. Todos os direitos reservados.
