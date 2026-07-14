<?php

use Igrejas\Models\User;

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Membro> $membros
 * @var array<int, array{cargo: string, instrumento: ?string, fotoPath: ?string}> $acessoPorMembroId
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Membros</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> membro<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/membros/novo" class="btn-k btn-k-grad">
            <i class="bi bi-person-plus"></i> Novo membro
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/membros" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome, e-mail ou cargo..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/membros" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($membros === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-people"></i></div>
            <h2><?= $search !== '' ? 'Nenhum membro encontrado' : 'Nenhum membro cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro nome, e-mail ou cargo.'
                    : 'Comece cadastrando o primeiro membro da igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/membros/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-person-plus"></i> Cadastrar membro
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="membro-grade">
            <?php foreach ($membros as $membro): ?>
                <?php
                $acesso = $acessoPorMembroId[$membro->id] ?? null;
                $temCargo = $acesso !== null && $acesso['cargo'] !== User::CARGO_MEMBRO;
                $cargoInfo = $temCargo ? (User::CARGOS[$acesso['cargo']] ?? null) : null;
                ?>
                <div class="membro-card">
                    <a href="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>" class="membro-card-link">
                        <div class="membro-card-foto">
                            <?php if ($acesso !== null && $acesso['fotoPath'] !== null): ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($acesso['fotoPath'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <span class="membro-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($membro->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>

                            <?php if ($cargoInfo !== null): ?>
                                <span class="membro-card-badge" title="<?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($acesso['cargo'] === User::CARGO_MUSICO && $acesso['instrumento'] !== null && isset(User::INSTRUMENTOS[$acesso['instrumento']])): ?>
                                        <?= User::INSTRUMENTOS[$acesso['instrumento']]['emoji'] ?>
                                    <?php else: ?>
                                        <i class="bi <?= htmlspecialchars($cargoInfo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="membro-card-nome"><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($cargoInfo !== null): ?>
                            <div class="membro-card-cargo">
                                <?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($acesso['cargo'] === User::CARGO_MUSICO && $acesso['instrumento'] !== null && isset(User::INSTRUMENTOS[$acesso['instrumento']])): ?>
                                    &middot; <?= htmlspecialchars(User::INSTRUMENTOS[$acesso['instrumento']]['label'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>

                    <span class="status-badge membro-card-status <?= $membro->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                        <?= $membro->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                    </span>

                    <div class="membro-card-info">
                        <?php if ($membro->email): ?>
                            <div class="membro-card-info-linha"><i class="bi bi-envelope"></i> <?= htmlspecialchars($membro->email, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($membro->telefone): ?>
                            <div class="membro-card-info-linha"><i class="bi bi-telephone"></i> <?= htmlspecialchars($membro->telefone, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="membro-card-info-linha">
                            <i class="bi bi-calendar-check"></i>
                            <?= $membro->dataMembresia
                                ? 'Desde ' . (new DateTimeImmutable($membro->dataMembresia))->format('d/m/Y')
                                : 'Data de entrada não informada' ?>
                            <?= $membro->idade() !== null ? ' &middot; ' . $membro->idade() . ' anos' : '' ?>
                        </div>
                    </div>

                    <div class="membro-card-acoes">
                        <a
                            href="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>"
                            class="crud-icon-btn"
                            aria-label="Ver perfil de <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <i class="bi bi-person-lines-fill"></i>
                        </a>
                        <form
                            method="POST"
                            action="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>/excluir"
                            data-confirm="Remover &quot;<?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>&quot; da lista de membros?"
                        >
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <button
                                type="submit"
                                class="crud-icon-btn danger"
                                aria-label="Excluir <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($lastPage > 1): ?>
            <div class="crud-pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <a
                        href="<?= $basePath ?>/dashboard/membros?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
