# MangaThèque

Suivi des mangas, manhwas et manhuas lus en ligne. PHP 8 + MySQL, données récupérées depuis l'API AniList et mises en cache en base.

## Installation (MAMP)

1. Lancer MAMP (MySQL sur le port 8889, `root` / `root`).
2. Importer `sql/schema.sql` dans phpMyAdmin (onglet *Importer*), ou : `/Applications/MAMP/Library/bin/mysql80/bin/mysql -uroot -proot -h127.0.0.1 -P8889 < sql/schema.sql`
3. Servir le dossier :
   - soit en le plaçant dans `/Applications/MAMP/htdocs/`,
   - soit avec le serveur intégré de PHP :
     ```bash
     /Applications/MAMP/bin/php/php8.4.15/bin/php -S localhost:8000
     ```
     puis ouvrir http://localhost:8000
4. Si les identifiants MySQL diffèrent, modifier `config/config.php`.
5. Base créée avant l'ajout des favoris ? Exécuter aussi `sql/migrations/001_favoris_sans_langue.sql`.

Le dossier `assets/covers/` doit être accessible en écriture (les covers y sont téléchargées).

## Pages

| Page | Rôle |
| --- | --- |
| `index.php` | Bibliothèque : grille + filtres (recherche, genres, type, statut, favoris, tri A→Z par défaut / Z→A / récent / ancien) |
| `ajouter.php` | Ajout : recherche AniList avec pré-remplissage, ou saisie manuelle |
| `manga.php?id=` | Fiche : résumé, genres, progression, +1 chapitre, modification, actualisation AniList, suppression |
| `api/recherche.php?q=` | Endpoint JSON utilisé par la recherche |
| `favori.php` | Ajoute / retire une œuvre des favoris (JSON avec JS, redirection sans) |

## Cache AniList

- Table `anilist_cache` : recherches (1 jour) et fiches (7 jours), durées réglables dans `config/config.php`.
- Une fois l'œuvre ajoutée, résumé, genres, statut et cover sont stockés dans `oeuvres` : l'affichage ne fait plus aucun appel à l'API.
- Le bouton « Actualiser » d'une fiche force un nouvel appel.

## Styles (SCSS · SMACSS + BEM)

Les sources sont dans `assets/scss/`, compilées vers `assets/css/main.css` (seul fichier chargé par les pages).

```bash
npm install          # une seule fois
npm run build:css    # compile (minifié + source map)
npm run watch:css    # recompile à chaque modification
```

```
assets/scss/
├── main.scss         point d'entrée (@use dans l'ordre SMACSS)
├── abstracts/        variables Sass et mixins (breakpoints, line-clamp, champ…)
├── theme/            variables CSS (couleurs générées depuis une map Sass)
├── base/             reset et éléments HTML
├── layout/           zones de page, préfixe .l-   (.l-bibliotheque, .l-grille…)
├── module/           un partial par bloc BEM, imbriqué avec &__ / &--
└── state/            états, préfixe .is-           (.is-active, .is-hidden, .is-loading)
```

Le JavaScript cible des attributs `data-*` et non les classes, pour que le style et le comportement restent indépendants.
