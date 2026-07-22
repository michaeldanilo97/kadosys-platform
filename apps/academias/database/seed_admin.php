<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Seed de uma academia + usuario administrador - KADOSYS Academias
|--------------------------------------------------------------------------
|
| Uso (linha de comando, apos configurar config/database.php e rodar
| database/install.sql):
|
|   php database/seed_admin.php "Nome da Academia" "Nome do Admin" "email@dominio" "senha-forte"
|
| Reaproveita a academia se o slug (gerado a partir do nome) ja
| existir, e cria/atualiza o usuario admin vinculado a ela.
|
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script so pode ser executado via linha de comando.');
}

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require $vendorAutoload;
}

require_once dirname(__DIR__) . '/src/Core/Database.php';

[$script, $nomeAcademia, $nomeAdmin, $email, $password] = array_pad($argv, 5, null);

if (!$nomeAcademia || !$nomeAdmin || !$email || !$password) {
    fwrite(STDERR, "Uso: php database/seed_admin.php \"Nome da Academia\" \"Nome do Admin\" \"email@dominio\" \"senha\"\n");
    exit(1);
}

$pdo = \Academias\Core\Database::connection();

$slug = strtolower((string) $nomeAcademia);
$slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');

$academiaExistente = $pdo->prepare('SELECT id FROM academias WHERE slug = :slug LIMIT 1');
$academiaExistente->execute(['slug' => $slug]);
$academiaId = $academiaExistente->fetchColumn();

if ($academiaId) {
    $academiaId = (int) $academiaId;
} else {
    $criarAcademia = $pdo->prepare(
        "INSERT INTO academias (nome, slug, status, qr_checkin_token, created_at)
         VALUES (:nome, :slug, 'ativo', :qr_checkin_token, NOW())"
    );
    $criarAcademia->execute([
        'nome' => $nomeAcademia,
        'slug' => $slug,
        'qr_checkin_token' => bin2hex(random_bytes(20)),
    ]);
    $academiaId = (int) $pdo->lastInsertId();

    $criarUnidade = $pdo->prepare(
        "INSERT INTO unidades (academia_id, nome, slug, principal, ativa, created_at)
         VALUES (:academia_id, 'Unidade Principal', 'principal', 1, 1, NOW())"
    );
    $criarUnidade->execute(['academia_id' => $academiaId]);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO users (academia_id, name, email, password, role, active)
     VALUES (:academia_id, :name, :email, :password, "admin", 1)
     ON DUPLICATE KEY UPDATE
        name = :name_update,
        password = :password_update,
        active = 1'
);

$stmt->execute([
    'academia_id' => $academiaId,
    'name' => $nomeAdmin,
    'email' => $email,
    'password' => $hash,
    'name_update' => $nomeAdmin,
    'password_update' => $hash,
]);

echo "Academia \"{$nomeAcademia}\" (slug: {$slug}, id: {$academiaId}) e usuario administrador criados/atualizados: {$email}\n";
