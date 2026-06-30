<?php
/**
 * View: auth.login
 */

$title = 'Login';
$error = session('_login_error');

ob_start();
?>

<h5 class="card-title mb-3">Acessar plataforma</h5>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('/login') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string) old('email', '')) ?>" required>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Senha</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Lembrar-me</label>
    </div>

    <button type="submit" class="btn btn-primary w-100">Entrar</button>
</form>

<?php
$content = ob_get_clean();

require __DIR__ . '/../layouts/auth.php';
