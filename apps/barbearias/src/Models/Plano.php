<?php

declare(strict_types=1);

namespace Barbearias\Models;

/**
 * Planos anunciados na pagina de vendas (ver resources/views/landing/
 * home.php). Mesmos nomes do KADOSYS Igrejas (Essencial/Plus/Premium),
 * precos mais acessiveis pensados pro publico de barbearia.
 *
 * Ainda nao ha nenhum modulo com restricao por plano (Profissionais,
 * Servicos, Clientes e Agendamentos, os proximos a serem construidos,
 * ainda sao "Em breve") - quando isso mudar, adicionar aqui o mesmo
 * padrao de Igrejas\Models\Plano::MODULO_MINIMO/disponivel().
 */
final class Plano
{
    public const ESSENCIAL = 'essencial';
    public const PREMIUM = 'premium';
    public const ENTERPRISE = 'enterprise';

    /**
     * Dias de teste gratis no cadastro publico, sem pedir pagamento -
     * ver CadastroController e AuthMiddleware::bloquearSeTrialExpirado().
     */
    public const TRIAL_DIAS = 5;

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
        self::ESSENCIAL => 29.90,
        self::PREMIUM => 49.90,
        self::ENTERPRISE => 69.90,
    ];

    public static function label(string $plano): string
    {
        return self::LABELS[$plano] ?? self::LABELS[self::ESSENCIAL];
    }

    public static function valorMensal(string $plano): float
    {
        return self::VALOR_MENSAL[$plano] ?? self::VALOR_MENSAL[self::ESSENCIAL];
    }

    public static function valido(string $plano): bool
    {
        return array_key_exists($plano, self::VALOR_MENSAL);
    }
}
