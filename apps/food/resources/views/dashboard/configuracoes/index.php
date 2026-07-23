<?php

use Food\Core\Csrf;
use Food\Models\Impressora;
use Food\Models\Restaurante;
use Food\Models\User;

/**
 * @var array $config
 * @var Restaurante $restaurante
 * @var string $planoLabel
 * @var array<int, User> $equipe
 * @var array<int, Impressora> $impressoras
 * @var array<int, string> $perfilErrors
 * @var string|null $perfilSuccess
 * @var array<int, string> $fiscalErrors
 * @var string|null $fiscalSuccess
 * @var array<int, string> $equipeErrors
 * @var array $equipeOld
 * @var string|null $equipeSuccess
 * @var array<int, string> $impressoraErrors
 * @var User $user
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Conta</p>
            <h1 class="dashboard-title">Configurações</h1>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Plano e cobrança</h2>
        </div>
        <p style="color: var(--gray-400); margin: 0 0 1rem;">
            Plano atual: <strong style="color: var(--text);"><?= htmlspecialchars($planoLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <div class="crud-form-actions" style="margin-top: 0;">
            <a href="<?= $basePath ?>/dashboard/assinatura" class="btn-k btn-k-outline">Gerenciar plano e pagamento</a>
            <a href="<?= $basePath ?>/dashboard/faturas" class="btn-k btn-k-outline">Ver faturas</a>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Dados do restaurante</h2>
        </div>

        <?php if ($perfilSuccess): ?>
            <div class="form-alert form-alert-success">
                <div><?= htmlspecialchars($perfilSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($perfilErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($perfilErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/perfil" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="nome">Nome do restaurante</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($restaurante->nome, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($restaurante->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="cor_primaria">Cor de destaque</label>
                    <input type="color" id="cor_primaria" name="cor_primaria" value="<?= htmlspecialchars($restaurante->corPrimaria ?? '#F97316', ENT_QUOTES, 'UTF-8') ?>" style="height: 2.7rem; padding: 0.25rem;">
                    <span class="form-field-hint">Usada no painel e nos comprovantes.</span>
                </div>
                <div class="form-field">
                    <label for="logo">Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                    <span class="form-field-hint">PNG, JPG ou WEBP - até 5MB.</span>
                    <?php if ($restaurante->logoPath): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($restaurante->logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo atual" class="foto-preview">
                    <?php endif; ?>
                </div>
            </div>
            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Salvar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Dados fiscais</h2>
            <p>Aparecem em relatórios internos - não emite nota fiscal (NF-e/NFC-e).</p>
        </div>

        <?php if ($fiscalSuccess): ?>
            <div class="form-alert form-alert-success">
                <div><?= htmlspecialchars($fiscalSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($fiscalErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($fiscalErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/fiscal" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="documento_tipo">Tipo de documento</label>
                <select id="documento_tipo" name="documento_tipo">
                    <option value="cpf" <?= $restaurante->documentoTipo === 'cpf' ? 'selected' : '' ?>>CPF</option>
                    <option value="cnpj" <?= $restaurante->documentoTipo === 'cnpj' ? 'selected' : '' ?>>CNPJ</option>
                </select>
            </div>
            <div class="form-field">
                <label for="documento">CPF/CNPJ</label>
                <input type="text" id="documento" name="documento" value="<?= htmlspecialchars($restaurante->documento, ENT_QUOTES, 'UTF-8') ?>" placeholder="Só números">
            </div>
            <div class="form-field">
                <label for="razao_social">Razão social</label>
                <input type="text" id="razao_social" name="razao_social" value="<?= htmlspecialchars($restaurante->razaoSocial ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome fantasia ou razão social">
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Salvar dados fiscais</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Pix do restaurante</h2>
        </div>
        <p style="color: var(--gray-400); margin: 0 0 1rem;">
            Cadastre sua própria chave Pix pra gerar um QR Code no PDV - o dinheiro cai direto na sua conta, sem
            passar pelo KADOSYS.
        </p>
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/pix" class="crud-form-grid">
            <div class="form-field">
                <label for="pix_chave">Chave Pix</label>
                <input type="text" id="pix_chave" name="pix_chave" value="<?= htmlspecialchars($restaurante->pixChave ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="CPF, CNPJ, e-mail, telefone ou chave aleatória">
            </div>
            <div class="form-field">
                <label for="pix_nome_beneficiario">Nome do beneficiário</label>
                <input type="text" id="pix_nome_beneficiario" name="pix_nome_beneficiario" value="<?= htmlspecialchars($restaurante->pixNomeBeneficiario ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="25" placeholder="Como aparece no app do banco">
            </div>
            <div class="form-field">
                <label for="pix_cidade">Cidade</label>
                <input type="text" id="pix_cidade" name="pix_cidade" value="<?= htmlspecialchars($restaurante->pixCidade ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="15" placeholder="Sua cidade">
            </div>
            <?= Csrf::field() ?>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Salvar chave Pix</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Impressoras</h2>
            <p>Cadastro informativo (nome/IP de rede) - sem impressão automática, o comprovante do PDV sai pela impressão do navegador.</p>
        </div>

        <?php if ($impressoraErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($impressoraErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($impressoras !== []): ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>IP</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impressoras as $impressora): ?>
                            <tr>
                                <td><?= htmlspecialchars($impressora->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($impressora->ip ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/impressoras/<?= $impressora->id ?>/excluir" onsubmit="return confirm('Remover a impressora <?= htmlspecialchars(addslashes($impressora->nome), ENT_QUOTES, 'UTF-8') ?>?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 0.95rem; margin: 1.75rem 0 1rem;">Adicionar impressora</h3>
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/impressoras" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="imp_nome">Nome</label>
                <input type="text" id="imp_nome" name="nome" placeholder="Ex.: Balcão, Cozinha" required>
            </div>
            <div class="form-field">
                <label for="imp_ip">IP de rede</label>
                <input type="text" id="imp_ip" name="ip" placeholder="Ex.: 192.168.0.50">
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Adicionar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Equipe</h2>
            <p>Quem tem acesso ao painel do restaurante.</p>
        </div>

        <?php if ($equipeSuccess): ?>
            <div class="form-alert form-alert-success">
                <div><?= htmlspecialchars($equipeSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($equipeErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($equipeErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Papel</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipe as $membro): ?>
                        <tr>
                            <td><?= htmlspecialchars($membro->name, ENT_QUOTES, 'UTF-8') ?><?= $membro->id === $user->id ? ' <span class="text-dim">(você)</span>' : '' ?></td>
                            <td class="text-dim"><?= htmlspecialchars($membro->email, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="status-badge info"><?= $membro->role === User::ROLE_ADMIN ? 'Admin' : 'Equipe' ?></span></td>
                            <td><span class="status-badge <?= $membro->active ? 'ok' : 'dim' ?>"><?= $membro->active ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="actions-col">
                                <a href="<?= $basePath ?>/dashboard/configuracoes/equipe/<?= $membro->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                <?php if ($membro->id !== $user->id): ?>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/equipe/<?= $membro->id ?>/excluir" onsubmit="return confirm('Remover o acesso de <?= htmlspecialchars(addslashes($membro->name), ENT_QUOTES, 'UTF-8') ?>?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover">🗑️</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 style="font-size: 0.95rem; margin: 1.75rem 0 1rem;">Adicionar acesso</h3>
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/equipe">
            <?= Csrf::field() ?>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="eq_name">Nome</label>
                    <input type="text" id="eq_name" name="name" value="<?= htmlspecialchars($equipeOld['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="eq_email">E-mail</label>
                    <input type="email" id="eq_email" name="email" value="<?= htmlspecialchars($equipeOld['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="eq_password">Senha</label>
                    <input type="password" id="eq_password" name="password" minlength="8" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="form-field">
                    <label for="eq_role">Papel</label>
                    <select id="eq_role" name="role">
                        <option value="usuario" <?= ($equipeOld['role'] ?? 'usuario') === 'usuario' ? 'selected' : '' ?>>Equipe</option>
                        <option value="admin" <?= ($equipeOld['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>
            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Adicionar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Backup</h2>
            <p>Exporte um arquivo JSON com todos os dados do seu restaurante (produtos, pedidos, financeiro, clientes etc.) pra guardar por conta própria.</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/configuracoes/backup" class="btn-k btn-k-outline"><i class="bi bi-download"></i> Baixar backup (JSON)</a>
    </div>
</main>
