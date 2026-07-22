-- Liga cada entrada da fila a uma conta de cliente (por telefone, mesmo
-- padrao ja usado no agendamento publico - ver Cliente::buscarPorTelefone),
-- pra que o historico e a fidelidade tambem funcionem em barbearias no
-- modo Fila (antes, fila_atendimento so guardava nome/telefone soltos,
-- sem nenhum vinculo com clientes). NULL quando quem entrou na fila nao
-- informou telefone (o campo e opcional no formulario publico).
ALTER TABLE fila_atendimento ADD COLUMN cliente_id INT UNSIGNED NULL AFTER barbearia_id;
ALTER TABLE fila_atendimento ADD CONSTRAINT fila_atendimento_cliente_id_foreign
    FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL;

-- Registro de pagamento ao concluir um atendimento da fila (mesmo
-- padrao do agendamento - ver AgendamentoController::pagamento). Sem
-- isso nao tinha como conceder pontos de fidelidade nem lancar no
-- caixa/financeiro um atendimento feito via fila.
ALTER TABLE financeiro_lancamentos ADD COLUMN fila_atendimento_id INT UNSIGNED NULL AFTER agendamento_id;
ALTER TABLE financeiro_lancamentos ADD UNIQUE KEY financeiro_lancamentos_fila_atendimento_id_unique (fila_atendimento_id);
ALTER TABLE financeiro_lancamentos ADD CONSTRAINT financeiro_lancamentos_fila_atendimento_id_foreign
    FOREIGN KEY (fila_atendimento_id) REFERENCES fila_atendimento (id) ON DELETE SET NULL;
