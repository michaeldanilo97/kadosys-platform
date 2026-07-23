<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Seed de um restaurante + usuario administrador - KADOSYS Food
|--------------------------------------------------------------------------
|
| Uso (linha de comando, apos configurar config/database.php e rodar
| database/install.sql):
|
|   php database/seed_admin.php "Nome do Restaurante" "Nome do Admin" "email@dominio" "senha-forte"
|
| Reaproveita o restaurante se o slug (gerado a partir do nome) ja
| existir, e cria/atualiza o usuario admin vinculado a ele.
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

[$script, $nomeRestaurante, $nomeAdmin, $email, $password] = array_pad($argv, 5, null);

if (!$nomeRestaurante || !$nomeAdmin || !$email || !$password) {
    fwrite(STDERR, "Uso: php database/seed_admin.php \"Nome do Restaurante\" \"Nome do Admin\" \"email@dominio\" \"senha\"\n");
    exit(1);
}

$pdo = \Food\Core\Database::connection();

$slug = strtolower((string) $nomeRestaurante);
$slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');

$restauranteExistente = $pdo->prepare('SELECT id FROM restaurantes WHERE slug = :slug LIMIT 1');
$restauranteExistente->execute(['slug' => $slug]);
$restauranteId = $restauranteExistente->fetchColumn();

if ($restauranteId) {
    $restauranteId = (int) $restauranteId;
} else {
    $criarRestaurante = $pdo->prepare(
        "INSERT INTO restaurantes (nome, slug, status, created_at) VALUES (:nome, :slug, 'ativo', NOW())"
    );
    $criarRestaurante->execute(['nome' => $nomeRestaurante, 'slug' => $slug]);
    $restauranteId = (int) $pdo->lastInsertId();

    if (class_exists(\Food\Models\Categoria::class)) {
        \Food\Models\Categoria::seedPadrao($restauranteId);
    }
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO users (restaurante_id, name, email, password, role, active)
     VALUES (:restaurante_id, :name, :email, :password, "admin", 1)
     ON DUPLICATE KEY UPDATE
        name = :name_update,
        password = :password_update,
        active = 1'
);

$stmt->execute([
    'restaurante_id' => $restauranteId,
    'name' => $nomeAdmin,
    'email' => $email,
    'password' => $hash,
    'name_update' => $nomeAdmin,
    'password_update' => $hash,
]);

echo "Restaurante \"{$nomeRestaurante}\" (slug: {$slug}, id: {$restauranteId}) e usuario administrador criados/atualizados: {$email}\n";
