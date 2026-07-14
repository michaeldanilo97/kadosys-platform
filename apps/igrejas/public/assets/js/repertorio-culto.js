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
  var tomUrlBase = root.getAttribute('data-tom-url-base');

  var vazioEl = root.querySelector('[data-culto-vazio]');
  var conteudoEl = root.querySelector('[data-culto-conteudo]');
  var tituloEl = root.querySelector('[data-culto-titulo]');
  var tomEl = root.querySelector('[data-culto-tom]');
  var bpmEl = root.querySelector('[data-culto-bpm]');

  var tomControlesEl = root.querySelector('[data-culto-tom-controles]');
  var tomSelectEl = root.querySelector('[data-culto-tom-select]');
  var tomBaixarBtn = root.querySelector('[data-culto-tom-baixar]');
  var tomSubirBtn = root.querySelector('[data-culto-tom-subir]');

  /**
   * Aviso flutuante chamando atencao que o tom da musica atual mudou -
   * aparece pra TODOS os musicos (nao so quem mudou), ja que a
   * mudanca chega pra eles via o mesmo polling que acompanha a musica
   * atual. Some sozinho depois de alguns segundos.
   */
  function mostrarToast(mensagem) {
    var toast = document.createElement('div');
    toast.className = 'mc-toast';
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

  var ultimoTomItemId = null;
  var ultimoTomValor = null;

  // Mesma tabela cromatica do transpositor de cifras (ver
  // louvor-transpositor.js) - aqui so precisamos transpor a nota-raiz
  // do tom (ex.: "Fm" -> "F#m"), nao uma cifra inteira.
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

  var todosTons = (window.KADOSYS_TONS ? window.KADOSYS_TONS.maiores.concat(window.KADOSYS_TONS.menores) : []);

  if (tomSelectEl) {
    todosTons.forEach(function (tom) {
      var option = document.createElement('option');
      option.value = tom;
      option.textContent = tom;
      tomSelectEl.appendChild(option);
    });
  }

  /**
   * Garante que o tom atual da musica sempre apareca certo no select,
   * mesmo quando ele nao esta nas listas padrao (grafia antiga, ex.:
   * "Db" em vez de "C#") - sem isso, atribuir um valor sem <option>
   * correspondente faz o navegador desmarcar a selecao (select.value
   * volta pra "") e os botoes de +/- meio tom passam a calcular a
   * partir de um tom errado.
   */
  function garantirOpcaoTom(tom) {
    if (!tomSelectEl || !tom) {
      return;
    }

    var existe = Array.prototype.some.call(tomSelectEl.options, function (opcao) {
      return opcao.value === tom;
    });

    if (existe) {
      return;
    }

    var anterior = tomSelectEl.querySelector('[data-tom-outro]');

    if (anterior) {
      anterior.remove();
    }

    var opcao = document.createElement('option');
    opcao.value = tom;
    opcao.textContent = tom + ' (outro)';
    opcao.setAttribute('data-tom-outro', '1');
    tomSelectEl.appendChild(opcao);
  }

  function transporTom(tom, semitons) {
    var match = /^([A-G](?:#|b)?)(m?)$/.exec(tom || '');

    if (!match || INDICE_NOTA[match[1]] === undefined) {
      return null;
    }

    var novoIndice = ((INDICE_NOTA[match[1]] + semitons) % 12 + 12) % 12;

    return NOTAS_CANONICAS[novoIndice] + match[2];
  }
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
  var atualItemId = null;
  var alterandoTom = false;

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
      atualItemId = null;

      if (tomControlesEl) {
        tomControlesEl.hidden = true;
      }

      return;
    }

    vazioEl.hidden = true;
    conteudoEl.hidden = false;
    atualItemId = atual.id;

    tituloEl.textContent = atual.titulo;
    tomEl.textContent = atual.tomAtual ? 'Tom: ' + atual.tomAtual : '';
    bpmEl.textContent = atual.andamentoBpm ? atual.andamentoBpm + ' BPM' : '';
    letraEl.textContent = atual.letra || 'Letra ainda não cadastrada.';
    cifraEl.textContent = atual.cifra || 'Cifra ainda não cadastrada.';

    // So chama atencao quando o tom da MESMA musica muda ao vivo - se
    // for a primeira renderizacao ou a musica atual trocou (avancou/
    // voltou), o tom "novo" e so o tom normal dela, nao uma mudanca.
    if (ultimoTomItemId === atual.id && ultimoTomValor !== atual.tomAtual) {
      mostrarToast('Tom alterado para ' + (atual.tomAtual || 'nenhum') + '.');
    }

    ultimoTomItemId = atual.id;
    ultimoTomValor = atual.tomAtual;

    if (tomControlesEl && !alterandoTom) {
      tomControlesEl.hidden = false;
      garantirOpcaoTom(atual.tomAtual);
      tomSelectEl.value = atual.tomAtual || '';
    }

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

  function alterarTom(novoTom) {
    if (!atualItemId || !novoTom || alterandoTom) {
      return;
    }

    alterandoTom = true;

    var dados = new URLSearchParams();
    dados.set('tom', novoTom);

    fetch(tomUrlBase + '/' + atualItemId + '/tom', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: dados.toString(),
    })
      .then(function (resposta) { return resposta.json(); })
      .then(function (resultado) {
        alterandoTom = false;

        if (resultado.versao !== undefined) {
          lastVersao = resultado.versao;
          renderizarAtual(resultado);
        }
      })
      .catch(function () {
        alterandoTom = false;
      });
  }

  if (tomSelectEl) {
    tomSelectEl.addEventListener('change', function () { alterarTom(tomSelectEl.value); });
  }

  if (tomBaixarBtn) {
    tomBaixarBtn.addEventListener('click', function () { alterarTom(transporTom(tomSelectEl.value, -1)); });
  }

  if (tomSubirBtn) {
    tomSubirBtn.addEventListener('click', function () { alterarTom(transporTom(tomSelectEl.value, 1)); });
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
