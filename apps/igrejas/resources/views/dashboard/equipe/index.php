<?php

use Igrejas\Core\View;
use Igrejas\Models\User;

/**
 * @var array $config
 * @var array<int, User> $membrosEquipe
 */
$basePath = $config['base_path'] ?? '';

// Departamentos: mesma ordem de User::todosAtivosParaEquipe() (a
// consulta ja vem ordenada por cargo), so precisa agrupar aqui pra
// desenhar uma secao por departamento em vez de uma grade unica.
$departamentos = [
    User::CARGO_MUSICO => 'Músicos',
    User::CARGO_MIDIA => 'Mídia',
    User::CARGO_EQUIPAMENTO => 'Equipamento',
];
$porDepartamento = array_fill_keys(array_keys($departamentos), []);
foreach ($membrosEquipe as $pessoa) {
    $porDepartamento[$pessoa->cargo][] = $pessoa;
}
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/equipe.css?v=<?= View::assetVersion('assets/css/equipe.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Equipe</h1>
        <p class="dash-page-subtitle">
            <?= count($membrosEquipe) ?> pessoa<?= count($membrosEquipe) === 1 ? '' : 's' ?> com acesso ao sistema.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/perfil" class="btn-k btn-k-grad">
            <i class="bi bi-person-gear"></i> Editar meu perfil
        </a>
    </div>
</div>

<?php if ($membrosEquipe === []): ?>
    <div class="dash-panel">
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-people"></i></div>
            <h2>Nenhum usuário ativo ainda</h2>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($departamentos as $cargoSlug => $nomeDepartamento): ?>
        <?php if ($porDepartamento[$cargoSlug] === []) continue; ?>
        <div class="equipe-departamento">
            <h2 class="equipe-departamento-titulo">
                <?= htmlspecialchars($nomeDepartamento, ENT_QUOTES, 'UTF-8') ?>
                <span class="equipe-departamento-contagem"><?= count($porDepartamento[$cargoSlug]) ?></span>
            </h2>
            <div class="equipe-grade">
                <?php foreach ($porDepartamento[$cargoSlug] as $pessoa): ?>
                    <?php $cargoInfo = User::CARGOS[$pessoa->cargo] ?? User::CARGOS[User::CARGO_MEMBRO]; ?>
                    <a class="equipe-card" href="<?= $basePath ?>/dashboard/equipe/<?= $pessoa->id ?>">
                        <div class="equipe-card-foto">
                            <?php if ($pessoa->fotoPath !== null): ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($pessoa->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($pessoa->name, ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <span class="equipe-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($pessoa->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>

                            <span class="equipe-card-badge" title="<?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($pessoa->cargo === User::CARGO_MUSICO && $pessoa->instrumento !== null && isset(User::INSTRUMENTOS[$pessoa->instrumento])): ?>
                                    <?= User::INSTRUMENTOS[$pessoa->instrumento]['emoji'] ?>
                                <?php else: ?>
                                    <i class="bi <?= htmlspecialchars($cargoInfo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="equipe-card-nome"><?= htmlspecialchars($pessoa->name, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="equipe-card-cargo">
                            <?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($pessoa->cargo === User::CARGO_MUSICO && $pessoa->instrumento !== null && isset(User::INSTRUMENTOS[$pessoa->instrumento])): ?>
                                &middot; <?= htmlspecialchars(User::INSTRUMENTOS[$pessoa->instrumento]['label'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>

                        <span class="status-badge equipe-card-status is-ativo">Ativo</span>

                        <div class="equipe-card-info">
                            <div class="equipe-card-info-linha"><i class="bi bi-envelope"></i> <?= htmlspecialchars($pessoa->email, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
