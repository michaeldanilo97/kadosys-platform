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
  var botaoFullscreen = document.querySelector('[data-acao-fullscreen]');

  var navBox = root.querySelector('[data-projecao-nav]');
  var botaoLerAgora = root.querySelector('[data-acao-ler-agora]');
  var previewRef = root.querySelector('[data-preview-ref]');
  var previewTexto = root.querySelector('[data-preview-texto]');
  var previewProximo = root.querySelector('[data-preview-proximo]');
  var previewProximoTexto = root.querySelector('[data-preview-proximo-texto]');
  var botoesNav = root.querySelectorAll('[data-nav-acao]');

  var videoProgresso = root.querySelector('[data-video-progresso]');
  var videoProgressoPreenchido = root.querySelector('[data-video-progresso-preenchido]');
  var videoProgressoTempo = root.querySelector('[data-video-progresso-tempo]');

  var lastVersao = null;
  var sincronizando = false;
  var modoAtual = null;

  var NOMES_MODO = { biblia: 'a Bíblia', video: 'o vídeo', logo: 'a logo', blank: 'a tela em branco' };

  /**
   * Troca de modo (biblia/video/logo/blank) interrompe o que ja esta
   * sendo exibido ao vivo no telao - confirma antes, para evitar trocar
   * por engano no meio de um culto. So pede confirmacao quando ha algo
   * diferente do novo modo realmente em exibicao (nao pede nada na
   * primeira vez, com a tela em branco, ou ao continuar no mesmo modo -
   * ex.: navegar entre versiculos com a biblia ja em exibicao).
   *
   * Assincrono (Promise<boolean>) porque usa o popup proprio do sistema
   * (ver kadosys-modal.js) no lugar do window.confirm nativo.
   */
  function confirmarTroca(novoModo) {
    if (!modoAtual || modoAtual === 'blank' || modoAtual === novoModo) {
      return Promise.resolve(true);
    }

    var atual = NOMES_MODO[modoAtual] || 'outro conteudo';
    var proximo = NOMES_MODO[novoModo] || 'outro conteudo';
    var mensagem = 'Ja tem ' + atual + ' em exibicao no telao. Trocar para ' + proximo + ' agora?';

    if (!window.KadosysModal) {
      return Promise.resolve(window.confirm(mensagem));
    }

    return window.KadosysModal.confirmar(mensagem, { confirmar: 'Trocar', icone: 'bi-easel2' });
  }

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

      confirmarTroca('biblia').then(function (ok) {
        if (!ok) {
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
      });
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

  /**
   * Mantem o botao Play/Pausar/Fadeout marcado como "active" de acordo
   * com o que REALMENTE esta sendo exibido no telao (reportado pelo
   * poll), nao so com o ultimo botao clicado neste painel - sem isso,
   * o destaque ficava errado ao reabrir a pagina, ao trocar de video
   * (o backend sempre volta pra "tocando" ao carregar um novo link,
   * ver ProjecaoEstado::definirVideo) ou quando outra pessoa opera o
   * telao por uma sessao diferente.
   */
  function sincronizarBotoesVideo(dados) {
    var estadoAtivo = (dados && dados.modo === 'video' && dados.video) ? dados.video.estado : null;

    botoesVideo.forEach(function (botao) {
      botao.classList.toggle('active', botao.getAttribute('data-video-acao') === estadoAtivo);
    });
  }

  function aplicarEstado(dados) {
    modoAtual = dados ? dados.modo : null;
    sincronizarBotoesVideo(dados);

    if (!dados || dados.modo !== 'biblia' || !dados.biblia || !dados.biblia.livroId) {
      if (navBox) {
        navBox.hidden = true;
      }

      if (botaoLerAgora) {
        botaoLerAgora.hidden = true;
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

    if (botaoLerAgora) {
      botaoLerAgora.hidden = false;
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

  function formatarTempo(segundos) {
    var s = Math.max(0, Math.floor(segundos || 0));
    var minutos = Math.floor(s / 60);
    var resto = s % 60;

    return (minutos < 10 ? '0' : '') + minutos + ':' + (resto < 10 ? '0' : '') + resto;
  }

  function atualizarProgressoVideo(dados) {
    if (!videoProgresso) {
      return;
    }

    if (!dados || dados.modo !== 'video' || !dados.video || !dados.video.duracao) {
      videoProgresso.hidden = true;

      return;
    }

    var atual = dados.video.tempoAtual || 0;
    var duracao = dados.video.duracao;

    videoProgresso.hidden = false;
    videoProgressoPreenchido.style.width = Math.min(100, (atual / duracao) * 100) + '%';
    videoProgressoTempo.textContent = formatarTempo(atual) + ' / ' + formatarTempo(duracao);
  }

  function poll() {
    fetch(pollUrl, { cache: 'no-store' })
      .then(function (resposta) {
        return resposta.json();
      })
      .then(function (dados) {
        // O progresso do video (tempo atual/duracao) e atualizado a
        // cada poll mesmo sem a "versao" ter mudado - o telao reporta
        // o tempo por um canal separado que nao bate a revisao geral
        // do estado, de proposito (ver ProjecaoEstado::atualizarTempoVideo).
        atualizarProgressoVideo(dados);

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

      confirmarTroca('video').then(function (ok) {
        if (!ok) {
          return;
        }

        var url = formVideo.querySelector('#video_url').value;
        var dados = new URLSearchParams();
        dados.set('url', url);

        enviar('/video', dados).then(function () {
          modoAtual = 'video';
        });
      });
    });
  }

  botoesVideo.forEach(function (botao) {
    botao.addEventListener('click', function () {
      // Retorno visual imediato: marca o botao como ativo assim que e
      // clicado, sem esperar a resposta do servidor - antes, o "active"
      // so aparecia depois do fetch resolver, o que dava a sensacao de
      // que o botao "nao fazia nada" ao ser clicado (ainda mais se a
      // rede estivesse lenta ou o efeito real no telao demorasse).
      botoesVideo.forEach(function (b) {
        b.classList.remove('active');
      });
      botao.classList.add('active');

      var dados = new URLSearchParams();
      dados.set('estado', botao.getAttribute('data-video-acao'));

      enviar('/video/estado', dados);
    });
  });

  if (botaoLogo) {
    botaoLogo.addEventListener('click', function () {
      confirmarTroca('logo').then(function (ok) {
        if (!ok) {
          return;
        }

        enviar('/logo').then(function () {
          modoAtual = 'logo';
        });
      });
    });
  }

  if (botaoLimpar) {
    botaoLimpar.addEventListener('click', function () {
      confirmarTroca('blank').then(function (ok) {
        if (!ok) {
          return;
        }

        enviar('/limpar').then(function () {
          modoAtual = 'blank';
        });
      });
    });
  }

  if (botaoLerAgora) {
    botaoLerAgora.addEventListener('click', function () {
      botaoLerAgora.disabled = true;

      enviar('/biblia/ler').finally(function () {
        botaoLerAgora.disabled = false;
      });
    });
  }

  if (botaoFullscreen) {
    var chamarSePromise = function (valor) {
      if (valor && typeof valor.catch === 'function') {
        valor.catch(function () {});
      }
    };

    var atualizarIconeFullscreen = function () {
      var emTelaCheia = !!(document.fullscreenElement || document.webkitFullscreenElement);
      var icone = botaoFullscreen.querySelector('i');

      botaoFullscreen.classList.toggle('active', emTelaCheia);

      if (icone) {
        icone.className = emTelaCheia ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
      }
    };

    botaoFullscreen.addEventListener('click', function () {
      try {
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
          var pedido = document.documentElement.requestFullscreen || document.documentElement.webkitRequestFullscreen;

          if (pedido) {
            chamarSePromise(pedido.call(document.documentElement));
          }
        } else {
          var saida = document.exitFullscreen || document.webkitExitFullscreen;

          if (saida) {
            chamarSePromise(saida.call(document));
          }
        }
      } catch (erro) {
        // Navegador sem suporte a Fullscreen API; nada a fazer.
      }
    });

    document.addEventListener('fullscreenchange', atualizarIconeFullscreen);
    document.addEventListener('webkitfullscreenchange', atualizarIconeFullscreen);
  }

  setInterval(poll, 1500);
  poll();
})();
