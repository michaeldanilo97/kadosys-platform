(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initPermissoesGrid();
  });

  // Mesmo padrao visual do initPlanoEscolha() do cadastro.js (classe
  // .selecionado controlada por JS) - cada card tem 3 radios (sem
  // acesso/visualizar/editar); "selecionado" quando qualquer opcao
  // diferente de "sem acesso" (value vazio) estiver marcada.
  function initPermissoesGrid() {
    var cartoes = document.querySelectorAll('[data-permissao-card]');

    if (!cartoes.length) {
      return;
    }

    cartoes.forEach(function (cartao) {
      var radios = cartao.querySelectorAll('input[type="radio"]');

      if (!radios.length) {
        return;
      }

      function atualizar() {
        var marcado = cartao.querySelector('input[type="radio"]:checked');
        cartao.classList.toggle('selecionado', !!marcado && marcado.value !== '');
      }

      radios.forEach(function (radio) {
        radio.addEventListener('change', atualizar);
      });
      atualizar();
    });
  }
})();
