<?php

use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\FilaAtendimento;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var Cliente $cliente
 * @var array<int, Agendamento> $proximos
 * @var array<int, Agendamento> $historico
 * @var array<int, FilaAtendimento> $historicoFila
 * @var array<int, int> $avaliados
 * @var string $csrf
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8');

$statusLabel = [
    Agendamento::STATUS_AGENDADO => 'Agendado',
    Agendamento::STATUS_CONCLUIDO => 'Concluído',
    Agendamento::STATUS_CANCELADO => 'Cancelado',
];
?>
<div class="cadastro-shell" style="max-width: 760px;">
    <div class="dash-page-head" style="margin-bottom: 1.5rem;">
        <div>
            <p class="hero-eyebrow" style="margin-bottom: 0.5rem;">Olá, <?= htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') ?></p>
            <h1 style="margin: 0;"><?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/sair">
            <?= $csrf ?>
            <button type="submit" class="btn-logout">Sair</button>
        </form>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($barbearia->fidelidadePontosPorReal !== null): ?>
        <div class="glass-card cadastro-card" style="text-align:center;">
            <p class="form-field-hint" style="margin: 0 0 0.35rem;">Seus pontos de fidelidade</p>
            <p style="margin:0; font-size:1.8rem; font-weight:800;"><?= $cliente->pontosFidelidade ?></p>
        </div>
    <?php endif; ?>

    <?php if ($barbearia->usaFila()): ?>
        <div class="glass-card cadastro-card">
            <div class="dash-panel-head" style="margin-bottom: 1rem;">
                <h2 style="margin:0; font-size:1.1rem;">Minhas passagens na fila</h2>
                <a href="<?= $basePath ?>/fila/<?= $slug ?>" class="btn-k btn-k-grad btn-k-sm">+ Entrar na fila</a>
            </div>

            <?php if ($historicoFila === []): ?>
                <p class="crud-empty" style="padding: 1rem 0;">Nenhum atendimento pela fila ainda.</p>
            <?php else: ?>
                <?php foreach ($historicoFila as $item): ?>
                    <div class="confirmacao-detalhes" style="margin: 0 0 1rem;">
                        <div><span>Data</span><span><?= (new DateTimeImmutable($item->atendidoEm ?? $item->entrouEm))->format('d/m/Y H:i') ?></span></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="glass-card cadastro-card">
        <div class="dash-panel-head" style="margin-bottom: 1rem;">
            <h2 style="margin:0; font-size:1.1rem;">Próximos agendamentos</h2>
            <a href="<?= $basePath ?>/agendar/<?= $slug ?>" class="btn-k btn-k-grad btn-k-sm">+ Agendar</a>
        </div>

        <?php if ($proximos === []): ?>
            <p class="crud-empty" style="padding: 1rem 0;">Nenhum agendamento futuro.</p>
        <?php else: ?>
            <?php foreach ($proximos as $agendamento): ?>
                <div class="confirmacao-detalhes" style="margin: 0 0 0.75rem;">
                    <div><span>Data</span><span><?= (new DateTimeImmutable($agendamento->dataHora))->format('d/m/Y H:i') ?></span></div>
                    <div><span>Profissional</span><span><?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div><span>Serviço</span><span><?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?></span></div>
                </div>
                <div class="form-field-row" style="margin: 0 0 1.5rem; gap: 0.5rem;">
                    <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>/agendamentos/<?= $agendamento->id ?>/reagendar" class="btn-k btn-k-outline btn-k-sm">Reagendar</a>
                    <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/agendamentos/<?= $agendamento->id ?>/cancelar" onsubmit="return confirm('Cancelar este agendamento?');">
                        <?= $csrf ?>
                        <button type="submit" class="btn-k btn-k-outline btn-k-sm">Cancelar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="glass-card cadastro-card">
        <h2 style="margin:0 0 1rem; font-size:1.1rem;">Meus atendimentos</h2>

        <?php if ($historico === []): ?>
            <p class="crud-empty" style="padding: 1rem 0;">Nenhum atendimento ainda.</p>
        <?php else: ?>
            <?php foreach ($historico as $agendamento): ?>
                <div class="confirmacao-detalhes" style="margin: 0 0 1rem;">
                    <div><span>Data</span><span><?= (new DateTimeImmutable($agendamento->dataHora))->format('d/m/Y H:i') ?></span></div>
                    <div><span>Profissional</span><span><?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div><span>Serviço</span><span><?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div><span>Status</span><span><?= $statusLabel[$agendamento->status] ?? $agendamento->status ?></span></div>
                </div>

                <?php if ($agendamento->status === Agendamento::STATUS_CONCLUIDO): ?>
                    <?php if (isset($avaliados[$agendamento->id])): ?>
                        <p class="form-field-hint" style="margin: -0.5rem 0 1.5rem;">✓ Você já avaliou esse atendimento.</p>
                    <?php else: ?>
                        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/avaliacoes/<?= $agendamento->id ?>" style="margin: -0.5rem 0 1.5rem;">
                            <?= $csrf ?>
                            <div class="form-field-row">
                                <div class="form-field">
                                    <label for="nota-<?= $agendamento->id ?>">Como foi o atendimento?</label>
                                    <select id="nota-<?= $agendamento->id ?>" name="nota" required>
                                        <option value="">Escolha uma nota</option>
                                        <option value="5">★★★★★ (5)</option>
                                        <option value="4">★★★★ (4)</option>
                                        <option value="3">★★★ (3)</option>
                                        <option value="2">★★ (2)</option>
                                        <option value="1">★ (1)</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="comentario-<?= $agendamento->id ?>">Comentário (opcional)</label>
                                    <input type="text" id="comentario-<?= $agendamento->id ?>" name="comentario" placeholder="Conte como foi...">
                                </div>
                            </div>
                            <button type="submit" class="btn-k btn-k-outline btn-k-sm">Avaliar</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
