(function () {
  'use strict';

  var TENTATIVA_INTERVALO_MS = 3000;
  var MAX_TENTATIVAS = 20; // ~1 minuto

  document.addEventListener('DOMContentLoaded', function () {
    initVerificacao();
  });

  function initVerificacao() {
    var loginUrl = window.KADOSYS_LOGIN_URL;
    var statusUrl = window.KADOSYS_STATUS_URL;
    var statusEl = document.querySelector('[data-provisionamento-status]');
    var fallbackEl = document.querySelector('[data-provisionamento-fallback]');

    if (!loginUrl || !statusUrl || !statusEl) {
      return;
    }

    var tentativas = 0;
    var verificando = false;

    var intervalo = setInterval(function () {
      if (verificando) {
        return;
      }
      verificando = true;
      tentativas++;

      // O check roda no proprio servidor (mesma origem, sem CORS),
      // que consegue ler o status/corpo de verdade da pagina de login
      // do subdominio novo - so confirma "pronto" quando a pagina
      // carregou por completo, nao so quando o servidor respondeu
      // alguma coisa (o que incluiria uma pagina de erro).
      fetch(statusUrl, { cache: 'no-store' })
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
          verificando = false;

          if (!dados || !dados.pronto) {
            if (tentativas >= MAX_TENTATIVAS && fallbackEl) {
              fallbackEl.hidden = false;
            }

            return;
          }

          clearInterval(intervalo);
          statusEl.classList.add('confirmado');
          statusEl.innerHTML = '<i class="bi bi-check-circle"></i> Tudo pronto! Entrando...';

          setTimeout(function () {
            window.location.href = loginUrl;
          }, 800);
        })
        .catch(function () {
          verificando = false;

          if (tentativas >= MAX_TENTATIVAS && fallbackEl) {
            fallbackEl.hidden = false;
          }
        });
    }, TENTATIVA_INTERVALO_MS);
  }
})();
