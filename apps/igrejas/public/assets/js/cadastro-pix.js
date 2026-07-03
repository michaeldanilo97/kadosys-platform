(function () {
  'use strict';

  var POLL_INTERVALO_MS = 4000;

  document.addEventListener('DOMContentLoaded', function () {
    initCopiarCodigo();
    initPollingStatus();
  });

  function initCopiarCodigo() {
    var botao = document.querySelector('[data-pix-copiar]');
    var input = document.getElementById('pix_copia_cola');

    if (!botao || !input) {
      return;
    }

    botao.addEventListener('click', function () {
      copiarTexto(input.value).then(function () {
        var iconeOriginal = botao.innerHTML;
        botao.classList.add('copiado');
        botao.innerHTML = '<i class="bi bi-check2"></i>';

        setTimeout(function () {
          botao.classList.remove('copiado');
          botao.innerHTML = iconeOriginal;
        }, 2000);
      });
    });
  }

  function copiarTexto(texto) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(texto);
    }

    return new Promise(function (resolve) {
      var campoTemp = document.createElement('textarea');
      campoTemp.value = texto;
      campoTemp.style.position = 'fixed';
      campoTemp.style.opacity = '0';
      document.body.appendChild(campoTemp);
      campoTemp.select();
      document.execCommand('copy');
      document.body.removeChild(campoTemp);
      resolve();
    });
  }

  function initPollingStatus() {
    var statusEl = document.querySelector('[data-pix-status]');
    var statusUrl = window.KADOSYS_PIX_STATUS_URL;
    var retornoUrl = window.KADOSYS_PIX_RETORNO_URL;

    if (!statusEl || !statusUrl) {
      return;
    }

    var consultando = false;

    var intervalo = setInterval(function () {
      if (consultando) {
        return;
      }
      consultando = true;

      fetch(statusUrl, { headers: { Accept: 'application/json' } })
        .then(function (resposta) {
          return resposta.json();
        })
        .then(function (dados) {
          consultando = false;
          atualizarTela(dados.status);
        })
        .catch(function () {
          consultando = false;
        });
    }, POLL_INTERVALO_MS);

    function atualizarTela(status) {
      if (status === 'aguardando_pagamento') {
        return;
      }

      if (status === 'erro') {
        clearInterval(intervalo);
        statusEl.classList.add('erro');
        statusEl.innerHTML = '<i class="bi bi-x-circle"></i> Deu um problema ao confirmar o pagamento. Fale com o suporte.';
        return;
      }

      if (status === 'concluido') {
        clearInterval(intervalo);
        statusEl.classList.add('confirmado');
        statusEl.innerHTML = '<i class="bi bi-check-circle"></i> Pagamento confirmado! Preparando sua igreja...';

        setTimeout(function () {
          window.location.href = retornoUrl;
        }, 1500);
        return;
      }

      // pago_aguardando_provisionamento / provisionando: ja caiu o Pix,
      // so falta o banco da igreja ficar pronto.
      statusEl.classList.add('confirmado');
      statusEl.innerHTML = '<i class="bi bi-check-circle"></i> Pagamento recebido! Preparando sua igreja...';
    }
  }
})();
