(function () {
  'use strict';

  var botao = document.querySelector('[data-transpor-acordes]');

  if (!botao) {
    return;
  }

  var selectTom = document.querySelector('[data-tom-select]');
  var letraTextarea = document.getElementById('letra');
  var cifraTextarea = document.getElementById('cifra');
  var spanDe = document.querySelector('[data-tom-de]');

  /**
   * Aviso flutuante chamando atencao pra transposicao que acabou de
   * acontecer - sem isso, a letra/cifra mudava de tom silenciosamente e
   * so quem reparasse na rolagem do texto notaria.
   */
  function mostrarToast(mensagem) {
    var toast = document.createElement('div');
    toast.className = 'kadosys-toast';
    toast.setAttribute('role', 'status');
    toast.textContent = mensagem;
    document.body.appendChild(toast);

    requestAnimationFrame(function () {
      toast.classList.add('is-visivel');
    });

    setTimeout(function () {
      toast.classList.remove('is-visivel');
      setTimeout(function () { toast.remove(); }, 300);
    }, 3500);
  }

  // Mesma grafia usada no <select> de tons (ver Louvor::TONS_MAIORES no
  // PHP, passado aqui via window.KADOSYS_TONS) - reaproveitada como
  // "tabela de saida" da transposicao, pra sempre devolver os acordes
  // com uma grafia consistente (bemol nos tons que normalmente aparecem
  // assim em cifra brasileira), independente de como a cifra colada
  // originalmente grafava as notas (sustenido ou bemol).
  var NOTAS_CANONICAS = (window.KADOSYS_TONS && window.KADOSYS_TONS.maiores) || ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];

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

  // Formato de um "token" de acorde valido: nota (A-G, com sustenido ou
  // bemol), seguida de qualquer combinacao de qualidade/extensao
  // (m, 7, 9, dim, sus4, add9, 7M etc.), extensao entre parenteses
  // opcional (ex.: "(4/9)") e baixo opcional depois de uma barra (ex.:
  // "/B", acorde com inversao) - usado tanto pra decidir se uma LINHA
  // inteira e "so acordes" (todo token bate com isso) quanto, dentro de
  // transporAcorde(), pra separar raiz/resto de cada token.
  var FORMATO_ACORDE = /^[A-G](#|b)?([a-zA-Z0-9]*)(\([^)]*\))?(\/[A-G](#|b)?)?$/;

  function transporNota(nota, semitons) {
    var indice = INDICE_NOTA[nota];

    if (indice === undefined) {
      return nota;
    }

    var novoIndice = ((indice + semitons) % 12 + 12) % 12;

    return NOTAS_CANONICAS[novoIndice];
  }

  /**
   * Transpõe um unico token de acorde (ex.: "Fm7(9)", "Eb/G") -
   * preserva tudo que nao for a nota (qualidade, extensoes,
   * parenteses), so troca a raiz e, se houver, a nota do baixo (acorde
   * com inversao). O baixo so e reconhecido se aparecer bem no FINAL do
   * token (ex.: "G/B") - uma barra no meio de parenteses (ex.: o "4/9"
   * dentro de "Eb7(4/9)") nao e uma inversao, e uma extensao, entao fica
   * de fora dessa checagem.
   */
  function transporAcorde(token, semitons) {
    var mRaiz = token.match(/^[A-G](#|b)?/);

    if (!mRaiz) {
      return token;
    }

    var raiz = mRaiz[0];
    var resto = token.slice(raiz.length);
    var novaRaiz = transporNota(raiz, semitons);

    var mBaixo = resto.match(/\/([A-G])(#|b)?$/);

    if (mBaixo) {
      var baixoOriginal = mBaixo[0].slice(1);
      var novoBaixo = transporNota(baixoOriginal, semitons);
      resto = resto.slice(0, resto.length - mBaixo[0].length) + '/' + novoBaixo;
    }

    return novaRaiz + resto;
  }

  /**
   * Uma linha so e considerada "linha de acordes" se TODOS os tokens
   * nela baterem com FORMATO_ACORDE - mesma convencao que cifras
   * impressas usam (uma linha e inteira de acordes OU inteira de letra,
   * nunca misturada token a token). Isso evita transpor por engano uma
   * palavra comum da letra que por acaso comeca com uma letra de nota
   * (ex.: "Deus" nao bate no formato porque "eus" nao e uma
   * qualidade/extensao valida seguida do resto das regras).
   */
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

  function transporTexto(texto, semitons) {
    return texto.split('\n').map(function (linha) {
      if (!linhaEhDeAcordes(linha)) {
        return linha;
      }

      return linha.replace(/\S+/g, function (token) {
        return transporAcorde(token, semitons);
      });
    }).join('\n');
  }

  function tomParaIndice(tom) {
    if (!tom) {
      return null;
    }

    var raiz = tom.replace(/m$/, '');

    return INDICE_NOTA[raiz] !== undefined ? INDICE_NOTA[raiz] : null;
  }

  function calcularSemitons(tomOriginal, tomNovo) {
    var indiceOriginal = tomParaIndice(tomOriginal);
    var indiceNovo = tomParaIndice(tomNovo);

    if (indiceOriginal === null || indiceNovo === null) {
      return null;
    }

    return ((indiceNovo - indiceOriginal) % 12 + 12) % 12;
  }

  botao.addEventListener('click', function () {
    var tomOriginal = botao.getAttribute('data-tom-original');
    var tomNovo = selectTom ? selectTom.value : '';

    if (!tomOriginal || !tomNovo) {
      window.alert('Selecione o tom atual e o novo tom antes de transpor.');

      return;
    }

    var semitons = calcularSemitons(tomOriginal, tomNovo);

    if (semitons === null) {
      window.alert('Não reconheci um dos tons pra calcular a transposição.');

      return;
    }

    if (semitons === 0) {
      return;
    }

    if (letraTextarea) {
      letraTextarea.value = transporTexto(letraTextarea.value, semitons);
    }

    if (cifraTextarea) {
      cifraTextarea.value = transporTexto(cifraTextarea.value, semitons);
    }

    // Depois de transpor, o "tom de origem" pra um proximo clique passa
    // a ser o tom que acabou de ser aplicado - sem isso, clicar duas
    // vezes seguidas transporia a partir do tom ORIGINAL de novo (dobro
    // do intervalo), em vez de continuar a partir de onde parou.
    botao.setAttribute('data-tom-original', tomNovo);

    if (spanDe) {
      spanDe.textContent = tomNovo;
    }

    mostrarToast('Tom alterado para ' + tomNovo + ' - letra e cifra transpostas.');
  });
})();
