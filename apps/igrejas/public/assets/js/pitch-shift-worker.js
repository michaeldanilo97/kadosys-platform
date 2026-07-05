/**
 * Web Worker que roda o algoritmo de mudanca de tom (ver
 * pitch-shift-core.js) fora da thread principal - processar uma musica
 * inteira pode levar alguns segundos, o que travaria a pagina toda se
 * rodasse na thread da UI.
 *
 * Mensagem recebida: { semitons: number, sampleRate: number, channels: Float32Array[] }
 * (os buffers dos canais sao transferidos, nao copiados - ver
 * postMessage com transferList em playback-player.js).
 *
 * Mensagem enviada de volta: { channels: Float32Array[] } (tambem via
 * transferList, pra nao duplicar memoria).
 */
importScripts('pitch-shift-core.js');

self.onmessage = function (evento) {
    var dados = evento.data;
    var canaisProcessados = dados.channels.map(function (canal) {
        return PitchShiftCore.pitchShiftMono(canal, dados.semitons, dados.sampleRate);
    });

    self.postMessage(
        { channels: canaisProcessados },
        canaisProcessados.map(function (canal) {
            return canal.buffer;
        })
    );
};
