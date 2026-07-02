<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Importacao do texto biblico - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Os 66 livros (nomes, abreviacoes, total de capitulos) ja sao inseridos
| pela migracao database/migrations/005_create_projecao_tables.sql. As
| versoes/traducoes disponiveis (nvi, acf, aa) sao fixas em
| Igrejas\Models\BibliaVersao. Este script importa o TEXTO dos
| versiculos de uma versao, a partir de um arquivo JSON no formato do
| projeto publico thiagobodruk/biblia (https://github.com/thiagobodruk/biblia):
|
|   [
|     { "abbrev": "gn", "name": "Genesis", "chapters": [ ["...", "..."], ["..."] ] },
|     ...
|   ]
|
| Os livros aparecem no arquivo na mesma ordem canonica (1-66) usada na
| tabela biblia_livros, entao o indice do livro no array (comecando em 1)
| e usado diretamente como livro_id - sem depender da abreviacao.
|
| Uso (linha de comando), rode uma vez para cada versao:
|
|   php database/seed_biblia.php nvi caminho/para/nvi.json
|   php database/seed_biblia.php acf caminho/para/acf.json
|   php database/seed_biblia.php aa  caminho/para/aa.json
|
| O caminho tambem pode ser uma URL http(s) (ex.: o link "raw" do
| arquivo no GitHub), se o servidor tiver allow_url_fopen habilitado.
|
| A importacao roda dentro de uma transacao e usa
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
require_once dirname(__DIR__) . '/src/Models/BibliaVersao.php';

[$script, $versao, $origem] = array_pad($argv, 3, null);

if (!$versao || !$origem) {
    fwrite(STDERR, "Uso: php database/seed_biblia.php <versao> <arquivo-ou-url.json>\n");
    fwrite(STDERR, 'Versoes aceitas: ' . implode(', ', array_keys(\Igrejas\Models\BibliaVersao::todas())) . "\n");
    exit(1);
}

if (!\Igrejas\Models\BibliaVersao::valida($versao)) {
    fwrite(STDERR, "Versao \"{$versao}\" nao reconhecida. Aceitas: " . implode(', ', array_keys(\Igrejas\Models\BibliaVersao::todas())) . "\n");
    exit(1);
}

$isUrl = str_starts_with($origem, 'http://') || str_starts_with($origem, 'https://');

if (!$isUrl && !is_file($origem)) {
    fwrite(STDERR, "Arquivo nao encontrado: {$origem}\n");
    exit(1);
}

$conteudo = file_get_contents($origem);

if ($conteudo === false) {
    fwrite(STDERR, "Nao foi possivel ler: {$origem}\n");
    exit(1);
}

// Alguns arquivos vem com BOM UTF-8 (comum em exports do Windows).
$conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);

$livros = json_decode($conteudo, true);

if (!is_array($livros) || $livros === []) {
    fwrite(STDERR, "Arquivo JSON invalido ou vazio.\n");
    exit(1);
}

$pdo = \Igrejas\Core\Database::connection();
$pdo->beginTransaction();

$stmt = $pdo->prepare(
    'INSERT INTO biblia_versiculos (livro_id, versao, capitulo, versiculo, texto)
     VALUES (:livro_id, :versao, :capitulo, :versiculo, :texto)
     ON DUPLICATE KEY UPDATE texto = VALUES(texto)'
);

$total = 0;

foreach ($livros as $indice => $livro) {
    $livroId = $indice + 1; // ordem canonica do arquivo == ordem canonica de biblia_livros (1-66).

    if ($livroId < 1 || $livroId > 66 || !isset($livro['chapters']) || !is_array($livro['chapters'])) {
        continue;
    }

    foreach ($livro['chapters'] as $capituloIndice => $versiculos) {
        if (!is_array($versiculos)) {
            continue;
        }

        foreach ($versiculos as $versiculoIndice => $texto) {
            $stmt->execute([
                'livro_id' => $livroId,
                'versao' => $versao,
                'capitulo' => $capituloIndice + 1,
                'versiculo' => $versiculoIndice + 1,
                'texto' => trim((string) $texto),
            ]);

            $total++;
        }
    }
}

$pdo->commit();

echo "Importacao concluida ({$versao}): {$total} versiculos gravados.\n";
