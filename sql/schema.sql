-- Base de données de la MangaThèque
-- À importer dans phpMyAdmin (MAMP) ou via : mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS manga_tracker
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE manga_tracker;

-- Œuvres lues (mangas, manhwas, manhuas)
CREATE TABLE IF NOT EXISTS oeuvres (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    anilist_id          INT UNSIGNED NULL,
    titre               VARCHAR(255) NOT NULL,
    titre_alternatif    VARCHAR(255) NULL,
    type                ENUM('manga', 'manhwa', 'manhua') NOT NULL DEFAULT 'manga',
    langue              VARCHAR(5) NOT NULL DEFAULT 'fr',
    resume              TEXT NULL,
    cover_url           VARCHAR(500) NULL,
    cover_locale        VARCHAR(255) NULL,
    statut_publication  VARCHAR(30) NULL,
    statut_lecture      ENUM('en_cours', 'a_lire', 'termine', 'en_pause', 'abandonne') NOT NULL DEFAULT 'en_cours',
    chapitre_actuel     INT UNSIGNED NOT NULL DEFAULT 0,
    chapitres_total     INT UNSIGNED NULL,
    lien                VARCHAR(500) NULL,
    annee               SMALLINT UNSIGNED NULL,
    anilist_synchro_le  DATETIME NULL,
    cree_le             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_oeuvres_anilist (anilist_id),
    KEY idx_oeuvres_titre (titre),
    KEY idx_oeuvres_langue (langue),
    KEY idx_oeuvres_cree_le (cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Genres (noms AniList en anglais, traduits à l'affichage)
CREATE TABLE IF NOT EXISTS genres (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom  VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_genres_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oeuvre_genre (
    oeuvre_id  INT UNSIGNED NOT NULL,
    genre_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (oeuvre_id, genre_id),
    CONSTRAINT fk_og_oeuvre FOREIGN KEY (oeuvre_id) REFERENCES oeuvres (id) ON DELETE CASCADE,
    CONSTRAINT fk_og_genre  FOREIGN KEY (genre_id)  REFERENCES genres (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache des réponses AniList (recherches et fiches) pour limiter les appels à l'API
CREATE TABLE IF NOT EXISTS anilist_cache (
    cle      VARCHAR(191) NOT NULL,
    donnees  MEDIUMTEXT NOT NULL,
    cree_le  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
