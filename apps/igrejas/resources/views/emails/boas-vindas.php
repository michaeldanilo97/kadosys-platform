<?php

use Igrejas\Models\Plano;

/**
 * @var string $nomeAdmin
 * @var string $nomeIgreja
 * @var string $urlAcesso
 * @var string $plano
 */
$planoLabel = Plano::label($plano);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bem-vindo ao KADOSYS Igrejas</title>
</head>
<body style="margin:0;padding:0;background:#0b1120;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b1120;padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="100%" style="max-width:480px;background:#111827;border-radius:16px;overflow:hidden;">
<tr>
<td style="background:linear-gradient(135deg,#3B82F6,#8B5CF6);padding:28px 32px;">
<span style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#ffffff;">KADOSYS Igrejas</span>
</td>
</tr>
<tr>
<td style="padding:32px;color:#E5E7EB;">
<h1 style="margin:0 0 16px;font-size:20px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
Bem-vindo(a), <?= htmlspecialchars($nomeAdmin, ENT_QUOTES, 'UTF-8') ?>!
</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
A conta da <strong><?= htmlspecialchars($nomeIgreja, ENT_QUOTES, 'UTF-8') ?></strong> no KADOSYS Igrejas foi criada
com sucesso, no plano <strong><?= htmlspecialchars($planoLabel, ENT_QUOTES, 'UTF-8') ?></strong>.
</p>
<p style="margin:0 0 24px;font-size:15px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
Acesse o painel administrativo com o e-mail e a senha que você cadastrou:
</p>
<p style="text-align:center;margin:0 0 24px;">
<a href="<?= htmlspecialchars($urlAcesso, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;background:#3B82F6;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 28px;border-radius:999px;font-size:15px;font-family:Arial,Helvetica,sans-serif;">
Acessar meu painel
</a>
</p>
<p style="margin:0;font-size:13px;color:#9CA3AF;font-family:Arial,Helvetica,sans-serif;">
Endereço do seu painel:
<a href="<?= htmlspecialchars($urlAcesso, ENT_QUOTES, 'UTF-8') ?>" style="color:#60A5FA;"><?= htmlspecialchars($urlAcesso, ENT_QUOTES, 'UTF-8') ?></a>
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
