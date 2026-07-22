/*
 * Service worker do painel KADOSYS Academias.
 *
 * So cacheia assets estaticos (CSS/JS/icones) - nunca paginas HTML,
 * ja que o dashboard e multi-tenant e depende de sessao (cachear uma
 * pagina serviria dados de outra academia/usuario pra quem abrir o
 * app offline depois). O ganho e so o app "abrir instantaneo" (visual
 * shell) mesmo em conexao ruim - os dados em si sempre vem da rede.
 */
const CACHE_NAME = 'kadosys-academias-v1';

const APP_SHELL = [
    './assets/css/app.css',
    './assets/css/site.css',
    './assets/js/dashboard.js',
    './assets/icons/icon-192.png',
    './assets/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(APP_SHELL))
            .catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET' || !request.url.includes('/assets/')) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                return response;
            });
        })
    );
});
