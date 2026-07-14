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

// Ja existe um usuario (e portanto um membro vinculado) com esse
// e-mail? Reaproveita o membro_id em vez de criar um Membro duplicado
// a cada execucao deste script.
$existente = $pdo->prepare('SELECT membro_id FROM users WHERE email = :email LIMIT 1');
$existente->execute(['email' => $email]);
$membroIdExistente = $existente->fetchColumn();

if ($membroIdExistente) {
    $membroId = (int) $membroIdExistente;
} else {
    $membroStmt = $pdo->prepare(
        'INSERT INTO membros (nome, email, status, data_membresia, created_at)
         VALUES (:nome, :email, "ativo", CURDATE(), NOW())'
    );
    $membroStmt->execute(['nome' => $name, 'email' => $email]);
    $membroId = (int) $pdo->lastInsertId();
}

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password, role, active, membro_id)
     VALUES (:name, :email, :password, "admin", 1, :membro_id)
     ON DUPLICATE KEY UPDATE
        name = :name_update,
        password = :password_update,
        active = 1,
        membro_id = :membro_id_update'
);

$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => $hash,
    'membro_id' => $membroId,
    'name_update' => $name,
    'password_update' => $hash,
    'membro_id_update' => $membroId,
]);

echo "Usuario administrador criado/atualizado com sucesso: {$email}\n";
