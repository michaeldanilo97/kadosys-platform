(function () {
  'use strict';

  var root = document.querySelector('[data-telao]');

  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url');
  var tempoVideoUrl = pollUrl.replace(/\/estado$/, '/video/tempo');
  var layers = {
    blank: root.querySelector('[data-telao-layer="blank"]'),
    video: root.querySelector('[data-telao-layer="video"]'),
    biblia: root.querySelector('[data-telao-layer="biblia"]'),
    logo: root.querySelector('[data-telao-layer="logo"]'),
    pix: root.querySelector('[data-telao-layer="pix"]'),
    imagem: root.querySelector('[data-telao-layer="imagem"]'),
  };
  var bibliaTexto = root.querySelector('[data-telao-biblia-texto]');
  var bibliaRef = root.querySelector('[data-telao-biblia-ref]');
  var stage = root.querySelector('[data-telao-stage]');
  var marcacaoCanvas = root.querySelector('[data-telao-marcacao]');
  var marcacaoCtx = marcacaoCanvas ? marcacaoCanvas.getContext('2d') : null;
  var pixAviso = root.querySelector('[data-telao-pix-aviso]');
  var pixGrupo = root.querySelector('[data-telao-pix-grupo]');
  var pixQrWraps = {
    dizimo: root.querySelector('[data-telao-pix-qr="dizimo"]'),
    oferta: root.querySelector('[data-telao-pix-qr="oferta"]'),
  };
  var pixInstrucao = root.querySelector('[data-telao-pix-instrucao]');
  var pixMensagem = root.querySelector('[data-telao-pix-mensagem]');
  var imagemImg = root.querySelector('[data-telao-imagem-img]');
  // O link publico de doacao (ver DoacaoController) e derivado do
  // proprio poll-url (que ja carrega o base-path certo da instalacao),
  // trocando o sufixo "/projecao/{token}/estado" por "/doar" - evita
  // ter que passar essa informacao a parte do servidor pro telao.
  var linkDoacao = window.location.origin + pollUrl.replace(/\/projecao\/.*$/, '/doar');
  // Ultimo payload desenhado em cada QR (chave: categoria) - so
  // redesenha quando o payload daquela categoria muda de verdade,
  // evitando repintar (e "piscar") os dois QR a cada poll (1.5s).
  var ultimoPayloadPixRenderizado = {};

  var lastVersao = null;
  var lastLeituraId = null;
  var player = null;
  var ytReady = false;
  var currentVideoId = null;
  var pendingVideo = null;
  var pendingVideoId = null;
  var ultimoEstadoVideo = null;
  var audioDesbloqueado = false;
  var vozDestravada = false;
  var avisoAudio = root.querySelector('[data-telao-audio-unlock]');
  var videoOverlay = root.querySelector('[data-telao-video-overlay]');

  /**
   * Mensagens de erro do YouTube (ver onError no criarPlayer) e do
   * timeout de buffering (ver agendarChecagemReproducao) - mostradas
   * dentro do proprio overlay do video (que ja cobria a camada pra
   * bloquear clique), em vez de deixar a tela muda sobre o motivo real
   * de o video nao tocar.
   */
  function mostrarErroVideo(mensagem) {
    if (!videoOverlay) {
      return;
    }

    videoOverlay.textContent = mensagem;
    videoOverlay.classList.add('tem-erro');
  }

  function esconderErroVideo() {
    if (!videoOverlay) {
      return;
    }

    videoOverlay.textContent = '';
    videoOverlay.classList.remove('tem-erro');
  }

  // Mapa dos codigos de erro documentados da API do YouTube - ver
  // https://developers.google.com/youtube/iframe_api_reference#onError.
  // Sem isso, um link invalido ou um video com incorporacao desabilitada
  // pelo dono (bem comum em videoclipes oficiais, trechos de filme etc.)
  // ficava com o player mudo e preso pra sempre - o video "aparecia"
  // (a camada preta do player) mas nunca tocava, sem nenhuma pista do
  // motivo real (nao era travamento de autoplay, o unico caso que os
  // outros mecanismos de recuperacao tratavam).
  var MENSAGENS_ERRO_YOUTUBE = {
    2: 'Link de video invalido.',
    5: 'Este video nao pode ser reproduzido neste navegador.',
    100: 'Video nao encontrado ou removido.',
    101: 'O dono deste video nao permite reproduzi-lo em outros sites. Escolha outro video.',
    150: 'O dono deste video nao permite reproduzi-lo em outros sites. Escolha outro video.',
  };

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

    if (biblia.bibliaVersao) {
      referencia += ' · ' + biblia.bibliaVersao.toUpperCase();
    }

    bibliaRef.textContent = referencia;
  }

  /**
   * Exibicao rapida de Pix (dizimo + oferta, ver painel "Exibicoes
   * rapidas" no operador) - os dois QR sao desenhados no proprio
   * navegador do telao (mesma biblioteca vendorizada usada em
   * doacao-pix.js), a partir dos payloads BR Code ja montados no
   * servidor (ver ProjecaoEstado::montarPixJson()). Mostrados JUNTOS
   * (mesmo momento do culto) mas nas pontas opostas da tela - perto
   * demais, a camera do celular as vezes foca/le o QR errado ao tentar
   * escanear o de do lado. So redesenha cada QR quando o payload
   * DAQUELE especifico muda de verdade, nao a cada poll - redesenhar
   * sem necessidade pisca a tela.
   */
  function renderPix(pix) {
    if (!pixAviso || !pixGrupo || !pixInstrucao) {
      return;
    }

    var qrCodes = pix ? pix.qrCodes : null;

    if (!qrCodes || !qrCodes.length) {
      pixAviso.hidden = false;
      pixAviso.textContent = 'Esta igreja ainda não configurou uma chave Pix.';
      pixGrupo.hidden = true;
      ultimoPayloadPixRenderizado = {};

      return;
    }

    pixAviso.hidden = true;
    pixGrupo.hidden = false;

    qrCodes.forEach(function (item) {
      var wrap = pixQrWraps[item.categoria];

      if (!wrap || !item.payload || item.payload === ultimoPayloadPixRenderizado[item.categoria]) {
        return;
      }

      ultimoPayloadPixRenderizado[item.categoria] = item.payload;

      try {
        var qr = window.qrcode(0, 'M');
        qr.addData(item.payload);
        qr.make();
        wrap.innerHTML = qr.createImgTag(7, 4, 'QR code Pix - ' + item.label);
      } catch (erro) {
        wrap.innerHTML = '';
      }
    });

    if (pixMensagem) {
      var mensagem = pix.mensagem;
      pixMensagem.hidden = !mensagem;
      pixMensagem.textContent = mensagem || '';
    }

    pixInstrucao.innerHTML = 'Prefere fazer depois? Acesse <strong>' + escapeHtml(linkDoacao.replace(/^https?:\/\//, '')) + '</strong> do seu celular.';
  }

  function renderImagem(path) {
    if (!imagemImg) {
      return;
    }

    if (!path) {
      imagemImg.removeAttribute('src');

      return;
    }

    var urlCompleta = linkDoacao.replace(/\/doar$/, '/' + path);

    if (imagemImg.getAttribute('src') !== urlCompleta) {
      imagemImg.setAttribute('src', urlCompleta);
    }
  }

  function ajustarStage() {
    if (!stage || !window.KadosysBiblia) {
      return;
    }

    window.KadosysBiblia.ajustarPalco(layers.biblia, stage);

    if (marcacaoCanvas) {
      var proporcao = window.devicePixelRatio || 1;

      marcacaoCanvas.width = stage.clientWidth * proporcao;
      marcacaoCanvas.height = stage.clientHeight * proporcao;
      marcacaoCtx.setTransform(proporcao, 0, 0, proporcao, 0, 0);
    }
  }

  function renderMarcacao(tracos) {
    if (!marcacaoCtx) {
      return;
    }

    marcacaoCtx.clearRect(0, 0, marcacaoCanvas.clientWidth, marcacaoCanvas.clientHeight);

    if (!tracos || !tracos.length) {
      return;
    }

    marcacaoCtx.lineJoin = 'round';
    marcacaoCtx.lineCap = 'round';
    marcacaoCtx.strokeStyle = '#d4a13f';
    marcacaoCtx.lineWidth = 3;

    var largura = marcacaoCanvas.clientWidth;
    var altura = marcacaoCanvas.clientHeight;

    tracos.forEach(function (traco) {
      if (!traco || traco.length < 2) {
        return;
      }

      marcacaoCtx.beginPath();
      marcacaoCtx.moveTo(traco[0].x * largura, traco[0].y * altura);

      for (var i = 1; i < traco.length; i++) {
        marcacaoCtx.lineTo(traco[i].x * largura, traco[i].y * altura);
      }

      marcacaoCtx.stroke();
    });
  }

  var FADEOUT_PASSOS = 10;
  var FADEOUT_INTERVALO_MS = 200;
  var fadeoutTimer = null;

  /**
   * Cancela um fadeout em andamento (se houver) - chamado sempre que um
   * comando novo de play/pausa/video chega, pra nao deixar o volume
   * baixo depois de um fadeout interrompido por um "play" seguinte.
   */
  function cancelarFadeout(restaurarVolume) {
    if (fadeoutTimer) {
      clearInterval(fadeoutTimer);
      fadeoutTimer = null;
    }

    if (restaurarVolume && player && typeof player.setVolume === 'function') {
      try {
        player.setVolume(100);
      } catch (erro) {
        // Ignora; o proximo play tenta de novo.
      }
    }
  }

  /**
   * Fadeout de verdade: baixa o volume aos poucos (nao so pausa na
   * hora) e, no final, encerra o video e volta o volume a 100% (pronto
   * pra proxima vez que um video for carregado).
   */
  function iniciarFadeout() {
    if (fadeoutTimer || !player) {
      return;
    }

    var passosRestantes = FADEOUT_PASSOS;

    fadeoutTimer = setInterval(function () {
      passosRestantes--;

      try {
        player.setVolume(Math.max(0, Math.round((passosRestantes / FADEOUT_PASSOS) * 100)));
      } catch (erro) {
        // Ignora; tenta de novo no proximo passo.
      }

      if (passosRestantes <= 0) {
        clearInterval(fadeoutTimer);
        fadeoutTimer = null;

        try {
          player.stopVideo();
          player.setVolume(100);
        } catch (erro) {
          // Ignora.
        }
      }
    }, FADEOUT_INTERVALO_MS);
  }

  /**
   * Confirma se o player REALMENTE esta com o video certo carregado -
   * nao so compara com a variavel local currentVideoId, que e marcada
   * de forma otimista antes de loadVideoById() ser confirmado. Sem essa
   * checagem, uma falha silenciosa na primeira tentativa (ex.: o player
   * ainda terminando de inicializar, sem nenhum video anterior
   * carregado) deixava o telao preso sem video pra sempre, so
   * resolvendo com um reload manual da pagina - com essa checagem, o
   * proximo poll (1.5s depois) detecta a divergencia e tenta de novo.
   */
  function precisaRecarregar(videoId) {
    if (videoId !== currentVideoId) {
      return true;
    }

    // Nao consegue confirmar o video carregado (metodo indisponivel ou
    // erro ao consultar) - assume que NAO precisa recarregar. A
    // alternativa (assumir que precisa) e mais perigosa: aplicarVideo()
    // roda a cada poll (~1.5s) enquanto o modo for video, entao um
    // "true" persistente aqui forcaria loadVideoById() de novo a cada
    // poll, reiniciando um video que na verdade JA estava tocando bem -
    // preso num loop que nunca deixa a reproducao real comecar.
    if (typeof player.getVideoData !== 'function') {
      return false;
    }

    try {
      var dados = player.getVideoData();

      return !dados || !dados.video_id || dados.video_id !== videoId;
    } catch (erro) {
      return false;
    }
  }

  var RECARREGAR_CHAVE_PREFIXO = 'kadosys_telao_recarregou_';

  function jaRecarregouPara(videoId) {
    try {
      return window.sessionStorage.getItem(RECARREGAR_CHAVE_PREFIXO + videoId) === '1';
    } catch (erro) {
      return false;
    }
  }

  function marcarRecarregadoPara(videoId) {
    try {
      window.sessionStorage.setItem(RECARREGAR_CHAVE_PREFIXO + videoId, '1');
    } catch (erro) {
      // sessionStorage indisponivel (ex.: navegacao privada) - sem guarda
      // contra reload repetido, mas melhor arriscar isso do que deixar o
      // video travado sem tocar.
    }
  }

  /**
   * Alguns navegadores mobile (confirmado: precisava de F5 manual pro
   * video tocar) so permitem autoplay de um video carregado durante a
   * inicializacao da propria pagina - loadVideoById() chamado bem
   * depois, via polling e sem nenhum gesto do usuario, fica bloqueado
   * silenciosamente (o video nunca sai do estado "nao iniciado"), tanto
   * pro video quanto pro audio. Um reload da pagina refaz o carregamento
   * pelo caminho que sabidamente funciona (o video ja vem certo desde o
   * estado inicial, aplicado no onReady do player - ver criarPlayer()).
   *
   * So considera "travado" o video que fica parado no estado "nao
   * iniciado" (-1) ou "na fila" (5) - que e exatamente o sintoma do
   * autoplay bloqueado. "Bufferizando" (3), "pausado" (2) ou "tocando"
   * (1) significam que o player ACEITOU o comando e esta trabalhando -
   * recarregar a pagina nesses casos e sempre errado (era a causa da
   * tela preta/reinicios no PC: um video so bufferizando era tratado
   * como travado, recarregado no meio do carregamento, e depois do
   * unico reload permitido por video ficava preso numa tela preta ate
   * um F5 manual).
   *
   * Se o video estiver mesmo travado mas o reload unico por video ja
   * tiver sido gasto, mostra o aviso de "toque na tela" como saida -
   * o clique (ver o handler de click no final do arquivo) reaplica o
   * play com gesto do usuario, que os navegadores sempre aceitam.
   */
  var checagemVideoId = null;

  /**
   * "Bufferizando" (estado 3) sozinho nao significa travado - um video
   * que so esta carregando de verdade eventualmente comeca a tocar. Mas
   * bufferizar PARA SEMPRE, sem o tempo de reproducao avancar nem um
   * segundo, e outro sintoma real de travamento (ex.: rede da igreja
   * bloqueando o CDN de video do YouTube, mesmo com youtube.com
   * acessivel) - so que mais lento de confirmar que o autoplay
   * bloqueado (por isso um limite bem mais alto que LIMITE_TRAVADO).
   */
  var LIMITE_BUFFER_SEM_PROGRESSO = 25; // ~1s cada = 25s bufferizando sem tocar nada

  function agendarChecagemReproducao(videoId) {
    // Idempotente: chamada a cada poll enquanto o estado for "tocando"
    // (ver aplicarVideo), mas so uma checagem fica ativa por vez - e um
    // video ja rodando nao precisa de vigia nenhum.
    if (!videoId || checagemVideoId === videoId || videoEstaTocandoDeVerdade()) {
      return;
    }

    checagemVideoId = videoId;
    esconderErroVideo();

    var observacoesTravado = 0;
    var observacoesBufferParado = 0;
    var observacoesIndefinido = 0;
    var LIMITE_TRAVADO = 6; // ~1s cada = 6s parado em "nao iniciado" pra concluir que travou

    // getPlayerState() pode voltar "undefined" so porque o iframe por
    // baixo do wrapper YT.Player ainda esta conectando (handshake normal,
    // sem nada de errado) - em rede mais lenta isso as vezes demora mais
    // que os 6s do LIMITE_TRAVADO. Por isso ganha um limite bem mais
    // generoso e PROPRIO, sem forcar reload (reload no meio de uma
    // conexao que ia dar certo interrompe o video bem na hora que ele
    // comecaria a tocar - foi visto acontecendo: o audio chegava a
    // engatar e parava). Some com os -1/5 (que sao definitivos, o
    // player ja respondeu e disse "nao comecei") pra nao repetir o
    // bug antigo de tela preta permanente se o iframe travar mesmo.
    var LIMITE_INDEFINIDO = 20; // ~1s cada = 20s sem NENHUMA resposta do player

    var intervalo = setInterval(function () {
      if (!player || typeof player.getPlayerState !== 'function' || ultimoEstadoVideo !== 'tocando' || videoId !== currentVideoId) {
        clearInterval(intervalo);
        checagemVideoId = null;

        return;
      }

      var estadoAtual;

      try {
        estadoAtual = player.getPlayerState();
      } catch (erro) {
        clearInterval(intervalo);
        checagemVideoId = null;

        return;
      }

      if (estadoAtual === 1) {
        // Comecou a tocar normalmente - nao precisa recarregar.
        clearInterval(intervalo);
        checagemVideoId = null;

        return;
      }

      if (estadoAtual === undefined || estadoAtual === null) {
        // Player ainda nao respondeu nada (handshake do iframe em
        // andamento) - da um tempo generoso antes de considerar
        // travado, sem mexer nos outros contadores.
        observacoesIndefinido++;
      } else if (estadoAtual === -1 || estadoAtual === 5) {
        // -1 (nao iniciado) e 5 (na fila): o player JA respondeu e
        // confirmou que nao comecou - esses sim sao definitivos o
        // suficiente pro limite curto (autoplay bloqueado).
        observacoesTravado++;
        observacoesBufferParado = 0;
        observacoesIndefinido = 0;
      } else if (estadoAtual === 3) {
        // Bufferizando: so e "travado" se o tempo de reproducao continuar
        // em zero por muito tempo - bufferizar de verdade avanca o
        // tempo assim que os primeiros segundos chegam.
        observacoesTravado = 0;
        observacoesIndefinido = 0;

        var tempoAtual = 0;

        try {
          tempoAtual = player.getCurrentTime() || 0;
        } catch (erro) {
          tempoAtual = 0;
        }

        if (tempoAtual > 0) {
          observacoesBufferParado = 0;
        } else {
          observacoesBufferParado++;
        }
      } else {
        // Pausado (2) ou encerrado (0): o player aceitou o comando, so
        // esta trabalhando - zera as contagens e continua observando.
        observacoesTravado = 0;
        observacoesBufferParado = 0;
        observacoesIndefinido = 0;
      }

      if (observacoesIndefinido >= LIMITE_INDEFINIDO) {
        // ~20s sem o player responder nada - nao e mais handshake
        // lento, e o iframe que nunca vai conectar (ex.: dominio do
        // embed bloqueado pela rede). Reload nao ajuda aqui (o mesmo
        // bloqueio se repetiria), entao so avisa.
        clearInterval(intervalo);
        checagemVideoId = null;
        mostrarErroVideo('Nao foi possivel carregar o player do YouTube. Verifique se este dispositivo tem acesso a internet e se o YouTube nao esta bloqueado na rede.');
      } else if (observacoesTravado >= LIMITE_TRAVADO) {
        clearInterval(intervalo);
        checagemVideoId = null;

        if (!jaRecarregouPara(videoId)) {
          marcarRecarregadoPara(videoId);
          window.location.reload();
        } else if (avisoAudio) {
          // Reload unico ja gasto e o video continua travado (mas o
          // player responde normalmente) - mostra o aviso de toque como
          // ultima saida (um clique reaplica o play com gesto, que
          // sempre e aceito).
          avisoAudio.classList.add('is-visivel');
        }
      } else if (observacoesBufferParado >= LIMITE_BUFFER_SEM_PROGRESSO) {
        // Bufferizando ha muito tempo sem tocar nem um segundo - nao e
        // um problema de autoplay bloqueado (o player ja aceitou o
        // comando), entao um reload da pagina nao ajuda; mais provavel
        // ser rede bloqueando o video em si. Mostra isso na tela em vez
        // de deixar travado pra sempre sem nenhuma pista.
        clearInterval(intervalo);
        checagemVideoId = null;
        mostrarErroVideo('O video esta demorando demais para carregar. Verifique a conexao com a internet ou se o link e valido.');
      }
    }, 1000);
  }

  function aplicarVideo(video) {
    var videoId = extrairIdYoutube(video.url);

    if (!ytReady) {
      pendingVideo = video;
      pendingVideoId = videoId;

      return;
    }

    if (!player) {
      return;
    }

    if (videoId && precisaRecarregar(videoId)) {
      currentVideoId = videoId;

      try {
        player.loadVideoById(videoId);
        agendarChecagemReproducao(videoId);
      } catch (erro) {
        currentVideoId = null;
      }
    }

    ultimoEstadoVideo = video.estado;

    if (video.estado === 'fadeout') {
      iniciarFadeout();

      return;
    }

    cancelarFadeout(true);

    if (video.estado === 'pausado') {
      try {
        player.pauseVideo();
      } catch (erro) {
        // Player ainda nao pronto; sera reaplicado no proximo poll.
      }
    } else if (video.estado === 'tocando') {
      try {
        player.playVideo();

        // O comando de play chega aqui via polling, sem gesto direto do
        // usuario (o telao normalmente fica numa TV, sem ninguem pra
        // tocar na tela) - navegadores so permitem autoplay COM SOM sem
        // gesto se o video comecar mudo. Por isso o player e criado com
        // mute:1 (ver criarPlayer()), e aqui tenta desmutar logo em
        // seguida - desmutar um video que ja esta tocando (mesmo que
        // mudo) geralmente e permitido, diferente de comecar tocando
        // com som direto. Tenta de novo a cada poll (1.5s) ate
        // conseguir, caso a primeira tentativa seja ignorada.
        player.unMute();
        player.setVolume(100);
      } catch (erro) {
        // Player ainda nao pronto; sera reaplicado no proximo poll.
      }

      // Vigia o inicio da reproducao tambem por este caminho - cobre o
      // video que veio pelo estado inicial da pagina (pos-reload), que
      // nao passa pelo loadVideoById acima e antes ficava sem nenhuma
      // checagem (tela preta sem recuperacao se o autoplay bloqueasse).
      agendarChecagemReproducao(videoId);

      // Se mesmo assim o navegador manteve o video mudo (politica mais
      // rigorosa que exige mesmo um toque), mostra o aviso - inofensivo
      // numa TV sem toque (so fica ali), mas resolve o caso de abrir o
      // telao num notebook/tablet com toque disponivel.
      var aindaMudo = typeof player.isMuted === 'function' && player.isMuted();

      if (aindaMudo && !audioDesbloqueado && avisoAudio) {
        avisoAudio.classList.add('is-visivel');
      } else if (avisoAudio && videoEstaTocandoDeVerdade()) {
        // So esconde o aviso quando o video REALMENTE esta rodando -
        // sem essa condicao, o poll apagava (1.5s depois) o aviso de
        // toque mostrado pela checagem de travamento (ver
        // agendarChecagemReproducao), tirando a unica saida visivel do
        // usuario num video travado.
        avisoAudio.classList.remove('is-visivel');
      }
    }
  }

  function videoEstaTocandoDeVerdade() {
    if (!player || typeof player.getPlayerState !== 'function') {
      return false;
    }

    try {
      return player.getPlayerState() === 1;
    } catch (erro) {
      return false;
    }
  }

  // Cache das vozes disponiveis - getVoices() pode devolver uma lista
  // vazia na primeira chamada em alguns navegadores, so populando de
  // verdade de forma assincrona (evento voiceschanged). Escuta esse
  // evento uma vez e guarda a lista, em vez de consultar de novo (e
  // arriscar lista vazia) toda vez que "Ler agora" for clicado.
  var vozesDisponiveis = ('speechSynthesis' in window) ? window.speechSynthesis.getVoices() : [];

  if ('speechSynthesis' in window) {
    window.speechSynthesis.addEventListener('voiceschanged', function () {
      vozesDisponiveis = window.speechSynthesis.getVoices();
    });
  }

  /**
   * Escolhe a melhor voz em portugues disponivel no navegador/aparelho.
   * A qualidade "realista" depende do que esta instalado no dispositivo
   * (fora do nosso controle) - vozes de rede (Google/Microsoft Online,
   * localService === false) soam bem mais naturais que a voz offline
   * padrao do sistema, entao sao sempre preferidas quando existem.
   */
  function melhorVozPtBr() {
    var candidatas = vozesDisponiveis.filter(function (voz) {
      return voz.lang && voz.lang.toLowerCase().indexOf('pt') === 0;
    });

    if (!candidatas.length) {
      return null;
    }

    candidatas.sort(function (a, b) {
      return pontuarVoz(b) - pontuarVoz(a);
    });

    return candidatas[0];
  }

  // A Web Speech API nao informa o "genero" da voz - a unica pista e o
  // proprio nome (ex.: "Microsoft Daniel", "Luciana", "Felipe"). Listas
  // dos nomes conhecidos das vozes em portugues nos principais sistemas
  // (Windows/Microsoft, Google/Android, Apple), pra dar preferencia a
  // uma voz masculina na leitura biblica (pedido do usuario: soa mais
  // proximo de um pregador).
  var NOMES_VOZ_MASCULINA = ['daniel', 'antonio', 'antônio', 'felipe', 'ricardo', 'thiago', 'fabio', 'fábio', 'donato', 'reed', 'male'];
  var NOMES_VOZ_FEMININA = ['maria', 'francisca', 'camila', 'vitoria', 'vitória', 'leticia', 'letícia', 'fernanda', 'luciana', 'joana', 'catarina', 'yara', 'brenda', 'female', 'feminino'];

  function nomeContemAlgum(nome, lista) {
    for (var i = 0; i < lista.length; i++) {
      if (nome.indexOf(lista[i]) !== -1) {
        return true;
      }
    }

    return false;
  }

  function pontuarVoz(voz) {
    var pontos = 0;
    var nome = voz.name.toLowerCase();

    if (voz.lang.toLowerCase() === 'pt-br') {
      pontos += 2;
    }

    if (!voz.localService) {
      pontos += 3;
    }

    if (nome.indexOf('google') !== -1) {
      pontos += 2;
    }

    // Preferencia por voz masculina, com peso maior que a soma maxima
    // dos criterios de qualidade acima (2+3+2=7) - garante que uma voz
    // masculina, mesmo local/offline, sempre ganha de qualquer voz
    // feminina, por melhor que seja.
    if (nomeContemAlgum(nome, NOMES_VOZ_MASCULINA)) {
      pontos += 8;
    } else if (nomeContemAlgum(nome, NOMES_VOZ_FEMININA)) {
      pontos -= 2;
    }

    return pontos;
  }

  /**
   * "Ler agora" (botao no painel do operador): le em voz alta o texto
   * biblico projetado no momento, usando o sintetizador de voz do
   * proprio navegador (Web Speech API) - sem custo, sem depender de
   * nenhum servico externo. So funciona aqui no telao porque e a unica
   * tela com uma "audiencia" ouvindo (o preletor/operador tem os
   * proprios controles, nao precisam de leitura em voz alta).
   */
  function lerBibliaEmVoz(biblia) {
    if (!('speechSynthesis' in window) || !biblia || !biblia.versiculos || !biblia.versiculos.length) {
      return;
    }

    var texto = biblia.versiculos.map(function (versiculo) {
      return versiculo.texto;
    }).join(' ');

    window.speechSynthesis.cancel();

    var fala = new SpeechSynthesisUtterance(texto);
    fala.lang = 'pt-BR';
    fala.rate = 0.95;

    var voz = melhorVozPtBr();

    if (voz) {
      fala.voice = voz;
    }

    window.speechSynthesis.speak(fala);
  }

  /**
   * Detecta um pedido novo de "Ler agora" (ver lerBibliaEmVoz() acima) -
   * verificado tanto quando o estado muda quanto quando so o poll
   * repete sem mudanca de versao, ja que uma releitura do MESMO
   * versiculo nao muda "versao" de proposito (ver
   * ProjecaoEstado::lerAgora()).
   */
  function verificarLeitura(dados) {
    if (dados.leituraId === undefined || dados.leituraId === lastLeituraId) {
      return;
    }

    lastLeituraId = dados.leituraId;

    if (dados.modo === 'biblia') {
      lerBibliaEmVoz(dados.biblia);
    }
  }

  var modoAtual = null;

  /**
   * Pausa o player do YouTube quando o modo de exibicao deixa de ser
   * "video" - sem isso, trocar pra Biblia/logo/Pix/imagem so escondia
   * a camada do video visualmente (CSS), mas o iframe continuava
   * rodando por baixo e o AUDIO continuava tocando, sobreposto ao que
   * apareceu na tela (reportado ao vivo: texto biblico na tela com o
   * audio do video ainda tocando, depois de assumir o comando do
   * preletor pra Biblia enquanto um video estava em exibicao).
   */
  function pausarVideoAoSairDoModo() {
    ultimoEstadoVideo = null;

    if (!player || typeof player.pauseVideo !== 'function') {
      return;
    }

    try {
      if (videoEstaTocandoDeVerdade()) {
        player.pauseVideo();
      }
    } catch (erro) {
      // Player ainda nao pronto - nao ha nada tocando pra pausar.
    }
  }

  function aplicarEstado(estado) {
    if (!estado || estado.ativo === false) {
      modoAtual = null;
      pausarVideoAoSairDoModo();
      mostrarSomente(['blank']);

      return;
    }

    modoAtual = estado.modo;

    if (estado.modo !== 'video') {
      pausarVideoAoSairDoModo();
    }

    if (estado.modo === 'blank') {
      mostrarSomente(['blank']);
    } else if (estado.modo === 'biblia') {
      renderBiblia(estado.biblia);
      ajustarStage();

      if (window.KadosysBiblia) {
        window.KadosysBiblia.ajustarTamanhoTexto(stage, bibliaTexto);
      }

      renderMarcacao(estado.biblia ? estado.biblia.marcacao : null);
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
    } else if (estado.modo === 'pix') {
      renderPix(estado.pix);
      mostrarSomente(['pix']);
    } else if (estado.modo === 'imagem') {
      renderImagem(estado.imagem ? estado.imagem.path : null);
      mostrarSomente(['imagem']);
    }
  }

  /**
   * Reporta o progresso da reproducao (tempo atual/duracao) para o
   * servidor periodicamente - o operador nao tem acesso direto ao
   * player (que so existe aqui, no telao), entao esse e o unico jeito
   * dele ver o andamento exato do video.
   */
  function reportarTempoVideo() {
    if (modoAtual !== 'video' || !player || typeof player.getCurrentTime !== 'function') {
      return;
    }

    var tempoAtual = Math.floor(player.getCurrentTime() || 0);
    var duracao = Math.floor(player.getDuration() || 0);

    var dados = new URLSearchParams();
    dados.set('tempo_atual', String(tempoAtual));
    dados.set('duracao', String(duracao));

    fetch(tempoVideoUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: dados.toString(),
    }).catch(function () {});
  }

  function criarPlayer() {
    if (player || !window.YT || !window.YT.Player) {
      return;
    }

    ytReady = true;

    player = new YT.Player('telao-player', {
      playerVars: {
        autoplay: 0,
        // Comeca mudo de proposito - navegadores sempre permitem
        // autoplay mudo sem gesto do usuario (essencial pro telao, que
        // roda numa TV sem ninguem pra tocar na tela). O desmute
        // acontece logo em seguida, em aplicarVideo() - ver o
        // comentario la pra entender por que isso funciona sem gesto.
        mute: 1,
        controls: 0,
        modestbranding: 1,
        rel: 0,
        playsinline: 1,
        disablekb: 1,
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
        onStateChange: function (evento) {
          // Assim que o video realmente comeca a tocar, some com
          // qualquer erro/aviso mostrado antes (ex.: um video anterior
          // com problema, ou o proprio timeout de buffering) - nao faz
          // sentido continuar exibindo isso sobre um video que esta
          // funcionando.
          if (evento.data === 1) {
            esconderErroVideo();
          }
        },
        onError: function (evento) {
          var mensagem = MENSAGENS_ERRO_YOUTUBE[evento.data] || 'Nao foi possivel reproduzir este video.';
          mostrarErroVideo(mensagem);
        },
      },
    });
  }

  window.onYouTubeIframeAPIReady = criarPlayer;

  // Salvaguarda: em rede lenta, cache quebrado ou o script do YouTube
  // sendo bloqueado por algum motivo, o callback global as vezes nao
  // dispara e o player nunca chega a ser criado - fazendo o video nunca
  // iniciar e os botoes de play/pause do operador nao terem efeito
  // nenhum, so resolvendo com um hard-refresh. Aqui, tenta periodicamente
  // por conta propria enquanto o player nao existir, e como ultimo
  // recurso reinjeta o script apos alguns segundos sem sucesso.
  var tentativasPlayer = 0;
  var LIMITE_TENTATIVAS_PLAYER = 25; // ~800ms cada = 20s tentando antes de avisar

  var intervaloPlayer = setInterval(function () {
    if (player) {
      clearInterval(intervaloPlayer);
      esconderErroVideo();

      return;
    }

    tentativasPlayer++;
    criarPlayer();

    if (player) {
      esconderErroVideo();

      return;
    }

    if (tentativasPlayer === 6) {
      var scriptAntigo = document.querySelector('script[src*="youtube.com/iframe_api"]');

      if (scriptAntigo) {
        scriptAntigo.parentNode.removeChild(scriptAntigo);
      }

      var novoScript = document.createElement('script');
      novoScript.src = 'https://www.youtube.com/iframe_api';
      document.body.appendChild(novoScript);
    } else if (tentativasPlayer === LIMITE_TENTATIVAS_PLAYER) {
      // Depois de ~20s sem conseguir nem criar o player (nao e so um
      // video especifico com problema - aqui e a propria API do
      // YouTube que nunca respondeu), o mais provavel e a rede estar
      // bloqueando o dominio do YouTube por completo. Sem isso, os
      // botoes de play/pause do operador ficavam sem efeito nenhum e
      // sem nenhuma explicacao visivel na tela.
      mostrarErroVideo('Nao foi possivel carregar o player do YouTube. Verifique se este dispositivo tem acesso a internet e se o YouTube nao esta bloqueado na rede.');
    }
  }, 800);

  function poll() {
    fetch(pollUrl, { cache: 'no-store' })
      .then(function (resposta) {
        return resposta.json();
      })
      .then(function (dados) {
        if (dados.versao === lastVersao && dados.ativo !== false) {
          // Sem mudanca de versao, mas em modo video reaplica mesmo
          // assim a cada poll - e o que garante a autocorrecao de
          // precisaRecarregar() quando a primeira tentativa de carregar
          // o video falha silenciosamente (ver aplicarVideo()). Sem
          // isso, so um proximo comando do operador (que muda a versao)
          // daria outra chance, deixando o telao preso sem video ate
          // isso acontecer ou a pagina ser recarregada manualmente.
          if (modoAtual === 'video' && dados.video) {
            aplicarVideo(dados.video);
          }

          verificarLeitura(dados);

          return;
        }

        lastVersao = dados.versao;
        aplicarEstado(dados);
        verificarLeitura(dados);
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
        lastLeituraId = estadoInicial.leituraId;
        aplicarEstado(Object.assign({ ativo: true }, estadoInicial));
      }
    } catch (erro) {
      // Sem estado inicial valido; o primeiro poll resolve.
    }
  }

  if (window.ResizeObserver) {
    new ResizeObserver(ajustarStage).observe(layers.biblia);
  } else {
    window.addEventListener('resize', ajustarStage);
  }

  ajustarStage();

  // Tela cheia com duplo clique em qualquer ponto do telao; duplo clique
  // de novo sai da tela cheia.
  function chamarSePromise(resultado) {
    if (resultado && typeof resultado.catch === 'function') {
      resultado.catch(function () {});
    }
  }

  document.addEventListener('dblclick', function () {
    var elementoFullscreen = document.fullscreenElement || document.webkitFullscreenElement;

    try {
      if (!elementoFullscreen) {
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
      // Navegador sem suporte a fullscreen ou chamada bloqueada; ignora.
    }
  });

  // Primeiro toque/clique na tela do telao "destrava" o autoplay com som
  // no navegador (necessario porque o comando de play chega por polling,
  // sem gesto direto do usuario) - e reaplica o video se ele deveria
  // estar tocando mas ficou bloqueado silenciosamente.
  document.addEventListener('click', function () {
    audioDesbloqueado = true;

    if (avisoAudio) {
      avisoAudio.classList.remove('is-visivel');
    }

    if (player && ultimoEstadoVideo === 'tocando') {
      try {
        player.playVideo();
        player.unMute();
        player.setVolume(100);
      } catch (erro) {
        // Ignora; sera reaplicado no proximo poll ou proximo toque.
      }
    }

    // "Destrava" tambem o sintetizador de voz ("Ler agora") - no
    // iOS/Android, speechSynthesis.speak() so funciona se disparado
    // dentro de um gesto direto do usuario; chamado depois via polling
    // (sem gesto nenhum, como e o caso aqui) fica mudo/ignorado. Falar
    // uma vez algo bem curto e quase inaudivel, dentro deste clique,
    // "destrava" o motor de voz pro resto da sessao - chamadas
    // posteriores (via lerBibliaEmVoz) passam a funcionar normalmente.
    if (!vozDestravada && 'speechSynthesis' in window) {
      vozDestravada = true;

      try {
        var destrava = new SpeechSynthesisUtterance(' ');
        destrava.volume = 0;
        window.speechSynthesis.speak(destrava);
      } catch (erro) {
        vozDestravada = false;
      }
    }
  });

  setInterval(poll, 1500);
  setInterval(reportarTempoVideo, 2000);
  poll();
})();
