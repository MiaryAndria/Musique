<?php

namespace App\Controller\Api;

use App\Entity\Song;
use App\Entity\Artiste;
use App\Entity\Genre;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API Controller pour la gestion des chansons MP3.
 * 
 * Point 3 du sujet : Réception des métadonnées et fichiers MP3 envoyés par Java.
 * Java envoie via POST multipart/form-data → Symfony persiste en BDD + stocke le fichier.
 */
#[Route('/api')]
class SongApiController extends AbstractController
{
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $em,
        private SongRepository $songRepository,
    ) {
        // Le répertoire d'upload sera configuré via services.yaml
    }

    /**
     * POST /api/songs
     * 
     * Réception des métadonnées + fichier MP3 depuis Java (ApiUploader).
     * Attend un multipart/form-data avec :
     *   - fichier: le fichier MP3 binaire
     *   - title: titre de la chanson
     *   - artist: artiste
     *   - genre: genre musical
     *   - duration: durée en secondes
     */
    #[Route('/songs', name: 'api_songs_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Récupérer le fichier uploadé
        $file = $request->files->get('fichier');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier MP3 reçu'], Response::HTTP_BAD_REQUEST);
        }

        // Créer le répertoire d'upload si nécessaire
        $uploadDir = $this->getParameter('upload_directory');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalFilename = $file->getClientOriginalName();

        // Vérification si la musique existe déjà pour éviter les doublons
        $existingSong = $this->songRepository->findOneBy(['filename' => $originalFilename]);
        if ($existingSong) {
            return $this->json([
                'message' => 'Cette chanson existe déjà',
                'song' => $existingSong->toArray()
            ], Response::HTTP_OK); // 200 OK permet à Java de supprimer son fichier local
        }

        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_.-] remove; Lower()',
            pathinfo($originalFilename, PATHINFO_FILENAME)
        );
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Déplacer le fichier vers le répertoire d'upload
        try {
            $file->move($uploadDir, $newFilename);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du stockage du fichier',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Créer l'entité Song avec les métadonnées
        $song = new Song();
        $song->setFilename($originalFilename);
        $song->setTitle($request->request->get('title', ''));
        
        $artistString = trim($request->request->get('artist', ''));
        if ($artistString) {
            // Séparation des artistes s'il y a des 'feat', 'x', ',', '&', '/'
            $artistNames = preg_split('/(?i)(\s+x\s+|\s+feat\.?\s+|\s+ft\.?\s+|,|&|\/)/', $artistString);
            foreach ($artistNames as $aName) {
                $aName = rtrim(trim($aName), '.');
                
                if ($aName) {
                    // Recherche insensible à la casse dans PostgreSQL
                    $artiste = $this->em->getRepository(Artiste::class)->createQueryBuilder('a')
                        ->where('LOWER(a.nom) = LOWER(:nom)')
                        ->setParameter('nom', $aName)
                        ->getQuery()
                        ->getOneOrNullResult();
                        
                    if (!$artiste) {
                        $artiste = new Artiste();
                        $artiste->setNom($aName);
                        $this->em->persist($artiste);
                    }
                    $song->addArtiste($artiste);
                }
            }
        }

        $genreName = trim($request->request->get('genre', ''));
        if ($genreName) {
            $genre = $this->em->getRepository(Genre::class)->findOneBy(['nom' => $genreName]);
            if (!$genre) {
                $genre = new Genre();
                $genre->setNom($genreName);
                $this->em->persist($genre);
            }
            $song->addGenre($genre);
        }

        $albumName = trim($request->request->get('album', ''));
        if ($albumName) {
            $album = $this->em->getRepository(\App\Entity\Album::class)->createQueryBuilder('a')
                ->where('LOWER(a.nom) = LOWER(:nom)')
                ->setParameter('nom', $albumName)
                ->getQuery()
                ->getOneOrNullResult();
                
            if (!$album) {
                $album = new \App\Entity\Album();
                $album->setNom($albumName);
                $this->em->persist($album);
            }
            $song->addAlbum($album);
        }

        $song->setDuration((int) $request->request->get('duration', 0));
        $song->setFilePath($newFilename);

        // Persister en base de données PostgreSQL
        $this->em->persist($song);
        $this->em->flush();

        return $this->json([
            'message' => 'Chanson reçue et enregistrée avec succès',
            'song' => $song->toArray()
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/songs
     * 
     * Liste toutes les chansons enregistrées en base.
     */
    #[Route('/songs', name: 'api_songs_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $songs = $this->songRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json([
            'count' => count($songs),
            'songs' => array_map(fn(Song $s) => $s->toArray(), $songs)
        ]);
    }

    /**
     * GET /api/songs/search?q=...
     * 
     * Recherche multi-critères (titre, artiste, genre, album, filename).
     */
    #[Route('/songs/search', name: 'api_songs_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (empty($query)) {
            return $this->json(['error' => 'Paramètre de recherche "q" requis'], Response::HTTP_BAD_REQUEST);
        }

        $songs = $this->songRepository->searchByCriteria($query);

        return $this->json([
            'query' => $query,
            'count' => count($songs),
            'songs' => array_map(fn(Song $s) => $s->toArray(), $songs)
        ]);
    }

    /**
     * GET /api/songs/{id}
     * 
     * Détails d'une chanson par son ID.
     */
    #[Route('/songs/{id}', name: 'api_songs_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $song = $this->songRepository->find($id);

        if (!$song) {
            return $this->json(['error' => 'Chanson non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['song' => $song->toArray()]);
    }

    /**
     * PUT /api/songs/{id}
     * 
     * Modifier les métadonnées d'une chanson (compléter les infos manquantes).
     */
    #[Route('/songs/{id}', name: 'api_songs_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $song = $this->songRepository->find($id);

        if (!$song) {
            return $this->json(['error' => 'Chanson non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) $song->setTitle($data['title']);
        
        if (isset($data['artist'])) {
            $artistString = trim($data['artist']);
            if ($artistString) {
                // Vider les artistes actuels
                foreach ($song->getArtistes() as $a) {
                    $song->removeArtiste($a);
                }
                
                // Séparer les nouveaux artistes (incluant '/')
                $artistNames = preg_split('/(?i)(\s+x\s+|\s+feat\.?\s+|\s+ft\.?\s+|,|&|\/)/', $artistString);
                foreach ($artistNames as $aName) {
                    // Enlever les espaces et les points à la fin
                    $aName = rtrim(trim($aName), '.');
                    
                    if ($aName) {
                        $artiste = $this->em->getRepository(Artiste::class)->createQueryBuilder('a')
                            ->where('LOWER(a.nom) = LOWER(:nom)')
                            ->setParameter('nom', $aName)
                            ->getQuery()
                            ->getOneOrNullResult();
                            
                        if (!$artiste) {
                            $artiste = new Artiste();
                            $artiste->setNom($aName);
                            $this->em->persist($artiste);
                        }
                        $song->addArtiste($artiste);
                    }
                }
            }
        }
        
        if (isset($data['genre'])) {
            $genreName = trim($data['genre']);
            if ($genreName) {
                $genre = $this->em->getRepository(Genre::class)->findOneBy(['nom' => $genreName]);
                if (!$genre) {
                    $genre = new Genre();
                    $genre->setNom($genreName);
                    $this->em->persist($genre);
                }
                foreach ($song->getGenres() as $g) {
                    $song->removeGenre($g);
                }
                $song->addGenre($genre);
            }
        }
        
        if (isset($data['album'])) $song->setAlbum($data['album']);
        if (isset($data['duration'])) $song->setDuration((int) $data['duration']);

        $this->em->flush();

        return $this->json([
            'message' => 'Chanson mise à jour',
            'song' => $song->toArray()
        ]);
    }

    /**
     * DELETE /api/songs/{id}
     * 
     * Supprimer une chanson et son fichier associé.
     */
    #[Route('/songs/{id}', name: 'api_songs_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $song = $this->songRepository->find($id);

        if (!$song) {
            return $this->json(['error' => 'Chanson non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Supprimer le fichier physique
        $uploadDir = $this->getParameter('upload_directory');
        $filePath = $uploadDir . '/' . $song->getFilePath();
        if ($song->getFilePath() && file_exists($filePath)) {
            unlink($filePath);
        }

        $this->em->remove($song);
        $this->em->flush();

        return $this->json(['message' => 'Chanson supprimée']);
    }

    /**
     * GET /api/songs/{id}/download
     * 
     * Télécharger le fichier MP3 binaire.
     */
    #[Route('/songs/{id}/download', name: 'api_songs_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(int $id): Response
    {
        $song = $this->songRepository->find($id);

        if (!$song) {
            return $this->json(['error' => 'Chanson non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $uploadDir = $this->getParameter('upload_directory');
        $filePath = $uploadDir . '/' . $song->getFilePath();

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Fichier MP3 introuvable sur le serveur'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $song->getFilename()
        );

        return $response;
    }
}
