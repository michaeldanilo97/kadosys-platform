(function () {
  'use strict';

  var cargoSelect = document.querySelector('[data-cargo-select]');
  var instrumentoField = document.querySelector('[data-instrumento-field]');

  if (!cargoSelect || !instrumentoField) {
    return;
  }

  cargoSelect.addEventListener('change', function () {
    instrumentoField.hidden = cargoSelect.value !== 'musico';
  });
})();
