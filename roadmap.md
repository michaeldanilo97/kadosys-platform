# Roadmap — Kadosys Platform

## v1 (atual) — Infraestrutura base

Entregue nesta versao:

- [x] Core do framework: Application, Container, Config, Env
- [x] Router completo (GET, POST, PUT, PATCH, DELETE, grupos,
      prefixos, middlewares, rotas nomeadas, parametros dinamicos,
      404, 405)
- [x] Suporte nativo a `APP_BASE_PATH` (instalacao em subdiretorios)
- [x] Request / Response
- [x] Database: Connection (PDO Singleton), QueryBuilder, Model base,
      Migration base, Seeder base, MigrationRunner
- [x] Arquitetura multi-tenant (`tenant_id` automatico)
- [x] Auth: login, logout, hash de senha, "lembrar-me", guards
      (auth/guest), ACL simples (role)
- [x] Sessao e CSRF
- [x] View engine (templates PHP puro, com layouts)
- [x] Validator
- [x] Logger
- [x] ExceptionHandler
- [x] Service Providers
- [x] Helpers globais (url, route, asset, redirect, config, env,
      view, csrf, session, old, abort, dd)
- [x] CLI propria (`php kadosys`): migrate, make:app, make:controller,
      make:model, make:migration
- [x] Dashboard administrativo (Bootstrap 5): sidebar, topbar,
      breadcrumb, cards, tema claro/escuro — sem funcionalidades de
      negocio
- [x] Aplicativo "igrejas" criado apenas como esqueleto de
      infraestrutura (Controllers, Models, Services, Modules, Views,
      Routes, Assets, Config, Lang)
- [x] Documentacao completa (README, architecture, database, roadmap)

## v2 — Modulos de negocio do app Igrejas

- [x] Modulo Membros (cadastro, listagem com busca e paginacao, edicao e
      remocao; KPI de membros ativos no dashboard)

Ainda **nao implementados**, conforme escopo definido:

- [ ] Modulo Eventos (agenda, inscricoes)
- [ ] Modulo Financeiro (dizimos, ofertas, despesas, relatorios)
- [ ] Modulo Escalas (equipes, ministerios, disponibilidade)
- [ ] Modulo Biblia (leitura, planos de leitura)
- [ ] Modulo Louvores (repertorio, cifras, letras)
- [ ] Modulo Projecao / Telao (apresentacao em culto)

## v3 — Novos aplicativos sobre o mesmo Core

- [ ] CRM (funil de vendas, contatos, pipeline)
- [ ] Streamer (gestao de transmissoes ao vivo)
- [ ] Escola (matriculas, turmas, notas)
- [ ] Condominio (moradores, reservas, ocorrencias)
- [ ] ERP (estoque, vendas, compras)
- [ ] RH (folha de pagamento, ponto, ferias)

## v4 — Evolucao da plataforma

- [ ] Sistema de filas (jobs assincronos)
- [ ] Cache (file/redis) integrado ao Core
- [ ] API REST padronizada para todos os aplicativos
- [ ] Sistema de notificacoes (e-mail, push, SMS)
- [ ] Internacionalizacao completa (alem de pt_BR)
- [ ] Testes automatizados (PHPUnit) cobrindo o Core
- [ ] Painel "super admin" para gerenciar tenants entre aplicativos
- [ ] Marketplace interno de modulos plugaveis

## Principios que permanecem inalterados em todas as versoes futuras

- O Core nunca sera duplicado: continuara existindo uma unica vez,
  compartilhado por todos os aplicativos.
- Nenhum aplicativo tera seu proprio Composer, vendor, Router,
  Database ou Session.
- Toda nova funcionalidade de infraestrutura sera implementada no
  Core, nunca em um aplicativo especifico.
- SOLID, PSR-4, PSR-12, DRY, KISS e Clean Code continuam obrigatorios.
