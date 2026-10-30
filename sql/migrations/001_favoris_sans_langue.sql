-- Retire la langue de lecture et ajoute les favoris
-- À exécuter une fois sur une base créée avant ce changement

USE manga_tracker;

ALTER TABLE oeuvres
    DROP INDEX idx_oeuvres_langue,
    DROP COLUMN langue,
    ADD COLUMN favori TINYINT(1) NOT NULL DEFAULT 0 AFTER statut_lecture,
    ADD KEY idx_oeuvres_favori (favori);
