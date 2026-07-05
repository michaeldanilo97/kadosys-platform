(function () {
  'use strict';

  // Resolve a URL do worker relativa a este proprio script (mesma pasta)
  // - funciona independente do "base_path" (instalacao central ou
  // qualquer subdominio de igreja), sem precisar de nenhuma variavel
  // global injetada pela view.
  var SCRIPT_URL = document.currentScript ? document.currentScript.src : '';
  var WORKER_URL = SCRIPT_URL.replace(/[^/]+$/, 'pitch-shift-worker.js');

  var buffersDecodificados = new WeakMap(); // <audio> -> Promise<AudioBuffer>
  var blobsPorTom = new WeakMap(); // <audio> -> Map<semitons, blobUrl>
  var contextoAudioCompartilhado = null;

  function contextoAudio() {
    if (!contextoAudioCompartilhado) {
      var AudioContextClasse = window.AudioContext || window.webkitAudioContext;
      contextoAudioCompartilhado = new AudioContextClasse();
    }
    return contextoAudioCompartilhado;
  }

  function obterAudioBufferDecodificado(audio) {
    if (!buffersDecodificados.has(audio)) {
      var promise = fetch(audio.dataset.originalSrc)
        .then(function (resposta) {
          return resposta.arrayBuffer();
        })
        .then(function (arrayBuffer) {
          return contextoAudio().decodeAudioData(arrayBuffer);
        });

      buffersDecodificados.set(audio, promise);
    }

    return buffersDecodificados.get(audio);
  }

  /**
   * Codifica canais de audio (Float32Array, -1..1) num WAV PCM 16-bit
   * padrao - formato simples e universalmente suportado por qualquer
   * <audio>, sem precisar de nenhuma biblioteca externa.
   */
  function codificarWav(canais, sampleRate) {
    var numCanais = canais.length;
    var numFrames = canais[0].length;
    var blockAlign = numCanais * 2;
    var dataSize = numFrames * blockAlign;
    var buffer = new ArrayBuffer(44 + dataSize);
    var view = new DataView(buffer);

    function escreverString(offset, texto) {
      for (var i = 0; i < texto.length; i++) {
        view.setUint8(offset + i, texto.charCodeAt(i));
      }
    }

    escreverString(0, 'RIFF');
    view.setUint32(4, 36 + dataSize, true);
    escreverString(8, 'WAVE');
    escreverString(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, numCanais, true);
    view.setUint32(24, sampleRate, true);
    view.setUint32(28, sampleRate * blockAlign, true);
    view.setUint16(32, blockAlign, true);
    view.setUint16(34, 16, true);
    escreverString(36, 'data');
    view.setUint32(40, dataSize, true);

    var offset = 44;

    for (var i = 0; i < numFrames; i++) {
      for (var canal = 0; canal < numCanais; canal++) {
        var amostra = Math.max(-1, Math.min(1, canais[canal][i]));
        view.setInt16(offset, amostra < 0 ? amostra * 0x8000 : amostra * 0x7fff, true);
        offset += 2;
      }
    }

    return new Blob([buffer], { type: 'audio/wav' });
  }

  function marcarProcessando(seletor, statusEl, processando) {
    seletor.disabled = processando;

    if (statusEl) {
      statusEl.hidden = !processando;
    }
  }

  /**
   * Troca o src do <audio> preservando posicao/estado de reproducao -
   * trocar o src de um <audio> normalmente reinicia a reproducao do
   * zero, o que seria uma experiencia ruim se o usuario mudar o tom no
   * meio da musica.
   */
  function aplicarSrc(audio, src) {
    if (audio.dataset.srcAtual === src) {
      return;
    }

    var estavaTocando = !audio.paused;
    var posicaoAtual = audio.currentTime;

    function restaurar() {
      audio.currentTime = Math.min(posicaoAtual, audio.duration || posicaoAtual);

      if (estavaTocando) {
        audio.play().catch(function () {
          // autoplay bloqueado - o usuario so precisa apertar play de
          // novo, sem consequencia pro tom aplicado.
        });
      }

      audio.removeEventListener('loadedmetadata', restaurar);
    }

    audio.addEventListener('loadedmetadata', restaurar);
    audio.dataset.srcAtual = src;
    audio.src = src;
    audio.load();
  }

  function processarTom(audio, seletor, statusEl, semitons) {
    var cache = blobsPorTom.get(audio) || new Map();
    blobsPorTom.set(audio, cache);

    if (semitons === 0) {
      aplicarSrc(audio, audio.dataset.originalSrc);

      return;
    }

    if (cache.has(semitons)) {
      aplicarSrc(audio, cache.get(semitons));

      return;
    }

    marcarProcessando(seletor, statusEl, true);

    obterAudioBufferDecodificado(audio)
      .then(function (audioBuffer) {
        var canais = [];

        for (var i = 0; i < audioBuffer.numberOfChannels; i++) {
          // copia (nao referencia direta) - o array vai ser TRANSFERIDO
          // pro worker (transferList), o que "esvazia" o buffer
          // original - precisamos manter o AudioBuffer decodificado
          // intacto pra poder trocar de tom de novo sem re-baixar/
          // decodificar o arquivo original.
          canais.push(Float32Array.from(audioBuffer.getChannelData(i)));
        }

        var worker = new Worker(WORKER_URL);

        worker.onmessage = function (evento) {
          var blob = codificarWav(evento.data.channels, audioBuffer.sampleRate);
          var url = URL.createObjectURL(blob);

          cache.set(semitons, url);
          worker.terminate();
          marcarProcessando(seletor, statusEl, false);
          aplicarSrc(audio, url);
        };

        worker.onerror = function (erro) {
          console.error('Erro ao processar o tom do playback:', erro.message);
          worker.terminate();
          marcarProcessando(seletor, statusEl, false);
          seletor.value = '0';
        };

        worker.postMessage(
          { semitons: semitons, sampleRate: audioBuffer.sampleRate, channels: canais },
          canais.map(function (canal) {
            return canal.buffer;
          })
        );
      })
      .catch(function (erro) {
        console.error('Erro ao decodificar o audio do playback:', erro.message);
        marcarProcessando(seletor, statusEl, false);
        seletor.value = '0';
      });
  }

  document.querySelectorAll('.playback-item').forEach(function (item) {
    var audio = item.querySelector('[data-playback-audio]');
    var seletorTom = item.querySelector('[data-playback-tom]');
    var statusEl = item.querySelector('[data-playback-tom-status]');

    if (!audio || !seletorTom) {
      return;
    }

    audio.dataset.originalSrc = audio.getAttribute('src');
    audio.dataset.srcAtual = audio.dataset.originalSrc;

    seletorTom.addEventListener('change', function () {
      var semitons = parseInt(seletorTom.value, 10) || 0;

      processarTom(audio, seletorTom, statusEl, semitons);
    });
  });
})();
