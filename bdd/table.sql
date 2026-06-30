1. TABLE: t_user (Utilisateurs)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
name         | VARCHAR(255)    | UNIQUE, NOT NULL               | Nom d'utilisateur
password     | VARCHAR(255)    | NOT NULL                       | Mot de passe haché
created_at   | DATETIME        | NOT NULL                       | Date de création
updated_at   | DATETIME        | NULL                           | Date de modification


2. TABLE: t_artist (Artistes)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
name         | VARCHAR(255)    | UNIQUE, NOT NULL               | Nom de l'artiste
created_at   | DATETIME        | NOT NULL                       | Date de création


3. TABLE: t_genre (Genres musicaux)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
name         | VARCHAR(100)    | UNIQUE, NOT NULL               | Nom du genre (Pop, Rock, Jazz)
created_at   | DATETIME        | NOT NULL                       | Date de création


4. TABLE: t_album (Albums)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
name         | VARCHAR(255)    | NOT NULL                       | Nom de l'album
artist_id    | INTEGER         | FOREIGN KEY → t_artist.id      | Artiste de l'album
release_date | DATE            | NULL                           | Date de sortie
created_at   | DATETIME        | NOT NULL                       | Date de création


5. TABLE: t_musique (Musiques / Fichiers MP3)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
titre        | VARCHAR(255)    | NOT NULL                       | Titre de la musique
duree        | INTEGER         | NOT NULL                       | Durée en secondes
file_path    | VARCHAR(255)    | NOT NULL                       | Chemin du fichier MP3
created_at   | DATETIME        | NOT NULL                       | Date d'upload
updated_at   | DATETIME        | NULL                           | Date de modification


6. TABLE: t_playlist (Playlists)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
name         | VARCHAR(255)    | NOT NULL                       | Nom de la playlist
user_id      | INTEGER         | FOREIGN KEY → t_user.id        | Propriétaire de la playlist
created_at   | DATETIME        | NOT NULL                       | Date de création
updated_at   | DATETIME        | NULL                           | Date de modification


7. TABLE: t_musique_artist (Musique ↔ Artiste)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
musique_id   | INTEGER         | FOREIGN KEY → t_musique.id     | ID de la musique
artist_id    | INTEGER         | FOREIGN KEY → t_artist.id      | ID de l'artiste
album_id     | INTEGER         | FOREIGN KEY → t_album.id       | ID de l'album
genre_id     | INTEGER         | FOREIGN KEY → t_genre.id       | ID du genre
created_at   | DATETIME        | NOT NULL                       | Date de création
-----------------------------------------------------------------------------
Contrainte UNIQUE: (musique_id, artist_id)


8. TABLE: t_musique_genre (Musique ↔ Genre)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
musique_id   | INTEGER         | FOREIGN KEY → t_musique.id     | ID de la musique
genre_id     | INTEGER         | FOREIGN KEY → t_genre.id       | ID du genre
created_at   | DATETIME        | NOT NULL                       | Date de création
-----------------------------------------------------------------------------
Contrainte UNIQUE: (musique_id, genre_id)


9. TABLE: t_playlist_musique (Playlist ↔ Musique)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
playlist_id  | INTEGER         | FOREIGN KEY → t_playlist.id    | ID de la playlist
musique_id   | INTEGER         | FOREIGN KEY → t_musique.id     | ID de la musique
created_at   | DATETIME        | NOT NULL                       | Date de création
-----------------------------------------------------------------------------
Contrainte UNIQUE: (playlist_id, musique_id)


10. TABLE: t_user_playlist (User ↔ Playlist)
-----------------------------------------------------------------------------
Colonne      | Type            | Contraintes                    | Description
-----------------------------------------------------------------------------
id           | INTEGER         | PRIMARY KEY, AUTO_INCREMENT    | ID unique
user_id      | INTEGER         | FOREIGN KEY → t_user.id        | ID de l'utilisateur
playlist_id  | INTEGER         | FOREIGN KEY → t_playlist.id    | ID de la playlist
created_at   | DATETIME        | NOT NULL                       | Date de création
-----------------------------------------------------------------------------
Contrainte UNIQUE: (user_id, playlist_id)



