<?php

declare(strict_types=1);

namespace Food\Models;

/**
 * Planos anunciados na pagina de vendas (ver resources/views/landing/
 * home.php). Mesmos nomes do KADOSYS Igrejas/Barbearias/Academias
 * (Essencial/Plus/Premium), precos combinados com o usuario pra faixa
 * de R$49,90 a R$99,99.
 *
 * Ainda nao ha nenhum modulo com restricao por plano - quando isso
 * mudar, adicionar aqui o mesmo padrao de
 * Igrejas\Models\Plano::MODULO_MINIMO/disponivel().
 */
final class Plano
{
    public const ESSENCIAL = 'essencial';
    public const PREMIUM = 'premium';
    public const ENTERPRISE = 'enterprise';

    /**
     * Dias de teste gratis no cadastro publico, sem pedir pagamento -
     * ver CadastroController e AuthMiddleware::bloquearSePagamentoPendente().
     */
    public const TRIAL_DIAS = 7;

    /**
     * @var array<string, string>
     */
    public const LABELS = [
        self::ESSENCIAL => 'Essencial',
        self::PREMIUM => 'Plus',
        self::ENTERPRISE => 'Premium',
    ];

    /**
     * Valor mensal dos 3 planos - todos autoatendidos (cadastro publico
     * + checkout automatico via Mercado Pago, cartao ou Pix).
     *
     * @var array<string, float>
     */
    public const VALOR_MENSAL = [
        self::ESSENCIAL => 49.90,
        self::PREMIUM => 74.90,
        self::ENTERPRISE => 99.99,
    ];

    /**
     * O que cada plano inclui - usado tanto na pagina de vendas
     * (landing/home.php) quanto na troca de plano dentro da dashboard
     * (dashboard/configuracoes/index.php).
     *
     * @var array<string, array<int, string>>
     */
    public const FEATURES = [
        self::ESSENCIAL => [
            'Cadastro de produtos + ficha técnica',
            'Controle de estoque de ingredientes',
            'PDV e caixa completos',
            'Suporte por WhatsApp',
        ],
        self::PREMIUM => [
            'Tudo do Essencial',
            'Pedidos multi-canal (balcão, WhatsApp, delivery)',
            'Tela de produção (cozinha/TV)',
            'Precificação inteligente + taxa iFood',
        ],
        self::ENTERPRISE => [
            'Tudo do Plus',
            'Financeiro completo (contas a pagar/receber)',
            'Relatórios e DRE',
            'Suporte prioritário',
        ],
    ];

    public static function label(string $plano): string
    {
        return self::LABELS[$plano] ?? self::LABELS[self::ESSENCIAL];
    }

    public static function valorMensal(string $plano): float
    {
        return self::VALOR_MENSAL[$plano] ?? self::VALOR_MENSAL[self::ESSENCIAL];
    }

    /** @return array<int, string> */
    public static function features(string $plano): array
    {
        return self::FEATURES[$plano] ?? self::FEATURES[self::ESSENCIAL];
    }

    public static function valido(string $plano): bool
    {
        return array_key_exists($plano, self::VALOR_MENSAL);
    }
}
