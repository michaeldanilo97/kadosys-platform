<?php

/**
 * @var array $config
 * @var \Igrejas\Models\ConfiguracaoIgreja $configuracao
 * @var \Igrejas\Models\Assinatura|null $assinatura
 * @var bool $pagamentoConfigurado
 * @var bool $pixDisponivel
 * @var \Igrejas\Models\Tenant|null $tenant
 * @var string|null $success
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $configuracao->logoPath ? $basePath . '/' . $configuracao->logoPath : null;

// Durante o teste gratis nenhum plano foi de fato contratado ainda -
// mesmo que a igreja tenha "escolhido" um plano no cadastro, nenhum
// deles pode aparecer como "atual" (o botao Assinar precisa continuar
// disponivel pros 3, inclusive pro que ela esta testando agora).
$emTrial = $tenant !== null && $tenant->metodoPagamento === 'trial';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Configuracoes</h1>
        <p class="dash-page-subtitle">Dados gerais da igreja e preferencias do sistema.</p>
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

<?= \Igrejas\Core\View::render('dashboard.configuracoes._plano_panel', [
    'configuracao' => $configuracao,
    'emTrial' => $emTrial,
    'tenant' => $tenant,
    'assinatura' => $assinatura,
    'pagamentoConfigurado' => $pagamentoConfigurado,
    'pixDisponivel' => $pixDisponivel,
    'csrf' => $csrf,
    'basePath' => $basePath,
]) ?>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-image"></i> Logo da igreja</h2>
    </div>
    <p class="dash-page-subtitle" style="margin-bottom: 1.4rem;">
        Usada na tela de projecao quando o operador aplica o "fadeout" de um video.
    </p>

    <?php if ($logoUrl): ?>
        <div class="logo-preview">
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo atual da igreja">
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/logo" enctype="multipart/form-data" class="crud-form">
        <?= $csrf ?>
        <div class="crud-field">
            <label for="logo">Enviar nova logo (PNG, JPG, WEBP ou SVG, ate 5MB)</label>
            <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required>
        </div>
        <div class="crud-form-actions" style="justify-content: flex-start;">
            <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-upload"></i> Salvar logo</button>
        </div>
    </form>

    <?php if ($logoUrl): ?>
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/logo/remover" data-confirm="Remover a logo atual?" style="margin-top: 0.6rem;">
            <?= $csrf ?>
            <button type="submit" class="btn-k btn-k-outline" style="border-color: rgba(248,113,113,0.4); color: var(--danger);">
                <i class="bi bi-trash"></i> Remover logo
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-person-plus"></i> Cadastro de membros</h2>
    </div>
    <p class="dash-page-subtitle" style="margin-bottom: 1.4rem;">
        Escolha como novos membros entram no sistema: cadastrados manualmente pela secretaria, ou se cadastrando sozinhos.
    </p>

    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/cadastro-membros" class="crud-form">
        <?= $csrf ?>
        <label class="toggle-switch-field">
            <input type="checkbox" name="cadastro_membros_habilitado" value="1" <?= $configuracao->cadastroMembrosHabilitado ? 'checked' : '' ?>>
            <span class="toggle-switch"></span>
            <span class="toggle-switch-label">
                Permitir que membros se cadastrem sozinhos
                <span class="auth-field-hint">
                    Quando ligado, um link "Cadastre-se" aparece na tela de login desta igreja - qualquer pessoa pode preencher os proprios dados (com autopreenchimento de endereco pelo CEP) e virar um membro, sem precisar de acesso ao painel. Quando desligado, so a secretaria cadastra membros pelo modulo Membros.
                </span>
            </span>
        </label>
        <div class="crud-form-actions" style="justify-content: flex-start;">
            <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-qr-code"></i> Doacao via Pix</h2>
    </div>
    <p class="dash-page-subtitle" style="margin-bottom: 1.4rem;">
        Cadastre a chave Pix da igreja para gerar uma pagina publica de doacao (dizimos, ofertas) - o dinheiro cai
        direto na conta da igreja, sem passar pela plataforma. Como e um Pix por chave (sem gateway), nao ha
        confirmacao automatica de pagamento - o doador confirma manualmente que fez o Pix.
    </p>

    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/chave-pix" class="crud-form">
        <?= $csrf ?>
        <div class="crud-field">
            <label for="pix_chave">Chave Pix</label>
            <input
                type="text"
                id="pix_chave"
                name="pix_chave"
                value="<?= htmlspecialchars($configuracao->pixChave ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="CPF, CNPJ, e-mail, telefone ou chave aleatoria"
            >
        </div>
        <div class="crud-field">
            <label for="pix_nome_beneficiario">Nome exibido no Pix (opcional)</label>
            <input
                type="text"
                id="pix_nome_beneficiario"
                name="pix_nome_beneficiario"
                value="<?= htmlspecialchars($configuracao->pixNomeBeneficiario ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Padrao: nome da igreja"
                maxlength="25"
            >
            <span class="auth-field-hint">Maximo 25 caracteres, sem acentos (limitacao do padrao Pix).</span>
        </div>
        <div class="crud-form-actions" style="justify-content: flex-start;">
            <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>

    <?php if ($configuracao->doacaoPixHabilitada()): ?>
        <div class="auth-field" style="margin-top: 1.2rem;">
            <label for="link_doacao">Link publico para compartilhar</label>
            <div class="auth-slug-input">
                <input type="text" class="form-control" id="link_doacao" value="<?= htmlspecialchars($linkDoacao, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button type="button" class="pix-copiar-btn" data-copiar-link="link_doacao" aria-label="Copiar link">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>
