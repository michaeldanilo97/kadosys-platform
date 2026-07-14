/**
 * Service Worker do Modo Culto / tela cheia de louvor: se a internet
 * cair durante o culto, quem ja tinha a pagina aberta continua vendo a
 * ultima cifra/tom/repertorio carregado em vez de dar erro - e volta a
 * sincronizar sozinho quando a rede voltar, sem nenhum codigo extra
 * nas paginas (o fetch() de la simplesmente recebe a resposta em cache
 * como se a rede tivesse respondido).
 *
 * Estrategia "network-first com fallback pro cache": tenta a rede
 * sempre que possivel (pra nunca mostrar dado desatualizado enquanto
 * online) e so cai pro cache quando a rede falha de verdade. So mexe
 * em requisicoes GET do mesmo dominio - POST (avancar, mudar tom,
 * mensagem) nao e cacheavel e continua exigindo rede normalmente.
 */

var CACHE_NAME = 'kadosys-culto-v1';

self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys()
      .then(function (chaves) {
        return Promise.all(
          chaves
            .filter(function (chave) { return chave !== CACHE_NAME; })
            .map(function (chave) { return caches.delete(chave); })
        );
      })
      .then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  var request = event.request;

  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
    return;
  }

  event.respondWith(
    fetch(request)
      .then(function (resposta) {
        var copia = resposta.clone();
        caches.open(CACHE_NAME).then(function (cache) { cache.put(request, copia); });

        return resposta;
      })
      .catch(function () {
        return caches.match(request).then(function (emCache) {
          if (!emCache) {
            return Response.error();
          }

          // Marca a resposta como "veio do cache" (rede falhou de
          // verdade) - a pagina usa esse header pra saber quando
          // mostrar o aviso de "sem conexao" (ver
          // repertorio-culto.js/culto-offline.js), ja que so
          // conseguir os dados nao significa que a rede esta OK.
          var headers = new Headers(emCache.headers);
          headers.set('X-Kadosys-From-Cache', '1');

          return emCache.blob().then(function (corpo) {
            return new Response(corpo, {
              status: emCache.status,
              statusText: emCache.statusText,
              headers: headers,
            });
          });
        });
      })
  );
});
