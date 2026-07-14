(function () {
  'use strict';

  var criarAcesso = document.querySelector('[data-criar-acesso]');
  var campos = document.querySelectorAll('[data-acesso-campo]');

  if (!criarAcesso || campos.length === 0) {
    return;
  }

  criarAcesso.addEventListener('change', function () {
    campos.forEach(function (campo) {
      campo.hidden = !criarAcesso.checked;
    });
  });
})();
