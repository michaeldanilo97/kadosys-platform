<?php

use Barbearias\Core\View;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Unidade;

/**
 * Painel de recepcao: tela em tela cheia (sem sidebar), pensada pra
 * ficar aberta numa TV/tablet do salao. Sem JS de polling - so um
 * meta refresh simples, que basta pra manter a fila atualizada sem
 * exigir nada especial do navegador/aparelho usado na recepcao.
 *
 * @var array $config
 * @var Barbearia $barbearia
 * @var array<int, Agendamento> $agendamentos
 * @var array<int, Unidade> $unidades
 * @var int $unidadeId
 */
$basePath = $config['base_path'] ?? '';
$agora = new DateTimeImmutable();

$statusInfo = static function (Agendamento $agendamento) use ($agora): array {
    if ($agendamento->status === Agendamento::STATUS_CONCLUIDO) {
        return ['label' => 'Concluído', 'classe' => 'ok'];
    }

    if (new DateTimeImmutable($agendamento->dataHora) < $agora) {
        return ['label' => 'Atrasado', 'classe' => 'danger'];
    }

    return ['label' => 'Aguardando', 'classe' => 'dim'];
};

$proximoId = null;

foreach ($agendamentos as $agendamento) {
    if ($agendamento->status === Agendamento::STATUS_AGENDADO && new DateTimeImmutable($agendamento->dataHora) >= $agora) {
        $proximoId = $agendamento->id;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title><?= htmlspecialchars($pageTitle ?? 'Recepção - KADOSYS Barbearias', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
    <script>
        document.documentElement.setAttribute('data-theme', 'dark');
    </script>
</head>
<body class="recepcao-body">
<main class="recepcao-shell">
    <header class="recepcao-header">
        <div>
            <p class="recepcao-eyebrow">Fila do dia</p>
            <h1><?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <div class="recepcao-relogio" data-relogio><?= $agora->format('H:i') ?></div>
    </header>

    <?php if ($unidades !== []): ?>
        <nav class="recepcao-unidades">
            <a href="<?= $basePath ?>/dashboard/recepcao" class="<?= $unidadeId === 0 ? 'ativo' : '' ?>">Todas as unidades</a>
            <?php foreach ($unidades as $unidade): ?>
                <a href="<?= $basePath ?>/dashboard/recepcao?unidade_id=<?= $unidade->id ?>" class="<?= $unidadeId === $unidade->id ? 'ativo' : '' ?>">
                    <?= htmlspecialchars($unidade->nome, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($agendamentos === []): ?>
        <p class="recepcao-vazio">Nenhum atendimento agendado pra hoje.</p>
    <?php else: ?>
        <div class="recepcao-lista">
            <?php foreach ($agendamentos as $agendamento): ?>
                <?php $status = $statusInfo($agendamento); ?>
                <div class="recepcao-linha<?= $agendamento->id === $proximoId ? ' proximo' : '' ?>">
                    <span class="recepcao-hora"><?= (new DateTimeImmutable($agendamento->dataHora))->format('H:i') ?></span>
                    <span class="recepcao-cliente"><?= htmlspecialchars($agendamento->clienteNome, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="recepcao-detalhe">
                        <?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?>
                        · <?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="status-badge <?= $status['classe'] ?>"><?= $status['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script>
    // So o relogio no topo atualiza sozinho no navegador - o resto da
    // pagina (fila) so muda de verdade quando o meta refresh recarrega.
    setInterval(function () {
        var el = document.querySelector('[data-relogio]');
        if (!el) {
            return;
        }
        var agora = new Date();
        var horas = String(agora.getHours()).padStart(2, '0');
        var minutos = String(agora.getMinutes()).padStart(2, '0');
        el.textContent = horas + ':' + minutos;
    }, 1000);
</script>
</body>
</html>
