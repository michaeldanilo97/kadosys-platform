(function () {
    'use strict';

    var THEME_STORAGE_KEY = 'kadosys_barbearias_theme';

    document.addEventListener('DOMContentLoaded', function () {
        initTheme();
        initSidebar();
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
            icone.textContent = tema === 'light' ? '🌙' : '☀️';
        }
    }

    function initSidebar() {
        var sidebar = document.querySelector('[data-sidebar]');
        var overlay = document.querySelector('[data-sidebar-overlay]');
        var toggleBtn = document.querySelector('[data-sidebar-toggle]');

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
    }

    // PWA: registra o service worker (so cacheia CSS/JS/icones, nunca
    // paginas - ver public/sw.js) pra permitir instalar o painel como
    // app e abrir mais rapido em conexao ruim.
    if ('serviceWorker' in navigator) {
        var basePath = document.body.getAttribute('data-base-path') || '';
        navigator.serviceWorker.register(basePath + '/sw.js').catch(function () {});
    }
})();
