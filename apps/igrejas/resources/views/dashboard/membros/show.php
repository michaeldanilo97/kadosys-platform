<?php

use Igrejas\Core\View;

/**
 * Perfil do membro - aberto ao clicar no nome na listagem. Reune, numa
 * unica tela com abas: dados pessoais/contato/endereco (editaveis,
 * salvos no mesmo form), ministerios/grupos, participacoes em cultos,
 * historico e documentos anexados.
 *
 * @var array $config
 * @var \Igrejas\Models\Membro $membro
 * @var array<int, array{id:int, nome:string, papel:string}> $ministerios
 * @var array<int, array{id:int, nome:string, tipo:string, papel:string}> $grupos
 * @var array<int, \Igrejas\Models\Culto> $participacoes
 * @var array<int, \Igrejas\Models\MembroDocumento> $documentos
 * @var string|null $success
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$vinculos = array_merge(
    array_map(static fn (array $m) => ['tipo' => 'Ministério', 'nome' => $m['nome'], 'papel' => $m['papel']], $ministerios),
    array_map(static fn (array $g) => ['tipo' => ucfirst($g['tipo']), 'nome' => $g['nome'], 'papel' => $g['papel']], $grupos)
);

$telefoneWhats = $membro->telefone ? preg_replace('/\D/', '', $membro->telefone) : null;
?>

<div class="dash-page-head">
    <div>
        <nav class="member-breadcrumb-back">
            <a href="<?= $basePath ?>/dashboard/membros"><i class="bi bi-arrow-left"></i> Membros</a>
        </nav>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="member-profile-header dash-panel">
    <div class="member-profile-avatar">
        <?= htmlspecialchars(mb_strtoupper(mb_substr($membro->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="member-profile-heading">
        <div class="member-profile-name-row">
            <h1><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></h1>
            <span class="status-badge <?= $membro->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                <?= $membro->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>
        <div class="member-profile-meta">
            <?php if ($membro->dataMembresia): ?>
                <span><i class="bi bi-calendar-check"></i> Membro desde <?= (new DateTimeImmutable($membro->dataMembresia))->format('d/m/Y') ?></span>
            <?php endif; ?>
            <span><i class="bi bi-hash"></i> ID <?= str_pad((string) $membro->id, 4, '0', STR_PAD_LEFT) ?></span>
            <?php if ($membro->idade() !== null): ?>
                <span><i class="bi bi-cake2"></i> <?= $membro->idade() ?> anos</span>
            <?php endif; ?>
        </div>
        <?php if ($vinculos !== []): ?>
            <div class="member-profile-badges">
                <?php foreach ($vinculos as $vinculo): ?>
                    <span class="member-badge">
                        <?= htmlspecialchars($vinculo['nome'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($vinculo['papel'] === 'Líder'): ?>
                            <span class="member-badge-papel">Líder</span>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="member-profile-contact">
        <?php if ($membro->email): ?>
            <a href="mailto:<?= htmlspecialchars($membro->email, ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-envelope"></i> <?= htmlspecialchars($membro->email, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endif; ?>
        <?php if ($membro->telefone): ?>
            <a href="https://wa.me/55<?= $telefoneWhats ?>" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($membro->telefone, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="member-tabs" data-member-tabs role="tablist">
    <button type="button" class="member-tab is-active" data-tab-btn="dados">Dados</button>
    <button type="button" class="member-tab" data-tab-btn="contato">Contato</button>
    <button type="button" class="member-tab" data-tab-btn="endereco">Endereço</button>
    <button type="button" class="member-tab" data-tab-btn="ministerios">Ministérios</button>
    <button type="button" class="member-tab" data-tab-btn="participacoes">Participações</button>
    <button type="button" class="member-tab" data-tab-btn="historico">Histórico</button>
    <button type="button" class="member-tab" data-tab-btn="documentos">Documentos</button>
</div>

<div class="member-tabs-body">
    <div class="member-tabs-main">
        <form method="POST" action="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>" class="crud-form">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="dash-panel member-tab-panel is-active" data-tab-panel="dados">
                <h3><i class="bi bi-person"></i> Informações pessoais</h3>
                <div class="crud-form-grid">
                    <div class="crud-field crud-field-full">
                        <label for="nome">Nome completo *</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="crud-field">
                        <label for="data_nascimento">Data de nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars((string) $membro->dataNascimento, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="genero">Sexo</label>
                        <select id="genero" name="genero">
                            <option value="" <?= $membro->genero === null ? 'selected' : '' ?>>Não informado</option>
                            <option value="feminino" <?= $membro->genero === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                            <option value="masculino" <?= $membro->genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="outro" <?= $membro->genero === 'outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="crud-field">
                        <label for="estado_civil">Estado civil</label>
                        <select id="estado_civil" name="estado_civil">
                            <option value="" <?= $membro->estadoCivil === null ? 'selected' : '' ?>>Não informado</option>
                            <option value="solteiro" <?= $membro->estadoCivil === 'solteiro' ? 'selected' : '' ?>>Solteiro(a)</option>
                            <option value="casado" <?= $membro->estadoCivil === 'casado' ? 'selected' : '' ?>>Casado(a)</option>
                            <option value="divorciado" <?= $membro->estadoCivil === 'divorciado' ? 'selected' : '' ?>>Divorciado(a)</option>
                            <option value="viuvo" <?= $membro->estadoCivil === 'viuvo' ? 'selected' : '' ?>>Viúvo(a)</option>
                            <option value="outro" <?= $membro->estadoCivil === 'outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="crud-field">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars((string) $membro->cpf, ENT_QUOTES, 'UTF-8') ?>" placeholder="000.000.000-00">
                    </div>
                    <div class="crud-field">
                        <label for="rg">RG</label>
                        <input type="text" id="rg" name="rg" value="<?= htmlspecialchars((string) $membro->rg, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="naturalidade">Naturalidade</label>
                        <input type="text" id="naturalidade" name="naturalidade" value="<?= htmlspecialchars((string) $membro->naturalidade, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cidade - UF">
                    </div>
                    <div class="crud-field">
                        <label for="data_membresia">Membro desde</label>
                        <input type="date" id="data_membresia" name="data_membresia" value="<?= htmlspecialchars((string) $membro->dataMembresia, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="ativo" <?= $membro->status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= $membro->status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="crud-field crud-field-full">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars((string) $membro->observacoes, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <div class="crud-form-actions">
                    <a href="<?= $basePath ?>/dashboard/membros" class="btn-k btn-k-ghost">Cancelar</a>
                    <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-check-lg"></i> Salvar</button>
                </div>
            </div>

            <div class="dash-panel member-tab-panel" data-tab-panel="contato">
                <h3><i class="bi bi-telephone"></i> Contato</h3>
                <div class="crud-form-grid">
                    <div class="crud-field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars((string) $membro->email, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="telefone">Telefone / WhatsApp</label>
                        <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars((string) $membro->telefone, ENT_QUOTES, 'UTF-8') ?>" placeholder="(00) 00000-0000">
                    </div>
                </div>
                <div class="crud-form-actions">
                    <a href="<?= $basePath ?>/dashboard/membros" class="btn-k btn-k-ghost">Cancelar</a>
                    <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-check-lg"></i> Salvar</button>
                </div>
            </div>

            <div class="dash-panel member-tab-panel" data-tab-panel="endereco">
                <h3><i class="bi bi-geo-alt"></i> Endereço</h3>
                <div class="crud-form-grid">
                    <div class="crud-field">
                        <label for="cep">CEP</label>
                        <div class="member-cep-field">
                            <input
                                type="text" id="cep" name="cep"
                                value="<?= htmlspecialchars((string) $membro->cep, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="00000-000"
                                data-cep-input
                                data-cep-logradouro="logradouro"
                                data-cep-bairro="bairro"
                                data-cep-cidade="cidade"
                                data-cep-estado="estado"
                            >
                            <span class="member-cep-status" data-cep-status></span>
                        </div>
                    </div>
                    <div class="crud-field crud-field-full">
                        <label for="logradouro">Logradouro</label>
                        <input type="text" id="logradouro" name="logradouro" value="<?= htmlspecialchars((string) $membro->logradouro, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rua, avenida...">
                    </div>
                    <div class="crud-field">
                        <label for="numero">Número</label>
                        <input type="text" id="numero" name="numero" value="<?= htmlspecialchars((string) $membro->numero, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" value="<?= htmlspecialchars((string) $membro->complemento, ENT_QUOTES, 'UTF-8') ?>" placeholder="Apto, bloco...">
                    </div>
                    <div class="crud-field">
                        <label for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars((string) $membro->bairro, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars((string) $membro->cidade, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="crud-field">
                        <label for="estado">UF</label>
                        <input type="text" id="estado" name="estado" value="<?= htmlspecialchars((string) $membro->estado, ENT_QUOTES, 'UTF-8') ?>" maxlength="2" style="text-transform: uppercase;">
                    </div>
                    <!-- Campo legado (endereco unico texto livre) mantido oculto so pra nao perder dado de cadastros antigos que ainda nao migraram pros campos estruturados. -->
                    <input type="hidden" name="endereco" value="<?= htmlspecialchars((string) $membro->endereco, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-form-actions">
                    <a href="<?= $basePath ?>/dashboard/membros" class="btn-k btn-k-ghost">Cancelar</a>
                    <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-check-lg"></i> Salvar</button>
                </div>
            </div>
        </form>

        <div class="dash-panel member-tab-panel" data-tab-panel="ministerios">
            <h3><i class="bi bi-diagram-3"></i> Ministérios e grupos</h3>
            <?php if ($vinculos === []): ?>
                <p class="member-tab-empty">Nenhum ministério ou grupo vinculado ainda.</p>
            <?php else: ?>
                <ul class="member-vinculo-list">
                    <?php foreach ($vinculos as $vinculo): ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($vinculo['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="member-tab-dim"><?= htmlspecialchars($vinculo['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span class="member-badge-papel <?= $vinculo['papel'] === 'Líder' ? 'is-lider' : '' ?>"><?= htmlspecialchars($vinculo['papel'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dash-panel member-tab-panel" data-tab-panel="participacoes">
            <h3><i class="bi bi-calendar-check"></i> Participações em cultos</h3>
            <?php if ($participacoes === []): ?>
                <p class="member-tab-empty">Nenhuma presença registrada ainda.</p>
            <?php else: ?>
                <ul class="member-participacao-list">
                    <?php foreach ($participacoes as $culto): ?>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <div>
                                <strong><?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="member-tab-dim"><?= (new DateTimeImmutable($culto->data))->format('d/m/Y') ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dash-panel member-tab-panel" data-tab-panel="historico">
            <h3><i class="bi bi-clock-history"></i> Histórico</h3>
            <ul class="member-historico-list">
                <li>
                    <i class="bi bi-person-plus"></i>
                    <div>
                        <strong>Cadastrado no sistema</strong>
                        <span class="member-tab-dim"><?= (new DateTimeImmutable($membro->createdAt))->format('d/m/Y') ?></span>
                    </div>
                </li>
                <?php if ($membro->dataMembresia): ?>
                    <li>
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Membro da igreja desde</strong>
                            <span class="member-tab-dim"><?= (new DateTimeImmutable($membro->dataMembresia))->format('d/m/Y') ?></span>
                        </div>
                    </li>
                <?php endif; ?>
                <?php foreach (array_slice($participacoes, 0, 10) as $culto): ?>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <div>
                            <strong>Presente em <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="member-tab-dim"><?= (new DateTimeImmutable($culto->data))->format('d/m/Y') ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="dash-panel member-tab-panel" data-tab-panel="documentos">
            <h3><i class="bi bi-file-earmark-text"></i> Documentos</h3>

            <form method="POST" action="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>/documentos" enctype="multipart/form-data" class="member-documento-upload">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="nome" placeholder="Nome do documento (opcional)">
                <input type="file" name="documento" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                <button type="submit" class="btn-k btn-k-outline"><i class="bi bi-upload"></i> Enviar</button>
            </form>

            <?php if ($documentos === []): ?>
                <p class="member-tab-empty">Nenhum documento anexado ainda.</p>
            <?php else: ?>
                <ul class="member-documento-list">
                    <?php foreach ($documentos as $documento): ?>
                        <li>
                            <a href="<?= $basePath ?>/<?= htmlspecialchars($documento->arquivoPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                <i class="bi bi-file-earmark"></i>
                                <span><?= htmlspecialchars($documento->nome, ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <span class="member-tab-dim"><?= number_format($documento->tamanhoBytes / 1024, 0) ?> KB</span>
                            <form method="POST" action="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>/documentos/<?= $documento->id ?>/excluir" data-confirm="Remover o documento &quot;<?= htmlspecialchars($documento->nome, ENT_QUOTES, 'UTF-8') ?>&quot;?">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="crud-icon-btn danger" aria-label="Excluir documento"><i class="bi bi-trash"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <aside class="member-tabs-side">
        <div class="dash-panel member-side-card">
            <div class="member-side-card-head">
                <h4><i class="bi bi-diagram-3"></i> Ministérios</h4>
                <button type="button" class="member-side-link" data-tab-btn="ministerios">Ver todos</button>
            </div>
            <?php if ($vinculos === []): ?>
                <p class="member-tab-empty">Sem vínculos ainda.</p>
            <?php else: ?>
                <ul class="member-side-list">
                    <?php foreach (array_slice($vinculos, 0, 4) as $vinculo): ?>
                        <li>
                            <span><?= htmlspecialchars($vinculo['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="member-badge-papel <?= $vinculo['papel'] === 'Líder' ? 'is-lider' : '' ?>"><?= htmlspecialchars($vinculo['papel'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dash-panel member-side-card">
            <div class="member-side-card-head">
                <h4><i class="bi bi-calendar-check"></i> Participações recentes</h4>
                <button type="button" class="member-side-link" data-tab-btn="participacoes">Ver todas</button>
            </div>
            <?php if ($participacoes === []): ?>
                <p class="member-tab-empty">Nenhuma presença registrada.</p>
            <?php else: ?>
                <ul class="member-side-list">
                    <?php foreach (array_slice($participacoes, 0, 4) as $culto): ?>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <div>
                                <span><?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="member-tab-dim"><?= (new DateTimeImmutable($culto->data))->format('d/m/Y') ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>
</div>

<script src="<?= $basePath ?>/assets/js/cep-autofill.js?v=<?= View::assetVersion('assets/js/cep-autofill.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/membro-perfil.js?v=<?= View::assetVersion('assets/js/membro-perfil.js') ?>"></script>
