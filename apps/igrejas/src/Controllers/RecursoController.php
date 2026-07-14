<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\TenantResolver;

/**
 * Paginas dedicadas de cada modulo no site institucional
 * (/recursos/{slug}) - versao detalhada do que hoje aparece resumido
 * na home (#recursos), com screenshot real do sistema rodando e a
 * lista de diferenciais daquele modulo especifico.
 */
final class RecursoController extends Controller
{
    /**
     * Unica fonte de verdade do conteudo de cada pagina - tambem usada
     * pelo layout (menu "Recursos" e rodape) pra montar os links, ver
     * layouts/landing.php.
     *
     * @var array<string, array{
     *     title: string,
     *     icon: string,
     *     tagline: string,
     *     intro: string,
     *     diferenciais: array<int, array{icon: string, titulo: string, texto: string}>,
     *     passos: array<int, array{titulo: string, texto: string}>,
     *     imagem: string,
     *     imagemSecundaria: ?string,
     *     imagemAlt: string,
     * }>
     */
    public const MODULOS = [
        'louvores' => [
            'title' => 'Louvores e Modo Culto',
            'icon' => 'bi-music-note-list',
            'tagline' => 'Cifra, tom e repertório sincronizados com o time inteiro, ao vivo.',
            'intro' => 'Cada música fica com letra, cifra e tom cadastrados uma única vez - '
                . 'e o Modo Culto leva isso pro palco: o líder avança a música ou muda o tom '
                . 'no próprio celular, e todo o time vê a mesma cifra atualizada na hora, sem '
                . 'ninguém precisar recarregar a página ou avisar no grupo do WhatsApp.',
            'diferenciais' => [
                ['icon' => 'bi-arrow-repeat', 'titulo' => 'Transposição automática de tom', 'texto' => 'Muda o tom de uma música e a cifra inteira se reescreve sozinha - nenhum acorde pra digitar de novo.'],
                ['icon' => 'bi-broadcast', 'titulo' => 'Modo Culto ao vivo', 'texto' => 'O líder controla do próprio celular; músicos e telão acompanham em tempo real, cada um na sua tela.'],
                ['icon' => 'bi-clock-history', 'titulo' => 'Sugestão automática de tom', 'texto' => 'O sistema aprende com o histórico de execuções e já sugere o tom mais usado de cada música.'],
                ['icon' => 'bi-chat-dots', 'titulo' => 'Anotações e avisos rápidos', 'texto' => 'Cada músico guarda anotações pessoais na música, e um chat rápido resolve combinados no meio do culto.'],
            ],
            'passos' => [
                ['titulo' => 'Cadastre a música', 'texto' => 'Letra, cifra e tom original, uma única vez - fica pronta pra qualquer culto ou ensaio.'],
                ['titulo' => 'Monte o repertório do culto', 'texto' => 'Escolha as músicas da vez e a ordem de execução.'],
                ['titulo' => 'Abra o Modo Culto', 'texto' => 'Líder e time acessam a mesma tela cheia, cada um no próprio celular ou tablet.'],
                ['titulo' => 'Mude o tom ao vivo', 'texto' => 'A cifra de todo o time se reescreve sozinha, na hora, sem ninguém digitar nada.'],
            ],
            'imagem' => 'modo_culto.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Modo Culto mostrando a cifra ao vivo, com controle de tom',
        ],
        'projecao' => [
            'title' => 'Projeção e Telão',
            'icon' => 'bi-easel2',
            'tagline' => 'Um tablet no púlpito, um computador na operação - do jeito que a sua igreja preferir.',
            'intro' => 'O preletor pode controlar a própria apresentação pelo celular ou tablet: versículo '
                . 'bíblico, vídeo do YouTube, tela de Pix ou uma imagem - tudo aparece no telão no '
                . 'mesmo instante. E a igreja que já tem um operador dedicado no computador continua '
                . 'operando exatamente como sempre operou - as duas formas funcionam ao mesmo tempo, '
                . 'cada uma na sua tela.',
            'diferenciais' => [
                ['icon' => 'bi-pencil', 'titulo' => 'Marcação ao vivo no versículo', 'texto' => 'O preletor circula, sublinha ou destaca trechos do versículo na tela do próprio tablet - e a marcação aparece no telão na mesma hora, pra igreja inteira ver.'],
                ['icon' => 'bi-tablet', 'titulo' => 'Controle pelo tablet ou pelo computador', 'texto' => 'O preletor pode navegar pela Bíblia e trocar de tela direto do púlpito, e o operador continua controlando tudo pelo computador quando a igreja preferir manter esse papel na equipe.'],
                ['icon' => 'bi-youtube', 'titulo' => 'Vídeos com controle remoto', 'texto' => 'Play, pausa e volume de vídeos do YouTube controlados a distância, direto pro telão.'],
                ['icon' => 'bi-qr-code', 'titulo' => 'Tela de Pix no telão', 'texto' => 'QR code de dízimo/oferta aparece na hora certa, sem precisar trocar de slide manualmente.'],
                ['icon' => 'bi-wifi', 'titulo' => 'Sincronização em tempo real', 'texto' => 'Funciona em qualquer tablet ou celular com navegador - sem instalar nada, sem cabo.'],
            ],
            'passos' => [
                ['titulo' => 'Inicie a sessão de Projeção', 'texto' => 'Um clique no painel gera um PIN e o link do telão.'],
                ['titulo' => 'Compartilhe o PIN com o preletor', 'texto' => 'Ou continue operando direto do computador, como preferir.'],
                ['titulo' => 'Navegue pela Bíblia, vídeo, Pix ou imagem', 'texto' => 'Cada troca de tela é só um toque, sem precisar montar slide.'],
                ['titulo' => 'O telão acompanha em tempo real', 'texto' => 'Inclusive as marcações feitas à mão sobre o versículo.'],
            ],
            'imagem' => 'telao.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Telão mostrando uma marcação feita ao vivo pelo preletor sobre o versículo, com o tablet do preletor ao lado',
        ],
        'agenda' => [
            'title' => 'Agenda',
            'icon' => 'bi-calendar3',
            'tagline' => 'Cultos, eventos e aniversariantes num calendário só.',
            'intro' => 'Um calendário mensal reúne os cultos cadastrados, os eventos da igreja e quem '
                . 'faz aniversário naquele mês - com opção de repetição automática pra cultos fixos e '
                . 'compromissos pessoais que só quem cadastrou consegue ver.',
            'diferenciais' => [
                ['icon' => 'bi-calendar-week', 'titulo' => 'Tudo num calendário só', 'texto' => 'Cultos, eventos e aniversariantes do mês, cada um com sua cor, no mesmo lugar.'],
                ['icon' => 'bi-arrow-repeat', 'titulo' => 'Recorrência automática', 'texto' => 'Cadastra o culto de domingo uma vez com "repetir toda semana" e ele já aparece nas próximas semanas.'],
                ['icon' => 'bi-lock', 'titulo' => 'Compromissos privados', 'texto' => 'Qualquer pessoa pode anotar um compromisso pessoal que só ela vê no próprio calendário.'],
                ['icon' => 'bi-gift', 'titulo' => 'Parabéns automático', 'texto' => 'E-mail de aniversário enviado sozinho, com mensagem personalizável pela igreja.'],
            ],
            'passos' => [
                ['titulo' => 'Cadastre o culto ou evento', 'texto' => 'Data, horário e uma cor pra identificar o tipo de compromisso.'],
                ['titulo' => 'Marque como recorrente, se for fixo', 'texto' => 'O culto de domingo, por exemplo, já aparece sozinho nas próximas semanas.'],
                ['titulo' => 'Aniversariantes entram automaticamente', 'texto' => 'O sistema já sabe quem faz aniversário naquele mês.'],
                ['titulo' => 'A igreja vê o calendário sempre atualizado', 'texto' => 'Sem precisar avisar ninguém manualmente.'],
            ],
            'imagem' => 'recursos/agenda.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Calendário mensal da Agenda com cultos, eventos e aniversariantes',
        ],
        'financeiro' => [
            'title' => 'Financeiro',
            'icon' => 'bi-cash-coin',
            'tagline' => 'Dízimos, ofertas e despesas organizados, com Pix direto pra conta da igreja.',
            'intro' => 'Cada entrada e saída fica categorizada e rastreável - e a igreja ainda ganha '
                . 'uma página pública própria de doação via Pix, com o dinheiro caindo direto na conta '
                . 'dela, sem nenhum intermediário ou taxa da plataforma.',
            'diferenciais' => [
                ['icon' => 'bi-graph-up', 'titulo' => 'Entradas e saídas por categoria', 'texto' => 'Dízimos, ofertas, missões, manutenção - cada lançamento no lugar certo, fácil de conferir.'],
                ['icon' => 'bi-qr-code-scan', 'titulo' => 'Doação via Pix, sem intermediário', 'texto' => 'Página pública de doação com QR code - o valor cai direto na conta Pix da própria igreja.'],
                ['icon' => 'bi-easel', 'titulo' => 'Pix também no telão', 'texto' => 'O mesmo QR code pode aparecer na tela durante o culto, na hora do dízimo e da oferta.'],
            ],
            'passos' => [
                ['titulo' => 'Cadastre a chave Pix da igreja', 'texto' => 'Uma vez só, em Configurações.'],
                ['titulo' => 'Compartilhe o link público de doação', 'texto' => 'Ou exiba o QR code direto no telão durante o culto.'],
                ['titulo' => 'Lance entradas e saídas por categoria', 'texto' => 'Dízimo, oferta, missões, manutenção - cada uma no lugar certo.'],
                ['titulo' => 'Acompanhe tudo consolidado', 'texto' => 'Sem planilha, sem lançamento duplicado.'],
            ],
            'imagem' => 'recursos/financeiro.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Painel financeiro com lançamentos de entradas e saídas',
        ],
        'membros' => [
            'title' => 'Membros',
            'icon' => 'bi-people',
            'tagline' => 'Cadastro completo, histórico e autocadastro pelos próprios membros.',
            'intro' => 'Ficha completa de cada membro, com endereço preenchido automaticamente pelo '
                . 'CEP e a opção de deixar que a própria pessoa se cadastre pelo site da igreja - sem '
                . 'a secretaria precisar digitar tudo manualmente.',
            'diferenciais' => [
                ['icon' => 'bi-person-vcard', 'titulo' => 'Ficha completa', 'texto' => 'Contato, endereço, data de membresia e observações, tudo num só cadastro.'],
                ['icon' => 'bi-geo-alt', 'titulo' => 'Endereço automático pelo CEP', 'texto' => 'Preenche rua, bairro e cidade sozinho - só falta o número.'],
                ['icon' => 'bi-person-plus', 'titulo' => 'Autocadastro público', 'texto' => 'Se a igreja quiser, o próprio visitante ou membro se cadastra sozinho, pelo link do site.'],
                ['icon' => 'bi-shield-check', 'titulo' => 'Acesso já com permissões prontas', 'texto' => 'Ao criar o login de um membro, ele já nasce com o perfil de acesso padrão da igreja.'],
            ],
            'passos' => [
                ['titulo' => 'Ative o autocadastro público (opcional)', 'texto' => 'A igreja decide se libera esse link ou não.'],
                ['titulo' => 'O membro se cadastra sozinho', 'texto' => 'Ou a secretaria cadastra manualmente - as duas formas continuam funcionando.'],
                ['titulo' => 'O endereço se preenche pelo CEP', 'texto' => 'Só falta completar o número da casa.'],
                ['titulo' => 'A ficha completa fica pronta pra liderança', 'texto' => 'Contato, histórico e observações, tudo num só lugar.'],
            ],
            'imagem' => 'recursos/membros.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Listagem de membros cadastrados com busca',
        ],
        'equipe' => [
            'title' => 'Equipe',
            'icon' => 'bi-grid-3x3-gap-fill',
            'tagline' => 'Quem tem acesso ao sistema, numa galeria com foto e função.',
            'intro' => 'Uma galeria visual - estilo rede social - de todo mundo que tem login no '
                . 'sistema, com foto, cargo e instrumento pra quem é músico. Cada pessoa edita a '
                . 'própria foto e dados no perfil.',
            'diferenciais' => [
                ['icon' => 'bi-images', 'titulo' => 'Galeria com foto e cargo', 'texto' => 'Visual em cards, muito mais fácil de reconhecer quem é quem do que uma lista de nomes.'],
                ['icon' => 'bi-music-note-beamed', 'titulo' => 'Instrumento de cada músico', 'texto' => 'Quem toca o quê fica visível pra todo o ministério de louvor de relance.'],
                ['icon' => 'bi-person-badge', 'titulo' => 'Perfil de autoatendimento', 'texto' => 'Cada pessoa atualiza a própria foto e função, sem precisar pedir pro admin.'],
            ],
            'passos' => [
                ['titulo' => 'Convide a pessoa com login próprio', 'texto' => 'Ela já entra com o nível de acesso padrão da igreja.'],
                ['titulo' => 'Cada um edita a própria foto e função', 'texto' => 'Sem precisar pedir pro administrador.'],
                ['titulo' => 'Aparece na galeria visual da equipe', 'texto' => 'Com cargo e instrumento, se for músico.'],
                ['titulo' => 'Fica fácil reconhecer quem é quem', 'texto' => 'De relance, sem precisar decorar uma lista de nomes.'],
            ],
            'imagem' => 'recursos/equipe.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Galeria da Equipe com fotos, cargos e instrumentos',
        ],
        'ministerios' => [
            'title' => 'Ministérios',
            'icon' => 'bi-diagram-3',
            'tagline' => 'Cada área da igreja organizada com líder e voluntários.',
            'intro' => 'Louvor, infantil, ação social - cada ministério com seu líder e sua lista de '
                . 'voluntários, pronto pra escalar e acompanhar quem está envolvido em quê.',
            'diferenciais' => [
                ['icon' => 'bi-person-check', 'titulo' => 'Líder por ministério', 'texto' => 'Cada área tem um responsável claro, escolhido entre os membros cadastrados.'],
                ['icon' => 'bi-people-fill', 'titulo' => 'Voluntários organizados', 'texto' => 'Adiciona e remove voluntários de cada ministério sem bagunçar os outros.'],
            ],
            'passos' => [
                ['titulo' => 'Crie o ministério', 'texto' => 'Louvor, infantil, ação social - quantos a igreja precisar.'],
                ['titulo' => 'Escolha um líder', 'texto' => 'Selecionado entre os membros já cadastrados.'],
                ['titulo' => 'Adicione os voluntários', 'texto' => 'Cada ministério com sua própria lista, sem bagunçar os outros.'],
                ['titulo' => 'Acompanhe quem está envolvido em quê', 'texto' => 'Visão clara de cada área da igreja.'],
            ],
            'imagem' => 'recursos/ministerios.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Listagem de ministérios com líder e voluntários',
        ],
        'grupos' => [
            'title' => 'Grupos',
            'icon' => 'bi-people-fill',
            'tagline' => 'Células, classes e pequenos grupos, com dia, horário e participantes.',
            'intro' => 'Controle de células, classes de discipulado e outros pequenos grupos - com '
                . 'dia da semana, horário, local e a lista de quem participa de cada um.',
            'diferenciais' => [
                ['icon' => 'bi-calendar-event', 'titulo' => 'Dia e horário fixos', 'texto' => 'Cada grupo com seu encontro recorrente já registrado, fácil de consultar.'],
                ['icon' => 'bi-house-heart', 'titulo' => 'Local e líder', 'texto' => 'Endereço do encontro e quem lidera, tudo num cadastro só.'],
            ],
            'passos' => [
                ['titulo' => 'Cadastre a célula ou classe', 'texto' => 'Nome, tipo e uma breve descrição.'],
                ['titulo' => 'Defina dia, horário e local', 'texto' => 'O encontro recorrente já fica registrado.'],
                ['titulo' => 'Adicione os participantes', 'texto' => 'Quem faz parte daquele grupo específico.'],
                ['titulo' => 'Consulte a lista sempre que precisar', 'texto' => 'Todos os grupos ativos, organizados num só lugar.'],
            ],
            'imagem' => 'recursos/grupos.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Listagem de grupos e células com dia e horário',
        ],
        'playbacks' => [
            'title' => 'Playbacks',
            'icon' => 'bi-music-note-beamed',
            'tagline' => 'Biblioteca de áudio pronta pro ministério de louvor.',
            'intro' => 'Uma biblioteca central de playbacks, vinculada a cada música - o time de '
                . 'louvor encontra o áudio certo sem precisar procurar em pastas espalhadas ou pedir '
                . 'no grupo.',
            'diferenciais' => [
                ['icon' => 'bi-collection-play', 'titulo' => 'Biblioteca centralizada', 'texto' => 'Todo playback num só lugar, vinculado à música certa.'],
                ['icon' => 'bi-sliders', 'titulo' => 'Controle de tom liberado por plano', 'texto' => 'Planos superiores liberam troca de tom do playback em tempo real.'],
            ],
            'passos' => [
                ['titulo' => 'Faça upload do áudio', 'texto' => 'Direto do computador, sem limite de pastas.'],
                ['titulo' => 'Vincule à música certa', 'texto' => 'O playback fica atrelado à letra e cifra já cadastradas.'],
                ['titulo' => 'O time de louvor encontra na hora', 'texto' => 'Biblioteca centralizada, sem procurar em grupo de WhatsApp.'],
                ['titulo' => 'Toca direto do sistema', 'texto' => 'Sem pendrive, sem aplicativo externo.'],
            ],
            'imagem' => 'recursos/playbacks.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Biblioteca de playbacks do ministério de louvor',
        ],
        'comunicacao' => [
            'title' => 'Comunicação',
            'icon' => 'bi-megaphone',
            'tagline' => 'Avisos direcionados, sem depender só do grupo de WhatsApp.',
            'intro' => 'Avisos e comunicados publicados direto no painel de cada usuário - com opção '
                . 'de mandar só pra liderança ou pra todo mundo, sem se perder no meio de outras '
                . 'mensagens.',
            'diferenciais' => [
                ['icon' => 'bi-people', 'titulo' => 'Público-alvo escolhido', 'texto' => 'Aviso só pra liderança, ou pra todo mundo - a escolha é da igreja em cada publicação.'],
                ['icon' => 'bi-bell', 'titulo' => 'Aparece no painel de cada um', 'texto' => 'Sem depender de grupo de WhatsApp lotado - o aviso fica ali, dentro do sistema.'],
            ],
            'passos' => [
                ['titulo' => 'Escreva o aviso', 'texto' => 'Direto do painel, sem precisar de outro aplicativo.'],
                ['titulo' => 'Escolha o público', 'texto' => 'Só liderança, ou todo mundo - a decisão é sua a cada publicação.'],
                ['titulo' => 'Publica direto no painel de cada um', 'texto' => 'Aparece assim que a pessoa faz login.'],
                ['titulo' => 'Ninguém se perde no meio do WhatsApp', 'texto' => 'O aviso continua ali, disponível quando for preciso reler.'],
            ],
            'imagem' => 'recursos/comunicacao.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Lista de avisos publicados no módulo de Comunicação',
        ],
        'patrimonio' => [
            'title' => 'Patrimônio',
            'icon' => 'bi-building',
            'tagline' => 'Bens, imóveis e equipamentos da igreja, com valor e status.',
            'intro' => 'Controle de todo o patrimônio da igreja - do templo ao microfone - com valor '
                . 'estimado, número de patrimônio e status de manutenção.',
            'diferenciais' => [
                ['icon' => 'bi-tag', 'titulo' => 'Número de patrimônio', 'texto' => 'Cada bem identificado e rastreável, do imóvel ao equipamento de som.'],
                ['icon' => 'bi-tools', 'titulo' => 'Status de manutenção', 'texto' => 'Sabe na hora o que está ativo, em manutenção ou já foi baixado.'],
            ],
            'passos' => [
                ['titulo' => 'Cadastre o bem', 'texto' => 'Do imóvel ao equipamento de som, com número de patrimônio.'],
                ['titulo' => 'Registre o valor estimado', 'texto' => 'Pra manter o controle patrimonial da igreja em dia.'],
                ['titulo' => 'Atualize o status quando precisar', 'texto' => 'Ativo, em manutenção ou baixado.'],
                ['titulo' => 'Consulte tudo organizado', 'texto' => 'Sem depender de planilha ou caderno separado.'],
            ],
            'imagem' => 'recursos/patrimonio.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Listagem de bens do patrimônio da igreja',
        ],
        'relatorios' => [
            'title' => 'Relatórios',
            'icon' => 'bi-bar-chart-line',
            'tagline' => 'Indicadores consolidados da igreja, sem precisar montar planilha.',
            'intro' => 'Números de frequência, membros e finanças consolidados automaticamente - a '
                . 'liderança enxerga a saúde da igreja sem precisar cruzar dados manualmente.',
            'diferenciais' => [
                ['icon' => 'bi-graph-up-arrow', 'titulo' => 'Indicadores prontos', 'texto' => 'Frequência, crescimento de membros e finanças, já calculados automaticamente.'],
                ['icon' => 'bi-clock', 'titulo' => 'Sempre atualizado', 'texto' => 'Reflete os dados de verdade do sistema, sem planilha manual desatualizada.'],
            ],
            'passos' => [
                ['titulo' => 'O sistema usa os dados do dia a dia', 'texto' => 'Nenhum lançamento extra - é o que já está cadastrado nos outros módulos.'],
                ['titulo' => 'Frequência, membros e financeiro se cruzam sozinhos', 'texto' => 'Sem precisar montar tabela ou gráfico manualmente.'],
                ['titulo' => 'A liderança acessa quando quiser', 'texto' => 'Indicadores sempre atualizados, direto no painel.'],
                ['titulo' => 'Decisões com números reais', 'texto' => 'Não com estimativa de memória.'],
            ],
            'imagem' => 'recursos/relatorios.png',
            'imagemSecundaria' => null,
            'imagemAlt' => 'Painel de relatórios com indicadores da igreja',
        ],
    ];

    public function show(string $slug): void
    {
        if (TenantResolver::atual() !== null) {
            $this->redirect('/login');
        }

        $modulo = self::MODULOS[$slug] ?? null;

        if ($modulo === null) {
            http_response_code(404);
            echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada - KADOSYS Igrejas'], 'landing');

            return;
        }

        $slugsOrdenados = array_keys(self::MODULOS);
        $posicaoAtual = array_search($slug, $slugsOrdenados, true);
        $proximoSlug = $slugsOrdenados[($posicaoAtual + 1) % count($slugsOrdenados)];

        echo $this->view('landing.recurso', [
            'pageTitle' => $modulo['title'] . ' - KADOSYS Igrejas',
            'slug' => $slug,
            'modulo' => $modulo,
            'proximoSlug' => $proximoSlug,
            'proximoModulo' => self::MODULOS[$proximoSlug],
        ], 'landing');
    }
}
