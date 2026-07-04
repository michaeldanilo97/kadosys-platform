(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initSlugSugerido();
    initPlanoEscolha();
    initDocumentoTipo();
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

  function initDocumentoTipo() {
    var radios = document.querySelectorAll('[data-documento-tipo]');
    var input = document.querySelector('[data-documento-input]');
    var label = document.querySelector('[data-documento-label]');
    var razaoField = document.querySelector('[data-razao-social-field]');
    var razaoInput = document.querySelector('[data-razao-social-input]');

    if (!radios.length || !input) {
      return;
    }

    function tipoSelecionado() {
      var checked = document.querySelector('[data-documento-tipo]:checked');
      return checked ? checked.value : 'cpf';
    }

    function aplicarMascara(valor, tipo) {
      var digitos = valor.replace(/\D/g, '').slice(0, tipo === 'cnpj' ? 14 : 11);

      if (tipo === 'cnpj') {
        return digitos
          .replace(/^(\d{2})(\d)/, '$1.$2')
          .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
          .replace(/\.(\d{3})(\d)/, '.$1/$2')
          .replace(/(\d{4})(\d)/, '$1-$2');
      }

      return digitos
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function atualizar() {
      var tipo = tipoSelecionado();

      if (label) {
        label.textContent = tipo === 'cnpj' ? 'CNPJ' : 'CPF';
      }

      input.placeholder = tipo === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00';
      input.value = aplicarMascara(input.value, tipo);

      if (razaoField) {
        razaoField.hidden = tipo !== 'cnpj';
      }

      if (razaoInput) {
        razaoInput.required = tipo === 'cnpj';
      }
    }

    radios.forEach(function (radio) {
      radio.addEventListener('change', atualizar);
    });

    input.addEventListener('input', function () {
      input.value = aplicarMascara(input.value, tipoSelecionado());
    });

    atualizar();
  }
})();
