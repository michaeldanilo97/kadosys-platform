<?php

declare(strict_types=1);

namespace Barbearias\Core;

/**
 * Envio de e-mails transacionais via mail() nativo do PHP - sem SDK/
 * biblioteca externa, seguindo o mesmo estilo "sem dependencias" do
 * restante do projeto. Suficiente para hospedagem cPanel, onde mail()
 * entrega via o MTA local (Exim) da propria conta. Mesmo padrao usado
 * em Igrejas\Core\Mailer.
 */
final class Mailer
{
    /**
     * @return bool true se o servidor aceitou a mensagem pra entrega
     *              (nao garante que o destinatario vai recebe-la de
     *              fato - so o proprio SMTP local ja confirma isso).
     */
    public static function enviar(string $paraEmail, string $paraNome, string $assunto, string $corpoHtml): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';

        if ($config['from_email'] === '') {
            return false;
        }

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::codificarCabecalho($config['from_name']) . ' <' . $config['from_email'] . '>',
            'Reply-To: ' . $config['from_email'],
            'X-Mailer: PHP/' . PHP_VERSION,
        ]);

        $destinatario = self::codificarCabecalho($paraNome) . ' <' . $paraEmail . '>';
        $assuntoCodificado = self::codificarCabecalho($assunto);

        return @mail($destinatario, $assuntoCodificado, $corpoHtml, $headers);
    }

    /**
     * Cabecalhos de e-mail (From, To, Subject) precisam ser codificados
     * em MIME encoded-word quando tem acentuacao - texto puro em UTF-8
     * direto no cabecalho quebra em varios clientes de e-mail.
     */
    private static function codificarCabecalho(string $texto): string
    {
        return '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }
}
