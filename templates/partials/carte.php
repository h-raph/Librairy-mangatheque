<?php
/** @var array $oeuvre */
$progression = $oeuvre['chapitres_total']
    ? "Ch. {$oeuvre['chapitre_actuel']} / {$oeuvre['chapitres_total']}"
    : "Ch. {$oeuvre['chapitre_actuel']}";
?>
<article class="carte">
    <a class="carte__lien" href="manga.php?id=<?= (int) $oeuvre['id'] ?>">
        <div class="carte__couverture">
            <img class="carte__image" src="<?= e(cover($oeuvre)) ?>" alt="" loading="lazy">
            <span class="badge badge--<?= e($oeuvre['type']) ?> carte__type"><?= e(libelle(TYPES, $oeuvre['type'])) ?></span>
        </div>
        <div class="carte__corps">
            <h3 class="carte__titre"><?= e($oeuvre['titre']) ?></h3>
            <p class="carte__meta"><?= e($progression) ?></p>
            <span class="badge badge--statut badge--<?= e(modificateur($oeuvre['statut_lecture'])) ?>">
                <?= e(libelle(STATUTS_LECTURE, $oeuvre['statut_lecture'])) ?>
            </span>
            <?php if ($oeuvre['genres']): ?>
                <ul class="carte__genres">
                    <?php foreach (array_slice($oeuvre['genres'], 0, 3) as $genre): ?>
                        <li class="carte__genre"><?= e(genre_fr($genre)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </a>
    <?php $classeFavori = 'favori--carte'; require __DIR__ . '/bouton-favori.php'; ?>
</article>
