<?php

use Igrejas\Core\View;
use Igrejas\Models\User;

/**
 * @var array $config
 * @var array<int, User> $membrosEquipe
 * @var string|null $logoIgreja
 */
$basePath = $config['base_path'] ?? '';
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
    <div class="equipe-grade">
        <?php foreach ($membrosEquipe as $pessoa): ?>
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
                        <?php elseif ($pessoa->cargo === User::CARGO_MEMBRO && $logoIgreja !== null): ?>
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($logoIgreja, ENT_QUOTES, 'UTF-8') ?>" alt="">
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
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
