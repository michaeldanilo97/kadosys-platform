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
  var previewRef = root.querySelector('[data-preview-ref]');
  var previewTexto = root.querySelector('[data-preview-texto]');
  var previewProximo = root.querySelector('[data-preview-proximo]');
  var previewProximoTexto = root.querySelector('[data-preview-proximo-texto]');
  var botoesNav = root.querySelectorAll('[data-nav-acao]');

  var lastVersao = null;
  var sincronizando = false;

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value;

    return div.innerHTML;
  }

  function enviar(caminho, corpo) {
    return fetch(baseUrl + caminho, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo ? corpo.toString() : '',
    });
  }

  var apiVersao = null;
  var apiLivro = null;
  var apiCapitulo = null;
  var apiVersiculo = null;
  var livroSelect = null;
  var capituloInput = null;
  var versiculoInicioSelect = null;
  var versiculoFimSelect = null;

  if (formBiblia) {
    livroSelect = formBiblia.querySelector('[data-campo="livro_id"]');
    capituloInput = formBiblia.querySelector('[data-campo="capitulo"]');
    versiculoInicioSelect = formBiblia.querySelector('[data-campo="versiculo_inicio"]');
    versiculoFimSelect = formBiblia.querySelector('[data-campo="versiculo_fim"]');

    if (window.KadosysBiblia) {
      apiVersao = window.KadosysBiblia.montarVersaoPills(formBiblia.querySelector('[data-versao-pills]'));
      apiLivro = window.KadosysBiblia.montarComboLivro(formBiblia.querySelector('[data-livro-combo]'));
      apiCapitulo = window.KadosysBiblia.montarCapituloChips(formBiblia);
      apiVersiculo = window.KadosysBiblia.montarVersiculoChips(formBiblia, capituloInfoUrl);
    }

    var projetar = function () {
      if (sincronizando || !livroSelect.value || !capituloInput.value || !versiculoInicioSelect.value) {
        return;
      }

      var dados = new URLSearchParams();
      dados.set('biblia_versao', formBiblia.querySelector('[data-campo="biblia_versao"]').value);
      dados.set('livro_id', livroSelect.value);
      dados.set('capitulo', capituloInput.value);
      dados.set('versiculo_inicio', versiculoInicioSelect.value);

      if (versiculoFimSelect.value) {
        dados.set('versiculo_fim', versiculoFimSelect.value);
      }

      enviar('/biblia', dados).then(function (resposta) {
        return resposta.json();
      }).then(function (dadosResposta) {
        lastVersao = dadosResposta.versao;
        aplicarEstado(dadosResposta);
      }).catch(function () {});
    };

    // Clicar num chip de versiculo ja projeta direto no telao, sem
    // precisar de um botao "Projetar" separado (estilo Holyrics).
    versiculoInicioSelect.addEventListener('change', projetar);
    versiculoFimSelect.addEventListener('change', projetar);
  }

  function sincronizarPicker(biblia) {
    if (!apiVersao || !apiLivro || !apiCapitulo || !apiVersiculo || !biblia.livroId) {
      return;
    }

    sincronizando = true;
    apiVersao.definir(biblia.bibliaVersao);
    apiLivro.definir(biblia.livroId, biblia.livroNome);
    apiCapitulo.definir(biblia.capitulo);
    apiVersiculo.definir(biblia.versiculoInicio, biblia.versiculoFim).then(function () {
      sincronizando = false;
    }).catch(function () {
      sincronizando = false;
    });
  }

  function aplicarEstado(dados) {
    if (!dados || dados.modo !== 'biblia' || !dados.biblia || !dados.biblia.livroId) {
      if (navBox) {
        navBox.hidden = true;
      }

      if (previewRef) {
        previewRef.innerHTML = '<i class="bi bi-broadcast"></i> Nada em projecao';
      }

      if (previewTexto) {
        previewTexto.innerHTML = '<span class="vazio">Escolha versao, livro, capitulo e versiculo acima para comecar.</span>';
      }

      if (previewProximo) {
        previewProximo.hidden = true;
      }

      return;
    }

    var biblia = dados.biblia;
    var referencia = biblia.livroNome + ' ' + biblia.capitulo + ':' + biblia.versiculoInicio;

    if (biblia.versiculoFim && biblia.versiculoFim !== biblia.versiculoInicio) {
      referencia += '-' + biblia.versiculoFim;
    }

    if (biblia.bibliaVersao) {
      referencia += ' · ' + biblia.bibliaVersao.toUpperCase();
    }

    if (navBox) {
      navBox.hidden = false;
    }

    if (previewRef) {
      previewRef.innerHTML = '<i class="bi bi-broadcast"></i> ' + escapeHtml(referencia);
    }

    if (previewTexto) {
      if (biblia.versiculos && biblia.versiculos.length) {
        previewTexto.innerHTML = biblia.versiculos.map(function (versiculo) {
          return '<span class="numero">' + versiculo.numero + '</span> ' + escapeHtml(versiculo.texto) + ' ';
        }).join('');
      } else {
        previewTexto.innerHTML = '<span class="vazio">Texto ainda nao importado para esta versao.</span>';
      }
    }

    if (biblia.proximaPreview && previewProximo) {
      previewProximo.hidden = false;
      previewProximoTexto.textContent = biblia.proximaPreview.livroNome + ' ' + biblia.proximaPreview.capitulo + ':' + biblia.proximaPreview.versiculo + ' — ' + (biblia.proximaPreview.texto || '');
    } else if (previewProximo) {
      previewProximo.hidden = true;
    }

    sincronizarPicker(biblia);
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
