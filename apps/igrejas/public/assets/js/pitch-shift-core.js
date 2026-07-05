/**
 * Algoritmo de mudanca de tom (subir/abaixar semitons) SEM mudar a
 * velocidade/duracao do audio - diferente de simplesmente mudar o
 * playbackRate (que muda tom E velocidade juntos, como um toca-discos).
 *
 * Tecnica: separa em 2 passos independentes -
 *   1) time-stretch (granular overlap-add): alonga ou encurta o audio
 *      SEM mudar o tom, usando janelas sobrepostas (Hann) somadas e
 *      normalizadas pela soma real das janelas em cada amostra (assim
 *      nao depende de acertar hop/overlap pra bater uma formula de
 *      normalizacao fixa).
 *   2) resample (interpolacao linear): reproduz o resultado do passo 1
 *      mais rapido ou mais devagar, o que muda o tom E devolve a
 *      duracao original (os dois efeitos se cancelam na duracao e se
 *      somam no tom).
 *
 * Arquivo sem dependencia de DOM/Web Audio de proposito - roda tanto no
 * worker (ver pitch-shift-worker.js) quanto em Node (ver o teste em
 * scratchpad), o que permite verificar a matematica (duracao e
 * frequencia resultante) sem precisar ouvir o audio.
 */
(function (root) {
    'use strict';

    function semitonsToFactor(semitons) {
        return Math.pow(2, semitons / 12);
    }

    function hannWindow(size) {
        var win = new Float32Array(size);

        for (var i = 0; i < size; i++) {
            win[i] = size > 1 ? 0.5 - 0.5 * Math.cos((2 * Math.PI * i) / (size - 1)) : 1;
        }

        return win;
    }

    /**
     * Acha, dentro de +-searchRadius amostras ao redor de "idealStart",
     * a posicao de leitura que melhor alinha em FASE com o grain anterior
     * (correlacao maxima entre o inicio do candidato e a "cauda" do grain
     * anterior, que e exatamente a regiao onde os dois vao se sobrepor no
     * overlap-add). Sem isso (OLA "ingenuo"), cada grain e somado numa
     * fase praticamente aleatoria em relacao ao anterior, o que cancela/
     * reforca trechos do sinal de forma imprevisivel e distorce o tom
     * resultante - e o motivo classico de um OLA simples soar "errado"
     * mesmo com a janela/normalizacao corretas.
     */
    function acharMelhorAlinhamento(input, idealStart, searchRadius, prevGrain, hopOut, correlationLen) {
        var melhorOffset = idealStart;
        var melhorScore = -Infinity;

        for (var delta = -searchRadius; delta <= searchRadius; delta++) {
            var candidateStart = idealStart + delta;

            if (candidateStart < 0 || candidateStart + correlationLen > input.length) {
                continue;
            }

            var score = 0;

            for (var i = 0; i < correlationLen; i++) {
                score += input[candidateStart + i] * prevGrain[hopOut + i];
            }

            if (score > melhorScore) {
                melhorScore = score;
                melhorOffset = candidateStart;
            }
        }

        return melhorOffset;
    }

    /**
     * Alonga (factor > 1) ou encurta (factor < 1) o sinal por "factor",
     * preservando o tom - saida tem aproximadamente input.length * factor
     * amostras. Usa WSOLA (busca de melhor alinhamento de fase entre
     * grains consecutivos, ver acharMelhorAlinhamento) em vez de OLA
     * simples, pra nao distorcer o tom por cancelamento de fase.
     */
    function timeStretch(input, factor, sampleRate) {
        var grainSize = Math.max(64, Math.round(sampleRate * 0.04));
        var hopOut = Math.max(1, Math.floor(grainSize / 4));
        var hopIn = hopOut / factor;
        var searchRadius = Math.max(1, Math.floor(hopOut / 4));
        var correlationLen = Math.min(128, grainSize - hopOut);
        var outputLength = Math.max(1, Math.round(input.length * factor));
        var output = new Float32Array(outputLength);
        var windowSum = new Float32Array(outputLength);
        var win = hannWindow(grainSize);

        var inPosIdeal = 0;
        var outPos = 0;
        var prevGrain = null;

        while (outPos < outputLength) {
            var idealStart = Math.round(inPosIdeal);
            var inStart = idealStart;

            if (prevGrain !== null) {
                inStart = acharMelhorAlinhamento(input, idealStart, searchRadius, prevGrain, hopOut, correlationLen);
            }

            var grain = new Float32Array(grainSize);

            for (var i = 0; i < grainSize; i++) {
                var inIdx = inStart + i;
                var outIdx = outPos + i;

                var amostra = inIdx >= 0 && inIdx < input.length ? input[inIdx] : 0;
                grain[i] = amostra;

                if (outIdx < outputLength) {
                    output[outIdx] += amostra * win[i];
                    windowSum[outIdx] += win[i];
                }
            }

            prevGrain = grain;
            inPosIdeal += hopIn;
            outPos += hopOut;
        }

        for (var j = 0; j < outputLength; j++) {
            if (windowSum[j] > 1e-6) {
                output[j] /= windowSum[j];
            }
        }

        return output;
    }

    /**
     * Reamostra o sinal por "factor" (factor > 1 = mais rapido/agudo,
     * factor < 1 = mais devagar/grave) via interpolacao linear.
     */
    function resample(input, factor) {
        var outputLength = Math.max(1, Math.floor(input.length / factor));
        var output = new Float32Array(outputLength);

        for (var i = 0; i < outputLength; i++) {
            var srcPos = i * factor;
            var idx = Math.floor(srcPos);
            var frac = srcPos - idx;
            var a = idx < input.length ? input[idx] : 0;
            var b = idx + 1 < input.length ? input[idx + 1] : a;

            output[i] = a + (b - a) * frac;
        }

        return output;
    }

    /**
     * Muda o tom de um canal mono por "semitons" semitons, mantendo a
     * MESMA quantidade de amostras de entrada (duracao identica).
     */
    function pitchShiftMono(input, semitons, sampleRate) {
        if (semitons === 0) {
            return Float32Array.from(input);
        }

        var factor = semitonsToFactor(semitons);
        var stretched = timeStretch(input, factor, sampleRate);
        var resampled = resample(stretched, factor);

        if (resampled.length === input.length) {
            return resampled;
        }

        var output = new Float32Array(input.length);
        output.set(resampled.subarray(0, Math.min(resampled.length, input.length)));

        return output;
    }

    var api = {
        semitonsToFactor: semitonsToFactor,
        timeStretch: timeStretch,
        resample: resample,
        pitchShiftMono: pitchShiftMono,
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    } else {
        root.PitchShiftCore = api;
    }
})(typeof self !== 'undefined' ? self : this);
