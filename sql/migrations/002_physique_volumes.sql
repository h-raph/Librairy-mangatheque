-- Active: 1770213586191@@127.0.0.1@8889@manga_tracker
-- Ajoute la possession en physique et le suivi par volume
-- À exécuter une fois, après avoir sélectionné la base (MAMP et alwaysdata)

ALTER TABLE oeuvres
ADD COLUMN physique TINYINT(1) NOT NULL DEFAULT 0 AFTER favori,
ADD COLUMN volume_actuel INT UNSIGNED NOT NULL DEFAULT 0 AFTER chapitres_total,
ADD COLUMN volumes_total INT UNSIGNED NULL AFTER volume_actuel;