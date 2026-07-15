<?php

declare(strict_types=1);

namespace Barbearias\Core;

/**
 * Validacao de CPF e CNPJ (algoritmo padrao de digitos verificadores) -
 * usada no cadastro publico, que exige um documento pra permitir
 * limitar abuso do teste gratis por CPF/CNPJ (ver
 * Barbearias\Models\Barbearia::documentoJaUsouTrial).
 */
final class Documento
{
    /**
     * Remove tudo que nao for digito (pontos, tracos, barras).
     */
    public static function apenasDigitos(string $documento): string
    {
        return preg_replace('/\D/', '', $documento) ?? '';
    }

    public static function validarCpf(string $cpf): bool
    {
        $cpf = self::apenasDigitos($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($indice = 0; $indice < $posicao; $indice++) {
                $soma += ((int) $cpf[$indice]) * (($posicao + 1) - $indice);
            }

            $digitoVerificador = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digitoVerificador) {
                return false;
            }
        }

        return true;
    }

    public static function validarCnpj(string $cnpj): bool
    {
        $cnpj = self::apenasDigitos($cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $pesosPrimeiroDigito = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesosSegundoDigito = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        foreach ([12 => $pesosPrimeiroDigito, 13 => $pesosSegundoDigito] as $posicao => $pesos) {
            $soma = 0;

            foreach ($pesos as $indice => $peso) {
                $soma += ((int) $cnpj[$indice]) * $peso;
            }

            $resto = $soma % 11;
            $digitoVerificador = $resto < 2 ? 0 : 11 - $resto;

            if ((int) $cnpj[$posicao] !== $digitoVerificador) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param 'cpf'|'cnpj' $tipo
     */
    public static function validar(string $tipo, string $documento): bool
    {
        return $tipo === 'cnpj' ? self::validarCnpj($documento) : self::validarCpf($documento);
    }
}
