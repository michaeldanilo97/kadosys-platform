<?php

declare(strict_types=1);

namespace Igrejas\Core;

/**
 * Transposicao de acordes em texto de cifra/letra - porta em PHP da
 * mesma logica usada no cliente (ver
 * public/assets/js/louvor-transpositor.js), necessaria pra transpor a
 * letra/cifra de verdade quando o tom muda ao vivo no Modo Culto (o
 * Modo Culto so exibe o que esta salvo no banco, nao tem textarea no
 * navegador pra mexer feito o cadastro).
 */
final class Transpositor
{
    /**
     * Mesma grafia usada no <select> de tons (ver Louvor::TONS_MAIORES)
     * - usada como "tabela de saida", pra sempre devolver os acordes com
     * grafia consistente (bemol nos tons que normalmente aparecem assim
     * em cifra brasileira), independente de como a cifra original
     * grafava as notas (sustenido ou bemol).
     *
     * @var array<int, string>
     */
    private const NOTAS_CANONICAS = ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];

    /** @var array<string, int> */
    private const INDICE_NOTA = [
        'C' => 0, 'B#' => 0,
        'C#' => 1, 'Db' => 1,
        'D' => 2,
        'D#' => 3, 'Eb' => 3,
        'E' => 4, 'Fb' => 4,
        'F' => 5, 'E#' => 5,
        'F#' => 6, 'Gb' => 6,
        'G' => 7,
        'G#' => 8, 'Ab' => 8,
        'A' => 9,
        'A#' => 10, 'Bb' => 10,
        'B' => 11, 'Cb' => 11,
    ];

    /**
     * Qualidades/extensoes de acorde reconhecidas (m, 7, 9, dim, sus4,
     * add9, 7M etc.) - uma LISTA FECHADA, e nao "qualquer letra",
     * porque varias palavras comuns em letras de louvor (ex.: "Deus",
     * "Fala", "Comigo", "Graça") comecam com uma nota (D, F, C...)
     * seguida de mais letras, e bateriam num formato de acorde
     * permissivo demais - o que fazia linhaEhDeAcordes() achar que uma
     * linha de LETRA inteira era "so acordes" e transpor palavras da
     * letra por engano, virando texto sem sentido (ex.: "Deus" virando
     * "C#eus"). Precisa ser IDENTICA a mesma lista no cliente (ver
     * public/assets/js/louvor-transpositor.js), senao a pre-visualizacao
     * no cadastro e a transposicao ao vivo no Modo Culto discordariam.
     */
    private const SUFIXO_ACORDE = '(?:dim7|dim|aug|sus2|sus4|sus|add2|add4|add6|add9|add11|add13|maj7|maj9|maj11|maj13|maj|min7|min9|min11|min13|min|m7b5|m7#5|m9b5|m6|m69|m7|m9|m11|m13|m2|m4|m|6|69|9|11|13|7M|9M|11M|13M|7|5|4|2)?';

    /**
     * Formato de um "token" de acorde valido: nota (A-G, com sustenido
     * ou bemol), seguida da qualidade/extensao (lista acima), extensao
     * entre parenteses opcional (ex.: "(4/9)") e baixo opcional depois
     * de uma barra (ex.: "/B", acorde com inversao).
     */
    private const FORMATO_ACORDE = '/^[A-G](#|b)?' . self::SUFIXO_ACORDE . '(\([^)]*\))?(\/[A-G](#|b)?)?$/i';

    /**
     * Quantos semitons separam dois tons (null se algum deles nao for
     * reconhecido, ex.: uma grafia antiga fora das listas canonicas -
     * nesse caso o chamador deve so trocar o tom, sem tentar transpor
     * o texto).
     */
    public static function calcularSemitons(?string $tomOriginal, ?string $tomNovo): ?int
    {
        $indiceOriginal = self::tomParaIndice($tomOriginal);
        $indiceNovo = self::tomParaIndice($tomNovo);

        if ($indiceOriginal === null || $indiceNovo === null) {
            return null;
        }

        return (($indiceNovo - $indiceOriginal) % 12 + 12) % 12;
    }

    public static function transporTexto(string $texto, int $semitons): string
    {
        $linhas = explode("\n", $texto);

        $linhas = array_map(
            static function (string $linha) use ($semitons): string {
                if (!self::linhaEhDeAcordes($linha)) {
                    return $linha;
                }

                return (string) preg_replace_callback(
                    '/\S+/',
                    static fn (array $match): string => self::transporAcorde($match[0], $semitons),
                    $linha
                );
            },
            $linhas
        );

        return implode("\n", $linhas);
    }

    private static function tomParaIndice(?string $tom): ?int
    {
        if ($tom === null || $tom === '') {
            return null;
        }

        $raiz = preg_replace('/m$/', '', $tom);

        return self::INDICE_NOTA[$raiz] ?? null;
    }

    private static function linhaEhDeAcordes(string $linha): bool
    {
        $tokens = array_filter(preg_split('/\s+/', trim($linha)) ?: [], static fn (string $token): bool => $token !== '');

        if ($tokens === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if (!preg_match(self::FORMATO_ACORDE, $token)) {
                return false;
            }
        }

        return true;
    }

    private static function transporNota(string $nota, int $semitons): string
    {
        $indice = self::INDICE_NOTA[$nota] ?? null;

        if ($indice === null) {
            return $nota;
        }

        $novoIndice = (($indice + $semitons) % 12 + 12) % 12;

        return self::NOTAS_CANONICAS[$novoIndice];
    }

    /**
     * Transpoe um unico token de acorde (ex.: "Fm7(9)", "Eb/G") -
     * preserva tudo que nao for a nota (qualidade, extensoes,
     * parenteses), so troca a raiz e, se houver, a nota do baixo
     * (acorde com inversao, reconhecido so no FINAL do token).
     */
    private static function transporAcorde(string $token, int $semitons): string
    {
        if (!preg_match('/^[A-G](#|b)?/', $token, $mRaiz)) {
            return $token;
        }

        $raiz = $mRaiz[0];
        $resto = substr($token, strlen($raiz));
        $novaRaiz = self::transporNota($raiz, $semitons);

        if (preg_match('/\/([A-G])(#|b)?$/', $resto, $mBaixo)) {
            $novoBaixo = self::transporNota($mBaixo[1] . ($mBaixo[2] ?? ''), $semitons);
            $resto = substr($resto, 0, -strlen($mBaixo[0])) . '/' . $novoBaixo;
        }

        return $novaRaiz . $resto;
    }
}
