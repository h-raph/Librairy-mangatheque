<?php
/** @var string $titrePage */
/** @var string $pageActive */
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titrePage) ?> · MangaThèque</title>
    <link rel="icon" href="assets/img/placeholder.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@400;600&family=Shippori+Mincho+B1:wght@700;800&family=Yuji+Syuku&display=swap">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="l-page">
    <header class="l-header header">
        <div class="l-container header__inner">
            <a class="header__logo" href="index.php">
                <span class="header__sceau" aria-hidden="true">漫</span>
                <span class="header__nom">Manga<span class="header__logo-accent">Thèque</span></span>
            </a>
            <nav class="nav" aria-label="Navigation principale">
                <a class="nav__lien <?= $pageActive === 'bibliotheque' ? 'is-active' : '' ?>" href="index.php">Bibliothèque</a>
                <a class="nav__lien nav__lien--cta <?= $pageActive === 'ajouter' ? 'is-active' : '' ?>" href="ajouter.php">+ Ajouter</a>
            </nav>
        </div>
    </header>

    <main class="l-main l-container">
        <?php foreach (flashs() as $flash): ?>
            <p class="alerte alerte--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endforeach; ?>
