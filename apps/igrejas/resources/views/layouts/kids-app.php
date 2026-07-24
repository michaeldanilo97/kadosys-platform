<?php

use Igrejas\Core\View;

/**
 * Layout minimo do "modo criança" (ver KidsLoginController/
 * KidsAppController) - sem sidebar/topbar administrativo de proposito,
 * so o mundo colorido da Biblioteca Kids (ver kids-biblioteca.css),
 * pra crianca usar sozinha num tablet/celular.
 *
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca|null $crianca
 */
$basePath = $config['base_path'] ?? '';

// Contexto pro mascote (ver public/assets/js/kids-mascote.js) reagir com
// a frase certa assim que a pagina carrega - "nivel" tem prioridade
// sobre "parabens" porque normalmente acontecem juntos (o nivel sobe
// junto com uma recompensa) e subir de nivel e a noticia mais especial
// das duas. Fora essas duas janelas, o mascote so aparece parado (a
// saudacao de "Oi, oi!" e o incentivo aleatorio ficam por conta do
// clique da propria crianca - ver kids-mascote.js).
$mascoteContexto = 'parado';
if (!empty($subiuNivel ?? null)) {
    $mascoteContexto = 'nivel';
} elseif (
    (isset($novosEmblemas) && $novosEmblemas !== [])
    || (isset($pontosGanhos) && $pontosGanhos !== null)
    || (isset($acessoApp) && $acessoApp !== null && ($acessoApp['xp'] > 0 || $acessoApp['moedas'] > 0))
) {
    $mascoteContexto = 'parabens';
} elseif (!empty($mascoteSaudacao ?? null)) {
    $mascoteContexto = 'saudacao';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Kids', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids-biblioteca.css?v=<?= View::assetVersion('assets/css/kids-biblioteca.css') ?>">
</head>
<body class="kids-standalone">
<?= $content ?>
<?php if (isset($crianca)): ?>
    <div class="kids-mascote" data-mascote="<?= htmlspecialchars($crianca->avatarMascote, ENT_QUOTES, 'UTF-8') ?>" data-mascote-contexto="<?= $mascoteContexto ?>" data-mascote-primeiro-nome="<?= htmlspecialchars(explode(' ', $crianca->nome)[0], ENT_QUOTES, 'UTF-8') ?>">
        <div class="kids-mascote-balao" data-mascote-balao hidden></div>
        <button type="button" class="kids-mascote-figura" data-mascote-figura aria-label="Falar com o mascote"></button>
    </div>
<?php endif; ?>
<script src="<?= $basePath ?>/assets/js/kids-sons.js?v=<?= View::assetVersion('assets/js/kids-sons.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-interacoes.js?v=<?= View::assetVersion('assets/js/kids-interacoes.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-memoria.js?v=<?= View::assetVersion('assets/js/kids-jogo-memoria.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-trivia.js?v=<?= View::assetVersion('assets/js/kids-jogo-trivia.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-cacapalavras.js?v=<?= View::assetVersion('assets/js/kids-jogo-cacapalavras.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-mascote.js?v=<?= View::assetVersion('assets/js/kids-mascote.js') ?>"></script>
</body>
</html>
