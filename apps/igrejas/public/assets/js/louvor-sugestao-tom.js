(function () {
  'use strict';

  var selectTom = document.querySelector('[data-tom-select]');
  var letraTextarea = document.getElementById('letra');
  var cifraTextarea = document.getElementById('cifra');
  var hintEl = document.querySelector('[data-tom-sugestao-hint]');

  if (!selectTom || (!letraTextarea && !cifraTextarea)) {
    return;
  }

  // So sugere enquanto o proprio usuario nao escolheu um tom na mao -
  // um clique real no select "desliga" a sugestao automatica pro resto
  // da edicao, respeitando a escolha da pessoa.
  var usuarioEscolheuManualmente = selectTom.value !== '';

  selectTom.addEventListener('change', function () {
    usuarioEscolheuManualmente = true;
    if (hintEl) {
      hintEl.hidden = true;
    }
  });

  var NOTAS_MAIORES = (window.KADOSYS_TONS && window.KADOSYS_TONS.maiores) || ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];
  var NOTAS_MENORES = (window.KADOSYS_TONS && window.KADOSYS_TONS.menores) || ['Cm', 'C#m', 'Dm', 'Ebm', 'Em', 'Fm', 'F#m', 'Gm', 'Abm', 'Am', 'Bbm', 'Bm'];

  var INDICE_NOTA = {
    'C': 0, 'B#': 0,
    'C#': 1, 'Db': 1,
    'D': 2,
    'D#': 3, 'Eb': 3,
    'E': 4, 'Fb': 4,
    'F': 5, 'E#': 5,
    'F#': 6, 'Gb': 6,
    'G': 7,
    'G#': 8, 'Ab': 8,
    'A': 9,
    'A#': 10, 'Bb': 10,
    'B': 11, 'Cb': 11,
  };

  // Mesmo formato usado no transpositor (ver louvor-transpositor.js) -
  // reconhece um token de acorde valido (nota + qualidade/extensao
  // opcional) pra decidir se uma linha inteira e "so acordes".
  var FORMATO_ACORDE = /^[A-G](#|b)?([a-zA-Z0-9]*)(\([^)]*\))?(\/[A-G](#|b)?)?$/;

  function linhaEhDeAcordes(linha) {
    var tokens = linha.trim().split(/\s+/).filter(function (token) {
      return token !== '';
    });

    if (tokens.length === 0) {
      return false;
    }

    return tokens.every(function (token) {
      return FORMATO_ACORDE.test(token);
    });
  }

  /**
   * Acha o primeiro token de acorde em qualquer texto colado (letra
   * com cifra junto, ou o campo Cifra separado) - so considera linhas
   * inteiramente feitas de acordes, igual ao transpositor, pra nao
   * confundir uma palavra comum da letra com uma nota musical.
   */
  function primeiroAcorde(texto) {
    if (!texto) {
      return null;
    }

    var linhas = texto.split('\n');

    for (var i = 0; i < linhas.length; i++) {
      if (!linhaEhDeAcordes(linhas[i])) {
        continue;
      }

      var tokens = linhas[i].trim().split(/\s+/).filter(function (token) {
        return token !== '';
      });

      if (tokens.length > 0) {
        return tokens[0];
      }
    }

    return null;
  }

  /**
   * Determina o tom (maior ou menor) a partir de um token de acorde -
   * ex.: "G" -> "G", "Am7" -> "Am", "F#m" -> "F#m". So reconhece "m"
   * como minor quando NAO for seguido de "aj" (pra nao confundir com
   * "maj7", que e maior).
   */
  function tomDoAcorde(token) {
    var mRaiz = token.match(/^[A-G](#|b)?/);

    if (!mRaiz) {
      return null;
    }

    var raiz = mRaiz[0];
    var resto = token.slice(raiz.length);
    var ehMenor = /^m([^a]|$)/.test(resto);

    var indice = INDICE_NOTA[raiz];

    if (indice === undefined) {
      return null;
    }

    return ehMenor ? NOTAS_MENORES[indice] : NOTAS_MAIORES[indice];
  }

  function sugerirTom() {
    if (usuarioEscolheuManualmente || selectTom.value !== '') {
      return;
    }

    var textoParaBuscar = (letraTextarea ? letraTextarea.value : '') + '\n' + (cifraTextarea ? cifraTextarea.value : '');
    var acorde = primeiroAcorde(textoParaBuscar);

    if (!acorde) {
      return;
    }

    var tom = tomDoAcorde(acorde);

    if (!tom) {
      return;
    }

    var opcaoExiste = Array.prototype.some.call(selectTom.options, function (opcao) {
      return opcao.value === tom;
    });

    if (!opcaoExiste) {
      return;
    }

    selectTom.value = tom;

    if (hintEl) {
      hintEl.hidden = false;
      hintEl.textContent = 'Sugerido automaticamente a partir do acorde "' + acorde + '" colado no texto - pode trocar acima.';
    }
  }

  if (letraTextarea) {
    letraTextarea.addEventListener('input', sugerirTom);
  }

  if (cifraTextarea) {
    cifraTextarea.addEventListener('input', sugerirTom);
  }
})();
