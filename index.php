<?php

require __DIR__ . '/src/bootstrap.php';

$filtres = [
    'q'      => trim((string) ($_GET['q'] ?? '')),
    'genres' => array_values(array_filter((array) ($_GET['genres'] ?? []), 'is_string')),
    'langue' => array_key_exists($_GET['langue'] ?? '', LANGUES) ? $_GET['langue'] : '',
    'type'   => array_key_exists($_GET['type'] ?? '', TYPES) ? $_GET['type'] : '',
    'statut' => array_key_exists($_GET['statut'] ?? '', STATUTS_LECTURE) ? $_GET['statut'] : '',
    'tri'    => array_key_exists($_GET['tri'] ?? '', TRIS) ? $_GET['tri'] : 'recent',
];

$oeuvres = oeuvres_lister($filtres);
$genres = genres_utilises();
$langues = langues_utilisees();
$nbFiltres = ($filtres['q'] !== '') + count($filtres['genres']) + ($filtres['langue'] !== '') + ($filtres['type'] !== '') + ($filtres['statut'] !== '');
$filtreActif = $nbFiltres > 0;

$titrePage = 'Bibliothèque';
$pageActive = 'bibliotheque';
$scripts = ['assets/js/filtres.js'];
require __DIR__ . '/templates/header.php';
?>

<div class="l-bibliotheque">
    <aside class="l-bibliotheque__filtres">
        <form class="filtres" method="get" action="index.php" data-filtres>
            <h2 class="filtres__titre">
                <button class="filtres__bascule" type="button" aria-expanded="true" aria-controls="filtres-contenu" data-filtres-bascule>
                    <span class="filtres__kanji" aria-hidden="true">選</span>
                    Filtres
                    <?php if ($nbFiltres): ?>
                        <span class="filtres__compte"><?= $nbFiltres ?></span>
                    <?php endif; ?>
                    <span class="filtres__chevron" aria-hidden="true"></span>
                </button>
            </h2>

            <div class="filtres__contenu" id="filtres-contenu">
                <div class="filtres__groupe">
                    <label class="filtres__label" for="q">Rechercher</label>
                    <input class="filtres__champ" type="search" id="q" name="q" value="<?= e($filtres['q']) ?>" placeholder="Titre…">
                </div>

                <div class="filtres__groupe">
                    <label class="filtres__label" for="tri">Trier par</label>
                    <select class="filtres__champ" id="tri" name="tri">
                        <?php foreach (TRIS as $valeur => $libelle): ?>
                            <option value="<?= e($valeur) ?>" <?= $filtres['tri'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtres__groupe">
                    <label class="filtres__label" for="langue">Langue</label>
                    <select class="filtres__champ" id="langue" name="langue">
                        <option value="">Toutes</option>
                        <?php foreach ($langues as $langue): ?>
                            <option value="<?= e($langue) ?>" <?= $filtres['langue'] === $langue ? 'selected' : '' ?>><?= e(libelle(LANGUES, $langue)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtres__groupe">
                    <label class="filtres__label" for="type">Type</label>
                    <select class="filtres__champ" id="type" name="type">
                        <option value="">Tous</option>
                        <?php foreach (TYPES as $valeur => $libelle): ?>
                            <option value="<?= e($valeur) ?>" <?= $filtres['type'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtres__groupe">
                    <label class="filtres__label" for="statut">Statut de lecture</label>
                    <select class="filtres__champ" id="statut" name="statut">
                        <option value="">Tous</option>
                        <?php foreach (STATUTS_LECTURE as $valeur => $libelle): ?>
                            <option value="<?= e($valeur) ?>" <?= $filtres['statut'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($genres): ?>
                    <fieldset class="filtres__groupe filtres__groupe--genres">
                        <legend class="filtres__label">Genres</legend>
                        <div class="filtres__puces">
                            <?php foreach ($genres as $genre): ?>
                                <label class="filtres__puce">
                                    <input class="filtres__case" type="checkbox" name="genres[]" value="<?= e($genre) ?>" <?= in_array($genre, $filtres['genres'], true) ? 'checked' : '' ?>>
                                    <span class="filtres__puce-texte"><?= e(genre_fr($genre)) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <div class="filtres__actions">
                    <button class="bouton bouton--principal filtres__appliquer" type="submit">Appliquer</button>
                    <?php if ($filtreActif): ?>
                        <a class="bouton bouton--secondaire" href="index.php">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </aside>

    <section class="l-bibliotheque__contenu">
        <header class="entete-page">
            <span class="entete-page__kanji" aria-hidden="true">書庫</span>
            <div class="entete-page__textes">
                <h1 class="entete-page__titre">Ma bibliothèque</h1>
                <p class="entete-page__compteur"><?= count($oeuvres) ?> œuvre<?= count($oeuvres) > 1 ? 's' : '' ?></p>
            </div>
        </header>

        <?php if ($oeuvres): ?>
            <div class="l-grille">
                <?php foreach ($oeuvres as $oeuvre): ?>
                    <?php require __DIR__ . '/templates/partials/carte.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php elseif ($filtreActif): ?>
            <div class="vide">
                <span class="vide__enso" aria-hidden="true">空</span>
                <p class="vide__texte">Aucune œuvre ne correspond à ces filtres.</p>
                <a class="bouton bouton--secondaire" href="index.php">Réinitialiser les filtres</a>
            </div>
        <?php else: ?>
            <div class="vide">
                <span class="vide__enso" aria-hidden="true">空</span>
                <p class="vide__texte">Ta bibliothèque est vide pour l'instant.</p>
                <a class="bouton bouton--principal" href="ajouter.php">Ajouter ma première lecture</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
