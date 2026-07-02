<?php

declare(strict_types=1);

namespace Igrejas\Models;

/**
 * Versoes/traducoes da Biblia disponiveis para consulta e projecao.
 *
 * Fixas no codigo (nao ha tabela propria): o texto vem do projeto publico
 * thiagobodruk/biblia (https://github.com/thiagobodruk/biblia), que
 * disponibiliza estas 3 traducoes em pt-BR. Ver database/seed_biblia.php
 * para a importacao.
 */
final class BibliaVersao
{
    /** @var array<string, string> codigo => nome de exibicao */
    private const VERSOES = [
        'nvi' => 'NVI - Nova Versao Internacional',
        'acf' => 'ACF - Almeida Corrigida Fiel',
        'aa' => 'AA - Almeida Revisada Imprensa Biblica',
    ];

    public const PADRAO = 'nvi';

    /**
     * @return array<string, string>
     */
    public static function todas(): array
    {
        return self::VERSOES;
    }

    public static function nome(?string $codigo): ?string
    {
        return $codigo !== null ? (self::VERSOES[$codigo] ?? null) : null;
    }

    public static function valida(?string $codigo): bool
    {
        return $codigo !== null && isset(self::VERSOES[$codigo]);
    }
}
