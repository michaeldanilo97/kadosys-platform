<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\User $usuarioEditado
 * @var array<string, array{title:string, icon:string}> $modulosDisponiveis
 * @var array<string, string> $modulosPermitidos slug => nivel
 * @var string|null $success
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$semRestricao = $modulosPermitidos === [];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Permissões de <?= htmlspecialchars($usuarioEditado->name, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="dash-page-subtitle">
            Escolha quais módulos <?= htmlspecialchars($usuarioEditado->name, ENT_QUOTES, 'UTF-8') ?> pode acessar,
            e se é só pra visualizar ou também pra editar/salvar. Sem nenhum módulo marcado, o acesso é o padrão:
            tudo que o plano contratado libera, com edição completa.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/permissoes" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="POST" action="<?= $basePath ?>/dashboard/permissoes/<?= $usuarioEditado->id ?>">
        <?= $csrf ?>

        <div class="crud-alert" style="background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.3); color: var(--primary-soft);">
            <i class="bi bi-info-circle"></i>
            <?= $semRestricao
                ? 'Nenhuma restrição aplicada agora - o usuário acessa tudo que o plano contratado libera, com edição completa.'
                : 'Restrição ativa - o usuário só acessa os módulos marcados abaixo, no nível escolhido.' ?>
        </div>

        <div class="plano-escolha" data-permissoes-grid style="margin-top: 1rem;">
            <?php foreach ($modulosDisponiveis as $slug => $modulo): ?>
                <?php $nivelAtual = $modulosPermitidos[$slug] ?? ''; ?>
                <div class="plano-escolha-card permissao-card" data-permissao-card>
                    <span class="nome"><i class="bi <?= htmlspecialchars($modulo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i> <?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="permissao-niveis">
                        <label>
                            <input type="radio" name="modulos[<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>]" value="" <?= $nivelAtual === '' ? 'checked' : '' ?>>
                            Sem acesso
                        </label>
                        <label>
                            <input type="radio" name="modulos[<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>]" value="visualizar" <?= $nivelAtual === 'visualizar' ? 'checked' : '' ?>>
                            Só visualizar
                        </label>
                        <label>
                            <input type="radio" name="modulos[<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>]" value="editar" <?= $nivelAtual === 'editar' ? 'checked' : '' ?>>
                            Editar
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/permissoes" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> Salvar permissões
            </button>
        </div>
    </form>
</div>

<script src="<?= $basePath ?>/assets/js/permissoes-form.js?v=<?= View::assetVersion('assets/js/permissoes-form.js') ?>"></script>
