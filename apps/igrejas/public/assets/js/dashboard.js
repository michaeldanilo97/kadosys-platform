(function () {
  'use strict';

  var THEME_STORAGE_KEY = 'kadosys_igrejas_theme';

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initSidebar();
    initSearchShortcut();
    initConfirmForms();
    initTopbarDropdowns();
    initCopiarLink();
    initPixMensagemForm();
    initCultoRecorrencia();
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
    var collapseBtn = document.querySelector('[data-sidebar-collapse]');
    var shell = document.querySelector('.dash-shell');
    var COLLAPSE_STORAGE_KEY = 'kadosys_igrejas_sidebar_collapsed';

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

    // Restore collapsed state from localStorage
    if (collapseBtn && shell) {
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
        // Popup proprio no lugar do window.confirm() nativo. Sempre
        // bloqueia o envio primeiro (o popup e assincrono) e reenvia por
        // codigo se o usuario confirmar - form.submit() programatico nao
        // dispara o evento "submit" de novo, entao nao entra em loop.
        event.preventDefault();

        if (!window.KadosysModal) {
          form.submit(); // fallback improvavel: script do modal nao carregou

          return;
        }

        // Rotulo "perigo" (vermelho) porque todo data-confirm do sistema
        // guarda uma acao com consequencia (remover, encerrar, trocar
        // plano) - o texto do botao fica generico porque nem todas sao
        // remocoes.
        window.KadosysModal.confirmar(form.getAttribute('data-confirm'), { perigo: true }).then(function (ok) {
          if (ok) {
            form.submit();
          }
        });
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

  /**
   * Mensagem da tela de Pix (Configuracoes): mostra so o bloco de
   * campos (texto livre ou versiculo biblico) que corresponde ao tipo
   * selecionado nos radios - os outros ficam no DOM (hidden), sem
   * precisar de nenhuma chamada ao servidor so pra alternar a tela.
   */
  function initPixMensagemForm() {
    var form = document.querySelector('[data-pix-mensagem-form]');

    if (!form) {
      return;
    }

    var radios = form.querySelectorAll('[data-pix-mensagem-tipo]');
    var blocos = form.querySelectorAll('[data-pix-mensagem-bloco]');

    function atualizar() {
      var selecionado = form.querySelector('[data-pix-mensagem-tipo]:checked');
      var tipo = selecionado ? selecionado.value : 'nenhuma';

      blocos.forEach(function (bloco) {
        bloco.hidden = bloco.getAttribute('data-pix-mensagem-bloco') !== tipo;
      });
    }

    radios.forEach(function (radio) {
      radio.addEventListener('change', atualizar);
    });

    atualizar();
  }

  /**
   * Cadastro de culto: o campo "Repetir ate" so faz sentido quando
   * "Toda semana" esta selecionado no select de recorrencia.
   */
  function initCultoRecorrencia() {
    var select = document.querySelector('[data-culto-recorrencia]');
    var wrap = document.querySelector('[data-culto-repetir-ate-wrap]');

    if (!select || !wrap) {
      return;
    }

    function atualizar() {
      wrap.hidden = select.value !== 'semanal';
    }

    select.addEventListener('change', atualizar);
    atualizar();
  }

  /**
   * Botao generico de "copiar" um campo readonly pro clipboard - usado
   * hoje no link publico de doacao (Configuracoes), mas serve pra
   * qualquer campo futuro que precise disso: data-copiar-link aponta
   * pro id do input com o texto a copiar.
   */
  function initCopiarLink() {
    var botoes = document.querySelectorAll('[data-copiar-link]');

    botoes.forEach(function (botao) {
      var input = document.getElementById(botao.getAttribute('data-copiar-link'));

      if (!input) {
        return;
      }

      botao.addEventListener('click', function () {
        copiarTexto(input.value).then(function () {
          var iconeOriginal = botao.innerHTML;
          botao.classList.add('copiado');
          botao.innerHTML = '<i class="bi bi-check2"></i>';

          setTimeout(function () {
            botao.classList.remove('copiado');
            botao.innerHTML = iconeOriginal;
          }, 1800);
        }).catch(function () {
          input.select();
        });
      });
    });
  }

  function copiarTexto(texto) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(texto);
    }

    return new Promise(function (resolve, reject) {
      var campoTemp = document.createElement('textarea');
      campoTemp.value = texto;
      campoTemp.style.position = 'fixed';
      campoTemp.style.opacity = '0';
      document.body.appendChild(campoTemp);
      campoTemp.select();

      try {
        document.execCommand('copy');
        resolve();
      } catch (erro) {
        reject(erro);
      } finally {
        document.body.removeChild(campoTemp);
      }
    });
  }
})();
