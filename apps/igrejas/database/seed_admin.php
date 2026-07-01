<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Seed do usuario administrador inicial - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Uso (linha de comando, apos configurar config/database.php e rodar a
| migracao database/migrations/001_create_tables.sql):
|
|   php database/seed_admin.php "Nome Completo" "[email protected]" "senha-forte"
|
| O hash da senha e gerado com password_hash() (bcrypt), o mesmo
| algoritmo usado pelo login da aplicacao (src/Models/User.php).
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

[$script, $name, $email, $password] = array_pad($argv, 4, null);

if (!$name || !$email || !$password) {
    fwrite(STDERR, "Uso: php database/seed_admin.php \"Nome\" \"email@dominio\" \"senha\"\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$pdo = \Igrejas\Core\Database::connection();

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password, role, active)
     VALUES (:name, :email, :password, "admin", 1)
     ON DUPLICATE KEY UPDATE
        name = :name_update,
        password = :password_update,
        active = 1'
);

$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => $hash,
    'name_update' => $name,
    'password_update' => $hash,
]);

echo "Usuario administrador criado/atualizado com sucesso: {$email}\n";
