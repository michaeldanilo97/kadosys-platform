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

    /** @var array<string, string> */
    public const LABELS = [
        self::ESSENCIAL => 'Essencial',
        self::PREMIUM => 'Premium',
        self::ENTERPRISE => 'Enterprise',
    ];

    /**
     * Valor mensal dos planos com assinatura automatica via Mercado Pago
     * (ver Igrejas\Core\MercadoPagoClient). Enterprise fica de fora de
     * proposito - e "sob consulta", negociado direto com o suporte, sem
     * checkout automatico nem cadastro publico autoatendido.
     *
     * TODO-TEMPORARIO: Essencial baixado pra R$1,00 so pra testar o
     * provisionamento automatico com um pagamento real barato (ver Ajuste
     * de teste). PRECISA voltar pra 97.00 antes de qualquer cliente real
     * se cadastrar - a pagina de vendas (landing/home.php) continua
     * mostrando R$97 fixo no texto, so o valor cobrado de verdade nesse
     * formulario que esta temporariamente diferente.
     *
     * @var array<string, float>
     */
    public const VALOR_MENSAL = [
        self::ESSENCIAL => 1.00,
        self::PREMIUM => 197.00,
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
        'patrimonio' => self::ENTERPRISE,
        'relatorios' => self::ENTERPRISE,
        'permissoes' => self::ENTERPRISE,
    ];

    public static function minimoParaModulo(string $slug): string
    {
        return self::MODULO_MINIMO[$slug] ?? self::ESSENCIAL;
    }

    public static function disponivel(string $planoAtual, string $slug): bool
    {
        $atual = self::ORDEM[$planoAtual] ?? self::ORDEM[self::ESSENCIAL];
        $minimo = self::ORDEM[self::minimoParaModulo($slug)];

        return $atual >= $minimo;
    }

    public static function label(string $plano): string
    {
        return self::LABELS[$plano] ?? self::LABELS[self::ESSENCIAL];
    }
}
