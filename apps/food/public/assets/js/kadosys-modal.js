/**
 * Popup de confirmacao proprio do sistema, no lugar do window.confirm()
 * nativo (que e feio, varia por navegador e nao da pra estilizar).
 *
 * Autocontido de proposito: injeta o proprio CSS num <style> quando usado
 * pela primeira vez, entao funciona em qualquer layout (dashboard,
 * preletor, etc.) so incluindo este script - sem depender de nenhuma
 * folha de estilo especifica de cada tela.
 *
 * Uso:
 *   KadosysModal.confirmar('Tem certeza?').then(function (ok) {
 *     if (ok) { ... }
 *   });
 */
(function () {
  'use strict';

  var CSS = [
    '.kmodal-overlay {',
    '  position: fixed;',
    '  inset: 0;',
    '  z-index: 99999;',
    '  display: flex;',
    '  align-items: center;',
    '  justify-content: center;',
    '  padding: 1rem;',
    '  background: rgba(2, 6, 23, 0.66);',
    '  backdrop-filter: blur(4px);',
    '  -webkit-backdrop-filter: blur(4px);',
    '  opacity: 0;',
    '  transition: opacity 0.16s ease;',
    '}',
    '.kmodal-overlay.is-aberto { opacity: 1; }',
    '.kmodal {',
    '  width: 100%;',
    '  max-width: 420px;',
    '  border-radius: 16px;',
    '  background: #0F172A;',
    '  border: 1px solid rgba(255, 255, 255, 0.14);',
    '  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);',
    '  padding: 1.4rem 1.5rem 1.25rem;',
    '  font-family: "Inter", "Segoe UI", system-ui, sans-serif;',
    '  color: #E5E7EB;',
    '  transform: translateY(10px) scale(0.98);',
    '  transition: transform 0.16s ease;',
    '}',
    '.kmodal-overlay.is-aberto .kmodal { transform: translateY(0) scale(1); }',
    '.kmodal-icone {',
    '  width: 44px;',
    '  height: 44px;',
    '  border-radius: 12px;',
    '  display: flex;',
    '  align-items: center;',
    '  justify-content: center;',
    '  font-size: 1.3rem;',
    '  margin-bottom: 0.85rem;',
    '  background: rgba(59, 130, 246, 0.14);',
    '  color: #60A5FA;',
    '  border: 1px solid rgba(59, 130, 246, 0.3);',
    '}',
    '.kmodal-mensagem {',
    '  font-size: 0.95rem;',
    '  line-height: 1.55;',
    '  margin: 0 0 1.2rem;',
    '  color: #E5E7EB;',
    '}',
    '.kmodal-acoes {',
    '  display: flex;',
    '  gap: 0.6rem;',
    '  justify-content: flex-end;',
    '}',
    '.kmodal-btn {',
    '  border-radius: 10px;',
    '  padding: 0.55rem 1.15rem;',
    '  font-size: 0.88rem;',
    '  font-weight: 600;',
    '  cursor: pointer;',
    '  transition: filter 0.12s ease, background 0.12s ease;',
    '  font-family: inherit;',
    '}',
    '.kmodal-btn:focus-visible { outline: 2px solid #60A5FA; outline-offset: 2px; }',
    '.kmodal-btn-cancelar {',
    '  background: rgba(255, 255, 255, 0.06);',
    '  border: 1px solid rgba(255, 255, 255, 0.14);',
    '  color: #E5E7EB;',
    '}',
    '.kmodal-btn-cancelar:hover { background: rgba(255, 255, 255, 0.12); }',
    '.kmodal-btn-confirmar {',
    '  background: linear-gradient(135deg, #3B82F6, #8B5CF6);',
    '  border: 1px solid transparent;',
    '  color: #FFFFFF;',
    '}',
    '.kmodal-btn-confirmar:hover { filter: brightness(1.12); }',
    '.kmodal-btn-confirmar.is-perigo {',
    '  background: linear-gradient(135deg, #EF4444, #B91C1C);',
    '}',
  ].join('\n');

  var estiloInjetado = false;

  function injetarEstilo() {
    if (estiloInjetado) {
      return;
    }

    var style = document.createElement('style');
    style.textContent = CSS;
    document.head.appendChild(style);
    estiloInjetado = true;
  }

  /**
   * @param {string} mensagem  Texto da pergunta.
   * @param {object} [opcoes]  { confirmar: 'Trocar', cancelar: 'Cancelar', perigo: true, icone: 'bi-...' }
   * @returns {Promise<boolean>} true se o usuario confirmou.
   */
  function confirmar(mensagem, opcoes) {
    opcoes = opcoes || {};
    injetarEstilo();

    return new Promise(function (resolve) {
      var overlay = document.createElement('div');
      overlay.className = 'kmodal-overlay';

      var modal = document.createElement('div');
      modal.className = 'kmodal';
      modal.setAttribute('role', 'alertdialog');
      modal.setAttribute('aria-modal', 'true');

      var icone = document.createElement('div');
      icone.className = 'kmodal-icone';
      icone.innerHTML = '<i class="bi ' + (opcoes.icone || (opcoes.perigo ? 'bi-exclamation-triangle' : 'bi-question-circle')) + '"></i>';

      var texto = document.createElement('p');
      texto.className = 'kmodal-mensagem';
      texto.textContent = mensagem;

      var acoes = document.createElement('div');
      acoes.className = 'kmodal-acoes';

      var btnCancelar = document.createElement('button');
      btnCancelar.type = 'button';
      btnCancelar.className = 'kmodal-btn kmodal-btn-cancelar';
      btnCancelar.textContent = opcoes.cancelar || 'Cancelar';

      var btnConfirmar = document.createElement('button');
      btnConfirmar.type = 'button';
      btnConfirmar.className = 'kmodal-btn kmodal-btn-confirmar' + (opcoes.perigo ? ' is-perigo' : '');
      btnConfirmar.textContent = opcoes.confirmar || 'Confirmar';

      acoes.appendChild(btnCancelar);
      acoes.appendChild(btnConfirmar);
      modal.appendChild(icone);
      modal.appendChild(texto);
      modal.appendChild(acoes);
      overlay.appendChild(modal);
      document.body.appendChild(overlay);

      var focoAnterior = document.activeElement;

      function fechar(resultado) {
        document.removeEventListener('keydown', aoTeclar, true);
        overlay.classList.remove('is-aberto');

        setTimeout(function () {
          if (overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
          }

          if (focoAnterior && typeof focoAnterior.focus === 'function') {
            focoAnterior.focus();
          }
        }, 170);

        resolve(resultado);
      }

      function aoTeclar(evento) {
        if (evento.key === 'Escape') {
          evento.preventDefault();
          fechar(false);
        } else if (evento.key === 'Enter') {
          evento.preventDefault();
          fechar(true);
        }
      }

      btnCancelar.addEventListener('click', function () { fechar(false); });
      btnConfirmar.addEventListener('click', function () { fechar(true); });
      overlay.addEventListener('click', function (evento) {
        if (evento.target === overlay) {
          fechar(false);
        }
      });
      document.addEventListener('keydown', aoTeclar, true);

      // Forca um frame antes de abrir, pra transicao de opacidade rodar.
      requestAnimationFrame(function () {
        overlay.classList.add('is-aberto');
        btnConfirmar.focus();
      });
    });
  }

  window.KadosysModal = { confirmar: confirmar };
})();
