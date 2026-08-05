<?php

use Academias\Core\Documento;

/**
 * @var array $config
 * @var array<int, array{aluno: \Academias\Models\Aluno, ultimoCheckin: ?string, diasSemVir: int}> $alunosRisco
 */
$basePath = $config['base_path'] ?? '';

function risco_evasao_whatsapp(?string $telefone): ?string
{
    if ($telefone === null) {
        return null;
    }

    $digitos = Documento::apenasDigitos($telefone);

    if ($digitos === '') {
        return null;
    }

    if (mb_strlen($digitos) <= 11) {
        $digitos = '55' . $digitos;
    }

    return 'https://wa.me/' . $digitos;
}
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Retenção</p>
            <h1 class="dashboard-title">Risco de evasão</h1>
            <p class="dash-page-subtitle">
                <?= count($alunosRisco) ?> aluno<?= count($alunosRisco) === 1 ? '' : 's' ?> ativo<?= count($alunosRisco) === 1 ? '' : 's' ?> sem aparecer há 7 dias ou mais
            </p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <?php if ($alunosRisco === []): ?>
            <p class="crud-empty">Ninguém em risco no momento - todos os alunos ativos apareceram nos últimos dias. 🎉</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Último check-in</th>
                            <th>Sumido há</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunosRisco as $linha): ?>
                            <?php $aluno = $linha['aluno']; $whats = risco_evasao_whatsapp($aluno->telefone); ?>
                            <tr>
                                <td><?= htmlspecialchars($aluno->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($aluno->telefone ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">
                                    <?= $linha['ultimoCheckin'] ? (new DateTimeImmutable($linha['ultimoCheckin']))->format('d/m/Y') : 'Nunca fez check-in' ?>
                                </td>
                                <td><span class="status-badge danger"><?= $linha['diasSemVir'] ?> dias</span></td>
                                <td class="actions-col">
                                    <?php if ($whats !== null): ?>
                                        <a href="<?= htmlspecialchars($whats, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="crud-icon-btn" title="Chamar no WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                    <?php endif; ?>
                                    <a href="<?= $basePath ?>/dashboard/alunos/<?= $aluno->id ?>/editar" class="crud-icon-btn" title="Ver cadastro"><i class="bi bi-pencil-fill"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
