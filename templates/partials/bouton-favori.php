<?php
/** @var array $oeuvre */
/** @var string $classeFavori  modificateur BEM : favori--carte ou favori--fiche */
$estFavori = (bool) $oeuvre['favori'];
$libelleFavori = $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris';
$retour = basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
?>
<form class="favori <?= e($classeFavori) ?>" method="post" action="favori.php" data-favori>
    <?= csrf_champ() ?>
    <input type="hidden" name="id" value="<?= (int) $oeuvre['id'] ?>">
    <input type="hidden" name="retour" value="<?= e($retour) ?>">
    <button class="favori__bouton <?= $estFavori ? 'is-active' : '' ?>" type="submit"
            aria-pressed="<?= $estFavori ? 'true' : 'false' ?>" aria-label="<?= e($libelleFavori) ?>" title="<?= e($libelleFavori) ?>">
        <svg class="favori__icone" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2.8l2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.2l-5.7 3.1 1.2-6.4-4.7-4.4 6.4-.8z"/>
        </svg>
        <span class="favori__texte" data-favori-texte><?= $estFavori ? 'Favori' : 'Ajouter aux favoris' ?></span>
    </button>
</form>
