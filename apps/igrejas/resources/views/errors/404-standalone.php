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
    <title>Página não encontrada - KADOSYS Igrejas</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background:
                radial-gradient(ellipse 60% 45% at 20% 10%, rgba(59, 130, 246, 0.16), transparent 60%),
                radial-gradient(ellipse 55% 40% at 85% 85%, rgba(139, 92, 246, 0.16), transparent 62%),
                #0F172A;
            color: #E5E7EB;
            text-align: center;
            padding: 2rem;
        }
        .kicker {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.76rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #60A5FA;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 5rem;
            margin: 0 0 0.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            color: #9CA3AF;
            margin: 0 0 2rem;
        }
        a {
            display: inline-block;
            padding: 0.85rem 1.8rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            color: #FFFFFF;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body>
    <div>
        <div class="kicker">Rota não encontrada</div>
        <h1>404</h1>
        <p>A página que você procura não existe ou foi movida.</p>
        <a href="<?= ($this->config['base_path'] ?? '') !== '' ? $this->config['base_path'] : '/' ?>">Voltar para o início</a>
    </div>
</body>
</html>
