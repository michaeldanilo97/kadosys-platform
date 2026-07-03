(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initPermissoesGrid();
  });

  // Mesmo padrao visual do initPlanoEscolha() do cadastro.js (classe
  // .selecionado controlada por JS) - mas com checkbox em vez de
  // radio, entao cada card alterna independente dos outros.
  function initPermissoesGrid() {
    var cartoes = document.querySelectorAll('[data-permissao-card]');

    if (!cartoes.length) {
      return;
    }

    cartoes.forEach(function (cartao) {
      var checkbox = cartao.querySelector('input[type="checkbox"]');

      if (!checkbox) {
        return;
      }

      function atualizar() {
        cartao.classList.toggle('selecionado', checkbox.checked);
      }

      checkbox.addEventListener('change', atualizar);
      atualizar();
    });
  }
})();
