(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initCepAutofill();
  });

  function initCepAutofill() {
    var cepInput = document.querySelector('[data-cep-input]');

    if (!cepInput) {
      return;
    }

    var statusEl = document.querySelector('[data-cep-status]');
    // Duas formas de preencher o endereco: campo unico concatenado
    // (data-cep-endereco, usado no autocadastro publico) ou campos
    // separados de logradouro/bairro (data-cep-logradouro/data-cep-bairro,
    // usado no perfil do membro no painel admin) - o que estiver presente
    // no HTML e o que o script usa.
    var enderecoAttr = cepInput.getAttribute('data-cep-endereco');
    var enderecoInput = enderecoAttr ? document.getElementById(enderecoAttr) : null;
    var logradouroAttr = cepInput.getAttribute('data-cep-logradouro');
    var logradouroInput = logradouroAttr ? document.getElementById(logradouroAttr) : null;
    var bairroAttr = cepInput.getAttribute('data-cep-bairro');
    var bairroInput = bairroAttr ? document.getElementById(bairroAttr) : null;
    var cidadeInput = document.getElementById(cepInput.getAttribute('data-cep-cidade') || 'cidade');
    var estadoInput = document.getElementById(cepInput.getAttribute('data-cep-estado') || 'estado');

    function aplicarMascara(valor) {
      var digitos = valor.replace(/\D/g, '').slice(0, 8);

      return digitos.replace(/(\d{5})(\d)/, '$1-$2');
    }

    function definirStatus(texto) {
      if (statusEl) {
        statusEl.textContent = texto;
      }
    }

    cepInput.addEventListener('input', function () {
      cepInput.value = aplicarMascara(cepInput.value);
    });

    cepInput.addEventListener('blur', function () {
      var digitos = cepInput.value.replace(/\D/g, '');

      if (digitos.length !== 8) {
        return;
      }

      definirStatus('Buscando endereço...');

      fetch('https://viacep.com.br/ws/' + digitos + '/json/')
        .then(function (resposta) {
          return resposta.json();
        })
        .then(function (dados) {
          if (dados.erro) {
            definirStatus('CEP não encontrado - preencha o endereço manualmente.');

            return;
          }

          if (enderecoInput && !enderecoInput.value) {
            enderecoInput.value = [dados.logradouro, dados.bairro].filter(Boolean).join(', ');
          }

          if (logradouroInput && !logradouroInput.value) {
            logradouroInput.value = dados.logradouro || '';
          }

          if (bairroInput && !bairroInput.value) {
            bairroInput.value = dados.bairro || '';
          }

          if (cidadeInput) {
            cidadeInput.value = dados.localidade || cidadeInput.value;
          }

          if (estadoInput) {
            estadoInput.value = dados.uf || estadoInput.value;
          }

          definirStatus('Endereço preenchido automaticamente.');
        })
        .catch(function () {
          definirStatus('Não foi possível buscar o CEP agora - preencha o endereço manualmente.');
        });
    });
  }
})();
