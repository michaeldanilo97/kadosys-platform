(function () {
    'use strict';

    var form = document.querySelector('[data-cadastro-form]');
    if (!form) {
        return;
    }

    // Sugere o identificador (slug) automaticamente a partir do nome da
    // restaurante, so enquanto o usuario nao editar o campo slug na mao.
    var nomeInput = form.querySelector('[data-cadastro-nome]');
    var slugInput = form.querySelector('[data-cadastro-slug]');
    var slugEditadoManualmente = slugInput && slugInput.value !== '';

    function normalizarSlug(valor) {
        return valor
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    if (nomeInput && slugInput) {
        nomeInput.addEventListener('input', function () {
            if (!slugEditadoManualmente) {
                slugInput.value = normalizarSlug(nomeInput.value);
            }
        });

        slugInput.addEventListener('input', function () {
            slugEditadoManualmente = slugInput.value !== '';
        });
    }

    // Mostra/esconde o campo de razao social e troca a mascara/label do
    // documento conforme CPF ou CNPJ estiver selecionado.
    var documentoTipoInputs = form.querySelectorAll('[data-documento-tipo]');
    var documentoInput = form.querySelector('[data-documento-input]');
    var documentoLabel = form.querySelector('[data-documento-label]');
    var razaoSocialField = form.querySelector('[data-razao-social-field]');

    function atualizarDocumento() {
        var selecionado = form.querySelector('[data-documento-tipo]:checked');
        var tipo = selecionado ? selecionado.value : 'cpf';

        if (tipo === 'cnpj') {
            if (documentoLabel) documentoLabel.textContent = 'CNPJ';
            if (documentoInput) documentoInput.placeholder = '00.000.000/0000-00';
            if (razaoSocialField) razaoSocialField.hidden = false;
        } else {
            if (documentoLabel) documentoLabel.textContent = 'CPF';
            if (documentoInput) documentoInput.placeholder = '000.000.000-00';
            if (razaoSocialField) razaoSocialField.hidden = true;
        }
    }

    documentoTipoInputs.forEach(function (input) {
        input.addEventListener('change', atualizarDocumento);
    });

    atualizarDocumento();
})();
