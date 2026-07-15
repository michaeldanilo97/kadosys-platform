<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Cliente;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;

/**
 * @var array $config
 * @var Agendamento|null $agendamento
 * @var array<int, Profissional> $profissionais
 * @var array<int, Servico> $servicos
 * @var array<int, Cliente> $clientes
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $agendamento !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/agendamentos/' . $agendamento->id : $basePath . '/dashboard/agendamentos';

$dataAtual = $old['data'] ?? ($agendamento !== null ? (new DateTimeImmutable($agendamento->dataHora))->format('Y-m-d') : '');
$horaAtual = $old['hora'] ?? ($agendamento !== null ? (new DateTimeImmutable($agendamento->dataHora))->format('H:i') : '');
$profissionalAtual = (int) ($old['profissional_id'] ?? $agendamento->profissionalId ?? 0);
$servicoAtual = (int) ($old['servico_id'] ?? $agendamento->servicoId ?? 0);
$clienteAtual = (int) ($old['cliente_id'] ?? $agendamento->clienteId ?? 0);
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Agenda</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar agendamento' : 'Novo agendamento' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/agendamentos" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($profissionais === [] || $servicos === [] || $clientes === []): ?>
        <div class="glass-card dash-panel">
            <p class="crud-empty" style="padding:1rem 0;">
                Antes de agendar, cadastre pelo menos um
                <?= $profissionais === [] ? '<a href="' . $basePath . '/dashboard/profissionais/novo">profissional</a>' : '' ?>
                <?= $servicos === [] ? ($profissionais === [] ? ', um ' : 'um ') . '<a href="' . $basePath . '/dashboard/servicos/novo">serviço</a>' : '' ?>
                <?= $clientes === [] ? (($profissionais === [] || $servicos === []) ? ' e um ' : 'um ') . '<a href="' . $basePath . '/dashboard/clientes/novo">cliente</a>' : '' ?>.
            </p>
        </div>
    <?php else: ?>
        <div class="glass-card dash-panel">
            <form method="POST" action="<?= $actionUrl ?>">
                <?= Csrf::field() ?>

                <div class="crud-form-grid">
                    <div class="form-field">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente->id ?>" <?= $clienteAtual === $cliente->id ? 'selected' : '' ?>><?= htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="profissional_id">Profissional</label>
                        <select id="profissional_id" name="profissional_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($profissionais as $profissional): ?>
                                <option value="<?= $profissional->id ?>" <?= $profissionalAtual === $profissional->id ? 'selected' : '' ?>><?= htmlspecialchars($profissional->nome, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field crud-field-full">
                        <label for="servico_id">Serviço</label>
                        <select id="servico_id" name="servico_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?= $servico->id ?>" <?= $servicoAtual === $servico->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($servico->nome, ENT_QUOTES, 'UTF-8') ?> - R$ <?= number_format($servico->preco, 2, ',', '.') ?> (<?= $servico->duracaoMinutos ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="data">Data</label>
                        <input type="date" id="data" name="data" value="<?= htmlspecialchars($dataAtual, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="hora">Hora</label>
                        <input type="time" id="hora" name="hora" value="<?= htmlspecialchars($horaAtual, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <?php if ($isEdit): ?>
                        <div class="form-field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="agendado" <?= $agendamento->status === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                                <option value="concluido" <?= $agendamento->status === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                <option value="cancelado" <?= $agendamento->status === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="form-field crud-field-full">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3" placeholder="Opcional"><?= htmlspecialchars($old['observacoes'] ?? $agendamento->observacoes ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="crud-form-actions">
                    <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Agendar' ?></button>
                    <a href="<?= $basePath ?>/dashboard/agendamentos" class="btn-k btn-k-outline">Cancelar</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>
