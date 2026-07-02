(function () {
  'use strict';

  var root = document.querySelector('[data-projecao-controles]');

  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url');
  var baseUrl = pollUrl.replace(/\/estado$/, '');
  var formBiblia = root.querySelector('[data-form-biblia]');
  var formVideo = root.querySelector('[data-form-video]');
  var botoesVideo = root.querySelectorAll('[data-video-acao]');
  var botaoLogo = document.querySelector('[data-acao-logo]');
  var botaoLimpar = document.querySelector('[data-acao-limpar]');

  function enviar(caminho, corpo) {
    return fetch(baseUrl + caminho, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo ? corpo.toString() : '',
    });
  }

  if (formBiblia) {
    var livroSelect = formBiblia.querySelector('[data-campo="livro_id"]');
    var capituloInput = formBiblia.querySelector('[data-campo="capitulo"]');

    livroSelect.addEventListener('change', function () {
      var opcao = livroSelect.options[livroSelect.selectedIndex];
      var totalCapitulos = opcao ? opcao.getAttribute('data-total-capitulos') : null;

      if (totalCapitulos) {
        capituloInput.setAttribute('max', totalCapitulos);
      }
    });

    formBiblia.addEventListener('submit', function (evento) {
      evento.preventDefault();

      var dados = new URLSearchParams();
      dados.set('biblia_versao', formBiblia.querySelector('[data-campo="biblia_versao"]').value);
      dados.set('livro_id', livroSelect.value);
      dados.set('capitulo', capituloInput.value);
      dados.set('versiculo_inicio', formBiblia.querySelector('[data-campo="versiculo_inicio"]').value);

      var fim = formBiblia.querySelector('[data-campo="versiculo_fim"]').value;
      if (fim) {
        dados.set('versiculo_fim', fim);
      }

      enviar('/biblia', dados);
    });
  }

  if (formVideo) {
    formVideo.addEventListener('submit', function (evento) {
      evento.preventDefault();

      var url = formVideo.querySelector('#video_url').value;
      var dados = new URLSearchParams();
      dados.set('url', url);

      enviar('/video', dados);
    });
  }

  botoesVideo.forEach(function (botao) {
    botao.addEventListener('click', function () {
      var dados = new URLSearchParams();
      dados.set('estado', botao.getAttribute('data-video-acao'));

      enviar('/video/estado', dados).then(function () {
        botoesVideo.forEach(function (b) {
          b.classList.remove('active');
        });
        botao.classList.add('active');
      });
    });
  });

  if (botaoLogo) {
    botaoLogo.addEventListener('click', function () {
      enviar('/logo');
    });
  }

  if (botaoLimpar) {
    botaoLimpar.addEventListener('click', function () {
      enviar('/limpar');
    });
  }
})();
