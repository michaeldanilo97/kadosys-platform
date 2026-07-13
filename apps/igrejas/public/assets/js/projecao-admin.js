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
  var botaoPix = document.querySelector('[data-acao-pix]');
  var botoesImagem = document.querySelectorAll('[data-acao-imagem]');

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

  var comandoIndicador = document.querySelector('[data-comando-indicador]');
  var comandoIndicadorTexto = document.querySelector('[data-comando-indicador-texto]');

  var lastVersao = null;
  var sincronizando = false;
  var modoAtual = null;
  var controladoPorAtual = null;

  /**
   * Estado local do progresso do video, atualizado a cada segundo por
   * conta propria (ver iniciarTickProgresso) em vez de so refletir
   * cegamente o que o servidor mandou a cada poll. O telao so reporta o
   * tempo a cada 2s (ver telao.js/reportarTempoVideo), enquanto este
   * painel consulta o servidor a cada 1.5s - como os dois ciclos nao
   * estao sincronizados, boa parte das consultas cai bem no meio de duas
   * atualizacoes reais e devolve um valor levemente atrasado, fazendo a
   * barra parecer "voltar" (o video continua tocando normalmente, so a
   * exibicao no painel que oscilava).
   */
  var progresso = { atual: 0, duracao: 0 };
  var progressoAtivo = false;
  var progressoTimer = null;

  var NOMES_MODO = { biblia: 'a Bíblia', video: 'o vídeo', logo: 'a logo', blank: 'a tela em branco', pix: 'o Pix', imagem: 'a imagem' };
  var NOMES_ORIGEM = { operador: 'O painel do operador', preletor: 'O preletor' };

  /**
   * Troca de modo (biblia/video/logo/blank) interrompe o que ja esta
   * sendo exibido ao vivo no telao - confirma antes, para evitar trocar
   * por engano no meio de um culto. So pede confirmacao quando ha algo
   * diferente do novo modo realmente em exibicao (nao pede nada na
   * primeira vez, com a tela em branco, ou ao continuar no mesmo modo -
   * ex.: navegar entre versiculos com a biblia ja em exibicao).
   *
   * Tambem cobre o cenario de "quem esta no comando": o preletor
   * (tablet do pastor) pode estar controlando o telao ao mesmo tempo
   * que este painel - sem isso, um lado sobrescrevia silenciosamente o
   * que o outro estava exibindo (ex.: o pastor navegando versiculos
   * enquanto o operador troca pra outro versiculo sem perceber que
   * atropelou o pastor). Quando o OUTRO lado esta no comando, pede
   * confirmacao mesmo que o modo em si nao esteja mudando.
   *
   * Assincrono (Promise<boolean>) porque usa o popup proprio do sistema
   * (ver kadosys-modal.js) no lugar do window.confirm nativo.
   */
  function confirmarTroca(novoModo) {
    var outroTemComando = !!controladoPorAtual && controladoPorAtual !== MEU_PAPEL;
    var mudandoModo = !!modoAtual && modoAtual !== 'blank' && modoAtual !== novoModo;

    if (!outroTemComando && !mudandoModo) {
      return Promise.resolve(true);
    }

    var atual = NOMES_MODO[modoAtual] || 'outro conteúdo';
    var proximo = NOMES_MODO[novoModo] || 'outro conteúdo';
    var mensagem;

    if (outroTemComando && mudandoModo) {
      mensagem = NOMES_ORIGEM[controladoPorAtual] + ' está no comando agora, exibindo ' + atual + '. Assumir o comando e trocar para ' + proximo + '?';
    } else if (outroTemComando) {
      mensagem = NOMES_ORIGEM[controladoPorAtual] + ' está no comando agora. Assumir o comando e continuar?';
    } else {
      mensagem = 'Já tem ' + atual + ' em exibição no telão. Trocar para ' + proximo + ' agora?';
    }

    if (!window.KadosysModal) {
      return Promise.resolve(window.confirm(mensagem));
    }

    return window.KadosysModal.confirmar(mensagem, { confirmar: outroTemComando ? 'Assumir comando' : 'Trocar', icone: 'bi-easel2' });
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value;

    return div.innerHTML;
  }

  // Identifica quem esta mandando o comando (ver "assumir comando"
  // abaixo) - o painel do operador e o tablet do preletor podem
  // controlar o mesmo telao ao mesmo tempo, sem sessao/login em comum
  // pra o servidor inferir isso sozinho.
  var MEU_PAPEL = 'operador';

  function enviar(caminho, corpo) {
    var dados = corpo || new URLSearchParams();
    dados.set('origem', MEU_PAPEL);

    return fetch(baseUrl + caminho, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: dados.toString(),
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

  /**
   * Mesma logica do video acima, mas pros botoes de "Exibicoes
   * rapidas" (Logo, Dizimo e Oferta, e cada imagem da galeria) - o
   * botao correspondente ao que esta REALMENTE no telao agora fica
   * marcado como ativo, nao so o ultimo clicado.
   */
  function sincronizarBotoesExibicao(dados) {
    var modo = dados ? dados.modo : null;

    if (botaoLogo) {
      botaoLogo.classList.toggle('active', modo === 'logo');
    }

    if (botaoPix) {
      botaoPix.classList.toggle('active', modo === 'pix');
    }

    var imagemAtivaId = (modo === 'imagem' && dados.imagem) ? String(dados.imagem.id) : null;

    botoesImagem.forEach(function (botao) {
      var ativo = botao.getAttribute('data-acao-imagem') === imagemAtivaId;

      botao.classList.toggle('active', ativo);

      var card = botao.closest('[data-imagem-card]');

      if (card) {
        card.classList.toggle('is-exibindo', ativo);
      }
    });
  }

  /**
   * Mostra "Preletor no comando" no painel quando o tablet do pastor
   * foi quem definiu por ultimo o conteudo em exibicao - avisa o
   * operador antes mesmo dele tentar mexer em algo (a confirmacao de
   * "assumir comando" so aparece DEPOIS de clicar; este indicador
   * avisa ANTES).
   */
  function sincronizarIndicadorComando() {
    if (!comandoIndicador || !comandoIndicadorTexto) {
      return;
    }

    var outroTemComando = !!controladoPorAtual && controladoPorAtual !== MEU_PAPEL;

    comandoIndicador.hidden = !outroTemComando;

    if (outroTemComando) {
      comandoIndicadorTexto.textContent = 'Preletor no comando';
    }
  }

  function aplicarEstado(dados) {
    modoAtual = dados ? dados.modo : null;
    controladoPorAtual = dados ? dados.controladoPor : null;
    sincronizarIndicadorComando();
    sincronizarBotoesVideo(dados);
    sincronizarBotoesExibicao(dados);

    if (!dados || dados.modo !== 'biblia' || !dados.biblia || !dados.biblia.livroId) {
      if (navBox) {
        navBox.hidden = true;
      }

      if (botaoLerAgora) {
        botaoLerAgora.hidden = true;
      }

      if (previewRef) {
        previewRef.innerHTML = '<i class="bi bi-broadcast"></i> Nada em projeção';
      }

      if (previewTexto) {
        previewTexto.innerHTML = '<span class="vazio">Escolha versão, livro, capítulo e versículo acima para começar.</span>';
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
        previewTexto.innerHTML = '<span class="vazio">Texto ainda não importado para esta versão.</span>';
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

  function renderizarProgresso() {
    videoProgressoPreenchido.style.width = Math.min(100, (progresso.atual / progresso.duracao) * 100) + '%';
    videoProgressoTempo.textContent = formatarTempo(progresso.atual) + ' / ' + formatarTempo(progresso.duracao);
  }

  /**
   * Avanca a exibicao 1s por vez, por conta propria, independente de
   * quando a proxima resposta do servidor chega - e o que da a sensacao
   * de contagem continua (como um cronometro de verdade) em vez de
   * pulos a cada poll.
   */
  function iniciarTickProgresso() {
    if (progressoTimer) {
      return;
    }

    progressoTimer = setInterval(function () {
      if (!progressoAtivo || progresso.duracao <= 0) {
        return;
      }

      progresso.atual = Math.min(progresso.duracao, progresso.atual + 1);
      renderizarProgresso();
    }, 1000);
  }

  function atualizarProgressoVideo(dados) {
    if (!videoProgresso) {
      return;
    }

    if (!dados || dados.modo !== 'video' || !dados.video || !dados.video.duracao) {
      videoProgresso.hidden = true;
      progressoAtivo = false;

      return;
    }

    videoProgresso.hidden = false;
    progressoAtivo = true;
    iniciarTickProgresso();

    var atualServidor = dados.video.tempoAtual || 0;
    var duracaoMudou = dados.video.duracao !== progresso.duracao;

    progresso.duracao = dados.video.duracao;

    // So aceita o valor do servidor se a duracao mudou (video novo -
    // sempre confia no valor novo, mesmo que va "pra tras" comparado ao
    // video anterior) OU se estiver IGUAL OU A FRENTE do que ja esta
    // sendo exibido. Um valor levemente atrasado do MESMO video (ver
    // comentario acima de "progresso") e simplesmente ignorado, deixando
    // o tick local seguir contando sem parecer que o video "voltou".
    if (duracaoMudou || atualServidor >= progresso.atual) {
      progresso.atual = atualServidor;
    }

    renderizarProgresso();
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
    confirmarTroca('biblia').then(function (ok) {
      if (!ok) {
        return;
      }

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
    });
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
          controladoPorAtual = MEU_PAPEL;
          sincronizarIndicadorComando();
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
          controladoPorAtual = MEU_PAPEL;
          sincronizarIndicadorComando();
        });
      });
    });
  }

  if (botaoPix) {
    botaoPix.addEventListener('click', function () {
      confirmarTroca('pix').then(function (ok) {
        if (!ok) {
          return;
        }

        enviar('/pix').then(function () {
          modoAtual = 'pix';
          controladoPorAtual = MEU_PAPEL;
          sincronizarIndicadorComando();
        });
      });
    });
  }

  botoesImagem.forEach(function (botao) {
    botao.addEventListener('click', function () {
      var imagemId = botao.getAttribute('data-acao-imagem');

      confirmarTroca('imagem').then(function (ok) {
        if (!ok) {
          return;
        }

        var dados = new URLSearchParams();
        dados.set('imagem_id', imagemId);

        enviar('/imagem', dados).then(function () {
          modoAtual = 'imagem';
          controladoPorAtual = MEU_PAPEL;
          sincronizarIndicadorComando();
        });
      });
    });
  });

  if (botaoLimpar) {
    botaoLimpar.addEventListener('click', function () {
      confirmarTroca('blank').then(function (ok) {
        if (!ok) {
          return;
        }

        enviar('/limpar').then(function () {
          modoAtual = 'blank';
          controladoPorAtual = MEU_PAPEL;
          sincronizarIndicadorComando();
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
