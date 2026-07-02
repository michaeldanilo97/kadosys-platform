(function () {
  'use strict';

  var root = document.querySelector('[data-projecao-controles]');

  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url');
  var baseUrl = pollUrl.replace(/\/estado$/, '');
  var capituloInfoUrl = root.getAttribute('data-capitulo-info-url');
  var formBiblia = root.querySelector('[data-form-biblia]');
  var formVideo = root.querySelector('[data-form-video]');
  var botoesVideo = root.querySelectorAll('[data-video-acao]');
  var botaoLogo = document.querySelector('[data-acao-logo]');
  var botaoLimpar = document.querySelector('[data-acao-limpar]');

  var navBox = root.querySelector('[data-projecao-nav]');
  var navAtualRef = root.querySelector('[data-nav-atual-ref]');
  var navPreviewBox = root.querySelector('[data-nav-preview]');
  var navPreviewRef = root.querySelector('[data-nav-preview-ref]');
  var navPreviewTexto = root.querySelector('[data-nav-preview-texto]');
  var botoesNav = root.querySelectorAll('[data-nav-acao]');

  var lastVersao = null;

  function enviar(caminho, corpo) {
    return fetch(baseUrl + caminho, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo ? corpo.toString() : '',
    });
  }

  function aplicarEstado(dados) {
    if (!dados || dados.modo !== 'biblia' || !dados.biblia || !dados.biblia.livroId) {
      if (navBox) {
        navBox.hidden = true;
      }
      return;
    }

    var biblia = dados.biblia;
    var referencia = biblia.livroNome + ' ' + biblia.capitulo + ':' + biblia.versiculoInicio;

    if (biblia.versiculoFim && biblia.versiculoFim !== biblia.versiculoInicio) {
      referencia += '-' + biblia.versiculoFim;
    }

    if (navBox) {
      navBox.hidden = false;
    }

    if (navAtualRef) {
      navAtualRef.textContent = referencia;
    }

    if (biblia.proximaPreview && navPreviewBox) {
      navPreviewBox.hidden = false;
      navPreviewRef.textContent = biblia.proximaPreview.livroNome + ' ' + biblia.proximaPreview.capitulo + ':' + biblia.proximaPreview.versiculo;
      navPreviewTexto.textContent = biblia.proximaPreview.texto || '';
    } else if (navPreviewBox) {
      navPreviewBox.hidden = true;
    }
  }

  function poll() {
    fetch(pollUrl, { cache: 'no-store' })
      .then(function (resposta) {
        return resposta.json();
      })
      .then(function (dados) {
        if (dados.versao === lastVersao) {
          return;
        }

        lastVersao = dados.versao;
        aplicarEstado(dados);
      })
      .catch(function () {});
  }

  if (formBiblia) {
    var livroSelect = formBiblia.querySelector('[data-campo="livro_id"]');
    var capituloInput = formBiblia.querySelector('[data-campo="capitulo"]');

    if (window.KadosysBiblia) {
      window.KadosysBiblia.montarComboLivro(formBiblia.querySelector('[data-livro-combo]'));
      window.KadosysBiblia.montarCapitulo(formBiblia);
      window.KadosysBiblia.montarVersiculos(formBiblia, capituloInfoUrl);
    }

    formBiblia.addEventListener('submit', function (evento) {
      evento.preventDefault();

      var dados = new URLSearchParams();
      dados.set('biblia_versao', formBiblia.querySelector('[data-campo="biblia_versao"]').value);
      dados.set('livro_id', livroSelect.value);
      dados.set('capitulo', capituloInput.value);
      dados.set('versiculo_inicio', formBiblia.querySelector('[data-campo="versiculo_inicio"]').value);

      var fim = formBiblia.querySelector('[data-campo="versiculo_fim"]').value;
      if (fim) {
        dados.set('versiculo_fim', fim);
      }

      enviar('/biblia', dados).then(function (resposta) {
        return resposta.json();
      }).then(function (dadosResposta) {
        lastVersao = dadosResposta.versao;
        aplicarEstado(dadosResposta);
      }).catch(function () {});
    });
  }

  function navegar(direcao) {
    var dados = new URLSearchParams();
    dados.set('direcao', direcao);

    enviar('/biblia/navegar', dados).then(function (resposta) {
      return resposta.json();
    }).then(function (dadosResposta) {
      if (dadosResposta.erro) {
        return;
      }

      lastVersao = dadosResposta.versao;
      aplicarEstado(dadosResposta);
    }).catch(function () {});
  }

  botoesNav.forEach(function (botao) {
    botao.addEventListener('click', function () {
      navegar(botao.getAttribute('data-nav-acao'));
    });
  });

  document.addEventListener('keydown', function (evento) {
    var alvo = evento.target;
    var estaDigitando = alvo.tagName === 'INPUT' || alvo.tagName === 'SELECT' || alvo.tagName === 'TEXTAREA';

    if (estaDigitando || navBox === null || navBox.hidden) {
      return;
    }

    if (evento.key === 'ArrowRight') {
      evento.preventDefault();
      navegar('proximo');
    } else if (evento.key === 'ArrowLeft') {
      evento.preventDefault();
      navegar('anterior');
    }
  });

  if (formVideo) {
    formVideo.addEventListener('submit', function (evento) {
      evento.preventDefault();

      var url = formVideo.querySelector('#video_url').value;
      var dados = new URLSearchParams();
      dados.set('url', url);

      enviar('/video', dados);
    });
  }

  botoesVideo.forEach(function (botao) {
    botao.addEventListener('click', function () {
      var dados = new URLSearchParams();
      dados.set('estado', botao.getAttribute('data-video-acao'));

      enviar('/video/estado', dados).then(function () {
        botoesVideo.forEach(function (b) {
          b.classList.remove('active');
        });
        botao.classList.add('active');
      });
    });
  });

  if (botaoLogo) {
    botaoLogo.addEventListener('click', function () {
      enviar('/logo');
    });
  }

  if (botaoLimpar) {
    botaoLimpar.addEventListener('click', function () {
      enviar('/limpar');
    });
  }

  setInterval(poll, 1500);
  poll();
})();
