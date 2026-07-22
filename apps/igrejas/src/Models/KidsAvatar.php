<?php

declare(strict_types=1);

namespace Igrejas\Models;

/**
 * Catalogo do Avatar da Crianca (modulo KADOSYS Kids): o xp acumulado em
 * kids_criancas (ganho a cada check-in - ver KidsCheckin::registrar() -
 * ou conteudo concluido - ver KidsConteudo::registrarConclusaoPor())
 * define o NIVEL da crianca (ver nivel()), que por sua vez desbloqueia
 * chapeus/acessorios/fundos/titulos deste catalogo estatico. Nao ha
 * tabela de "desbloqueios" no banco - um item esta desbloqueado sempre
 * que seu nivel_necessario <= nivel atual da crianca, calculado on the
 * fly (ver itemDesbloqueado()). So o que a crianca ESCOLHEU equipar fica
 * salvo (ver KidsCrianca::atualizarAvatar()).
 */
final class KidsAvatar
{
    /**
     * XP acumulado necessario pra ALCANCAR cada nivel (indice 0 = nivel
     * 1, sempre 0xp). Curva pensada pra o primeiro nivel vir rapido (1-2
     * check-ins) e os seguintes exigirem semanas de participacao
     * constante, ate um teto de 20 niveis.
     *
     * @var array<int, int>
     */
    private const LIMIARES_XP = [
        0, 40, 90, 160, 250, 360, 500, 660, 850, 1070,
        1320, 1600, 1910, 2250, 2620, 3020, 3450, 3910, 4400, 4920,
    ];

    public const NIVEL_MAXIMO = 20;

    /**
     * @return array<int, array{slug: string, emoji: string, nome: string, nivel: int}>
     */
    public static function catalogoChapeus(): array
    {
        return [
            ['slug' => 'bone', 'emoji' => '🧢', 'nome' => 'Boné', 'nivel' => 1],
            ['slug' => 'chapeu-sol', 'emoji' => '👒', 'nome' => 'Chapéu de Sol', 'nivel' => 3],
            ['slug' => 'coroa-principe', 'emoji' => '👑', 'nome' => 'Coroa de Príncipe(a)', 'nivel' => 5],
            ['slug' => 'cartola', 'emoji' => '🎩', 'nome' => 'Cartola', 'nivel' => 7],
            ['slug' => 'capacete-heroi', 'emoji' => '⛑️', 'nome' => 'Capacete de Herói', 'nivel' => 9],
            ['slug' => 'capacete-salvacao', 'emoji' => '🪖', 'nome' => 'Capacete da Salvação', 'nivel' => 11],
            ['slug' => 'aureola', 'emoji' => '😇', 'nome' => 'Auréola de Anjo', 'nivel' => 13],
            ['slug' => 'capelo-sabio', 'emoji' => '🎓', 'nome' => 'Capelo de Sábio', 'nivel' => 15],
            ['slug' => 'coroa-estrelas', 'emoji' => '🌟', 'nome' => 'Coroa de Estrelas', 'nivel' => 17],
            ['slug' => 'coroa-vitoria', 'emoji' => '💫', 'nome' => 'Coroa da Vitória', 'nivel' => 19],
        ];
    }

    /**
     * @return array<int, array{slug: string, emoji: string, nome: string, nivel: int}>
     */
    public static function catalogoAcessorios(): array
    {
        return [
            ['slug' => 'mochila', 'emoji' => '🎒', 'nome' => 'Mochila', 'nivel' => 2],
            ['slug' => 'biblia-bolso', 'emoji' => '📖', 'nome' => 'Bíblia de Bolso', 'nivel' => 4],
            ['slug' => 'escudo-fe', 'emoji' => '🛡️', 'nome' => 'Escudo da Fé', 'nivel' => 6],
            ['slug' => 'espada-espirito', 'emoji' => '⚔️', 'nome' => 'Espada do Espírito', 'nivel' => 8],
            ['slug' => 'violao', 'emoji' => '🎸', 'nome' => 'Violão', 'nivel' => 10],
            ['slug' => 'pomba-paz', 'emoji' => '🕊️', 'nome' => 'Pomba da Paz', 'nivel' => 12],
            ['slug' => 'estrela-guia', 'emoji' => '⭐', 'nome' => 'Estrela Guia', 'nivel' => 14],
            ['slug' => 'chama-espirito', 'emoji' => '🔥', 'nome' => 'Chama do Espírito', 'nivel' => 16],
            ['slug' => 'trofeu-ouro', 'emoji' => '🏆', 'nome' => 'Troféu de Ouro', 'nivel' => 18],
            ['slug' => 'joia-preciosa', 'emoji' => '💎', 'nome' => 'Joia Preciosa', 'nivel' => 20],
        ];
    }

    /**
     * @return array<int, array{slug: string, emoji: string, nome: string, nivel: int, gradiente: string}>
     */
    public static function catalogoFundos(): array
    {
        return [
            ['slug' => 'ceu-manha', 'emoji' => '🌤️', 'nome' => 'Céu de Manhã', 'nivel' => 1, 'gradiente' => 'linear-gradient(135deg, #4CC9F0, #9B5DE5)'],
            ['slug' => 'patio-igreja', 'emoji' => '⛪', 'nome' => 'Pátio da Igreja', 'nivel' => 4, 'gradiente' => 'linear-gradient(135deg, #FFD93D, #FF9F1C)'],
            ['slug' => 'arco-iris', 'emoji' => '🌈', 'nome' => 'Arco-íris', 'nivel' => 8, 'gradiente' => 'linear-gradient(135deg, #FF6FA5, #4CC9F0, #6BCB77)'],
            ['slug' => 'ceu-estrelado', 'emoji' => '✨', 'nome' => 'Céu Estrelado', 'nivel' => 12, 'gradiente' => 'linear-gradient(135deg, #3A2E5C, #9B5DE5)'],
            ['slug' => 'montanha-sagrada', 'emoji' => '🏔️', 'nome' => 'Montanha Sagrada', 'nivel' => 16, 'gradiente' => 'linear-gradient(135deg, #6BCB77, #4CC9F0)'],
            ['slug' => 'amanhecer-gloria', 'emoji' => '🌅', 'nome' => 'Amanhecer da Glória', 'nivel' => 20, 'gradiente' => 'linear-gradient(135deg, #FF9F1C, #FF6FA5, #9B5DE5)'],
        ];
    }

    /**
     * @return array<int, array{slug: string, nome: string, nivel: int}>
     */
    public static function catalogoTitulos(): array
    {
        return [
            ['slug' => 'pequeno-aprendiz', 'nome' => 'Pequeno Aprendiz', 'nivel' => 1],
            ['slug' => 'pequeno-missionario', 'nome' => 'Pequeno Missionário', 'nivel' => 3],
            ['slug' => 'guardiao-versiculos', 'nome' => 'Guardião dos Versículos', 'nivel' => 6],
            ['slug' => 'coracao-corajoso', 'nome' => 'Coração Corajoso', 'nivel' => 9],
            ['slug' => 'amigo-fiel', 'nome' => 'Amigo Fiel', 'nivel' => 12],
            ['slug' => 'luz-igrejinha', 'nome' => 'Luz da Igrejinha', 'nivel' => 15],
            ['slug' => 'campeao-fe', 'nome' => 'Campeão da Fé', 'nivel' => 18],
            ['slug' => 'heroi-palavra', 'nome' => 'Herói da Palavra', 'nivel' => 20],
        ];
    }

    /**
     * Nivel atual a partir do xp acumulado - o maior nivel cujo limiar
     * de xp ja foi atingido, sem passar do NIVEL_MAXIMO.
     */
    public static function nivel(int $xp): int
    {
        $nivel = 1;

        foreach (self::LIMIARES_XP as $indice => $limiar) {
            if ($xp >= $limiar) {
                $nivel = $indice + 1;
            }
        }

        return min($nivel, self::NIVEL_MAXIMO);
    }

    /**
     * Progresso ate o proximo nivel, pra barra de XP na tela do avatar.
     * No nivel maximo, retorna percentual 100 (barra cheia, sem "proximo
     * nivel" pra mostrar).
     *
     * @return array{nivelAtual: int, xpAtual: int, xpInicioNivel: int, xpProximoNivel: ?int, percentual: int}
     */
    public static function progresso(int $xp): array
    {
        $nivelAtual = self::nivel($xp);
        $xpInicioNivel = self::LIMIARES_XP[$nivelAtual - 1];

        if ($nivelAtual >= self::NIVEL_MAXIMO) {
            return [
                'nivelAtual' => $nivelAtual,
                'xpAtual' => $xp,
                'xpInicioNivel' => $xpInicioNivel,
                'xpProximoNivel' => null,
                'percentual' => 100,
            ];
        }

        $xpProximoNivel = self::LIMIARES_XP[$nivelAtual];
        $faixa = $xpProximoNivel - $xpInicioNivel;
        $percentual = $faixa > 0 ? (int) round((($xp - $xpInicioNivel) / $faixa) * 100) : 100;

        return [
            'nivelAtual' => $nivelAtual,
            'xpAtual' => $xp,
            'xpInicioNivel' => $xpInicioNivel,
            'xpProximoNivel' => $xpProximoNivel,
            'percentual' => max(0, min(100, $percentual)),
        ];
    }

    /**
     * @param array<int, array{slug: string, nivel: int}> $catalogo
     * @return array<int, array<string, mixed>>
     */
    public static function desbloqueados(array $catalogo, int $nivel): array
    {
        return array_values(array_filter($catalogo, static fn (array $item) => $item['nivel'] <= $nivel));
    }

    /**
     * @param array<int, array{slug: string, nivel: int}> $catalogo
     */
    public static function itemDesbloqueado(array $catalogo, ?string $slug, int $nivel): bool
    {
        if ($slug === null) {
            return true;
        }

        foreach ($catalogo as $item) {
            if ($item['slug'] === $slug) {
                return $item['nivel'] <= $nivel;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{slug: string}> $catalogo
     */
    public static function encontrar(array $catalogo, ?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }

        foreach ($catalogo as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }

        return null;
    }
}
