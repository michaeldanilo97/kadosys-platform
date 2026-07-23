<?php

declare(strict_types=1);

use Food\Controllers\AssinaturaController;
use Food\Controllers\AuthController;
use Food\Controllers\CadastroController;
use Food\Controllers\CaixaController;
use Food\Controllers\CategoriaController;
use Food\Controllers\CentroCustoController;
use Food\Controllers\ClienteController;
use Food\Controllers\CompraController;
use Food\Controllers\ConfiguracaoController;
use Food\Controllers\ContaPagarController;
use Food\Controllers\ContaReceberController;
use Food\Controllers\DashboardController;
use Food\Controllers\EstoqueController;
use Food\Controllers\FaturaController;
use Food\Controllers\FinanceiroController;
use Food\Controllers\FornecedorController;
use Food\Controllers\IngredienteController;
use Food\Controllers\LandingController;
use Food\Controllers\PdvController;
use Food\Controllers\PedidoController;
use Food\Controllers\PrecificacaoController;
use Food\Controllers\ProducaoController;
use Food\Controllers\ProdutoController;
use Food\Controllers\RelatorioController;
use Food\Controllers\WebhookController;
use Food\Core\Middleware\AuthMiddleware;
use Food\Core\Middleware\GuestMiddleware;

/** @var \Food\Core\Router $router */

// Pagina publica de vendas.
$router->get('/', [LandingController::class, 'index']);

// Cadastro publico (restaurante + admin + plano + pagamento).
$router->get('/cadastro', [CadastroController::class, 'form'], [GuestMiddleware::class]);
$router->post('/cadastro', [CadastroController::class, 'enviar'], [GuestMiddleware::class]);
$router->get('/cadastro/pix/{id}', [CadastroController::class, 'pix']);
$router->get('/cadastro/pix/{id}/status', [CadastroController::class, 'pixStatus']);
$router->get('/cadastro/retorno', [CadastroController::class, 'retorno']);

// Notificacoes assincronas do Mercado Pago (confirmacao de pagamento).
$router->post('/webhooks/mercadopago', [WebhookController::class, 'mercadoPago']);

// Autenticacao.
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/esqueci-senha', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/esqueci-senha', [AuthController::class, 'sendForgotPassword'], [GuestMiddleware::class]);
$router->get('/redefinir-senha/{token}', [AuthController::class, 'showResetPassword'], [GuestMiddleware::class]);
$router->post('/redefinir-senha/{token}', [AuthController::class, 'resetPassword'], [GuestMiddleware::class]);

// Dashboard administrativo (protegido).
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Tela de assinatura pendente (trial vencido / fatura Pix vencida / pagamento aguardando confirmacao).
$router->get('/dashboard/assinatura', [AssinaturaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/assinatura/status', [AssinaturaController::class, 'status'], [AuthMiddleware::class]);
$router->post('/dashboard/assinatura/pix', [AssinaturaController::class, 'gerarPix'], [AuthMiddleware::class]);
$router->post('/dashboard/assinatura/cartao', [AssinaturaController::class, 'assinarCartao'], [AuthMiddleware::class]);

// Categorias de produto (Doces, Bolos, Salgados, etc).
$router->get('/dashboard/categorias', [CategoriaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/categorias/nova', [CategoriaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/categorias', [CategoriaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/categorias/{id}/editar', [CategoriaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/categorias/{id}', [CategoriaController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/categorias/{id}/excluir', [CategoriaController::class, 'destroy'], [AuthMiddleware::class]);

// Ingredientes (base da Ficha Tecnica).
$router->get('/dashboard/ingredientes', [IngredienteController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/ingredientes/novo', [IngredienteController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/ingredientes', [IngredienteController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/ingredientes/{id}/editar', [IngredienteController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/ingredientes/{id}', [IngredienteController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/ingredientes/{id}/excluir', [IngredienteController::class, 'destroy'], [AuthMiddleware::class]);

// Fornecedores.
$router->get('/dashboard/fornecedores', [FornecedorController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/fornecedores/novo', [FornecedorController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/fornecedores', [FornecedorController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/fornecedores/{id}/editar', [FornecedorController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/fornecedores/{id}', [FornecedorController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/fornecedores/{id}/excluir', [FornecedorController::class, 'destroy'], [AuthMiddleware::class]);

// Produtos + Ficha Tecnica (custeio automatico via Food\Core\Custeio).
$router->get('/dashboard/produtos', [ProdutoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/novo', [ProdutoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos', [ProdutoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/{id}/editar', [ProdutoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}', [ProdutoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/excluir', [ProdutoController::class, 'destroy'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/{id}/ficha-tecnica', [ProdutoController::class, 'fichaTecnica'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/ficha-tecnica', [ProdutoController::class, 'fichaTecnicaAdicionar'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/ficha-tecnica/{itemId}/excluir', [ProdutoController::class, 'fichaTecnicaRemover'], [AuthMiddleware::class]);

// Estoque (log de movimentacoes + ajuste manual entrada/saida/inventario/perda).
$router->get('/dashboard/estoque', [EstoqueController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/estoque/movimentar', [EstoqueController::class, 'movimentarForm'], [AuthMiddleware::class]);
$router->post('/dashboard/estoque/movimentar', [EstoqueController::class, 'movimentar'], [AuthMiddleware::class]);

// Compras (entrada automatica no estoque + atualizacao de preco do ingrediente).
$router->get('/dashboard/compras', [CompraController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/compras/nova', [CompraController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/compras', [CompraController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/compras/{id}', [CompraController::class, 'show'], [AuthMiddleware::class]);
$router->post('/dashboard/compras/{id}/itens', [CompraController::class, 'itemAdicionar'], [AuthMiddleware::class]);

// Clientes.
$router->get('/dashboard/clientes', [ClienteController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/clientes/novo', [ClienteController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/clientes', [ClienteController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/clientes/{id}', [ClienteController::class, 'show'], [AuthMiddleware::class]);
$router->get('/dashboard/clientes/{id}/editar', [ClienteController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/clientes/{id}', [ClienteController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/clientes/{id}/excluir', [ClienteController::class, 'destroy'], [AuthMiddleware::class]);

// Pedidos (Balcao/WhatsApp/iFood manual/Delivery proprio) + baixa automatica de estoque ao confirmar.
$router->get('/dashboard/pedidos', [PedidoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/pedidos/novo', [PedidoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/pedidos', [PedidoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/pedidos/{id}', [PedidoController::class, 'show'], [AuthMiddleware::class]);
$router->post('/dashboard/pedidos/{id}/itens', [PedidoController::class, 'itemAdicionar'], [AuthMiddleware::class]);
$router->post('/dashboard/pedidos/{id}/itens/{itemId}/excluir', [PedidoController::class, 'itemRemover'], [AuthMiddleware::class]);
$router->post('/dashboard/pedidos/{id}/confirmar', [PedidoController::class, 'confirmar'], [AuthMiddleware::class]);
$router->post('/dashboard/pedidos/{id}/cancelar', [PedidoController::class, 'cancelar'], [AuthMiddleware::class]);

// Producao (tela cozinha/TV) - pedidos confirmados ate a entrega.
$router->get('/dashboard/producao', [ProducaoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/producao/tv', [ProducaoController::class, 'tv'], [AuthMiddleware::class]);
$router->get('/dashboard/producao/dados', [ProducaoController::class, 'dados'], [AuthMiddleware::class]);
$router->post('/dashboard/producao/{id}/avancar', [ProducaoController::class, 'avancar'], [AuthMiddleware::class]);

// Caixa (abertura/fechamento de turno + sangria/suprimento).
$router->get('/dashboard/caixa', [CaixaController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/caixa/abrir', [CaixaController::class, 'abrir'], [AuthMiddleware::class]);
$router->post('/dashboard/caixa/fechar', [CaixaController::class, 'fechar'], [AuthMiddleware::class]);
$router->post('/dashboard/caixa/sangria', [CaixaController::class, 'sangria'], [AuthMiddleware::class]);
$router->post('/dashboard/caixa/suprimento', [CaixaController::class, 'suprimento'], [AuthMiddleware::class]);

// PDV (venda touch com busca rapida/codigo de barras, split payment, troco, Pix dinamico).
$router->get('/dashboard/pdv', [PdvController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/pdv/itens', [PdvController::class, 'itemAdicionar'], [AuthMiddleware::class]);
$router->post('/dashboard/pdv/itens/{itemId}/excluir', [PdvController::class, 'itemRemover'], [AuthMiddleware::class]);
$router->get('/dashboard/pdv/pagamento', [PdvController::class, 'pagamentoForm'], [AuthMiddleware::class]);
$router->post('/dashboard/pdv/pagamento', [PdvController::class, 'pagamentoAdicionar'], [AuthMiddleware::class]);
$router->post('/dashboard/pdv/pagamento/{pagamentoId}/excluir', [PdvController::class, 'pagamentoRemover'], [AuthMiddleware::class]);
$router->post('/dashboard/pdv/finalizar', [PdvController::class, 'finalizar'], [AuthMiddleware::class]);
$router->get('/dashboard/pdv/{id}/recibo', [PdvController::class, 'recibo'], [AuthMiddleware::class]);
$router->post('/dashboard/pdv/cancelar', [PdvController::class, 'cancelar'], [AuthMiddleware::class]);

// Financeiro (dashboard/resumo + lancamentos automaticos).
$router->get('/dashboard/financeiro', [FinanceiroController::class, 'index'], [AuthMiddleware::class]);

// Contas a pagar.
$router->get('/dashboard/financeiro/contas-a-pagar', [ContaPagarController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-pagar', [ContaPagarController::class, 'store'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-pagar/{id}/pagar', [ContaPagarController::class, 'pagar'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-pagar/{id}/cancelar', [ContaPagarController::class, 'cancelar'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-pagar/{id}/excluir', [ContaPagarController::class, 'destroy'], [AuthMiddleware::class]);

// Contas a receber.
$router->get('/dashboard/financeiro/contas-a-receber', [ContaReceberController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-receber', [ContaReceberController::class, 'store'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-receber/{id}/receber', [ContaReceberController::class, 'receber'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-receber/{id}/cancelar', [ContaReceberController::class, 'cancelar'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/contas-a-receber/{id}/excluir', [ContaReceberController::class, 'destroy'], [AuthMiddleware::class]);

// Centros de custo.
$router->get('/dashboard/financeiro/centros-custo', [CentroCustoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/centros-custo', [CentroCustoController::class, 'store'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/centros-custo/{id}', [CentroCustoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/centros-custo/{id}/excluir', [CentroCustoController::class, 'destroy'], [AuthMiddleware::class]);

// Precificacao Inteligente (simulador avulso, nao salva produto).
$router->get('/dashboard/precificacao', [PrecificacaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/precificacao/simular', [PrecificacaoController::class, 'simular'], [AuthMiddleware::class]);
$router->post('/dashboard/precificacao/simular-ifood', [PrecificacaoController::class, 'simularIfood'], [AuthMiddleware::class]);
$router->post('/dashboard/precificacao/config', [PrecificacaoController::class, 'configSalvar'], [AuthMiddleware::class]);

// Relatorios (DRE, produtos, clientes, estoque, fluxo de caixa).
$router->get('/dashboard/relatorios', [RelatorioController::class, 'index'], [AuthMiddleware::class]);

// Faturas (historico de cobranca - sempre acessivel, mesmo bloqueado).
$router->get('/dashboard/faturas', [FaturaController::class, 'index'], [AuthMiddleware::class]);

// Configuracoes (perfil, white-label, Pix, dados fiscais, equipe, impressoras, backup).
$router->get('/dashboard/configuracoes', [ConfiguracaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/perfil', [ConfiguracaoController::class, 'atualizarPerfil'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/fiscal', [ConfiguracaoController::class, 'atualizarDadosFiscais'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/pix', [ConfiguracaoController::class, 'salvarPix'], [AuthMiddleware::class]);
$router->get('/dashboard/configuracoes/backup', [ConfiguracaoController::class, 'backup'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe', [ConfiguracaoController::class, 'criarUsuario'], [AuthMiddleware::class]);
$router->get('/dashboard/configuracoes/equipe/{id}/editar', [ConfiguracaoController::class, 'editarUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe/{id}', [ConfiguracaoController::class, 'atualizarUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe/{id}/excluir', [ConfiguracaoController::class, 'excluirUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/impressoras', [ConfiguracaoController::class, 'criarImpressora'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/impressoras/{id}/excluir', [ConfiguracaoController::class, 'excluirImpressora'], [AuthMiddleware::class]);
