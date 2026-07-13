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
          popularSelect(fimSelect, 0, 'Até (opcional)');
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
            popularSelect(fimSelect, total, 'Até (opcional)');
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

  /**
   * Reconstroi a grade de chips SO quando o total muda (ex.: trocou de
   * capitulo) - trocar so o versiculo ativo dentro do MESMO capitulo
   * (ex.: navegacao Anterior/Proximo, ou sincronizacao do poll) reusa
   * os botoes ja existentes, so alternando a classe "is-active" neles.
   * Sem isso, container.innerHTML = '' destruia e recriava todos os
   * botoes a cada troca de versiculo - o browser nunca conseguia
   * animar a transicao de cor (definida em .biblia-chip no CSS) porque
   * o botao "ativo" era sempre um elemento novo, sem estado anterior
   * pra transicionar a partir dele - dava a impressao de "piscar".
   */
  function popularChips(container, total, valorAtivo, valorFim, onClick) {
    var existentes = container.querySelectorAll('.biblia-chip');

    if (existentes.length !== total) {
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
   * Dropdown numerico 100% customizado (botao + lista de chips que abre
   * por cima do conteudo) - usado no Preletor no lugar de <select>
   * nativo pra Capitulo/Versiculo/Ate. Motivo: o navegador nao deixa
   * estilizar de forma confiavel a LISTA que abre de um <select> (a
   * caixa fechada ate da pra temer com color-scheme, mas o popup em si
   * ignora o tema em varios navegadores/SOs), o que deixava a lista
   * branca, sem contraste, e as vezes ate sobrepondo o texto da tela
   * de forma confusa.
   */
  function montarNumeroCombo(container) {
    var toggle = container.querySelector('[data-num-combo-toggle]');
    var lista = container.querySelector('[data-num-combo-lista]');
    var grid = container.querySelector('[data-num-combo-grid]');

    if (!toggle || !lista || !grid) {
      return null;
    }

    function abrir() {
      lista.hidden = false;
      toggle.setAttribute('aria-expanded', 'true');
    }

    function fechar() {
      lista.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function (evento) {
      evento.stopPropagation();

      if (lista.hidden) {
        abrir();
      } else {
        fechar();
      }
    });

    document.addEventListener('click', function (evento) {
      if (!container.contains(evento.target)) {
        fechar();
      }
    });

    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape' && !lista.hidden) {
        fechar();
      }
    });

    return {
      setToggleTexto: function (texto) {
        toggle.textContent = texto;
      },
      popular: function (total, valorAtivo, valorFim, onSelecionar) {
        popularChips(grid, total, valorAtivo, valorFim, function (numero, evento) {
          onSelecionar(numero, evento);
          fechar();
        });
      },
    };
  }

  /**
   * Capitulo do Preletor - mesma dependencia de montarCapituloChips
   * (repovoa quando o livro muda), so que escrevendo no dropdown
   * customizado (montarNumeroCombo) em vez do container de chips
   * sempre visivel usado no painel do operador.
   */
  function montarPreletorCapitulo(raiz) {
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var capituloOculto = raiz.querySelector('[data-campo="capitulo"]');
    var comboEl = raiz.querySelector('[data-num-combo="capitulo"]');
    var itens = Array.prototype.slice.call(raiz.querySelectorAll('[data-livro-combo-item]'));
    var combo = comboEl ? montarNumeroCombo(comboEl) : null;

    if (!oculto || !capituloOculto || !combo) {
      return { definir: function () {} };
    }

    function popular(valorAtivo) {
      var item = itens.filter(function (i) {
        return i.getAttribute('data-livro-id') === oculto.value;
      })[0];
      var total = item ? parseInt(item.getAttribute('data-total-capitulos'), 10) : 0;

      if (!total) {
        combo.setToggleTexto('Cap...');

        return;
      }

      combo.popular(total, valorAtivo, null, function (numero) {
        capituloOculto.value = String(numero);
        combo.setToggleTexto(String(numero));
        dispararChange(capituloOculto);
      });
    }

    oculto.addEventListener('change', function () {
      capituloOculto.value = '';
      combo.setToggleTexto('Cap...');
      popular(null);
    });

    return {
      definir: function (capitulo) {
        capituloOculto.value = String(capitulo);
        combo.setToggleTexto(String(capitulo));
        popular(capitulo);
      },
    };
  }

  /**
   * Versiculo inicio/fim do Preletor - mesma dependencia/logica de
   * montarVersiculoChips (livro+versao+capitulo, busca o total real no
   * servidor, reprojeta ao trocar de versao), so que com DOIS dropdowns
   * numericos separados (em vez de uma unica grade com shift+clique) -
   * mantem o mesmo layout de 2 campos (Vers./Ate) que ja existia no
   * Preletor antes desta troca.
   */
  function montarPreletorVersiculos(raiz, capituloInfoUrl) {
    var oculto = raiz.querySelector('[data-campo="livro_id"]');
    var versaoOculto = raiz.querySelector('[data-campo="biblia_versao"]');
    var capituloOculto = raiz.querySelector('[data-campo="capitulo"]');
    var inicioOculto = raiz.querySelector('[data-campo="versiculo_inicio"]');
    var fimOculto = raiz.querySelector('[data-campo="versiculo_fim"]');
    var comboInicioEl = raiz.querySelector('[data-num-combo="versiculo_inicio"]');
    var comboFimEl = raiz.querySelector('[data-num-combo="versiculo_fim"]');
    var comboInicio = comboInicioEl ? montarNumeroCombo(comboInicioEl) : null;
    var comboFim = comboFimEl ? montarNumeroCombo(comboFimEl) : null;

    if (!oculto || !capituloOculto || !inicioOculto || !comboInicio || !comboFim) {
      return { definir: function () { return Promise.resolve(); } };
    }

    function atualizar() {
      var livroId = oculto.value;
      var capitulo = capituloOculto.value;
      var versao = versaoOculto ? versaoOculto.value : '';

      if (!livroId || !capitulo || !versao) {
        comboInicio.setToggleTexto('Vers...');
        comboFim.setToggleTexto('Opcional');

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
            comboInicio.setToggleTexto('Sem texto');
            comboFim.setToggleTexto('Opcional');

            return;
          }

          var inicioAtual = inicioOculto.value ? Number(inicioOculto.value) : null;
          var fimAtual = fimOculto.value ? Number(fimOculto.value) : null;

          comboInicio.popular(total, inicioAtual, null, function (numero) {
            inicioOculto.value = String(numero);
            comboInicio.setToggleTexto(String(numero));
            dispararChange(inicioOculto);
            aplicarMinimoFim();
          });

          comboFim.popular(total, fimAtual, null, function (numero) {
            // Clicar de novo no mesmo numero ja selecionado limpa o "Ate"
            // (volta a ser opcional) - sem isso, uma vez escolhido nao
            // tinha como voltar atras sem trocar o versiculo inicial ou
            // o capitulo inteiro.
            if (fimOculto.value && Number(fimOculto.value) === numero) {
              fimOculto.value = '';
              comboFim.setToggleTexto('Opcional');
            } else {
              fimOculto.value = String(numero);
              comboFim.setToggleTexto(String(numero));
            }

            dispararChange(fimOculto);
          });

          if (inicioAtual) {
            comboInicio.setToggleTexto(String(inicioAtual));
          }

          if (fimAtual) {
            comboFim.setToggleTexto(String(fimAtual));
          }

          aplicarMinimoFim();
        })
        .catch(function () {});
    }

    /**
     * O "Ate" so faz sentido como o FIM de um intervalo que comeca no
     * versiculo ja escolhido - por isso os numeros menores que o
     * inicio ficam desabilitados na grade (em vez de deixar escolher
     * uma combinacao invertida, tipo inicio 5 e ate 3). Se o inicio
     * mudar pra um numero maior que o "Ate" ja escolhido, o "Ate"
     * anterior fica invalido e e limpo automaticamente.
     */
    function aplicarMinimoFim() {
      if (!comboFimEl) {
        return;
      }

      var minimo = inicioOculto.value ? Number(inicioOculto.value) : null;

      if (minimo !== null && fimOculto.value && Number(fimOculto.value) < minimo) {
        fimOculto.value = '';
        comboFim.setToggleTexto('Opcional');
      }

      Array.prototype.forEach.call(comboFimEl.querySelectorAll('.biblia-chip'), function (chip) {
        var valor = Number(chip.getAttribute('data-valor'));
        var bloqueado = minimo !== null && valor < minimo;
        chip.disabled = bloqueado;
        chip.classList.toggle('is-desabilitado', bloqueado);
      });
    }

    oculto.addEventListener('change', function () {
      inicioOculto.value = '';
      fimOculto.value = '';
      comboInicio.setToggleTexto('Vers...');
      comboFim.setToggleTexto('Opcional');
      atualizar();
    });
    capituloOculto.addEventListener('change', function () {
      inicioOculto.value = '';
      fimOculto.value = '';
      comboInicio.setToggleTexto('Vers...');
      comboFim.setToggleTexto('Opcional');
      atualizar();
    });

    if (versaoOculto) {
      // Trocar a versao/traducao mantem o mesmo versiculo que ja estava
      // selecionado (so busca o texto na nova traducao) e reprojeta
      // automaticamente - sem isso, trocar a versao no preletor nao
      // tinha nenhum efeito ate o usuario reclicar em um versiculo.
      versaoOculto.addEventListener('change', function () {
        var inicioAtual = inicioOculto.value;

        atualizar().then(function () {
          if (inicioAtual) {
            dispararChange(inicioOculto);
          }
        });
      });
    }

    return {
      definir: function (inicio, fim) {
        inicioOculto.value = inicio ? String(inicio) : '';
        fimOculto.value = fim && fim !== inicio ? String(fim) : '';
        comboInicio.setToggleTexto(inicio ? String(inicio) : 'Vers...');
        comboFim.setToggleTexto(fim && fim !== inicio ? String(fim) : 'Opcional');

        return atualizar();
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

  var ESCALA_MINIMA_TEXTO = 0.4;
  var PASSO_ESCALA_TEXTO = 0.06;

  /**
   * Reduz o tamanho do texto biblico (via a variavel CSS
   * --biblia-escala, ver .stage-biblia-texto em telao.css) ate ele
   * caber inteiro dentro do palco - sem isso, um intervalo de varios
   * versiculos (campo "Ate" da busca) podia estourar o palco 16:9 e
   * ter o final do texto cortado, tanto no telao quanto no preletor.
   * Chamado toda vez que o texto exibido muda (ver renderBiblia() no
   * telao.js e a funcao equivalente no preletor.js), sempre comecando
   * de novo em escala 1 (tamanho normal) - um versiculo unico
   * normalmente nao precisa reduzir nada.
   *
   * @param {HTMLElement} stage - o palco 16:9 (limite de altura/largura)
   * @param {HTMLElement} textoEl - o elemento com o texto biblico (filho do palco)
   */
  function ajustarTamanhoTexto(stage, textoEl) {
    if (!stage || !textoEl) {
      return;
    }

    stage.style.setProperty('--biblia-escala', '1');

    if (!textoEl.textContent || !textoEl.textContent.trim()) {
      return;
    }

    var escala = 1;

    // scrollHeight/Width do proprio texto comparado ao espaco
    // disponivel no palco (ja com o padding do .stage-biblia
    // descontado, por isso mede o palco inteiro - o padding e uma
    // fracao fixa dele, cqw/cqh - e nao so o elemento do texto).
    while (
      escala > ESCALA_MINIMA_TEXTO
      && (textoEl.scrollHeight > stage.clientHeight || textoEl.scrollWidth > stage.clientWidth)
    ) {
      escala = Math.max(ESCALA_MINIMA_TEXTO, escala - PASSO_ESCALA_TEXTO);
      stage.style.setProperty('--biblia-escala', String(escala));
    }
  }

  window.KadosysBiblia = {
    montarComboLivro: montarComboLivro,
    montarCapitulo: montarCapitulo,
    montarVersiculos: montarVersiculos,
    montarVersaoPills: montarVersaoPills,
    montarCapituloChips: montarCapituloChips,
    montarVersiculoChips: montarVersiculoChips,
    montarPreletorCapitulo: montarPreletorCapitulo,
    montarPreletorVersiculos: montarPreletorVersiculos,
    ajustarPalco: ajustarPalco,
    ajustarTamanhoTexto: ajustarTamanhoTexto,
  };
})(window);
