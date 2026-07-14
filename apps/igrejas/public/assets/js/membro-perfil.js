(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
  });

  function initTabs() {
    var botoes = document.querySelectorAll('[data-tab-btn]');
    var paineis = document.querySelectorAll('[data-tab-panel]');

    if (!botoes.length || !paineis.length) {
      return;
    }

    function ativar(nomeAba) {
      document.querySelectorAll('.member-tab[data-tab-btn]').forEach(function (botao) {
        botao.classList.toggle('is-active', botao.getAttribute('data-tab-btn') === nomeAba);
      });

      paineis.forEach(function (painel) {
        painel.classList.toggle('is-active', painel.getAttribute('data-tab-panel') === nomeAba);
      });
    }

    botoes.forEach(function (botao) {
      botao.addEventListener('click', function () {
        ativar(botao.getAttribute('data-tab-btn'));
      });
    });
  }
})();
