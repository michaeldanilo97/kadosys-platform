<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? config('app.name')) ?></title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --kadosys-sidebar-width: 260px;
        }

        body {
            min-height: 100vh;
        }

        .kadosys-sidebar {
            width: var(--kadosys-sidebar-width);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            transition: transform 0.2s ease-in-out;
        }

        .kadosys-content {
            margin-left: var(--kadosys-sidebar-width);
            min-height: 100vh;
        }

        .kadosys-topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .kadosys-sidebar .nav-link {
            color: var(--bs-secondary-color);
            border-radius: 0.5rem;
        }

        .kadosys-sidebar .nav-link.active,
        .kadosys-sidebar .nav-link:hover {
            background-color: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
        }

        @media (max-width: 991.98px) {
            .kadosys-sidebar {
                transform: translateX(-100%);
            }

            .kadosys-sidebar.show {
                transform: translateX(0);
            }

            .kadosys-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<nav class="kadosys-sidebar bg-body-tertiary border-end p-3 d-flex flex-column" id="kadosysSidebar">
    <div class="d-flex align-items-center gap-2 mb-4 px-2">
        <i class="bi bi-shield-check fs-3 text-primary"></i>
        <span class="fs-5 fw-semibold">Kadosys</span>
    </div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="<?= url('/dashboard') ?>" class="nav-link d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link d-flex align-items-center gap-2">
                <i class="bi bi-people"></i> Membros
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link d-flex align-items-center gap-2">
                <i class="bi bi-calendar-event"></i> Eventos
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link d-flex align-items-center gap-2">
                <i class="bi bi-cash-coin"></i> Financeiro
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link d-flex align-items-center gap-2">
                <i class="bi bi-gear"></i> Configuracoes
            </a>
        </li>
    </ul>

    <div class="mt-auto px-2 small text-secondary">
        Kadosys Platform v1
    </div>
</nav>

<div class="kadosys-content">
    <header class="kadosys-topbar bg-body border-bottom d-flex align-items-center justify-content-between px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="kadosysSidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <?php foreach ($breadcrumb ?? [['label' => 'Dashboard']] as $item): ?>
                        <li class="breadcrumb-item<?= empty($item['url']) ? ' active' : '' ?>">
                            <?php if (!empty($item['url'])): ?>
                                <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($item['label']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="kadosysThemeToggle" title="Alternar tema">
                <i class="bi bi-moon-stars"></i>
            </button>

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($authUserName ?? 'Usuario') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Meu perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= url('/logout') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger">Sair</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="p-4">
        <?= $content ?? '' ?>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var toggleBtn = document.getElementById('kadosysSidebarToggle');
        var sidebar = document.getElementById('kadosysSidebar');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('show');
            });
        }

        var themeToggle = document.getElementById('kadosysThemeToggle');
        var htmlEl = document.documentElement;
        var savedTheme = window.__kadosysTheme || 'light';
        htmlEl.setAttribute('data-bs-theme', savedTheme);

        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var current = htmlEl.getAttribute('data-bs-theme');
                var next = current === 'dark' ? 'light' : 'dark';
                htmlEl.setAttribute('data-bs-theme', next);
                window.__kadosysTheme = next;
            });
        }
    })();
</script>

</body>
</html>
