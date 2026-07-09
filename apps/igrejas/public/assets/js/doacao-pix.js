(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    renderizarQr();
    initCopiar();
  });

  /**
   * Desenha o QR code inteiramente no navegador (ver
   * assets/js/vendor/qrcode-generator.js) a partir do payload BR Code
   * ja montado no servidor (Igrejas\Core\PixEstatico) - sem nenhuma
   * chamada de rede, so processamento local.
   */
  function renderizarQr() {
    var alvo = document.querySelector('[data-pix-qr]');

    if (!alvo || !window.qrcode || !window.KADOSYS_PIX_PAYLOAD) {
      return;
    }

    try {
      // typeNumber 0 = a biblioteca escolhe o menor tamanho de QR que
      // comporta o payload; nivel de correcao de erro 'M' (15%) e o
      // equilibrio padrao usado pra Pix (QR pequeno o bastante pra
      // caber na tela, robusto o bastante pra tolerar uma foto/print
      // de tela amassada).
      var qr = window.qrcode(0, 'M');
      qr.addData(window.KADOSYS_PIX_PAYLOAD);
      qr.make();

      alvo.innerHTML = qr.createImgTag(6, 4, 'QR code Pix');
    } catch (erro) {
      alvo.innerHTML = '<p class="auth-field-hint">Nao foi possivel gerar o QR code. Use o codigo "copia e cola" abaixo.</p>';
    }
  }

  function initCopiar() {
    var botao = document.querySelector('[data-pix-copiar]');
    var input = document.getElementById('pix_copia_cola');

    if (!botao || !input) {
      return;
    }

    botao.addEventListener('click', function () {
      copiarTexto(input.value).then(function () {
        var iconeOriginal = botao.innerHTML;
        botao.classList.add('copiado');
        botao.innerHTML = '<i class="bi bi-check2"></i>';

        setTimeout(function () {
          botao.classList.remove('copiado');
          botao.innerHTML = iconeOriginal;
        }, 1800);
      }).catch(function () {
        // Copia falhou (permissao negada etc.) - o usuario ainda pode
        // selecionar e copiar manualmente do campo readonly.
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
