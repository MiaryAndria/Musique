# Todolist — Projet Mozika

---

## Environnement

- [ ] Création répertoire `/Mozika` pour placer les musiques
- [ ] Création de différents dossiers pour chaque extension :
  - `/Mozika/mp3`
  - `/Mozika/wav`
  - `/Mozika/logs`
  - `/Mozika/config`

---

## Programme Java

### 1. Automatisation (cron / scheduler)

- [ ] Créer la fonction de scheduling pour que le programme tourne automatiquement toutes les 2 à 5 minutes
  - Utiliser `ScheduledExecutorService` ou un cron interne Java
  - La fonction principale (scan + traitement) est appelée à chaque cycle

### 2. Parcours des dossiers

- [ ] Créer la fonction `scanDirectory(path)` pour parcourir chaque dossier
  - Lister tous les fichiers présents dans `/Mozika/mp3`, `/Mozika/wav`, etc.
  - Filtrer par extension (`.mp3`, `.wav`, etc.)
  - Retourner la liste des fichiers trouvés

### 3. Extraction des métadonnées

- [ ] Installer la bibliothèque nécessaire pour `getMetadata`
  - `mp3agic` pour les fichiers MP3
  - Bibliothèque adaptée pour `.wav` si nécessaire
- [ ] Créer la fonction `getMetadata(file)` pour chaque fichier dans chaque dossier (mp3, wav, etc.)
  - Extraire : nom du fichier, durée, artiste, titre, genre
  - Gérer les métadonnées manquantes (valeur par défaut `"Unknown"`)
  - La fonction est appelée pour chaque fichier détecté lors du scan

### 4. Écriture dans le fichier log

- [ ] Après extraction des métadonnées, insérer les informations dans `logs.txt`
  - Format : date, liste des fichiers traités, erreurs éventuelles
- [ ] Créer la fonction `writeLog(event, level)` — niveaux : `INFO`, `WARN`, `ERROR`
  - Appeler après chaque opération importante (scan, upload, delete)

### 5. Upload (FTP ou API)

- [ ] Créer un fichier de configuration `config.properties`
  - Paramètre `upload.mode = ftp` ou `upload.mode = api`
  - Credentials FTP (host, port, user, password)
  - URL de l'API Symfony
- [ ] Créer la fonction `uploadFTP(file, metadata)`
  - Connexion FTP, envoi du fichier binaire vers `/Mp3`, déconnexion propre
  - Logger le résultat (succès ou erreur)
- [ ] Créer la fonction `uploadViaAPI(file, metadata)`
  - Appel POST vers l'API Symfony (métadonnées en JSON + fichier en multipart)
  - Logger le résultat (succès ou erreur)
- [ ] Lire `upload.mode` depuis `config.properties` et router vers la bonne fonction
  - Si `ftp` → appel `uploadFTP()`
  - Si `api` → appel `uploadViaAPI()`

### 6. Écriture des messages logs.txt

- [ ] Logger tous les événements de l'application dans `logs.txt`
  - Démarrage du scheduler
  - Fichiers détectés lors du scan
  - Résultat de l'extraction des métadonnées
  - Résultat de l'upload (FTP ou API)
  - Résultat de la suppression

### 7. Delete des fichiers mp3

- [ ] Créer la fonction `deleteFile(file)`
  - Supprimer le fichier mp3 local après upload réussi
  - Logger la suppression dans `logs.txt`

---

## Programme Symfony

### 1. CRUD — Compléter les métadonnées manquantes

- [ ] Créer l'entité `Song` en base de données
  - Champs : `id`, `filename`, `title`, `artist`, `genre`, `album`, `duration`, `filePath`, `createdAt`, `updatedAt`
- [ ] Générer le CRUD pour l'entité `Song`
- [ ] Fonction `getMetadata()` — appelée par l'API Java pour recevoir et enregistrer les métadonnées
  - Réception des données → insertion en base
- [ ] Page de modification pour insertion des valeurs manquantes/nécessaires
  - Formulaire d'édition d'un song : compléter artiste, titre, genre, album si manquants

### 2. API — Compléter les informations

- [ ] `POST /api/songs` — recevoir et enregistrer les métadonnées depuis Java
- [ ] `POST /api/songs/{id}/upload` — recevoir le fichier binaire mp3
- [ ] `PUT /api/songs/{id}` — compléter ou corriger les métadonnées d'un song
- [ ] `GET /api/songs/{id}` — retourner les détails d'un song

### 3. Recherche et récupération des fichiers mp3

- [ ] Page de recherche des fichiers mp3
  - `GET /api/songs/search?q=...` — recherche sur tous les champs (titre, artiste, genre, album)
  - Affichage des résultats
- [ ] Récupération (retour) du fichier mp3
  - `GET /api/songs/{id}/download` — retourner le fichier mp3 binaire

### 4. Page de création de playlist

- [ ] Créer l'entité `Playlist` en base de données
  - Champs : `id`, `name`, `totalDuration`, `songs` (ManyToMany avec Song), `createdAt`
- [ ] Fonction `getGenreChanson()` — récupérer les genres disponibles
- [ ] Fonction `getArtiste()` — récupérer les artistes disponibles (sélection multiple possible)
- [ ] Fonction `getChanson()` — récupérer les chansons selon les critères choisis
- [ ] Fonction `creationPlaylist()` — créer la playlist avec vérification de la durée totale
  - Vérifier que la durée totale des chansons sélectionnées correspond à la durée souhaitée
- [ ] `POST /api/playlists` — enregistrer la playlist en base
- [ ] `GET /api/playlists/{id}` — retourner les détails d'une playlist

### 5. Page de choix : jouer ou télécharger

- [ ] Fonction `playSong()` — retourner les URLs des mp3 pour lecture côté web
- [ ] Fonction `downloadSong()` — générer et retourner un fichier `playlist.zip` contenant les mp3
  - `GET /api/playlists/{id}/download` — retourner le ZIP

### 6. Stockage des métadonnées en base de données

- [ ] Configurer Doctrine ORM (connexion BDD, paramètres)
- [ ] Créer et exécuter les migrations
- [ ] Vérifier que toutes les métadonnées reçues depuis Java sont bien persistées

---

## Page React

### 1. Setup et structure

- [ ] Initialiser le projet React
- [ ] Configurer React Router avec les routes principales
- [ ] Créer un service API `apiService.js` centralisant tous les appels axios vers Symfony

### 2. Page liste des mp3

- [ ] Créer la page `/songs`
  - Afficher la liste des mp3 avec : titre, artiste, genre, durée
  - Boutons par song : Écouter, Modifier, Télécharger, Supprimer
  - Appel API : `GET /api/songs`

### 3. Page recherche

- [ ] Créer la page de recherche
  - Barre de recherche → appel `GET /api/songs/search?q=...`
  - Filtres : genre, artiste, durée
  - Affichage des résultats en temps réel

### 4. Page détail / modification

- [ ] Créer le formulaire de modification des métadonnées d'un song
  - Appel API : `PUT /api/songs/{id}`

### 5. Lecteur audio

- [ ] Intégrer un lecteur audio (react-player ou howler.js)
  - Contrôles : play, pause, barre de progression, volume
  - Appel API : `GET /api/songs/{id}/download` pour récupérer le stream

### 6. Page création de playlist

- [ ] Créer le formulaire de création de playlist
  - Saisie : nom, genre de chanson, durée totale souhaitée, sélection artiste (plusieurs possible)
  - Appel API : `getGenreChanson`, `getArtiste`, `getChanson`
  - Vérification temps réel de la durée totale (feedback visuel)
  - Appel API : `POST /api/playlists` → `creationPlaylist`

### 7. Page de choix : jouer ou télécharger la playlist

- [ ] Bouton "Jouer la playlist"
  - Appel fonction `playSong()` → lecture séquentielle dans le lecteur intégré
- [ ] Bouton "Télécharger la playlist"
  - Appel fonction `downloadSong()` → `GET /api/playlists/{id}/download`