(function () {
  'use strict';

  var root = document.querySelector('[data-preletor]');

  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url');
  var bibliaUrl = root.getAttribute('data-biblia-url');
  var texto = root.querySelector('[data-preletor-texto]');
  var canvas = root.querySelector('[data-preletor-canvas]');
  var ctx = canvas.getContext('2d');
  var toolPenBtn = root.querySelector('[data-tool-pen]');
  var toolClearBtn = root.querySelector('[data-tool-clear]');
  var form = root.querySelector('[data-preletor-form]');
  var livroSelect = form.querySelector('[data-campo="livro_id"]');
  var capituloInput = form.querySelector('[data-campo="capitulo"]');

  var lastVersao = null;
  var lastReferenciaChave = null;
  var desenhando = false;
  var caneteAtiva = false;
  var ultimoPonto = null;

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value;

    return div.innerHTML;
  }

  function ajustarCanvas() {
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
  }

  function limparCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  }

  function alternarCaneta() {
    caneteAtiva = !caneteAtiva;
    canvas.style.pointerEvents = caneteAtiva ? 'auto' : 'none';
    toolPenBtn.classList.toggle('active', caneteAtiva);
  }

  function posicaoRelativa(evento) {
    var retangulo = canvas.getBoundingClientRect();

    return {
      x: evento.clientX - retangulo.left,
      y: evento.clientY - retangulo.top,
    };
  }

  function iniciarTraco(evento) {
    if (!caneteAtiva) {
      return;
    }

    desenhando = true;
    ultimoPonto = posicaoRelativa(evento);
    canvas.setPointerCapture(evento.pointerId);
  }

  function continuarTraco(evento) {
    if (!desenhando) {
      return;
    }

    var ponto = posicaoRelativa(evento);

    ctx.beginPath();
    ctx.moveTo(ultimoPonto.x, ultimoPonto.y);
    ctx.lineTo(ponto.x, ponto.y);
    ctx.stroke();

    ultimoPonto = ponto;
  }

  function finalizarTraco() {
    desenhando = false;
    ultimoPonto = null;
  }

  function renderTexto(biblia) {
    if (!biblia || !biblia.versiculos || !biblia.versiculos.length) {
      texto.innerHTML = '<p class="preletor-empty">Escolha um livro, capitulo e versiculo acima para projetar.</p>';

      return;
    }

    var referencia = biblia.livroNome + ' ' + biblia.capitulo + ':' + biblia.versiculoInicio;

    if (biblia.versiculoFim && biblia.versiculoFim !== biblia.versiculoInicio) {
      referencia += '-' + biblia.versiculoFim;
    }

    if (biblia.bibliaVersao) {
      referencia += ' · ' + biblia.bibliaVersao.toUpperCase();
    }

    var corpo = biblia.versiculos.map(function (versiculo) {
      return '<span class="numero">' + versiculo.numero + '</span>' + escapeHtml(versiculo.texto) + ' ';
    }).join('');

    texto.innerHTML = '<div class="ref">' + escapeHtml(referencia) + '</div><div>' + corpo + '</div>';
  }

  function aplicarEstado(dados) {
    if (!dados || dados.ativo === false || dados.modo !== 'biblia') {
      return;
    }

    var biblia = dados.biblia || {};
    var chave = [biblia.bibliaVersao, biblia.livroId, biblia.capitulo, biblia.versiculoInicio, biblia.versiculoFim].join('-');

    renderTexto(biblia);

    // Referencia mudou: as marcacoes a lapis sao efemeras por versiculo.
    if (chave !== lastReferenciaChave) {
      lastReferenciaChave = chave;
      limparCanvas();
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
      .catch(function () {
        // Falha de rede pontual; tenta novamente no proximo ciclo.
      });
  }

  livroSelect.addEventListener('change', function () {
    var opcaoSelecionada = livroSelect.options[livroSelect.selectedIndex];
    var totalCapitulos = opcaoSelecionada ? opcaoSelecionada.getAttribute('data-total-capitulos') : null;

    if (totalCapitulos) {
      capituloInput.setAttribute('max', totalCapitulos);
    }
  });

  form.addEventListener('submit', function (evento) {
    evento.preventDefault();

    var dados = new URLSearchParams();
    dados.set('biblia_versao', form.querySelector('[data-campo="biblia_versao"]').value);
    dados.set('livro_id', form.querySelector('[data-campo="livro_id"]').value);
    dados.set('capitulo', form.querySelector('[data-campo="capitulo"]').value);
    dados.set('versiculo_inicio', form.querySelector('[data-campo="versiculo_inicio"]').value);

    var fim = form.querySelector('[data-campo="versiculo_fim"]').value;
    if (fim) {
      dados.set('versiculo_fim', fim);
    }

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

  toolPenBtn.addEventListener('click', alternarCaneta);
  toolClearBtn.addEventListener('click', limparCanvas);

  canvas.addEventListener('pointerdown', iniciarTraco);
  canvas.addEventListener('pointermove', continuarTraco);
  canvas.addEventListener('pointerup', finalizarTraco);
  canvas.addEventListener('pointercancel', finalizarTraco);

  window.addEventListener('resize', ajustarCanvas);

  ajustarCanvas();
  setInterval(poll, 1500);
  poll();
})();
