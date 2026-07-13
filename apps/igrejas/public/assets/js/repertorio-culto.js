(function () {
  'use strict';

  var root = document.querySelector('[data-culto-root]');

  if (!root) {
    return;
  }

  var estadoUrl = root.getAttribute('data-estado-url');
  var avancarUrl = root.getAttribute('data-avancar-url');
  var voltarUrl = root.getAttribute('data-voltar-url');
  var mensagemUrl = root.getAttribute('data-mensagem-url');

  var vazioEl = root.querySelector('[data-culto-vazio]');
  var conteudoEl = root.querySelector('[data-culto-conteudo]');
  var tituloEl = root.querySelector('[data-culto-titulo]');
  var tomEl = root.querySelector('[data-culto-tom]');
  var bpmEl = root.querySelector('[data-culto-bpm]');
  var proximoEl = root.querySelector('[data-culto-proximo]');
  var letraEl = root.querySelector('[data-culto-texto="letra"]');
  var cifraEl = root.querySelector('[data-culto-texto="cifra"]');
  var abaLetra = root.querySelector('[data-culto-aba="letra"]');
  var abaCifra = root.querySelector('[data-culto-aba="cifra"]');

  var botaoAvancar = root.querySelector('[data-culto-avancar]');
  var botaoVoltar = root.querySelector('[data-culto-voltar]');

  var chatPainel = root.querySelector('[data-culto-chat]');
  var chatToggle = root.querySelector('[data-culto-chat-toggle]');
  var chatFechar = root.querySelector('[data-culto-chat-fechar]');
  var chatLista = root.querySelector('[data-culto-chat-lista]');
  var chatForm = root.querySelector('[data-culto-chat-form]');
  var chatInput = root.querySelector('[data-culto-chat-input]');
  var chatBadge = root.querySelector('[data-culto-chat-badge]');

  var lastVersao = null;
  var lastMensagemId = 0;
  var naoLidas = 0;
  var chatAberto = false;

  function mostrarAba(aba) {
    var ehCifra = aba === 'cifra';
    letraEl.hidden = ehCifra;
    cifraEl.hidden = !ehCifra;
    abaLetra.classList.toggle('is-ativa', !ehCifra);
    abaCifra.classList.toggle('is-ativa', ehCifra);
  }

  abaLetra.addEventListener('click', function () { mostrarAba('letra'); });
  abaCifra.addEventListener('click', function () { mostrarAba('cifra'); });

  /**
   * Renderiza a musica atual (ou o aviso de "aguardando o lider") e o
   * indicador de "proxima: ..." - chamado sempre que o poll detecta que
   * a versao (ordem/atual) mudou, ou logo apos o lider clicar em
   * avancar/voltar (resposta otimista, sem esperar o proximo poll).
   */
  function renderizarAtual(dados) {
    var itens = dados.itens || [];
    var atual = itens.find(function (item) { return item.id === dados.atualItemId; });

    if (!atual) {
      vazioEl.hidden = false;
      conteudoEl.hidden = true;
      proximoEl.textContent = '';

      return;
    }

    vazioEl.hidden = true;
    conteudoEl.hidden = false;

    tituloEl.textContent = atual.titulo;
    tomEl.textContent = atual.tomAtual ? 'Tom: ' + atual.tomAtual : '';
    bpmEl.textContent = atual.andamentoBpm ? atual.andamentoBpm + ' BPM' : '';
    letraEl.textContent = atual.letra || 'Letra ainda não cadastrada.';
    cifraEl.textContent = atual.cifra || 'Cifra ainda não cadastrada.';

    var posicaoAtual = itens.findIndex(function (item) { return item.id === atual.id; });
    var proximo = itens[posicaoAtual + 1];
    proximoEl.textContent = proximo ? 'Próxima: ' + proximo.titulo : 'Última música do repertório';
  }

  function renderizarChat(mensagens) {
    mensagens.forEach(function (mensagem) {
      if (mensagem.id <= lastMensagemId) {
        return;
      }

      lastMensagemId = mensagem.id;

      var bloco = document.createElement('div');
      bloco.className = 'mc-chat-msg';
      bloco.innerHTML = '<strong></strong><span></span>';
      bloco.querySelector('strong').textContent = mensagem.nome + ':';
      bloco.querySelector('span').textContent = ' ' + mensagem.texto;
      chatLista.appendChild(bloco);

      if (!chatAberto) {
        naoLidas++;
      }
    });

    chatLista.scrollTop = chatLista.scrollHeight;
    atualizarBadge();
  }

  function atualizarBadge() {
    if (chatBadge) {
      chatBadge.hidden = naoLidas === 0;
      chatBadge.textContent = String(naoLidas);
    }
  }

  function abrirChat() {
    chatAberto = true;
    naoLidas = 0;
    atualizarBadge();
    chatPainel.hidden = false;
    chatLista.scrollTop = chatLista.scrollHeight;
  }

  function fecharChat() {
    chatAberto = false;
    chatPainel.hidden = true;
  }

  chatToggle.addEventListener('click', function () {
    if (chatPainel.hidden) {
      abrirChat();
    } else {
      fecharChat();
    }
  });

  if (chatFechar) {
    chatFechar.addEventListener('click', fecharChat);
  }

  if (chatForm) {
    chatForm.addEventListener('submit', function (evento) {
      evento.preventDefault();

      var texto = chatInput.value.trim();

      if (texto === '') {
        return;
      }

      chatInput.value = '';

      var dados = new URLSearchParams();
      dados.set('texto', texto);

      fetch(mensagemUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: dados.toString(),
      });
    });
  }

  function enviarComando(url) {
    fetch(url, { method: 'POST' })
      .then(function (resposta) { return resposta.json(); })
      .then(function (dados) {
        if (dados.versao !== undefined) {
          lastVersao = dados.versao;
          renderizarAtual(dados);
        }
      });
  }

  if (botaoAvancar) {
    botaoAvancar.addEventListener('click', function () { enviarComando(avancarUrl); });
  }

  if (botaoVoltar) {
    botaoVoltar.addEventListener('click', function () { enviarComando(voltarUrl); });
  }

  function poll() {
    fetch(estadoUrl, { cache: 'no-store' })
      .then(function (resposta) { return resposta.json(); })
      .then(function (dados) {
        if (dados.ativo === false) {
          return;
        }

        if (dados.versao !== lastVersao) {
          lastVersao = dados.versao;
          renderizarAtual(dados);
        }

        if (dados.mensagens) {
          renderizarChat(dados.mensagens);
        }
      })
      .catch(function () {
        // Falha de rede pontual; tenta de novo no proximo ciclo.
      });
  }

  var estadoInicialTag = document.getElementById('culto-estado-inicial');

  if (estadoInicialTag) {
    try {
      var estadoInicial = JSON.parse(estadoInicialTag.textContent);
      lastVersao = estadoInicial.versao;
      renderizarAtual(estadoInicial);

      if (estadoInicial.mensagens) {
        renderizarChat(estadoInicial.mensagens);
        naoLidas = 0;
        atualizarBadge();
      }
    } catch (erro) {
      // Sem estado inicial valido; o primeiro poll resolve.
    }
  }

  setInterval(poll, 1200);
})();
