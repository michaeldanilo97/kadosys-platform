<?php

declare(strict_types=1);

namespace Food\Core;

/**
 * Monta o payload do "Pix Copia e Cola" (BR Code, padrao EMV QR Code do
 * Banco Central - manual "Arranjo Pix", secao QR Codes estaticos) a
 * partir da chave Pix do proprio restaurante - sem nenhuma chamada a
 * gateway de pagamento. O dinheiro cai direto na conta do restaurante; o
 * sistema so monta a string que vira QR code (renderizado no
 * navegador, ver assets/js/vendor/qrcode-generator.js) e o texto
 * "copia e cola". Mesma classe (portada) de Igrejas\Core\PixEstatico.
 *
 * Trade-off assumido: sem gateway, nao ha confirmacao automatica de
 * pagamento - a equipe confirma visualmente que o Pix caiu antes de
 * marcar o atendimento como pago (mesmo fluxo manual ja usado pra
 * forma_pagamento "pix" no PDV).
 */
final class PixEstatico
{
    private const GUI_PIX = 'br.gov.bcb.pix';

    /**
     * @param string $chave Chave Pix do restaurante (CPF, CNPJ, e-mail, telefone ou chave aleatoria)
     * @param string $nomeBeneficiario Nome exibido no app do banco de quem paga (max 25 chars)
     * @param string $cidade Cidade do beneficiario (max 15 chars)
     * @param float|null $valor Valor fixo (null = pagador digita o valor no proprio banco)
     * @param string $txid Identificador da transacao (alfanumerico, max 25 chars) - uso interno/extrato, MUITOS apps de banco nao mostram isso com destaque pro pagador
     * @param string|null $descricao Descricao visivel ao pagador na hora de confirmar o Pix (campo 26/02 do BR Code - diferente do txid) - max 50 chars
     */
    public static function montarPayload(
        string $chave,
        string $nomeBeneficiario,
        string $cidade,
        ?float $valor,
        string $txid,
        ?string $descricao = null
    ): string {
        $merchantAccountInfo = self::campo('00', self::GUI_PIX) . self::campo('01', $chave);

        if ($descricao !== null && trim($descricao) !== '') {
            $merchantAccountInfo .= self::campo('02', self::normalizarTexto($descricao, 50));
        }

        $partes = self::campo('00', '01')
            . self::campo('26', $merchantAccountInfo)
            . self::campo('52', '0000')
            . self::campo('53', '986');

        if ($valor !== null && $valor > 0) {
            $partes .= self::campo('54', number_format($valor, 2, '.', ''));
        }

        $partes .= self::campo('58', 'BR')
            . self::campo('59', self::normalizarTexto($nomeBeneficiario, 25))
            . self::campo('60', self::normalizarTexto($cidade, 15))
            . self::campo('62', self::campo('05', self::normalizarTxid($txid)));

        // O CRC e calculado sobre a string ate aqui, JA incluindo o
        // cabecalho do proprio campo 63 ("6304") - so os 4 digitos do
        // checksum em si ficam de fora do calculo.
        $partes .= '6304';

        return $partes . self::crc16($partes);
    }

    /**
     * Formata ID (2 digitos) + LENGTH (2 digitos, tamanho em bytes do
     * valor) + VALUE - a unidade basica de todo o formato TLV do BR Code.
     */
    private static function campo(string $id, string $valor): string
    {
        return $id . str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }

    /**
     * Nomes e cidade no Pix estatico devem ser ASCII simples (sem
     * acentos) - a maioria dos apps de banco aceita acentos na pratica,
     * mas o manual do BC recomenda evitar por compatibilidade. Remove
     * acentuacao e trunca no tamanho maximo do campo.
     */
    private static function normalizarTexto(string $texto, int $tamanhoMaximo): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $semAcento = $semAcento !== false ? $semAcento : $texto;
        $limpo = preg_replace('/[^A-Za-z0-9 ]/', '', $semAcento) ?? '';
        $limpo = trim($limpo);

        return substr($limpo !== '' ? $limpo : 'RESTAURANTE', 0, $tamanhoMaximo);
    }

    private static function normalizarTxid(string $txid): string
    {
        $limpo = preg_replace('/[^A-Za-z0-9]/', '', $txid) ?? '';

        return substr($limpo !== '' ? $limpo : 'PAGAMENTO', 0, 25);
    }

    /**
     * CRC-16/CCITT-FALSE (polinomio 0x1021, inicial 0xFFFF, sem XorOut) -
     * o algoritmo exigido pelo manual do BR Code para o campo 63.
     */
    private static function crc16(string $dados): string
    {
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($dados); $i++) {
            $crc ^= (ord($dados[$i]) << 8);

            for ($bit = 0; $bit < 8; $bit++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
