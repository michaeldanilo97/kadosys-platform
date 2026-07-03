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
      if (status === 'pendente') {
        return;
      }

      if (status === 'paga') {
        clearInterval(intervalo);
        statusEl.classList.add('confirmado');
        statusEl.innerHTML = '<i class="bi bi-check-circle"></i> Pagamento confirmado! Liberando o acesso...';

        setTimeout(function () {
          window.location.href = retornoUrl;
        }, 1500);
      }

      // 'expirada'/'cancelada': deixa o polling continuar - o admin pode
      // atualizar a pagina pra gerar uma cobranca nova (ver
      // ConfiguracaoController::faturaVencida).
    }
  }
})();
