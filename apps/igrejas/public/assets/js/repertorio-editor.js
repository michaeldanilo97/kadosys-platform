(function () {
  'use strict';

  var root = document.querySelector('[data-repertorio-editor]');

  if (!root) {
    return;
  }

  var lista = root.querySelector('[data-repertorio-lista]');
  var vazio = root.querySelector('[data-repertorio-vazio]');
  var busca = root.querySelector('[data-repertorio-busca]');

  var adicionarUrl = root.getAttribute('data-adicionar-url');
  var reordenarUrl = root.getAttribute('data-reordenar-url');
  var removerUrlBase = root.getAttribute('data-remover-url-base');
  var csrf = root.getAttribute('data-csrf');

  function enviar(url, dados) {
    dados = dados || new URLSearchParams();
    dados.set('_csrf_token', csrf);

    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: dados.toString(),
    }).then(function (resposta) {
      return resposta.json();
    });
  }

  /**
   * Reconstroi a lista de itens do repertorio a partir da resposta do
   * servidor (sempre o estado completo e atual) - mais simples e menos
   * sujeito a erro do que tentar remendar o DOM item por item a cada
   * acao (adicionar/remover/reordenar).
   */
  function renderizarLista(itens) {
    lista.innerHTML = '';

    itens.forEach(function (item) {
      var li = document.createElement('li');
      li.className = 'repertorio-item';
      li.draggable = true;
      li.setAttribute('data-item-id', item.id);

      var meta = [];
      if (item.tomAtual) {
        meta.push(item.tomAtual);
      }
      if (item.andamentoBpm) {
        meta.push(item.andamentoBpm + ' BPM');
      }

      li.innerHTML =
        '<span class="repertorio-item-arrasta"><i class="bi bi-grip-vertical"></i></span>' +
        '<span class="repertorio-item-titulo"></span>' +
        '<span class="repertorio-item-meta"></span>' +
        '<button type="button" class="crud-icon-btn danger" data-repertorio-remover aria-label="Remover"><i class="bi bi-trash"></i></button>';

      li.querySelector('.repertorio-item-titulo').textContent = item.titulo;
      li.querySelector('.repertorio-item-meta').textContent = meta.join(' · ');

      lista.appendChild(li);
    });

    if (vazio) {
      vazio.hidden = itens.length > 0;
    }

    ativarDragEDrop();
    ativarBotoesRemover();
  }

  function idsNaOrdemAtual() {
    return Array.prototype.map.call(lista.querySelectorAll('[data-item-id]'), function (li) {
      return li.getAttribute('data-item-id');
    });
  }

  var itemArrastado = null;

  function ativarDragEDrop() {
    var itens = lista.querySelectorAll('.repertorio-item');

    itens.forEach(function (li) {
      li.addEventListener('dragstart', function () {
        itemArrastado = li;
        li.classList.add('is-arrastando');
      });

      li.addEventListener('dragend', function () {
        li.classList.remove('is-arrastando');
        itemArrastado = null;
      });

      li.addEventListener('dragover', function (evento) {
        evento.preventDefault();

        if (!itemArrastado || itemArrastado === li) {
          return;
        }

        var retangulo = li.getBoundingClientRect();
        var meio = retangulo.top + retangulo.height / 2;

        if (evento.clientY < meio) {
          lista.insertBefore(itemArrastado, li);
        } else {
          lista.insertBefore(itemArrastado, li.nextSibling);
        }
      });

      li.addEventListener('drop', function (evento) {
        evento.preventDefault();
        enviar(reordenarUrl, new URLSearchParams({ itens: idsNaOrdemAtual().join(',') }));
      });
    });
  }

  function ativarBotoesRemover() {
    lista.querySelectorAll('[data-repertorio-remover]').forEach(function (botao) {
      botao.addEventListener('click', function () {
        var li = botao.closest('.repertorio-item');
        var itemId = li.getAttribute('data-item-id');

        enviar(removerUrlBase + '/' + itemId + '/remover').then(function (dados) {
          if (dados.itens) {
            renderizarLista(dados.itens);
          }
        });
      });
    });
  }

  root.querySelectorAll('[data-repertorio-adicionar]').forEach(function (botao) {
    botao.addEventListener('click', function () {
      var li = botao.closest('.repertorio-disponivel-item');
      var louvorId = li.getAttribute('data-louvor-id');

      enviar(adicionarUrl, new URLSearchParams({ louvor_id: louvorId })).then(function (dados) {
        if (dados.itens) {
          renderizarLista(dados.itens);
        }
      });
    });
  });

  if (busca) {
    busca.addEventListener('input', function () {
      var termo = busca.value.trim().toLowerCase();

      root.querySelectorAll('[data-repertorio-disponiveis] .repertorio-disponivel-item').forEach(function (li) {
        var titulo = li.getAttribute('data-louvor-titulo') || '';
        li.hidden = termo !== '' && titulo.indexOf(termo) === -1;
      });
    });
  }

  ativarDragEDrop();
  ativarBotoesRemover();
})();
