(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initCodigoBarras();
        initPixQr();
        initPixCopiar();
    });

    /**
     * Campo de codigo de barras: qualquer leitor USB "digita" o codigo
     * seguido de Enter, como um teclado normal - nao precisa de SDK
     * nenhum. Ao dar Enter, procura o produto pelo codigo no mapa
     * embutido em data-produtos (ver dashboard/pdv/index.php) e envia o
     * form escondido - reaproveita o MESMO endpoint que os tiles de
     * produto usam, sem logica de negocio duplicada aqui.
     */
    function initCodigoBarras() {
        var input = document.querySelector('[data-codigo-barras-input]');
        var shell = document.querySelector('[data-pdv-shell]');
        var form = document.querySelector('[data-form-codigo-barras]');
        var campoProdutoId = document.querySelector('[data-campo-produto-id]');
        var dica = document.querySelector('[data-codigo-barras-hint]');

        if (!input || !shell || !form || !campoProdutoId) {
            return;
        }

        var produtos = [];

        try {
            produtos = JSON.parse(shell.getAttribute('data-produtos') || '[]');
        } catch (erro) {
            produtos = [];
        }

        input.addEventListener('keydown', function (evento) {
            if (evento.key !== 'Enter') {
                return;
            }

            evento.preventDefault();
            var codigo = input.value.trim();

            if (codigo === '') {
                return;
            }

            var encontrado = produtos.find(function (produto) {
                return produto.codigoBarras === codigo;
            });

            if (!encontrado) {
                if (dica) {
                    dica.textContent = 'Código não encontrado: ' + codigo;
                    dica.hidden = false;
                }

                input.select();

                return;
            }

            if (dica) {
                dica.hidden = true;
            }

            campoProdutoId.value = String(encontrado.id);
            form.submit();
        });
    }

    /**
     * Renderiza o QR code Pix (valor ja fixo no payload, montado no
     * servidor com o restante a pagar - ver PdvController::pagamentoForm)
     * a primeira vez que a secao aparecer na tela de pagamento.
     */
    function initPixQr() {
        var alvo = document.querySelector('[data-pix-qr]');

        if (!alvo || !window.qrcode || !window.KADOSYS_PIX_PAYLOAD) {
            return;
        }

        try {
            var qr = window.qrcode(0, 'M');
            qr.addData(window.KADOSYS_PIX_PAYLOAD);
            qr.make();
            alvo.innerHTML = qr.createImgTag(5, 4, 'QR code Pix');
        } catch (erro) {
            alvo.innerHTML = '<p class="form-field-hint">Não foi possível gerar o QR code. Use o código "copia e cola" abaixo.</p>';
        }
    }

    function initPixCopiar() {
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
