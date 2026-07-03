(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initSlugSugerido();
    initPlanoEscolha();
  });

  function normalizarSlug(valor) {
    return valor
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function initSlugSugerido() {
    var nomeInput = document.querySelector('[data-cadastro-nome-igreja]');
    var slugInput = document.querySelector('[data-cadastro-slug]');

    if (!nomeInput || !slugInput) {
      return;
    }

    // So sugere automaticamente enquanto o usuario nao mexeu no campo de
    // slug na mao - depois que ele edita, respeita a escolha dele.
    var slugEditadoManualmente = slugInput.value.trim() !== '';

    slugInput.addEventListener('input', function () {
      slugEditadoManualmente = true;
    });

    nomeInput.addEventListener('input', function () {
      if (slugEditadoManualmente) {
        return;
      }

      slugInput.value = normalizarSlug(nomeInput.value);
    });

    slugInput.addEventListener('blur', function () {
      slugInput.value = normalizarSlug(slugInput.value);
    });
  }

  function initPlanoEscolha() {
    var cartoes = document.querySelectorAll('[data-plano-card]');

    if (!cartoes.length) {
      return;
    }

    function atualizar() {
      cartoes.forEach(function (cartao) {
        var radio = cartao.querySelector('input[type="radio"]');
        cartao.classList.toggle('selecionado', !!(radio && radio.checked));
      });
    }

    cartoes.forEach(function (cartao) {
      var radio = cartao.querySelector('input[type="radio"]');
      if (radio) {
        radio.addEventListener('change', atualizar);
      }
    });

    atualizar();
  }
})();
