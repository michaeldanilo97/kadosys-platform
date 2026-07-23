(function () {
    'use strict';

    var THEME_STORAGE_KEY = 'kadosys_food_theme';
    var COLLAPSE_STORAGE_KEY = 'kadosys_food_sidebar_collapsed';

    document.addEventListener('DOMContentLoaded', function () {
        initTheme();
        initSidebar();
        initTopbarDropdowns();
        initConfirmForms();
    });

    function initTheme() {
        var toggleBtn = document.querySelector('[data-theme-toggle]');

        if (!toggleBtn) {
            return;
        }

        atualizarIcone(document.documentElement.dataset.theme === 'light' ? 'light' : 'dark');

        toggleBtn.addEventListener('click', function () {
            var atual = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
            var novo = atual === 'light' ? 'dark' : 'light';

            aplicarTema(novo);

            try {
                window.localStorage.setItem(THEME_STORAGE_KEY, novo);
            } catch (error) {
                // Armazenamento indisponivel (modo privado, etc.) - ignora.
            }
        });
    }

    function aplicarTema(tema) {
        if (tema === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        atualizarIcone(tema);
    }

    function atualizarIcone(tema) {
        var icone = document.querySelector('[data-theme-icon]');

        if (icone) {
            icone.className = tema === 'light' ? 'bi bi-moon-stars' : 'bi bi-sun';
        }
    }

    function initSidebar() {
        var sidebar = document.querySelector('[data-sidebar]');
        var overlay = document.querySelector('[data-sidebar-overlay]');
        var toggleBtn = document.querySelector('[data-sidebar-toggle]');
        var collapseBtn = document.querySelector('[data-sidebar-collapse]');
        var shell = document.querySelector('.dash-shell');

        function fechar() {
            sidebar.classList.remove('aberta');
            overlay.classList.remove('aberta');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('aberta');
                overlay.classList.toggle('aberta');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', fechar);
        }

        // Menu recolhivel (desktop): encolhe a sidebar pra so mostrar os
        // icones. Independente do drawer mobile acima (".aberta") - so
        // faz sentido em telas grandes, onde a sidebar fica sempre visivel.
        if (collapseBtn && sidebar && shell) {
            var isCollapsed = false;

            try {
                isCollapsed = window.localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1';
            } catch (error) {
                isCollapsed = false;
            }

            if (isCollapsed) {
                sidebar.classList.add('is-collapsed');
                shell.classList.add('sidebar-collapsed');
            }

            collapseBtn.addEventListener('click', function () {
                var willBeCollapsed = !sidebar.classList.contains('is-collapsed');
                sidebar.classList.toggle('is-collapsed');
                shell.classList.toggle('sidebar-collapsed');

                try {
                    window.localStorage.setItem(COLLAPSE_STORAGE_KEY, willBeCollapsed ? '1' : '0');
                } catch (error) {
                    // Armazenamento indisponivel; ignora.
                }
            });
        }
    }

    // Sino de avisos da plataforma no rodape da sidebar: botao + painel
    // escondido por "hidden", fecha ao clicar fora ou apertar Esc.
    function initTopbarDropdowns() {
        var dropdowns = document.querySelectorAll('[data-topbar-dropdown]');

        if (dropdowns.length === 0) {
            return;
        }

        function fecharTodos(exceto) {
            dropdowns.forEach(function (dropdown) {
                if (dropdown === exceto) {
                    return;
                }

                var panel = dropdown.querySelector('[data-dropdown-panel]');
                var toggle = dropdown.querySelector('[data-dropdown-toggle]');

                if (panel) {
                    panel.hidden = true;
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        dropdowns.forEach(function (dropdown) {
            var toggle = dropdown.querySelector('[data-dropdown-toggle]');
            var panel = dropdown.querySelector('[data-dropdown-panel]');

            if (!toggle || !panel) {
                return;
            }

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();

                var abrindo = panel.hidden;
                fecharTodos(dropdown);
                panel.hidden = !abrindo;
                toggle.setAttribute('aria-expanded', abrindo ? 'true' : 'false');
            });
        });

        document.addEventListener('click', function () {
            fecharTodos(null);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                fecharTodos(null);
            }
        });
    }

    // Popup proprio (kadosys-modal.js) no lugar do window.confirm() nativo
    // em toda form marcada com data-confirm="mensagem" - form.submit()
    // programatico nao dispara "submit" de novo, entao nao entra em loop.
    function initConfirmForms() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!window.KadosysModal) {
                    form.submit();

                    return;
                }

                window.KadosysModal.confirmar(form.getAttribute('data-confirm'), { perigo: true }).then(function (ok) {
                    if (ok) {
                        form.submit();
                    }
                });
            });
        });
    }

    // PWA: registra o service worker (so cacheia CSS/JS/icones, nunca
    // paginas - ver public/sw.js) pra permitir instalar o painel como
    // app e abrir mais rapido em conexao ruim.
    if ('serviceWorker' in navigator) {
        var basePath = document.body.getAttribute('data-base-path') || '';
        navigator.serviceWorker.register(basePath + '/sw.js').catch(function () {});
    }
})();
