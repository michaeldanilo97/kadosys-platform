<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \DateTime $mesReferencia
 * @var string $mesAnterior
 * @var string $mesProximo
 * @var array<int, \Igrejas\Models\Culto> $cultos
 * @var array<int, \Igrejas\Models\AgendaEvento> $eventos
 * @var array<int, \Igrejas\Models\Membro> $aniversariantes
 * @var string|null $success
 * @var \Igrejas\Models\User $user
 */
$basePath = $config['base_path'] ?? '';

$diasDaSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$mesesPt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$ano = (int) $mesReferencia->format('Y');
$mesNum = (int) $mesReferencia->format('n');
$diasNoMes = (int) $mesReferencia->format('t');
$primeiroDiaSemana = (int) (new DateTime($mesReferencia->format('Y-m-01')))->format('w');
$hoje = new DateTime('today');

$cultosPorDia = [];
foreach ($cultos as $culto) {
    $dia = (int) substr($culto->data, 8, 2);
    $cultosPorDia[$dia][] = $culto;
}

$eventosPorDia = [];
foreach ($eventos as $evento) {
    $dia = (int) substr($evento->data, 8, 2);
    $eventosPorDia[$dia][] = $evento;
}

$aniversariantesPorDia = [];
foreach ($aniversariantes as $membro) {
    $dia = (int) substr($membro->dataNascimento, 8, 2);
    $aniversariantesPorDia[$dia][] = $membro;
}
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/agenda-calendario.css?v=<?= View::assetVersion('assets/css/agenda-calendario.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Agenda</h1>
        <p class="dash-page-subtitle">Cultos, eventos e aniversariantes num só calendário.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/agenda/novo" class="btn-k btn-k-grad">
            <i class="bi bi-calendar-plus"></i> Novo evento
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="agenda-tabs">
    <a href="<?= $basePath ?>/dashboard/agenda" class="agenda-tab is-active"><i class="bi bi-calendar3"></i> Calendário</a>
    <a href="<?= $basePath ?>/dashboard/agenda/lista" class="agenda-tab"><i class="bi bi-list-ul"></i> Lista</a>
</div>

<div class="dash-panel">
    <div class="agenda-cal-nav">
        <a href="<?= $basePath ?>/dashboard/agenda?mes=<?= $mesAnterior ?>" class="crud-icon-btn" aria-label="Mês anterior">
            <i class="bi bi-chevron-left"></i>
        </a>
        <h2><?= $mesesPt[$mesNum] ?> de <?= $ano ?></h2>
        <a href="<?= $basePath ?>/dashboard/agenda?mes=<?= $mesProximo ?>" class="crud-icon-btn" aria-label="Próximo mês">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="agenda-cal-grid">
        <?php foreach ($diasDaSemana as $nomeDia): ?>
            <div class="agenda-cal-weekday"><?= $nomeDia ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $primeiroDiaSemana; $i++): ?>
            <div class="agenda-cal-day is-outside"></div>
        <?php endfor; ?>

        <?php for ($dia = 1; $dia <= $diasNoMes; $dia++):
            $dataCelula = new DateTime(sprintf('%04d-%02d-%02d', $ano, $mesNum, $dia));
            $ehHoje = $dataCelula->format('Y-m-d') === $hoje->format('Y-m-d');
        ?>
            <div class="agenda-cal-day <?= $ehHoje ? 'is-today' : '' ?>">
                <span class="agenda-cal-day-num"><?= $dia ?></span>
                <div class="agenda-cal-items">
                    <?php foreach ($cultosPorDia[$dia] ?? [] as $culto): ?>
                        <a
                            href="<?= $basePath ?>/dashboard/cultos/<?= $culto->id ?>/editar"
                            class="agenda-cal-item tipo-culto"
                            title="Culto: <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?>"
                        ><i class="bi bi-calendar2-week"></i> <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>

                    <?php foreach ($eventosPorDia[$dia] ?? [] as $evento): ?>
                        <a
                            href="<?= $basePath ?>/dashboard/agenda/<?= $evento->id ?>/editar"
                            class="agenda-cal-item <?= $evento->ehPrivado() ? 'tipo-privado' : 'tipo-publico' ?>"
                            title="<?= $evento->ehPrivado() ? 'Só você vê: ' : '' ?><?= htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') ?>"
                        ><i class="bi <?= $evento->ehPrivado() ? 'bi-lock-fill' : 'bi-calendar3' ?>"></i> <?= htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>

                    <?php foreach ($aniversariantesPorDia[$dia] ?? [] as $membro): ?>
                        <a
                            href="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>/editar"
                            class="agenda-cal-item tipo-aniversario"
                            title="Aniversário: <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>"
                        ><i class="bi bi-gift-fill"></i> <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endfor; ?>

        <?php
        $totalCelulas = $primeiroDiaSemana + $diasNoMes;
        $sobra = (7 - ($totalCelulas % 7)) % 7;
        ?>
        <?php for ($i = 0; $i < $sobra; $i++): ?>
            <div class="agenda-cal-day is-outside"></div>
        <?php endfor; ?>
    </div>

    <div class="agenda-cal-legenda">
        <span><i style="background: rgba(59, 130, 246, 0.85);"></i> Culto</span>
        <span><i style="background: rgba(139, 92, 246, 0.85);"></i> Evento (todo mundo vê)</span>
        <span><i style="background: rgba(107, 114, 128, 0.85);"></i> Só você vê</span>
        <span><i style="background: rgba(251, 191, 36, 0.9);"></i> Aniversariante</span>
    </div>
</div>
