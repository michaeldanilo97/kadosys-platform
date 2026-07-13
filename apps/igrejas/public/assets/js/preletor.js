(function () {
  'use strict';

  var root = document.querySelector('[data-preletor]');

  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url');
  var bibliaUrl = root.getAttribute('data-biblia-url');
  var navegarUrl = root.getAttribute('data-navegar-url');
  var capituloInfoUrl = root.getAttribute('data-capitulo-info-url');
  var marcacaoUrl = root.getAttribute('data-marcacao-url');
  var texto = root.querySelector('[data-preletor-texto]');
  var refEl = root.querySelector('[data-preletor-ref]');
  var canvasWrap = root.querySelector('[data-preletor-canvas-wrap]');
  var stage = root.querySelector('[data-preletor-stage]');
  var canvas = root.querySelector('[data-preletor-canvas]');
  var ctx = canvas.getContext('2d');
  var toolPenBtn = root.querySelector('[data-tool-pen]');
  var toolClearBtn = root.querySelector('[data-tool-clear]');
  var toolFullscreenBtn = root.querySelector('[data-tool-fullscreen]');
  var toolAjudaBtn = root.querySelector('[data-tool-ajuda]');
  var ajudaPopup = root.querySelector('[data-preletor-ajuda-popup]');
  var ajudaFechar = root.querySelector('[data-preletor-ajuda-fechar]');
  var form = root.querySelector('[data-preletor-form]');
  var livroSelect = form.querySelector('[data-campo="livro_id"]');
  var capituloSelect = form.querySelector('[data-campo="capitulo"]');
  var previewBox = root.querySelector('[data-nav-preview]');
  var previewRef = root.querySelector('[data-nav-preview-ref]');
  var previewTexto = root.querySelector('[data-nav-preview-texto]');
  var botoesNav = root.querySelectorAll('[data-nav-acao]');
  var comandoIndicador = root.querySelector('[data-comando-indicador]');
  var comandoIndicadorTexto = root.querySelector('[data-comando-indicador-texto]');

  var lastVersao = null;
  var lastReferenciaChave = null;
  var modoAtual = null;
  var controladoPorAtual = null;
  var MEU_PAPEL = 'preletor';
  var desenhando = false;
  var caneteAtiva = false;
  var ultimoPonto = null;
  var tracoAtual = null;
  var tracos = [];

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value;

    return div.innerHTML;
  }

  function ajustarCanvas() {
    if (window.KadosysBiblia) {
      window.KadosysBiblia.ajustarPalco(canvasWrap, stage);
    }

    var proporcao = window.devicePixelRatio || 1;
    var largura = canvas.clientWidth;
    var altura = canvas.clientHeight;

    canvas.width = largura * proporcao;
    canvas.height = altura * proporcao;
    ctx.setTransform(proporcao, 0, 0, proporcao, 0, 0);
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#d4a13f';
    ctx.lineWidth = 3;

    redesenharTracos();
  }

  function redesenharTracos() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    var largura = canvas.clientWidth;
    var altura = canvas.clientHeight;

    tracos.forEach(function (traco) {
      if (traco.length < 2) {
        return;
      }

      ctx.beginPath();
      ctx.moveTo(traco[0].x * largura, traco[0].y * altura);

      for (var i = 1; i < traco.length; i++) {
        ctx.lineTo(traco[i].x * largura, traco[i].y * altura);
      }

      ctx.stroke();
    });
  }

  function limparCanvas(semEnviar) {
    tracos = [];
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (!semEnviar) {
      enviarMarcacao();
    }
  }

  function enviarMarcacao() {
    if (!marcacaoUrl) {
      return;
    }

    var dados = new URLSearchParams();
    dados.set('tracos', JSON.stringify(tracos));

    fetch(marcacaoUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: dados.toString(),
    }).catch(function () {});
  }

  function alternarCaneta() {
    caneteAtiva = !caneteAtiva;
    canvas.style.pointerEvents = caneteAtiva ? 'auto' : 'none';
    toolPenBtn.classList.toggle('active', caneteAtiva);
    toolPenBtn.setAttribute('aria-pressed', caneteAtiva ? 'true' : 'false');
    toolPenBtn.title = caneteAtiva ? 'Caneta ativada (toque para desativar)' : 'Caneta desativada (toque para ativar)';
  }

  function posicaoRelativa(evento) {
    var retangulo = canvas.getBoundingClientRect();

    return {
      x: Math.max(0, Math.min(1, (evento.clientX - retangulo.left) / retangulo.width)),
      y: Math.max(0, Math.min(1, (evento.clientY - retangulo.top) / retangulo.height)),
    };
  }

  function iniciarTraco(evento) {
    if (!caneteAtiva) {
      return;
    }

    desenhando = true;
    tracoAtual = [];
    ultimoPonto = posicaoRelativa(evento);
    tracoAtual.push(ultimoPonto);
    canvas.setPointerCapture(evento.pointerId);
  }

  function continuarTraco(evento) {
    if (!desenhando) {
      return;
    }

    var ponto = posicaoRelativa(evento);
    var largura = canvas.clientWidth;
    var altura = canvas.clientHeight;

    ctx.beginPath();
    ctx.moveTo(ultimoPonto.x * largura, ultimoPonto.y * altura);
    ctx.lineTo(ponto.x * largura, ponto.y * altura);
    ctx.stroke();

    ultimoPonto = ponto;
    tracoAtual.push(ponto);
  }

  function finalizarTraco() {
    if (!desenhando) {
      return;
    }

    desenhando = false;
    ultimoPonto = null;

    if (tracoAtual && tracoAtual.length > 1) {
      tracos.push(tracoAtual);
      enviarMarcacao();
    }

    tracoAtual = null;
  }

  function renderTexto(biblia) {
    if (!biblia || !biblia.versiculos || !biblia.versiculos.length) {
      texto.innerHTML = '<p class="preletor-empty">Escolha um livro, capítulo e versículo acima para projetar.</p>';
      refEl.textContent = '';

      return;
    }

    var referencia = biblia.livroNome + ' ' + biblia.capitulo + ':' + biblia.versiculoInicio;

    if (biblia.versiculoFim && biblia.versiculoFim !== biblia.versiculoInicio) {
      referencia += '-' + biblia.versiculoFim;
    }

    if (biblia.bibliaVersao) {
      referencia += ' · ' + biblia.bibliaVersao.toUpperCase();
    }

    texto.innerHTML = biblia.versiculos.map(function (versiculo) {
      return '<span class="numero">' + versiculo.numero + '</span>' + escapeHtml(versiculo.texto) + ' ';
    }).join('');

    refEl.textContent = referencia;
  }

  function renderPreview(biblia) {
    if (!previewBox) {
      return;
    }

    if (!biblia || !biblia.proximaPreview) {
      previewBox.hidden = true;

      return;
    }

    previewBox.hidden = false;
    previewRef.textContent = biblia.proximaPreview.livroNome + ' ' + biblia.proximaPreview.capitulo + ':' + biblia.proximaPreview.versiculo;
    previewTexto.textContent = biblia.proximaPreview.texto || '';
  }

  /**
   * Mostra "Operador no comando" no topo quando o painel do operador
   * foi quem definiu por ultimo o conteudo em exibicao - avisa o
   * pastor antes mesmo dele tentar mexer em algo (a confirmacao de
   * "assumir comando" so aparece DEPOIS de tentar projetar; este
   * indicador avisa ANTES).
   */
  function sincronizarIndicadorComando() {
    if (!comandoIndicador || !comandoIndicadorTexto) {
      return;
    }

    var outroTemComando = !!controladoPorAtual && controladoPorAtual !== MEU_PAPEL;

    comandoIndicador.hidden = !outroTemComando;

    if (outroTemComando) {
      comandoIndicadorTexto.textContent = 'Operador no comando';
    }
  }

  function aplicarEstado(dados) {
    modoAtual = dados ? dados.modo : null;
    controladoPorAtual = dados ? dados.controladoPor : null;
    sincronizarIndicadorComando();

    if (!dados || dados.ativo === false || dados.modo !== 'biblia') {
      return;
    }

    var biblia = dados.biblia || {};
    var chave = [biblia.bibliaVersao, biblia.livroId, biblia.capitulo, biblia.versiculoInicio, biblia.versiculoFim].join('-');

    renderTexto(biblia);
    renderPreview(biblia);

    if (window.KadosysBiblia) {
      window.KadosysBiblia.ajustarTamanhoTexto(stage, texto);
    }

    // Referencia mudou: as marcacoes a lapis sao efemeras por versiculo
    // (o servidor ja zera biblia_marcacao ao trocar a referencia). Excecao:
    // na primeira aplicacao de estado (ex.: o preletor acabou de abrir ou
    // recarregou a pagina), carrega a marcacao ja existente em vez de
    // limpar, para nao apagar o que outro dispositivo desenhou.
    var primeiraAplicacao = lastReferenciaChave === null;

    if (chave !== lastReferenciaChave) {
      lastReferenciaChave = chave;

      if (primeiraAplicacao && biblia.marcacao && biblia.marcacao.length) {
        tracos = biblia.marcacao;
        redesenharTracos();
      } else {
        limparCanvas(true);
      }
    } else if (biblia.marcacao) {
      // Mesma referencia: outro dispositivo (operador ou o proprio
      // preletor em outra aba) pode ter alterado a marcacao - mantem em
      // sincronia sem redesenhar do zero a cada poll se nada mudou.
      var chaveMarcacao = JSON.stringify(biblia.marcacao);

      if (chaveMarcacao !== JSON.stringify(tracos)) {
        tracos = biblia.marcacao;
        redesenharTracos();
      }
    }
  }

  function navegar(direcao) {
    confirmarTroca().then(function (ok) {
      if (!ok) {
        return;
      }

      var dados = new URLSearchParams();
      dados.set('direcao', direcao);
      dados.set('origem', MEU_PAPEL);

      fetch(navegarUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: dados.toString(),
      })
        .then(function (resposta) {
          return resposta.json();
        })
        .then(function (dadosResposta) {
          if (dadosResposta.erro) {
            return;
          }

          lastVersao = dadosResposta.versao;
          aplicarEstado(dadosResposta);
        })
        .catch(function () {});
    });
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
      .catch(function () {
        // Falha de rede pontual; tenta novamente no proximo ciclo.
      });
  }

  if (window.KadosysBiblia) {
    window.KadosysBiblia.montarVersaoPills(form.querySelector('[data-versao-pills]'));
    window.KadosysBiblia.montarComboLivro(root.querySelector('[data-livro-combo]'));
    window.KadosysBiblia.montarPreletorCapitulo(form);
    window.KadosysBiblia.montarPreletorVersiculos(form, capituloInfoUrl);
  }

  var versiculoInicioSelect = form.querySelector('[data-campo="versiculo_inicio"]');
  var versiculoFimSelect = form.querySelector('[data-campo="versiculo_fim"]');

  var NOMES_MODO = { biblia: 'a Bíblia', video: 'o vídeo', logo: 'a logo' };
  var NOMES_ORIGEM = { operador: 'O painel do operador', preletor: 'O preletor' };

  // Trocar para a biblia interrompe o que ja esta sendo exibido ao vivo
  // no telao (video ou logo) - confirma antes, para evitar trocar por
  // engano no meio de um culto. Nao pede nada se a biblia ja e o que
  // esta em exibicao (so navegando entre versiculos).
  //
  // Tambem cobre "quem esta no comando": o painel do operador pode
  // estar controlando o mesmo telao ao mesmo tempo que este tablet -
  // sem isso, um lado sobrescrevia silenciosamente o que o outro
  // estava exibindo. Quando o OUTRO lado esta no comando, pede
  // confirmacao mesmo so navegando entre versiculos.
  //
  // Assincrono (Promise<boolean>) porque usa o popup proprio do
  // sistema (ver kadosys-modal.js) no lugar do window.confirm nativo.
  function confirmarTroca() {
    var outroTemComando = !!controladoPorAtual && controladoPorAtual !== MEU_PAPEL;
    var mudandoModo = !!modoAtual && modoAtual !== 'blank' && modoAtual !== 'biblia';

    if (!outroTemComando && !mudandoModo) {
      return Promise.resolve(true);
    }

    var mensagem;

    if (outroTemComando && mudandoModo) {
      mensagem = NOMES_ORIGEM[controladoPorAtual] + ' está no comando agora, exibindo ' + (NOMES_MODO[modoAtual] || 'outro conteúdo') + '. Assumir o comando e trocar para a Bíblia?';
    } else if (outroTemComando) {
      mensagem = NOMES_ORIGEM[controladoPorAtual] + ' está no comando agora. Assumir o comando e continuar?';
    } else {
      mensagem = 'Já tem ' + (NOMES_MODO[modoAtual] || 'outro conteúdo') + ' em exibição no telão. Trocar para a Bíblia agora?';
    }

    if (!window.KadosysModal) {
      return Promise.resolve(window.confirm(mensagem));
    }

    return window.KadosysModal.confirmar(mensagem, { confirmar: outroTemComando ? 'Assumir comando' : 'Trocar', icone: 'bi-book' });
  }

  function projetar() {
    if (!livroSelect.value || !capituloSelect.value || !versiculoInicioSelect.value) {
      return;
    }

    confirmarTroca().then(function (ok) {
      if (!ok) {
        return;
      }

      var dados = new URLSearchParams();
      dados.set('biblia_versao', form.querySelector('[data-campo="biblia_versao"]').value);
      dados.set('livro_id', livroSelect.value);
      dados.set('capitulo', capituloSelect.value);
      dados.set('versiculo_inicio', versiculoInicioSelect.value);

      if (versiculoFimSelect.value) {
        dados.set('versiculo_fim', versiculoFimSelect.value);
      }

      dados.set('origem', MEU_PAPEL);

      fetch(bibliaUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: dados.toString(),
      })
        .then(function () {
          lastVersao = null;
          poll();
        })
        .catch(function () {});
    });
  }

  form.addEventListener('submit', function (evento) {
    evento.preventDefault();
    projetar();
  });

  // Trocar o versiculo (inicio/fim) ja projeta direto no telao, sem
  // precisar clicar em "Projetar" de novo a cada verso.
  versiculoInicioSelect.addEventListener('change', projetar);
  versiculoFimSelect.addEventListener('change', projetar);

  botoesNav.forEach(function (botao) {
    botao.addEventListener('click', function () {
      navegar(botao.getAttribute('data-nav-acao'));
    });
  });

  document.addEventListener('keydown', function (evento) {
    var alvo = evento.target;
    var estaDigitando = alvo.tagName === 'INPUT' || alvo.tagName === 'SELECT' || alvo.tagName === 'TEXTAREA';

    if (estaDigitando) {
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

  toolPenBtn.addEventListener('click', alternarCaneta);
  toolClearBtn.addEventListener('click', function () {
    limparCanvas(false);
  });

  function chamarSePromise(resultado) {
    if (resultado && typeof resultado.catch === 'function') {
      resultado.catch(function () {});
    }
  }

  // "Modo foco" (troca de classe CSS) e o mecanismo principal do botao de
  // tela cheia: esconde o topo e a previa, sobrando mais espaco pro
  // palco. A Fullscreen API nativa do navegador e so um bonus por cima
  // (esconde tambem a barra de enderecos) - em alguns tablets/navegadores
  // ela e bloqueada silenciosamente, e sem o modo foco por CSS o botao
  // parecia "nao fazer nada" nesses aparelhos.
  var telaCheiaAtiva = false;

  function definirIconeTelaCheia(ativo) {
    var icone = toolFullscreenBtn.querySelector('i');

    toolFullscreenBtn.classList.toggle('active', ativo);
    toolFullscreenBtn.setAttribute('aria-pressed', ativo ? 'true' : 'false');
    toolFullscreenBtn.title = ativo ? 'Sair da tela cheia' : 'Tela cheia';

    if (icone) {
      icone.className = ativo ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
    }
  }

  function alternarTelaCheia() {
    telaCheiaAtiva = !telaCheiaAtiva;
    root.classList.toggle('modo-foco', telaCheiaAtiva);
    definirIconeTelaCheia(telaCheiaAtiva);
    ajustarCanvas();

    try {
      if (telaCheiaAtiva) {
        var pedido = document.documentElement.requestFullscreen || document.documentElement.webkitRequestFullscreen;

        if (pedido) {
          chamarSePromise(pedido.call(document.documentElement));
        }
      } else if (document.fullscreenElement || document.webkitFullscreenElement) {
        var saida = document.exitFullscreen || document.webkitExitFullscreen;

        if (saida) {
          chamarSePromise(saida.call(document));
        }
      }
    } catch (erro) {
      // Navegador sem suporte a Fullscreen API ou chamada bloqueada; o
      // modo foco (CSS) acima ja garante o efeito principal mesmo assim.
    }
  }

  if (toolFullscreenBtn) {
    toolFullscreenBtn.addEventListener('click', alternarTelaCheia);

    // Sincroniza o modo foco se o usuario sair da tela cheia nativa por
    // fora do botao (tecla ESC, gesto de voltar do Android, etc.).
    document.addEventListener('fullscreenchange', function () {
      var emTelaCheia = !!(document.fullscreenElement || document.webkitFullscreenElement);

      if (!emTelaCheia && telaCheiaAtiva) {
        telaCheiaAtiva = false;
        root.classList.remove('modo-foco');
        definirIconeTelaCheia(false);
        ajustarCanvas();
      }
    });
  }

  if (toolAjudaBtn && ajudaPopup) {
    toolAjudaBtn.addEventListener('click', function (evento) {
      evento.stopPropagation();
      ajudaPopup.hidden = !ajudaPopup.hidden;
    });
  }

  if (ajudaFechar) {
    ajudaFechar.addEventListener('click', function () {
      ajudaPopup.hidden = true;
    });
  }

  document.addEventListener('click', function (evento) {
    if (!ajudaPopup || ajudaPopup.hidden) {
      return;
    }

    if (ajudaPopup.contains(evento.target)) {
      return;
    }

    ajudaPopup.hidden = true;
  });

  canvas.addEventListener('pointerdown', iniciarTraco);
  canvas.addEventListener('pointermove', continuarTraco);
  canvas.addEventListener('pointerup', finalizarTraco);
  canvas.addEventListener('pointercancel', finalizarTraco);

  // ResizeObserver em vez de so "window resize": o espaco disponivel para
  // o palco muda tambem quando a barra "A seguir" aparece/some ou quando
  // os campos do formulario quebram linha - nada disso dispara resize da
  // janela, mas precisa remedir o palco, senao a marcacao a lapis fica
  // registrada com a proporcao antiga (errada) e cai no lugar errado.
  if (window.ResizeObserver) {
    new ResizeObserver(ajustarCanvas).observe(canvasWrap);
  } else {
    window.addEventListener('resize', ajustarCanvas);
  }

  ajustarCanvas();
  setInterval(poll, 1500);
  poll();
})();
