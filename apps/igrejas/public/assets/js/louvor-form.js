(function () {
  'use strict';

  var radios = document.querySelectorAll('[data-modo-cadastro]');

  if (!radios.length) {
    return;
  }

  var blocoCifra = document.querySelector('[data-bloco-cifra]');
  var letraLabel = document.querySelector('[data-letra-label]');
  var letraHint = document.querySelector('[data-letra-hint]');
  var letraTextarea = document.getElementById('letra');

  var TEXTOS = {
    juntas: {
      label: 'Letra e cifra',
      placeholder: 'Cole aqui a letra com a cifra junto, como vem do Cifra Club - uma linha de acordes em cima de cada linha da letra',
      hint: 'O transpositor abaixo reconhece as linhas que são só acordes (mesmo misturadas com a letra) e transpõe elas ao trocar de tom.',
    },
    separadas: {
      label: 'Letra',
      placeholder: 'Cole aqui a letra completa do louvor (sem os acordes)',
      hint: 'A cifra fica no campo separado logo abaixo.',
    },
  };

  function aplicarModo(modo) {
    var textos = TEXTOS[modo] || TEXTOS.juntas;

    if (blocoCifra) {
      blocoCifra.hidden = modo === 'juntas';
    }

    if (letraLabel) {
      letraLabel.textContent = textos.label;
    }

    if (letraHint) {
      letraHint.textContent = textos.hint;
    }

    if (letraTextarea) {
      letraTextarea.setAttribute('placeholder', textos.placeholder);
    }
  }

  radios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      if (radio.checked) {
        aplicarModo(radio.value);
      }
    });
  });

  var selecionado = document.querySelector('[data-modo-cadastro]:checked');
  aplicarModo(selecionado ? selecionado.value : 'juntas');
})();
