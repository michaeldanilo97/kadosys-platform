(function () {
  'use strict';

  var TENTATIVA_INTERVALO_MS = 3000;
  var MAX_TENTATIVAS = 20; // ~1 minuto

  document.addEventListener('DOMContentLoaded', function () {
    initVerificacao();
  });

  function initVerificacao() {
    var loginUrl = window.KADOSYS_LOGIN_URL;
    var statusEl = document.querySelector('[data-provisionamento-status]');
    var fallbackEl = document.querySelector('[data-provisionamento-fallback]');

    if (!loginUrl || !statusEl) {
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

      // "no-cors" nao deixa ler o status da resposta, mas a Promise so
      // rejeita se o navegador nem conseguir chegar no servidor (DNS
      // ainda nao propagou) - suficiente pra saber quando da pra
      // redirecionar com seguranca pro subdominio novo.
      fetch(loginUrl, { mode: 'no-cors', cache: 'no-store' })
        .then(function () {
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
