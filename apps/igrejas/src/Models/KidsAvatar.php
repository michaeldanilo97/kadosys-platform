<?php

declare(strict_types=1);

namespace Igrejas\Models;

/**
 * Catalogo do Avatar da Crianca (modulo KADOSYS Kids): o xp acumulado em
 * kids_criancas (ganho a cada check-in - ver KidsCheckin::registrar() -
 * ou conteudo concluido - ver KidsConteudo::registrarConclusaoPor())
 * define o NIVEL da crianca (ver nivel()), que por sua vez desbloqueia
 * chapeus/acessorios/fundos/titulos deste catalogo estatico. Nao ha
 * tabela de "desbloqueios por nivel" no banco - um item com "nivel"
 * preenchido esta desbloqueado sempre que nivel_necessario <= nivel
 * atual da crianca, calculado on the fly (ver itemDesbloqueado()). Ja
 * os itens de "loja" (nivel = null, custo_moedas preenchido) so
 * desbloqueiam com uma compra permanente (ver KidsAvatarCompra) - as
 * duas formas de desbloqueio sao independentes, pra moedas terem uso
 * proprio alem do nivel. So o que a crianca ESCOLHEU equipar fica salvo
 * (ver KidsCrianca::atualizarAvatar()).
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
     * @return array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int}>
     */
    public static function catalogoChapeus(): array
    {
        return [
            ['slug' => 'bone', 'emoji' => '🧢', 'nome' => 'Boné', 'nivel' => 1, 'custoMoedas' => null],
            ['slug' => 'chapeu-sol', 'emoji' => '👒', 'nome' => 'Chapéu de Sol', 'nivel' => 3, 'custoMoedas' => null],
            ['slug' => 'coroa-principe', 'emoji' => '👑', 'nome' => 'Coroa de Príncipe(a)', 'nivel' => 5, 'custoMoedas' => null],
            ['slug' => 'cartola', 'emoji' => '🎩', 'nome' => 'Cartola', 'nivel' => 7, 'custoMoedas' => null],
            ['slug' => 'capacete-heroi', 'emoji' => '⛑️', 'nome' => 'Capacete de Herói', 'nivel' => 9, 'custoMoedas' => null],
            ['slug' => 'capacete-salvacao', 'emoji' => '🪖', 'nome' => 'Capacete da Salvação', 'nivel' => 11, 'custoMoedas' => null],
            ['slug' => 'aureola', 'emoji' => '😇', 'nome' => 'Auréola de Anjo', 'nivel' => 13, 'custoMoedas' => null],
            ['slug' => 'capelo-sabio', 'emoji' => '🎓', 'nome' => 'Capelo de Sábio', 'nivel' => 15, 'custoMoedas' => null],
            ['slug' => 'coroa-estrelas', 'emoji' => '🌟', 'nome' => 'Coroa de Estrelas', 'nivel' => 17, 'custoMoedas' => null],
            ['slug' => 'coroa-vitoria', 'emoji' => '💫', 'nome' => 'Coroa da Vitória', 'nivel' => 19, 'custoMoedas' => null],
            ['slug' => 'chapeu-festa', 'emoji' => '🥳', 'nome' => 'Chapéu de Festa', 'nivel' => null, 'custoMoedas' => 25],
            ['slug' => 'chapeu-magico', 'emoji' => '🧙', 'nome' => 'Chapéu Mágico', 'nivel' => null, 'custoMoedas' => 45],
        ];
    }

    /**
     * @return array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int}>
     */
    public static function catalogoAcessorios(): array
    {
        return [
            ['slug' => 'mochila', 'emoji' => '🎒', 'nome' => 'Mochila', 'nivel' => 2, 'custoMoedas' => null],
            ['slug' => 'biblia-bolso', 'emoji' => '📖', 'nome' => 'Bíblia de Bolso', 'nivel' => 4, 'custoMoedas' => null],
            ['slug' => 'escudo-fe', 'emoji' => '🛡️', 'nome' => 'Escudo da Fé', 'nivel' => 6, 'custoMoedas' => null],
            ['slug' => 'espada-espirito', 'emoji' => '⚔️', 'nome' => 'Espada do Espírito', 'nivel' => 8, 'custoMoedas' => null],
            ['slug' => 'violao', 'emoji' => '🎸', 'nome' => 'Violão', 'nivel' => 10, 'custoMoedas' => null],
            ['slug' => 'pomba-paz', 'emoji' => '🕊️', 'nome' => 'Pomba da Paz', 'nivel' => 12, 'custoMoedas' => null],
            ['slug' => 'estrela-guia', 'emoji' => '⭐', 'nome' => 'Estrela Guia', 'nivel' => 14, 'custoMoedas' => null],
            ['slug' => 'chama-espirito', 'emoji' => '🔥', 'nome' => 'Chama do Espírito', 'nivel' => 16, 'custoMoedas' => null],
            ['slug' => 'trofeu-ouro', 'emoji' => '🏆', 'nome' => 'Troféu de Ouro', 'nivel' => 18, 'custoMoedas' => null],
            ['slug' => 'joia-preciosa', 'emoji' => '💎', 'nome' => 'Joia Preciosa', 'nivel' => 20, 'custoMoedas' => null],
            ['slug' => 'balao', 'emoji' => '🎈', 'nome' => 'Balão Colorido', 'nivel' => null, 'custoMoedas' => 20],
            ['slug' => 'luneta', 'emoji' => '🔭', 'nome' => 'Luneta do Explorador', 'nivel' => null, 'custoMoedas' => 35],
        ];
    }

    /**
     * @return array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int, gradiente: string}>
     */
    public static function catalogoFundos(): array
    {
        return [
            ['slug' => 'ceu-manha', 'emoji' => '🌤️', 'nome' => 'Céu de Manhã', 'nivel' => 1, 'custoMoedas' => null, 'gradiente' => 'linear-gradient(135deg, #4CC9F0, #9B5DE5)'],
            ['slug' => 'patio-igreja', 'emoji' => '⛪', 'nome' => 'Pátio da Igreja', 'nivel' => 4, 'custoMoedas' => null, 'gradiente' => 'linear-gradient(135deg, #FFD93D, #FF9F1C)'],
            ['slug' => 'arco-iris', 'emoji' => '🌈', 'nome' => 'Arco-íris', 'nivel' => 8, 'custoMoedas' => null, 'gradiente' => 'linear-gradient(135deg, #FF6FA5, #4CC9F0, #6BCB77)'],
            ['slug' => 'ceu-estrelado', 'emoji' => '✨', 'nome' => 'Céu Estrelado', 'nivel' => 12, 'custoMoedas' => null, 'gradiente' => 'linear-gradient(135deg, #3A2E5C, #9B5DE5)'],
            ['slug' => 'montanha-sagrada', 'emoji' => '🏔️', 'nome' => 'Montanha Sagrada', 'nivel' => 16, 'custoMoedas' => null, 'gradiente' => 'linear-gradient(135deg, #6BCB77, #4CC9F0)'],
            ['slug' => 'amanhecer-gloria', 'emoji' => '🌅', 'nome' => 'Amanhecer da Glória', 'nivel' => 20, 'custoMoedas' => null, 'gradiente' => 'linear-gradient(135deg, #FF9F1C, #FF6FA5, #9B5DE5)'],
            ['slug' => 'praia', 'emoji' => '🏖️', 'nome' => 'Praia Ensolarada', 'nivel' => null, 'custoMoedas' => 30, 'gradiente' => 'linear-gradient(135deg, #FFD93D, #4CC9F0)'],
            ['slug' => 'espaco', 'emoji' => '🚀', 'nome' => 'Viagem Espacial', 'nivel' => null, 'custoMoedas' => 50, 'gradiente' => 'linear-gradient(135deg, #1B1035, #6B2FBF)'],
        ];
    }

    /**
     * @return array<int, array{slug: string, nome: string, nivel: ?int, custoMoedas: ?int}>
     */
    public static function catalogoTitulos(): array
    {
        return [
            ['slug' => 'pequeno-aprendiz', 'nome' => 'Pequeno Aprendiz', 'nivel' => 1, 'custoMoedas' => null],
            ['slug' => 'pequeno-missionario', 'nome' => 'Pequeno Missionário', 'nivel' => 3, 'custoMoedas' => null],
            ['slug' => 'guardiao-versiculos', 'nome' => 'Guardião dos Versículos', 'nivel' => 6, 'custoMoedas' => null],
            ['slug' => 'coracao-corajoso', 'nome' => 'Coração Corajoso', 'nivel' => 9, 'custoMoedas' => null],
            ['slug' => 'amigo-fiel', 'nome' => 'Amigo Fiel', 'nivel' => 12, 'custoMoedas' => null],
            ['slug' => 'luz-igrejinha', 'nome' => 'Luz da Igrejinha', 'nivel' => 15, 'custoMoedas' => null],
            ['slug' => 'campeao-fe', 'nome' => 'Campeão da Fé', 'nivel' => 18, 'custoMoedas' => null],
            ['slug' => 'heroi-palavra', 'nome' => 'Herói da Palavra', 'nivel' => 20, 'custoMoedas' => null],
            ['slug' => 'estrela-da-turma', 'nome' => 'Estrela da Turma', 'nivel' => null, 'custoMoedas' => 25],
            ['slug' => 'campeao-moedas', 'nome' => 'Campeão das Moedas', 'nivel' => null, 'custoMoedas' => 40],
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
     * @param array<int, array{slug: string, nivel: ?int}> $catalogo
     * @param array<int, string> $comprados slugs desta categoria ja comprados na loja (ver KidsAvatarCompra)
     * @return array<int, array<string, mixed>>
     */
    public static function desbloqueados(array $catalogo, int $nivel, array $comprados = []): array
    {
        return array_values(array_filter(
            $catalogo,
            static fn (array $item) => ($item['nivel'] !== null && $item['nivel'] <= $nivel) || in_array($item['slug'], $comprados, true)
        ));
    }

    /**
     * @param array<int, array{slug: string, nivel: ?int}> $catalogo
     * @param array<int, string> $comprados slugs desta categoria ja comprados na loja (ver KidsAvatarCompra)
     */
    public static function itemDesbloqueado(array $catalogo, ?string $slug, int $nivel, array $comprados = []): bool
    {
        if ($slug === null) {
            return true;
        }

        foreach ($catalogo as $item) {
            if ($item['slug'] === $slug) {
                return ($item['nivel'] !== null && $item['nivel'] <= $nivel) || in_array($slug, $comprados, true);
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
