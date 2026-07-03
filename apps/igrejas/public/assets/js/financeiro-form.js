(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initFiltroCategoriaPorTipo();
  });

  // Cada <option> de categoria tem um data-tipo (entrada/saida) - so
  // mostra as categorias do tipo escolhido, pra evitar vincular um
  // lancamento de saida a uma categoria de entrada (ou vice-versa).
  function initFiltroCategoriaPorTipo() {
    var tipoSelect = document.querySelector('[data-financeiro-tipo]');
    var categoriaSelect = document.querySelector('[data-financeiro-categoria]');

    if (!tipoSelect || !categoriaSelect) {
      return;
    }

    var opcoes = categoriaSelect.options;

    function atualizar() {
      var tipo = tipoSelect.value;
      var categoriaSelecionadaAindaVisivel = false;
      var i;

      for (i = 0; i < opcoes.length; i++) {
        if (!opcoes[i].dataset.tipo) {
          // "Sem categoria" - sempre visivel.
          continue;
        }

        var visivel = opcoes[i].dataset.tipo === tipo;
        opcoes[i].hidden = !visivel;

        if (visivel && opcoes[i].selected) {
          categoriaSelecionadaAindaVisivel = true;
        }
      }

      if (categoriaSelect.value !== '' && !categoriaSelecionadaAindaVisivel) {
        for (i = 0; i < opcoes.length; i++) {
          if (opcoes[i].value === categoriaSelect.value && opcoes[i].dataset.tipo && opcoes[i].dataset.tipo !== tipo) {
            categoriaSelect.value = '';
            break;
          }
        }
      }
    }

    tipoSelect.addEventListener('change', atualizar);
    atualizar();
  }
})();
