<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Seed de uma barbearia + usuario administrador - KADOSYS Barbearias
|--------------------------------------------------------------------------
|
| Uso (linha de comando, apos configurar config/database.php e rodar
| database/install.sql):
|
|   php database/seed_admin.php "Nome da Barbearia" "Nome do Admin" "email@dominio" "senha-forte"
|
| Reaproveita a barbearia se o slug (gerado a partir do nome) ja
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

[$script, $nomeBarbearia, $nomeAdmin, $email, $password] = array_pad($argv, 5, null);

if (!$nomeBarbearia || !$nomeAdmin || !$email || !$password) {
    fwrite(STDERR, "Uso: php database/seed_admin.php \"Nome da Barbearia\" \"Nome do Admin\" \"email@dominio\" \"senha\"\n");
    exit(1);
}

$pdo = \Barbearias\Core\Database::connection();

$slug = strtolower((string) $nomeBarbearia);
$slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');

$barbeariaExistente = $pdo->prepare('SELECT id FROM barbearias WHERE slug = :slug LIMIT 1');
$barbeariaExistente->execute(['slug' => $slug]);
$barbeariaId = $barbeariaExistente->fetchColumn();

if ($barbeariaId) {
    $barbeariaId = (int) $barbeariaId;
} else {
    $criarBarbearia = $pdo->prepare(
        "INSERT INTO barbearias (nome, slug, status, created_at) VALUES (:nome, :slug, 'ativo', NOW())"
    );
    $criarBarbearia->execute(['nome' => $nomeBarbearia, 'slug' => $slug]);
    $barbeariaId = (int) $pdo->lastInsertId();

    $criarUnidade = $pdo->prepare(
        "INSERT INTO unidades (barbearia_id, nome, slug, principal, ativa, created_at)
         VALUES (:barbearia_id, 'Unidade Principal', 'principal', 1, 1, NOW())"
    );
    $criarUnidade->execute(['barbearia_id' => $barbeariaId]);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO users (barbearia_id, name, email, password, role, active)
     VALUES (:barbearia_id, :name, :email, :password, "admin", 1)
     ON DUPLICATE KEY UPDATE
        name = :name_update,
        password = :password_update,
        active = 1'
);

$stmt->execute([
    'barbearia_id' => $barbeariaId,
    'name' => $nomeAdmin,
    'email' => $email,
    'password' => $hash,
    'name_update' => $nomeAdmin,
    'password_update' => $hash,
]);

echo "Barbearia \"{$nomeBarbearia}\" (slug: {$slug}, id: {$barbeariaId}) e usuario administrador criados/atualizados: {$email}\n";
