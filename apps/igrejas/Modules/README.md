# Modules

Esta pasta abrigara os modulos de negocio do aplicativo "igrejas"
(Biblia, Louvores, Projecao, Telao, Membros, Eventos, Financeiro,
Escalas, etc), ainda **nao implementados** nesta versao 1 da
Kadosys Platform.

## O que e um Module?

Um Module e uma unidade de funcionalidade de negocio mais granular
que um aplicativo inteiro, util quando um app possui multiplas
areas independentes (ex: o app "igrejas" podera ter os modulos
Membros, Financeiro e Escalas operando de forma desacoplada,
porem compartilhando os mesmos Controllers/Models/Services base).

## Estrutura sugerida para um futuro modulo

```
Modules/
└── Membros/
    ├── Controllers/
    ├── Models/
    ├── Services/
    └── Views/
```

Cada modulo segue exatamente os mesmos principios do Core:
SOLID, PSR-4, tipagem forte e Clean Code.
