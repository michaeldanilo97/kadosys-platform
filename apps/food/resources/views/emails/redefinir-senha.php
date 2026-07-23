<?php

/**
 * @var string $nomeUsuario
 * @var string $link
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperação de senha - KADOSYS Food</title>
</head>
<body style="margin:0;padding:0;background:#0F172A;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0F172A;padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="100%" style="max-width:480px;background:#111827;border-radius:16px;overflow:hidden;">
<tr>
<td style="background:linear-gradient(135deg,#3B82F6,#8B5CF6);padding:28px 32px;">
<span style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#ffffff;">KADOSYS Food</span>
</td>
</tr>
<tr>
<td style="padding:32px;color:#E5E7EB;">
<h1 style="margin:0 0 16px;font-size:20px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
Olá, <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>
</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
Recebemos um pedido pra redefinir a senha da sua conta. Clique no botão abaixo pra escolher uma senha nova - o link
vale por 1 hora.
</p>
<p style="text-align:center;margin:0 0 24px;">
<a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;background:#3B82F6;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 28px;border-radius:999px;font-size:15px;font-family:Arial,Helvetica,sans-serif;">
Redefinir minha senha
</a>
</p>
<p style="margin:0;font-size:13px;color:#9CA3AF;font-family:Arial,Helvetica,sans-serif;">
Se você não pediu essa recuperação, pode ignorar este e-mail - sua senha continua a mesma.
</p>
</td>
</tr>
<tr>
<td style="padding:20px 32px;border-top:1px solid #1F2937;">
<p style="margin:0;font-size:12px;color:#6B7280;font-family:Arial,Helvetica,sans-serif;">
Dúvidas? Responda este e-mail ou fale com a gente em contato@kadosys.com.br.
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
