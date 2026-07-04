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
        return Promise.resolve();
      }

      var url = capituloInfoUrl
        + '?livro_id=' + encodeURIComponent(livroId)
        + '&capitulo=' + encodeURIComponent(capitulo)
        + '&versao=' + encodeURIComponent(versao);

      return fetch(url, { cache: 'no-store' })
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

    oculto.addEventListener('change', function () {
      atualizar();
    });
    capituloSelect.addEventListener('change', function () {
      atualizar();
    });

    if (versaoSelect) {
      // Trocar a versao/traducao mantem o mesmo versiculo que ja estava
      // selecionado (so busca o texto na nova traducao) e reprojeta
      // automaticamente - sem isso, trocar a versao no preletor nao
      // tinha nenhum efeito ate o usuario reclicar em um versiculo.
      versaoSelect.addEventListener('change', function () {
        var inicioAtual = inicioSelect.value;

        atualizar().then(function () {
          if (inicioAtual) {
            dispararChange(inicioSelect);
          }
        });
      });
    }
  }

  /**
   * Variante "estilo Holyrics" do combo de livro: alem do filtro por
   * texto, expoe um metodo `definir()` para sincronizar a selecao
   * programaticamente (ex.: apos navegar por seta ou receber um novo
   * estado via polling) sem depender de o usuario ter clicado em nada.
   */
  function montarComboLivro(raiz) {
    var input = raiz.querySelector('[data-livro-combo-input]');
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var lista = raiz.querySelector('[data-livro-combo-lista]');
    var itens = Array.prototype.slice.call(raiz.querySelectorAll('[data-livro-combo-item]'));
    var grupos = Array.prototype.slice.call(raiz.querySelectorAll('[data-livro-combo-grupo]'));

    if (!input || !oculto || !lista) {
      return { definir: function () {} };
    }

    var dica = raiz.parentElement ? raiz.parentElement.querySelector('[data-livro-dica]') : null;

    function atualizarDica() {
      if (dica) {
        dica.hidden = oculto.value !== '';
      }
    }

    oculto.addEventListener('change', atualizarDica);
    atualizarDica();

    function abrir() {
      lista.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      document.body.classList.add('livro-combo-lista-aberta');
    }

    function fechar() {
      lista.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('livro-combo-lista-aberta');
    }

    function atualizarGrupos() {
      grupos.forEach(function (grupo) {
        var algumVisivel = false;
        var el = grupo.nextElementSibling;

        while (el && !el.hasAttribute('data-livro-combo-grupo')) {
          if (el.hasAttribute('data-livro-combo-item') && !el.hidden) {
            algumVisivel = true;
            break;
          }
          el = el.nextElementSibling;
        }

        grupo.hidden = !algumVisivel;
      });
    }

    function filtrar() {
      var termo = normalizar(input.value.trim());

      itens.forEach(function (item) {
        var nome = normalizar(item.getAttribute('data-nome'));
        item.hidden = termo !== '' && nome.indexOf(termo) === -1;
      });

      atualizarGrupos();
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
      atualizarGrupos();
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

    return {
      definir: function (livroId, nome) {
        oculto.value = String(livroId);
        input.value = nome || '';
        dispararChange(oculto);
      },
    };
  }

  /**
   * Segmented control (pills) para escolher a versao/traducao, com o
   * mesmo contrato data-campo="biblia_versao" usado pelo resto do
   * formulario (um input oculto guarda o valor real).
   */
  function montarVersaoPills(raiz) {
    var oculto = raiz.querySelector('[data-campo="biblia_versao"]');
    var pills = Array.prototype.slice.call(raiz.querySelectorAll('[data-versao-pill]'));

    if (!oculto) {
      return { definir: function () {} };
    }

    function marcarAtiva(codigo) {
      pills.forEach(function (pill) {
        pill.classList.toggle('is-active', pill.getAttribute('data-valor') === codigo);
      });
    }

    pills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        oculto.value = pill.getAttribute('data-valor');
        marcarAtiva(oculto.value);
        dispararChange(oculto);
      });
    });

    if (pills.length) {
      oculto.value = pills[0].getAttribute('data-valor');
      marcarAtiva(oculto.value);
    }

    return {
      // Usado para sincronizar com o estado do servidor (poll/navegacao):
      // so ajusta o valor e o destaque visual, sem disparar 'change' -
      // quem chama isso (sincronizarPicker) ja encadeia a atualizacao do
      // capitulo/versiculo explicitamente. Disparar 'change' aqui tambem
      // correria o risco de reprojetar de volta o que acabou de chegar.
      definir: function (codigo) {
        if (!codigo || oculto.value === codigo) {
          return;
        }

        oculto.value = codigo;
        marcarAtiva(codigo);
      },
    };
  }

  function popularChips(container, total, valorAtivo, valorFim, onClick) {
    container.innerHTML = '';

    for (var i = 1; i <= total; i++) {
      var chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'biblia-chip';
      chip.textContent = String(i);
      chip.setAttribute('data-valor', String(i));
      chip.addEventListener('click', function (evento) {
        onClick(Number(this.getAttribute('data-valor')), evento);
      });
      container.appendChild(chip);
    }

    marcarChipsAtivos(container, valorAtivo, valorFim);
  }

  function marcarChipsAtivos(container, valor, valorFim) {
    var min = valorFim ? Math.min(valor, valorFim) : valor;
    var max = valorFim ? Math.max(valor, valorFim) : valor;

    Array.prototype.forEach.call(container.querySelectorAll('.biblia-chip'), function (chip) {
      var n = Number(chip.getAttribute('data-valor'));
      chip.classList.toggle('is-active', n === valor || n === valorFim);
      chip.classList.toggle('is-range', n > min && n < max);
    });
  }

  /**
   * Grade de capitulos (chips numerados, estilo Holyrics) em vez de um
   * select. Reconstroi a grade sempre que o livro muda.
   */
  function montarCapituloChips(raiz) {
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var capituloOculto = raiz.querySelector('[data-campo="capitulo"]');
    var chipsContainer = raiz.querySelector('[data-capitulo-chips]');
    var secao = raiz.querySelector('[data-secao-capitulo]');
    var itens = Array.prototype.slice.call(raiz.querySelectorAll('[data-livro-combo-item]'));

    if (!oculto || !capituloOculto || !chipsContainer) {
      return { definir: function () {} };
    }

    function popular(valorAtivo) {
      var item = itens.filter(function (i) {
        return i.getAttribute('data-livro-id') === oculto.value;
      })[0];
      var total = item ? parseInt(item.getAttribute('data-total-capitulos'), 10) : 0;

      if (!total) {
        if (secao) {
          secao.classList.remove('is-visible');
        }

        return;
      }

      if (secao) {
        secao.classList.add('is-visible');
      }

      popularChips(chipsContainer, total, valorAtivo, null, function (numero) {
        capituloOculto.value = String(numero);
        marcarChipsAtivos(chipsContainer, numero);
        dispararChange(capituloOculto);
      });
    }

    oculto.addEventListener('change', function () {
      capituloOculto.value = '';
      popular(null);
    });

    return {
      definir: function (capitulo) {
        capituloOculto.value = String(capitulo);
        popular(capitulo);
      },
    };
  }

  /**
   * Grade de versiculos (chips numerados). Clique simples seleciona um
   * unico versiculo; shift+clique estende o intervalo a partir do
   * ultimo clicado (como selecao de celulas em planilha), preenchendo
   * versiculo_fim.
   */
  function montarVersiculoChips(raiz, capituloInfoUrl) {
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var versaoOculto = raiz.querySelector('[data-campo="biblia_versao"]');
    var capituloOculto = raiz.querySelector('[data-campo="capitulo"]');
    var inicioOculto = raiz.querySelector('[data-campo="versiculo_inicio"]');
    var fimOculto = raiz.querySelector('[data-campo="versiculo_fim"]');
    var chipsContainer = raiz.querySelector('[data-versiculo-chips]');
    var secao = raiz.querySelector('[data-secao-versiculo]');

    if (!oculto || !capituloOculto || !inicioOculto || !chipsContainer) {
      return { definir: function () { return Promise.resolve(); } };
    }

    var ultimoClicado = null;

    function popular(valorAtivo, valorFim) {
      var livroId = oculto.value;
      var capitulo = capituloOculto.value;
      var versao = versaoOculto ? versaoOculto.value : '';

      if (!livroId || !capitulo || !versao) {
        if (secao) {
          secao.classList.remove('is-visible');
        }

        return Promise.resolve();
      }

      var url = capituloInfoUrl
        + '?livro_id=' + encodeURIComponent(livroId)
        + '&capitulo=' + encodeURIComponent(capitulo)
        + '&versao=' + encodeURIComponent(versao);

      return fetch(url, { cache: 'no-store' })
        .then(function (resposta) {
          return resposta.json();
        })
        .then(function (dados) {
          var total = dados.totalVersiculos || 0;

          if (!total) {
            if (secao) {
              secao.classList.remove('is-visible');
            }

            return;
          }

          if (secao) {
            secao.classList.add('is-visible');
          }

          popularChips(chipsContainer, total, valorAtivo, valorFim, function (numero, evento) {
            if (evento.shiftKey && ultimoClicado) {
              inicioOculto.value = String(Math.min(ultimoClicado, numero));
              fimOculto.value = String(Math.max(ultimoClicado, numero));
            } else {
              ultimoClicado = numero;
              inicioOculto.value = String(numero);
              fimOculto.value = '';
            }

            marcarChipsAtivos(chipsContainer, Number(inicioOculto.value), fimOculto.value ? Number(fimOculto.value) : null);
            dispararChange(inicioOculto);
          });
        })
        .catch(function () {});
    }

    oculto.addEventListener('change', function () {
      inicioOculto.value = '';
      fimOculto.value = '';
      ultimoClicado = null;
      popular(null, null);
    });
    capituloOculto.addEventListener('change', function () {
      inicioOculto.value = '';
      fimOculto.value = '';
      ultimoClicado = null;
      popular(null, null);
    });
    if (versaoOculto) {
      // Trocar a versao/traducao mantem o mesmo capitulo/versiculo que ja
      // estava selecionado (so busca o texto na nova traducao) e reprojeta
      // automaticamente - sem isso, o clique na pill de versao nao tinha
      // nenhum efeito ate o usuario reclicar em um versiculo.
      versaoOculto.addEventListener('change', function () {
        var inicioAtual = inicioOculto.value ? Number(inicioOculto.value) : null;
        var fimAtual = fimOculto.value ? Number(fimOculto.value) : null;

        popular(inicioAtual, fimAtual).then(function () {
          if (inicioAtual) {
            dispararChange(inicioOculto);
          }
        });
      });
    }

    return {
      definir: function (inicio, fim) {
        ultimoClicado = inicio;
        inicioOculto.value = inicio ? String(inicio) : '';
        fimOculto.value = fim && fim !== inicio ? String(fim) : '';

        return popular(inicio, fim && fim !== inicio ? fim : null);
      },
    };
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
    montarVersaoPills: montarVersaoPills,
    montarCapituloChips: montarCapituloChips,
    montarVersiculoChips: montarVersiculoChips,
    ajustarPalco: ajustarPalco,
  };
})(window);
