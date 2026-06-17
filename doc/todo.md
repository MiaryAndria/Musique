## environnement
creation repertoire musique pour placer les musiques d'abord 
creation de differentes dossier pour chaque extensions differentes 

## programme Java 
creation fonction cron ou automatiser et faire que le programme tourne automatique 2 à 5min
creation fonction pour parcourir chaque dossier d'abord 

installation bibliotheque pour faire getMetadata
creation fonction une fonction getMetadata pour chaque fichier dans chaque dossier dossier (mp3 , wav ,etc) je veux dire pour extraction metadonnée (nom de fichiers , durée , artiste , titre , genre) de chaque musique pour chaque dossier 
ensuite insertion des informations des musiques recup dans fichier log.txt contenant date , liste , erreur 

creation fonction pour upload un protocole ftp 
fonction pour api  API avec Symfony => Java appel l'API on doit choisir le mode ftp ou api dans un fichiers de configuration : l'appli doit supporter les 2 

Ecriture des messages logs.txt
Delete mp3 

## programme symfony

fonction CRUD mameno ny informations manquantes ana metadata
=> fonction getMetaData appeller api dans java ensuite page modif pour insertion d'autre valeur nécessaires 
API pour completer les informations , 
page pour rechercher les fichiers mp3, pour recuperer (retourner) mp3
Page de création de playlist: avec choix genre de chanson, la durée total du playlist, artiste,
=> appelle fonction getGenreChanson ensuite getArtiste et getChanson ensuite creationPlaylist (avec verification durée Playlist)
Page de choix: jouer les chansons de la playlist cote web ou télécharger la playlist.zip
=> appelle fonction playSong ou downloadSong 
Stocker les metadata dans une base de donnee

## page react 

creation page nécessaires et appelle api de chaque fonctionnalité