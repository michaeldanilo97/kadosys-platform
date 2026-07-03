<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuracao de E-mail - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Usada pelo envio de e-mails transacionais (ver Igrejas\Core\Mailer),
| hoje limitado ao e-mail de boas-vindas disparado ao final do
| provisionamento automatico de uma nova igreja (ver Provisionador).
|
| O envio usa a funcao mail() nativa do PHP, que no cPanel entrega via o
| MTA local (Exim) da propria conta - funciona sem credenciais desde que
| o endereco do remetente seja uma caixa de fato existente no dominio
| hospedado nesta conta (senao cai em spam com mais facilidade por falha
| de alinhamento SPF/DKIM).
|
| Diferente de mercadopago.php/cpanel.php, aqui o valor padrao ja vem
| preenchido (nao e credencial sensivel, so um endereco de remetente) -
| mas pode ser sobrescrito por variavel de ambiente ou por
| "config/mail.local.php" (mesmo esquema, fora do Git) se um dia for
| preciso trocar.
*/

$local = __DIR__ . '/mail.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'from_email' => $overrides['from_email'] ?? (getenv('MAIL_FROM_EMAIL') ?: 'igrejas@kadosys.com.br'),
    'from_name' => $overrides['from_name'] ?? (getenv('MAIL_FROM_NAME') ?: 'KADOSYS Igrejas'),
];
