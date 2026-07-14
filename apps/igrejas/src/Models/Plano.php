<?php

declare(strict_types=1);

namespace Igrejas\Models;

/**
 * Regras de qual modulo do menu esta disponivel em cada plano contratado.
 * Espelha os planos anunciados na pagina de vendas (ver resources/views/
 * landing/home.php, secao #planos): Essencial, Premium e Enterprise, cada
 * um incluindo tudo do plano anterior.
 */
final class Plano
{
    public const ESSENCIAL = 'essencial';
    public const PREMIUM = 'premium';
    public const ENTERPRISE = 'enterprise';

    /** @var array<string, int> */
    private const ORDEM = [
        self::ESSENCIAL => 1,
        self::PREMIUM => 2,
        self::ENTERPRISE => 3,
    ];

    /**
     * Nomes usados na venda (ver landing/home.php) - as CONSTANTES/valores
     * gravados no banco continuam "essencial"/"premium"/"enterprise" por
     * baixo dos panos (sem migracao, sem risco), so o texto mostrado ao
     * cliente mudou: o antigo "Premium" virou "Plus", e o antigo
     * "Enterprise" (antes so "sob consulta") virou "Premium" autoatendido.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        self::ESSENCIAL => 'Essencial',
        self::PREMIUM => 'Plus',
        self::ENTERPRISE => 'Premium',
    ];

    /**
     * Valor mensal dos 3 planos - todos autoatendidos (cadastro publico +
     * checkout automatico via Mercado Pago, cartao ou Pix).
     *
     * @var array<string, float>
     */
    public const VALOR_MENSAL = [
        self::ESSENCIAL => 49.90,
        self::PREMIUM => 99.90,
        self::ENTERPRISE => 139.90,
    ];

    /**
     * Plano minimo exigido por modulo do menu (slug => plano). Modulos que
     * nao aparecem aqui sao tratados como Essencial (liberado a todos).
     *
     * @var array<string, string>
     */
    private const MODULO_MINIMO = [
        'ministerios' => self::PREMIUM,
        'grupos' => self::PREMIUM,
        'financeiro' => self::PREMIUM,
        'comunicacao' => self::PREMIUM,
        'kids' => self::PREMIUM,
        'patrimonio' => self::ENTERPRISE,
        'relatorios' => self::ENTERPRISE,
    ];

    public static function minimoParaModulo(string $slug): string
    {
        return self::MODULO_MINIMO[$slug] ?? self::ESSENCIAL;
    }

    /**
     * $emTrial libera todos os modulos independente do plano escolhido no
     * cadastro - o teste gratis de 7 dias precisa dar acesso completo ao
     * sistema, senao a igreja nao consegue avaliar os recursos dos planos
     * superiores antes de decidir por qual assinar (ver
     * Igrejas\Core\Middleware\PlanoMiddleware).
     */
    public static function disponivel(string $planoAtual, string $slug, bool $emTrial = false): bool
    {
        if ($emTrial) {
            return true;
        }

        $atual = self::ORDEM[$planoAtual] ?? self::ORDEM[self::ESSENCIAL];
        $minimo = self::ORDEM[self::minimoParaModulo($slug)];

        return $atual >= $minimo;
    }

    public static function label(string $plano): string
    {
        return self::LABELS[$plano] ?? self::LABELS[self::ESSENCIAL];
    }
}
