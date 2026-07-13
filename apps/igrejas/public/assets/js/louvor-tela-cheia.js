(function () {
  'use strict';

  var root = document.querySelector('[data-lct-root]');

  if (!root) {
    return;
  }

  var textoLetra = root.querySelector('[data-lct-texto="letra"]');
  var textoCifra = root.querySelector('[data-lct-texto="cifra"]');
  var abaLetra = root.querySelector('[data-lct-aba="letra"]');
  var abaCifra = root.querySelector('[data-lct-aba="cifra"]');
  var botaoFonteMais = root.querySelector('[data-lct-fonte-mais]');
  var botaoFonteMenos = root.querySelector('[data-lct-fonte-menos]');
  var botaoAutoScroll = root.querySelector('[data-lct-autoscroll]');
  var sliderVelocidade = root.querySelector('[data-lct-velocidade]');
  var botaoTelaCheia = root.querySelector('[data-lct-fullscreen]');
  var botaoPdf = root.querySelector('[data-lct-pdf]');

  function mostrarAba(aba) {
    var ehCifra = aba === 'cifra';

    if (textoLetra) {
      textoLetra.hidden = ehCifra;
    }
    if (textoCifra) {
      textoCifra.hidden = !ehCifra;
    }
    if (abaLetra) {
      abaLetra.classList.toggle('is-ativa', !ehCifra);
    }
    if (abaCifra) {
      abaCifra.classList.toggle('is-ativa', ehCifra);
    }
  }

  if (abaLetra) {
    abaLetra.addEventListener('click', function () {
      mostrarAba('letra');
    });
  }

  if (abaCifra) {
    abaCifra.addEventListener('click', function () {
      mostrarAba('cifra');
    });
  }

  // Tamanho de fonte compartilhado entre letra/cifra (variavel CSS
  // --lct-font-size, ver louvor-tela-cheia.css) - facilita ler de longe
  // numa TV/tablet apoiado no estante de partitura.
  var TAMANHO_MIN = 0.9;
  var TAMANHO_MAX = 3;
  var tamanhoAtual = 1.4;

  function aplicarTamanhoFonte() {
    document.documentElement.style.setProperty('--lct-font-size', tamanhoAtual + 'rem');
  }

  if (botaoFonteMais) {
    botaoFonteMais.addEventListener('click', function () {
      tamanhoAtual = Math.min(TAMANHO_MAX, tamanhoAtual + 0.15);
      aplicarTamanhoFonte();
    });
  }

  if (botaoFonteMenos) {
    botaoFonteMenos.addEventListener('click', function () {
      tamanhoAtual = Math.max(TAMANHO_MIN, tamanhoAtual - 0.15);
      aplicarTamanhoFonte();
    });
  }

  // Auto-scroll: desce a pagina sozinha num ritmo constante, pro musico
  // nao precisar tirar a mao do instrumento pra rolar a tela enquanto
  // toca. Velocidade em pixels por "tick" (100ms) - controlada pelo
  // slider, guardada mesmo com o auto-scroll pausado.
  var autoScrollTimer = null;
  var velocidade = sliderVelocidade ? parseInt(sliderVelocidade.value, 10) : 2;

  function alternarAutoScroll() {
    if (autoScrollTimer) {
      clearInterval(autoScrollTimer);
      autoScrollTimer = null;
      if (botaoAutoScroll) {
        botaoAutoScroll.classList.remove('is-ativo');
        botaoAutoScroll.innerHTML = '<i class="bi bi-play-fill"></i> Auto-scroll';
      }

      return;
    }

    autoScrollTimer = setInterval(function () {
      window.scrollBy(0, velocidade);

      // Chegou ao fim da pagina - para sozinho, sem ficar tentando
      // rolar pra sempre depois do fim do texto.
      var chegouAoFim = window.innerHeight + window.scrollY >= document.body.scrollHeight - 2;
      if (chegouAoFim) {
        alternarAutoScroll();
      }
    }, 100);

    if (botaoAutoScroll) {
      botaoAutoScroll.classList.add('is-ativo');
      botaoAutoScroll.innerHTML = '<i class="bi bi-pause-fill"></i> Auto-scroll';
    }
  }

  if (botaoAutoScroll) {
    botaoAutoScroll.addEventListener('click', alternarAutoScroll);
  }

  if (sliderVelocidade) {
    sliderVelocidade.addEventListener('input', function () {
      velocidade = parseInt(sliderVelocidade.value, 10);
    });
  }

  if (botaoTelaCheia) {
    botaoTelaCheia.addEventListener('click', function () {
      var elemento = document.documentElement;
      var pedido = elemento.requestFullscreen || elemento.webkitRequestFullscreen;
      var saida = document.exitFullscreen || document.webkitExitFullscreen;
      var estaEmTelaCheia = document.fullscreenElement || document.webkitFullscreenElement;

      try {
        if (!estaEmTelaCheia && pedido) {
          var resultado = pedido.call(elemento);
          if (resultado && typeof resultado.catch === 'function') {
            resultado.catch(function () {});
          }
        } else if (estaEmTelaCheia && saida) {
          saida.call(document);
        }
      } catch (erro) {
        // Navegador sem suporte a fullscreen ou chamada bloqueada; ignora.
      }
    });
  }

  if (botaoPdf) {
    botaoPdf.addEventListener('click', function () {
      window.print();
    });
  }
})();
