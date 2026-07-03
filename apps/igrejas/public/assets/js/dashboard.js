(function () {
  'use strict';

  var THEME_STORAGE_KEY = 'kadosys_igrejas_theme';

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initSidebar();
    initSearchShortcut();
    initConfirmForms();
    initTopbarDropdowns();
  });

  function initTheme() {
    var body = document.body;
    var toggleBtn = document.querySelector('[data-theme-toggle]');
    var storedTheme = null;

    try {
      storedTheme = window.localStorage.getItem(THEME_STORAGE_KEY);
    } catch (error) {
      storedTheme = null;
    }

    // O tema padrao do painel e o claro.
    var theme = storedTheme === 'dark' ? 'dark' : 'light';
    applyTheme(body, theme);

    if (!toggleBtn) {
      return;
    }

    toggleBtn.addEventListener('click', function () {
      var current = body.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
      var next = current === 'light' ? 'dark' : 'light';

      applyTheme(body, next);

      try {
        window.localStorage.setItem(THEME_STORAGE_KEY, next);
      } catch (error) {
        // Armazenamento indisponivel (modo privado, etc.); ignora.
      }
    });
  }

  function applyTheme(body, theme) {
    if (theme === 'light') {
      body.setAttribute('data-theme', 'light');
    } else {
      body.removeAttribute('data-theme');
    }

    var icon = document.querySelector('[data-theme-icon]');
    if (icon) {
      icon.className = theme === 'light' ? 'bi bi-moon-stars' : 'bi bi-sun';
    }
  }

  function initSidebar() {
    var sidebar = document.querySelector('[data-dash-sidebar]');
    var overlay = document.querySelector('[data-sidebar-overlay]');
    var openBtn = document.querySelector('[data-sidebar-open]');

    if (!sidebar || !overlay || !openBtn) {
      return;
    }

    function open() {
      sidebar.classList.add('show');
      overlay.classList.add('show');
    }

    function close() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    }

    openBtn.addEventListener('click', open);
    overlay.addEventListener('click', close);
  }

  function initSearchShortcut() {
    var input = document.querySelector('.dash-topbar-search input');

    if (!input) {
      return;
    }

    document.addEventListener('keydown', function (event) {
      var target = event.target;
      var isTyping = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable;

      if (event.key === '/' && !isTyping) {
        event.preventDefault();
        input.focus();
      }
    });
  }

  function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!window.confirm(form.getAttribute('data-confirm'))) {
          event.preventDefault();
        }
      });
    });
  }

  // Sino de notificacoes e menu do usuario no topo do painel: os dois
  // seguem o mesmo padrao (botao + painel escondido por "hidden"), so
  // um aberto por vez.
  function initTopbarDropdowns() {
    var dropdowns = document.querySelectorAll('[data-topbar-dropdown]');

    if (dropdowns.length === 0) {
      return;
    }

    function closeAll(except) {
      dropdowns.forEach(function (dropdown) {
        if (dropdown === except) {
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
        closeAll(dropdown);
        panel.hidden = !abrindo;
        toggle.setAttribute('aria-expanded', abrindo ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function () {
      closeAll(null);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeAll(null);
      }
    });
  }
})();
