<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuracao de E-mail - KADOSYS Academias
|--------------------------------------------------------------------------
|
| Usada pelo envio de e-mails transacionais (ver Academias\Core\Mailer):
| recuperacao de senha e lembrete automatico de agendamento.
|
| O envio usa a funcao mail() nativa do PHP, que no cPanel entrega via o
| MTA local (Exim) da propria conta - funciona sem credenciais desde que
| o endereco do remetente seja uma caixa de fato existente no dominio
| hospedado nesta conta (senao cai em spam com mais facilidade por falha
| de alinhamento SPF/DKIM). Mesmo mecanismo ja usado no KADOSYS Igrejas
| (ver Igrejas\Core\Mailer) - sem custo, sem SDK/API externa.
|
| Pode ser sobrescrito por variavel de ambiente ou por
| "config/mail.local.php" (mesmo esquema do database.php, fora do Git).
*/

$local = __DIR__ . '/mail.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'from_email' => $overrides['from_email'] ?? (getenv('MAIL_FROM_EMAIL') ?: 'academias@kadosys.com.br'),
    'from_name' => $overrides['from_name'] ?? (getenv('MAIL_FROM_NAME') ?: 'KADOSYS Academias'),
];
