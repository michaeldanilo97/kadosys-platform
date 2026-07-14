/**
 * Registra o Service Worker (ver public/service-worker.js) e mostra um
 * aviso discreto quando a pagina detecta que esta sem internet - usado
 * no Modo Culto e na tela cheia de louvor, os dois lugares pensados
 * pra ficar aberto durante o culto de verdade.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-sw-scope]');

  if (!root) {
    return;
  }

  function primeCache() {
    // Forca a pagina atual e os arquivos estaticos dela (CSS/JS) a
    // passar pelo Service Worker pelo menos uma vez, agora que ele
    // esta no controle - sem isso, so a navegacao INICIAL (antes do SW
    // instalar, que nunca passa por ele) fica de fora do cache, e um F5
    // durante uma queda de internet cai na tela de erro do proprio
    // navegador em vez de mostrar a ultima versao salva.
    fetch(window.location.href, { cache: 'reload' }).catch(function () {});

    Array.prototype.forEach.call(
      document.querySelectorAll('link[rel="stylesheet"], script[src]'),
      function (elemento) {
        var url = elemento.href || elemento.src;

        if (url) {
          fetch(url, { cache: 'reload' }).catch(function () {});
        }
      }
    );
  }

  if ('serviceWorker' in navigator) {
    var swUrl = root.getAttribute('data-sw-url');
    var swScope = root.getAttribute('data-sw-scope');

    navigator.serviceWorker.register(swUrl, { scope: swScope }).catch(function () {
      // Sem suporte ou falha no registro - a pagina continua
      // funcionando normalmente online, so sem a continuidade offline.
    });

    if (navigator.serviceWorker.controller) {
      primeCache();
    } else {
      navigator.serviceWorker.addEventListener('controllerchange', primeCache, { once: true });
    }
  }

  var banner = null;

  function mostrarBanner() {
    if (banner) {
      return;
    }

    var mensagem = root.getAttribute('data-sw-offline-msg')
      || 'Sem conexão - mostrando a última atualização recebida.';

    banner = document.createElement('div');
    banner.className = 'kadosys-offline-banner';
    banner.setAttribute('role', 'status');
    banner.innerHTML = '<i class="bi bi-wifi-off"></i> ' + mensagem;
    document.body.appendChild(banner);
  }

  function esconderBanner() {
    if (!banner) {
      return;
    }

    banner.remove();
    banner = null;
  }

  // navigator.onLine cobre o caso comum (wifi/dados caiu de verdade);
  // ver repertorio-culto.js pros eventos "kadosys:offline"/
  // "kadosys:online", que sao o sinal mais preciso (baseado em falha
  // real do polling, nao so no estado da interface de rede).
  window.addEventListener('offline', mostrarBanner);
  window.addEventListener('online', esconderBanner);
  window.addEventListener('kadosys:offline', mostrarBanner);
  window.addEventListener('kadosys:online', esconderBanner);

  if (!navigator.onLine) {
    mostrarBanner();
  }
})();
