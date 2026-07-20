<?php

/**
 * @var string $clienteNome
 * @var string $barbeariaNome
 * @var string $profissionalNome
 * @var string $servicoNome
 * @var string $dataFormatada
 * @var string $horaFormatada
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lembrete de agendamento - <?= htmlspecialchars($barbeariaNome, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body style="margin:0;padding:0;background:#0F172A;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0F172A;padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="100%" style="max-width:480px;background:#111827;border-radius:16px;overflow:hidden;">
<tr>
<td style="background:linear-gradient(135deg,#3B82F6,#8B5CF6);padding:28px 32px;">
<span style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#ffffff;"><?= htmlspecialchars($barbeariaNome, ENT_QUOTES, 'UTF-8') ?></span>
</td>
</tr>
<tr>
<td style="padding:32px;color:#E5E7EB;">
<h1 style="margin:0 0 16px;font-size:20px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
Olá, <?= htmlspecialchars($clienteNome, ENT_QUOTES, 'UTF-8') ?>
</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
Passando pra lembrar do seu horário amanhã, <strong><?= htmlspecialchars($dataFormatada, ENT_QUOTES, 'UTF-8') ?></strong>
às <strong><?= htmlspecialchars($horaFormatada, ENT_QUOTES, 'UTF-8') ?></strong>, com
<strong><?= htmlspecialchars($profissionalNome, ENT_QUOTES, 'UTF-8') ?></strong>
(<?= htmlspecialchars($servicoNome, ENT_QUOTES, 'UTF-8') ?>).
</p>
<p style="margin:0;font-size:13px;color:#9CA3AF;font-family:Arial,Helvetica,sans-serif;">
Precisa remarcar ou cancelar? Entre em contato direto com a <?= htmlspecialchars($barbeariaNome, ENT_QUOTES, 'UTF-8') ?>.
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
