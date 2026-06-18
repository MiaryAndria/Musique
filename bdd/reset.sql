-- ==========================================
-- SCRIPT DE RÉINITIALISATION DE LA BASE
-- ==========================================

-- Suppression des tables existantes (CASCADE gère les FK)

DROP TABLE IF EXISTS t_song_genre CASCADE;
DROP TABLE IF EXISTS t_song_artiste CASCADE;
DROP TABLE IF EXISTS t_playlist_song CASCADE;
DROP TABLE IF EXISTS t_song_album CASCADE;
DROP TABLE IF EXISTS t_song_categorie CASCADE;

DROP TABLE IF EXISTS song CASCADE;
DROP TABLE IF EXISTS playlist CASCADE;
DROP TABLE IF EXISTS genre CASCADE;
DROP TABLE IF EXISTS categorie CASCADE;
DROP TABLE IF EXISTS artiste CASCADE;
DROP TABLE IF EXISTS album CASCADE;
DROP TABLE IF EXISTS t_user CASCADE;
