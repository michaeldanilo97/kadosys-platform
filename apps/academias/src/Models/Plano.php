<?php

declare(strict_types=1);

namespace Academias\Models;

/**
 * Planos de assinatura da PROPRIA academia com a Kadosys (nao confundir
 * com `Academias\Models\PlanoMatricula`, que e o plano que a academia
 * vende pros ALUNOS dela). Mesma estrutura de 3 niveis + trial ja usada
 * em `Barbearias\Models\Plano`.
 */
final class Plano
{
    public const ESSENCIAL = 'essencial';
    public const PREMIUM = 'premium';
    public const ENTERPRISE = 'enterprise';

    /** Dias de teste gratis no cadastro publico. */
    public const TRIAL_DIAS = 5;

    public const LABELS = [
        self::ESSENCIAL => 'Essencial',
        self::PREMIUM => 'Plus',
        self::ENTERPRISE => 'Premium',
    ];

    public const VALOR_MENSAL = [
        self::ESSENCIAL => 29.90,
        self::PREMIUM => 49.90,
        self::ENTERPRISE => 69.90,
    ];

    public const FEATURES = [
        self::ESSENCIAL => ['Até 1 unidade', 'Alunos ilimitados', 'Check-in por QR Code', 'Suporte por WhatsApp'],
        self::PREMIUM => ['Tudo do Essencial', 'Ficha de treino com evolução de carga', 'Avaliação física', 'Ranking de frequência'],
        self::ENTERPRISE => ['Tudo do Plus', 'Múltiplas unidades', 'Professores ilimitados', 'Suporte prioritário'],
    ];

    public static function label(string $plano): string
    {
        return self::LABELS[$plano] ?? $plano;
    }

    public static function valorMensal(string $plano): float
    {
        return self::VALOR_MENSAL[$plano] ?? 0.0;
    }

    /** @return array<int, string> */
    public static function features(string $plano): array
    {
        return self::FEATURES[$plano] ?? [];
    }

    public static function valido(string $plano): bool
    {
        return isset(self::LABELS[$plano]);
    }
}
