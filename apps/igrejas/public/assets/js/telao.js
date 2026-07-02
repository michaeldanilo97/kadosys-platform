(function () {
  'use strict';

  var root = document.querySelector('[data-telao]');

  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url');
  var layers = {
    blank: root.querySelector('[data-telao-layer="blank"]'),
    video: root.querySelector('[data-telao-layer="video"]'),
    biblia: root.querySelector('[data-telao-layer="biblia"]'),
    logo: root.querySelector('[data-telao-layer="logo"]'),
  };
  var bibliaTexto = root.querySelector('[data-telao-biblia-texto]');
  var bibliaRef = root.querySelector('[data-telao-biblia-ref]');

  var lastVersao = null;
  var player = null;
  var ytReady = false;
  var currentVideoId = null;
  var pendingVideo = null;
  var pendingVideoId = null;

  function extrairIdYoutube(url) {
    if (!url) {
      return null;
    }

    var match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);

    return match ? match[1] : null;
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;

    return div.innerHTML;
  }

  function mostrarSomente(nomesVisiveis) {
    Object.keys(layers).forEach(function (nome) {
      var camada = layers[nome];

      if (!camada) {
        return;
      }

      camada.classList.toggle('is-visible', nomesVisiveis.indexOf(nome) !== -1);
    });
  }

  function renderBiblia(biblia) {
    if (!biblia || !biblia.versiculos || !biblia.versiculos.length) {
      bibliaTexto.innerHTML = '';
      bibliaRef.textContent = '';

      return;
    }

    bibliaTexto.innerHTML = biblia.versiculos.map(function (versiculo) {
      return '<span class="numero">' + versiculo.numero + '</span>' + escapeHtml(versiculo.texto) + ' ';
    }).join('');

    var referencia = biblia.livroNome + ' ' + biblia.capitulo + ':' + biblia.versiculoInicio;

    if (biblia.versiculoFim && biblia.versiculoFim !== biblia.versiculoInicio) {
      referencia += '-' + biblia.versiculoFim;
    }

    bibliaRef.textContent = referencia;
  }

  function aplicarVideo(video) {
    var videoId = extrairIdYoutube(video.url);

    if (!ytReady) {
      pendingVideo = video;
      pendingVideoId = videoId;

      return;
    }

    if (videoId && videoId !== currentVideoId) {
      currentVideoId = videoId;

      if (player) {
        player.loadVideoById(videoId);
      }
    }

    if (!player) {
      return;
    }

    if (video.estado === 'pausado' || video.estado === 'fadeout') {
      try {
        player.pauseVideo();
      } catch (erro) {
        // Player ainda nao pronto; sera reaplicado no proximo poll.
      }
    } else if (video.estado === 'tocando') {
      try {
        player.playVideo();
      } catch (erro) {
        // Player ainda nao pronto; sera reaplicado no proximo poll.
      }
    }
  }

  function aplicarEstado(estado) {
    if (!estado || estado.ativo === false) {
      mostrarSomente(['blank']);

      return;
    }

    if (estado.modo === 'blank') {
      mostrarSomente(['blank']);
    } else if (estado.modo === 'biblia') {
      renderBiblia(estado.biblia);
      mostrarSomente(['biblia']);
    } else if (estado.modo === 'video') {
      aplicarVideo(estado.video);
      var camadasVideo = ['video'];

      if (estado.video.estado === 'fadeout') {
        camadasVideo.push('logo');
      }

      mostrarSomente(camadasVideo);
    } else if (estado.modo === 'logo') {
      mostrarSomente(['logo']);
    }
  }

  window.onYouTubeIframeAPIReady = function () {
    ytReady = true;

    player = new YT.Player('telao-player', {
      playerVars: {
        autoplay: 0,
        controls: 0,
        modestbranding: 1,
        rel: 0,
        playsinline: 1,
      },
      videoId: pendingVideoId || undefined,
      events: {
        onReady: function () {
          currentVideoId = pendingVideoId;

          if (pendingVideo) {
            aplicarVideo(pendingVideo);
          }

          pendingVideo = null;
          pendingVideoId = null;
        },
      },
    });
  };

  function poll() {
    fetch(pollUrl, { cache: 'no-store' })
      .then(function (resposta) {
        return resposta.json();
      })
      .then(function (dados) {
        if (dados.versao === lastVersao && dados.ativo !== false) {
          return;
        }

        lastVersao = dados.versao;
        aplicarEstado(dados);
      })
      .catch(function () {
        // Falha de rede pontual; tenta novamente no proximo ciclo.
      });
  }

  var estadoInicialTag = document.getElementById('telao-estado-inicial');

  if (estadoInicialTag) {
    try {
      var estadoInicial = JSON.parse(estadoInicialTag.textContent);

      if (estadoInicial) {
        lastVersao = estadoInicial.versao;
        aplicarEstado(Object.assign({ ativo: true }, estadoInicial));
      }
    } catch (erro) {
      // Sem estado inicial valido; o primeiro poll resolve.
    }
  }

  setInterval(poll, 1500);
  poll();
})();
