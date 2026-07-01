<?php
/**
 * Pagina 404 standalone.
 *
 * Usada diretamente pelo Router (src/Core/Router.php) quando nenhuma rota
 * corresponde a URL solicitada, fora do contexto de qualquer layout.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina nao encontrada - KADOSYS Igrejas</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #16213a;
            color: #f6f3ea;
            text-align: center;
            padding: 2rem;
        }
        .folio {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #b6862c;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 4rem;
            margin: 0 0 0.6rem;
            font-weight: 700;
        }
        p {
            color: rgba(246, 243, 234, 0.7);
            margin: 0 0 2rem;
        }
        a {
            display: inline-block;
            padding: 0.8rem 1.6rem;
            border: 1px solid rgba(246, 243, 234, 0.3);
            border-radius: 4px;
            color: #f6f3ea;
            text-decoration: none;
            font-weight: 700;
        }
        a:hover {
            border-color: #b6862c;
            color: #d4a13f;
        }
    </style>
</head>
<body>
    <div>
        <div class="folio">Folio nao encontrado</div>
        <h1>404</h1>
        <p>A pagina que voce procura nao existe ou foi movida.</p>
        <a href="<?= ($this->config['base_path'] ?? '') !== '' ? $this->config['base_path'] : '/' ?>">Voltar para o inicio</a>
    </div>
</body>
</html>
