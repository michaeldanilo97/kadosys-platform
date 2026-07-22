(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initToggle();
        initCopiar();
    });

    /**
     * Mostra o QR Code Pix so quando "Pix" esta selecionado na forma de
     * pagamento - o valor ja vem fixo no payload (gerado no servidor
     * com o preco do servico, ver AgendamentoController::pagamentoForm),
     * entao o QR e desenhado uma unica vez, na primeira vez que a
     * secao aparece.
     */
    function initToggle() {
        var select = document.getElementById('forma_pagamento');
        var secao = document.querySelector('[data-pix-secao]');

        if (!select || !secao) {
            return;
        }

        function atualizar() {
            var mostrar = select.value === 'pix';
            secao.hidden = !mostrar;

            if (mostrar) {
                renderizarQr();
            }
        }

        select.addEventListener('change', atualizar);
        atualizar();
    }

    var qrRenderizado = false;

    function renderizarQr() {
        if (qrRenderizado) {
            return;
        }

        var alvo = document.querySelector('[data-pix-qr]');

        if (!alvo || !window.qrcode || !window.KADOSYS_PIX_PAYLOAD) {
            return;
        }

        try {
            var qr = window.qrcode(0, 'M');
            qr.addData(window.KADOSYS_PIX_PAYLOAD);
            qr.make();

            alvo.innerHTML = qr.createImgTag(6, 4, 'QR code Pix');
            qrRenderizado = true;
        } catch (erro) {
            alvo.innerHTML = '<p class="form-field-hint">Não foi possível gerar o QR code. Use o código "copia e cola" abaixo.</p>';
        }
    }

    function initCopiar() {
        var botao = document.querySelector('[data-pix-copiar]');
        var input = document.querySelector('[data-pix-copia-cola]');

        if (!botao || !input) {
            return;
        }

        botao.addEventListener('click', function () {
            copiarTexto(input.value).then(function () {
                var textoOriginal = botao.textContent;
                botao.textContent = 'Copiado!';

                setTimeout(function () {
                    botao.textContent = textoOriginal;
                }, 1800);
            }).catch(function () {
                input.select();
            });
        });
    }

    function copiarTexto(texto) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(texto);
        }

        return new Promise(function (resolve, reject) {
            var campoTemp = document.createElement('textarea');
            campoTemp.value = texto;
            campoTemp.style.position = 'fixed';
            campoTemp.style.opacity = '0';
            document.body.appendChild(campoTemp);
            campoTemp.select();

            try {
                document.execCommand('copy');
                resolve();
            } catch (erro) {
                reject(erro);
            } finally {
                document.body.removeChild(campoTemp);
            }
        });
    }
})();
