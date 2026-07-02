<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Importacao do texto biblico - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Os 66 livros (nomes, abreviacoes, total de capitulos) ja sao inseridos
| pela migracao database/migrations/005_create_projecao_tables.sql. Este
| script importa o TEXTO dos versiculos a partir de um arquivo JSON.
|
| Formato esperado do arquivo (lista simples de versiculos):
|
|   [
|     { "livro_id": 1, "capitulo": 1, "versiculo": 1, "texto": "No principio criou Deus os ceus e a terra." },
|     { "livro_id": 1, "capitulo": 1, "versiculo": 2, "texto": "..." },
|     ...
|   ]
|
| "livro_id" segue a ordem canonica 1-66 (1 = Genesis ... 66 = Apocalipse),
| igual a tabela biblia_livros. Se o arquivo de origem usar abreviacoes
| (ex.: "Gn", "Mt") em vez de livro_id numerico, ajuste o mapeamento no
| bloco RESOLVER_LIVRO_ID abaixo antes de rodar a importacao.
|
| Uso (linha de comando):
|
|   php database/seed_biblia.php caminho/para/biblia.json
|
| A importacao e feita em lote (chunks) dentro de uma transacao e usa
| "INSERT ... ON DUPLICATE KEY UPDATE", entao pode ser rodada novamente
| com seguranca (ex.: para corrigir versiculos) sem duplicar linhas.
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

[$script, $caminhoArquivo] = array_pad($argv, 2, null);

if (!$caminhoArquivo || !is_file($caminhoArquivo)) {
    fwrite(STDERR, "Uso: php database/seed_biblia.php caminho/para/biblia.json\n");
    exit(1);
}

$conteudo = file_get_contents($caminhoArquivo);
$versiculos = json_decode($conteudo, true);

if (!is_array($versiculos)) {
    fwrite(STDERR, "Arquivo JSON invalido ou vazio.\n");
    exit(1);
}

// Mapa de abreviacao => livro_id, usado apenas se o arquivo de origem
// identificar o livro por abreviacao em vez do id numerico 1-66.
$RESOLVER_LIVRO_ID = static function (array $linha, \PDO $pdo): ?int {
    if (isset($linha['livro_id'])) {
        return (int) $linha['livro_id'];
    }

    if (isset($linha['abreviacao'])) {
        static $cache = [];
        $abreviacao = (string) $linha['abreviacao'];

        if (!isset($cache[$abreviacao])) {
            $stmt = $pdo->prepare('SELECT id FROM biblia_livros WHERE abreviacao = :abreviacao LIMIT 1');
            $stmt->execute(['abreviacao' => $abreviacao]);
            $cache[$abreviacao] = $stmt->fetchColumn() ?: null;
        }

        return $cache[$abreviacao] !== null ? (int) $cache[$abreviacao] : null;
    }

    return null;
};

$pdo = \Igrejas\Core\Database::connection();
$pdo->beginTransaction();

$stmt = $pdo->prepare(
    'INSERT INTO biblia_versiculos (livro_id, capitulo, versiculo, texto)
     VALUES (:livro_id, :capitulo, :versiculo, :texto)
     ON DUPLICATE KEY UPDATE texto = VALUES(texto)'
);

$total = 0;
$ignorados = 0;

foreach ($versiculos as $linha) {
    $livroId = $RESOLVER_LIVRO_ID($linha, $pdo);

    if ($livroId === null || !isset($linha['capitulo'], $linha['versiculo'], $linha['texto'])) {
        $ignorados++;
        continue;
    }

    $stmt->execute([
        'livro_id' => $livroId,
        'capitulo' => (int) $linha['capitulo'],
        'versiculo' => (int) $linha['versiculo'],
        'texto' => trim((string) $linha['texto']),
    ]);

    $total++;
}

$pdo->commit();

echo "Importacao concluida: {$total} versiculos gravados";

if ($ignorados > 0) {
    echo ", {$ignorados} linhas ignoradas (dados incompletos ou livro nao identificado)";
}

echo ".\n";
