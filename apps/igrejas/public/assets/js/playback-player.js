(function () {
  'use strict';

  // Controle de tom: muda o pitch alterando a velocidade de reproducao
  // (como o pitch control de um toca-discos) - despues do <audio>.
  // Navegadores modernos por padrao MANTEM o tom original quando a
  // velocidade muda (preservesPitch = true) - precisa desligar isso
  // explicitamente (com os prefixos antigos de Firefox/Safari) pra que
  // mudar a velocidade realmente suba/desça o tom.
  function desligarPreservacaoDeTom(audio) {
    audio.preservesPitch = false;
    audio.mozPreservesPitch = false;
    audio.webkitPreservesPitch = false;
  }

  function aplicarTom(audio, semitons) {
    var taxa = Math.pow(2, semitons / 12);
    desligarPreservacaoDeTom(audio);
    audio.playbackRate = taxa;
  }

  document.querySelectorAll('.playback-item').forEach(function (item) {
    var audio = item.querySelector('[data-playback-audio]');
    var seletorTom = item.querySelector('[data-playback-tom]');

    if (!audio || !seletorTom) {
      return;
    }

    // Alguns navegadores resetam preservesPitch/playbackRate quando uma
    // nova reproducao comeca (troca de src, ou apos pausa/seek) - reaplica
    // o tom selecionado a cada play, nao so na troca do <select>.
    audio.addEventListener('play', function () {
      aplicarTom(audio, parseInt(seletorTom.value, 10) || 0);
    });

    seletorTom.addEventListener('change', function () {
      aplicarTom(audio, parseInt(seletorTom.value, 10) || 0);
    });
  });
})();
