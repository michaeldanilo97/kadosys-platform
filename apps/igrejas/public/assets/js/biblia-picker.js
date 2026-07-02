/**
 * Componentes compartilhados entre o painel do operador e o tablet do
 * preletor: busca de livro (combobox), selects dependentes de
 * capitulo/versiculo e o ajuste do "palco" 16:9 usado para alinhar a
 * marcacao a lapis entre o preletor e o telao.
 */
(function (window) {
  'use strict';

  function normalizar(texto) {
    return (texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  }

  function dispararChange(elemento) {
    elemento.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /**
   * Progressivamente melhora um bloco `[data-livro-combo]` (input de
   * texto + input oculto `[data-campo="livro_id"]` + lista de botoes
   * `[data-livro-combo-item]`) em um campo de busca com filtro.
   */
  function montarComboLivro(raiz) {
    var input = raiz.querySelector('[data-livro-combo-input]');
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var lista = raiz.querySelector('[data-livro-combo-lista]');
    var itens = Array.prototype.slice.call(raiz.querySelectorAll('[data-livro-combo-item]'));

    if (!input || !oculto || !lista) {
      return;
    }

    function abrir() {
      lista.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }

    function fechar() {
      lista.hidden = true;
      input.setAttribute('aria-expanded', 'false');
    }

    function filtrar() {
      var termo = normalizar(input.value.trim());

      itens.forEach(function (item) {
        var nome = normalizar(item.getAttribute('data-nome'));
        item.hidden = termo !== '' && nome.indexOf(termo) === -1;
      });

      abrir();
    }

    function selecionar(item) {
      oculto.value = item.getAttribute('data-livro-id');
      input.value = item.getAttribute('data-nome');
      fechar();
      dispararChange(oculto);
    }

    itens.forEach(function (item) {
      item.addEventListener('click', function () {
        selecionar(item);
      });
    });

    input.addEventListener('input', filtrar);
    input.addEventListener('focus', function () {
      itens.forEach(function (item) {
        item.hidden = false;
      });
      abrir();
    });

    input.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape') {
        fechar();
        input.blur();
      } else if (evento.key === 'Enter') {
        evento.preventDefault();
        var visivel = itens.filter(function (item) {
          return !item.hidden;
        })[0];

        if (visivel) {
          selecionar(visivel);
        }
      }
    });

    document.addEventListener('click', function (evento) {
      if (!raiz.contains(evento.target)) {
        fechar();
      }
    });
  }

  function popularSelect(select, total, textoOpcaoVazia) {
    var atual = select.value;
    select.innerHTML = '';

    var vazio = document.createElement('option');
    vazio.value = '';
    vazio.textContent = textoOpcaoVazia;
    vazio.disabled = !!select.required;
    vazio.selected = true;
    select.appendChild(vazio);

    for (var i = 1; i <= total; i++) {
      var opcao = document.createElement('option');
      opcao.value = String(i);
      opcao.textContent = String(i);
      select.appendChild(opcao);
    }

    if (atual && Number(atual) <= total) {
      select.value = atual;
    }
  }

  /**
   * Popula o select de capitulo (1..totalCapitulos) sempre que o livro
   * selecionado mudar, usando o `data-total-capitulos` ja presente nos
   * itens do combo (nao precisa de requisicao ao servidor).
   */
  function montarCapitulo(raiz) {
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var capituloSelect = raiz.querySelector('[data-campo="capitulo"]');
    var itens = Array.prototype.slice.call(raiz.querySelectorAll('[data-livro-combo-item]'));

    if (!oculto || !capituloSelect) {
      return;
    }

    oculto.addEventListener('change', function () {
      var item = itens.filter(function (i) {
        return i.getAttribute('data-livro-id') === oculto.value;
      })[0];
      var total = item ? parseInt(item.getAttribute('data-total-capitulos'), 10) : 0;

      popularSelect(capituloSelect, total || 0, 'Cap...');
      dispararChange(capituloSelect);
    });
  }

  /**
   * Popula os selects de versiculo inicial/final (1..totalVersiculos)
   * sempre que livro, versao ou capitulo mudarem, consultando o total
   * real de versiculos daquele capitulo no servidor (o cliente nao tem
   * essa informacao, pois pode variar por traducao).
   */
  function montarVersiculos(raiz, capituloInfoUrl) {
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var versaoSelect = raiz.querySelector('[data-campo="biblia_versao"]');
    var capituloSelect = raiz.querySelector('[data-campo="capitulo"]');
    var inicioSelect = raiz.querySelector('[data-campo="versiculo_inicio"]');
    var fimSelect = raiz.querySelector('[data-campo="versiculo_fim"]');

    if (!oculto || !capituloSelect || !inicioSelect) {
      return;
    }

    function atualizar() {
      var livroId = oculto.value;
      var capitulo = capituloSelect.value;
      var versao = versaoSelect ? versaoSelect.value : '';

      if (!livroId || !capitulo || !versao) {
        popularSelect(inicioSelect, 0, 'Vers...');
        if (fimSelect) {
          popularSelect(fimSelect, 0, 'Ate (opcional)');
        }
        return;
      }

      var url = capituloInfoUrl
        + '?livro_id=' + encodeURIComponent(livroId)
        + '&capitulo=' + encodeURIComponent(capitulo)
        + '&versao=' + encodeURIComponent(versao);

      fetch(url, { cache: 'no-store' })
        .then(function (resposta) {
          return resposta.json();
        })
        .then(function (dados) {
          var total = dados.totalVersiculos || 0;
          popularSelect(inicioSelect, total, total ? 'Vers...' : 'Sem texto');
          if (fimSelect) {
            popularSelect(fimSelect, total, 'Ate (opcional)');
          }
        })
        .catch(function () {});
    }

    oculto.addEventListener('change', atualizar);
    capituloSelect.addEventListener('change', atualizar);
    if (versaoSelect) {
      versaoSelect.addEventListener('change', atualizar);
    }
  }

  /**
   * Redimensiona `stage` (em pixels, letterboxed) para caber em
   * `container` mantendo proporcao 16:9 - usado tanto no telao quanto no
   * preletor, para que os dois exibam o texto na mesma proporcao e as
   * coordenadas relativas (0..1) da marcacao a lapis caiam no mesmo
   * lugar visual nas duas telas.
   */
  function ajustarPalco(container, stage) {
    if (!container || !stage) {
      return;
    }

    var largura = container.clientWidth;
    var altura = container.clientHeight;

    if (!largura || !altura) {
      return;
    }

    var proporcao = 16 / 9;
    var w = largura;
    var h = w / proporcao;

    if (h > altura) {
      h = altura;
      w = h * proporcao;
    }

    stage.style.width = Math.floor(w) + 'px';
    stage.style.height = Math.floor(h) + 'px';
  }

  window.KadosysBiblia = {
    montarComboLivro: montarComboLivro,
    montarCapitulo: montarCapitulo,
    montarVersiculos: montarVersiculos,
    ajustarPalco: ajustarPalco,
  };
})(window);
