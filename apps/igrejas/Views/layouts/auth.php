<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? config('app.name')) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-tertiary-bg);
        }

        .kadosys-auth-card {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>
<body>

<div class="kadosys-auth-card">
    <div class="text-center mb-4">
        <i class="bi bi-shield-check fs-1 text-primary"></i>
        <h4 class="mt-2 mb-0"><?= htmlspecialchars(config('app.name')) ?></h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <?= $content ?? '' ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
