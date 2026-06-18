<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Repository\ArtisteRepository;
use App\Repository\GenreRepository;
use App\Repository\PlaylistRepository;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/playlist')]
class PlaylistController extends AbstractController
{
    #[Route('/', name: 'app_playlist_index', methods: ['GET'])]
    public function index(PlaylistRepository $playlistRepo): Response
    {
        // Filtrer les playlists par utilisateur connecté
        $user = $this->getUser();
        $playlists = $playlistRepo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('playlist/index.html.twig', [
            'playlists' => $playlists
        ]);
    }

    #[Route('/create', name: 'app_playlist_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        ArtisteRepository $artisteRepo,
        GenreRepository $genreRepo,
        SongRepository $songRepo,
        RequestStack $requestStack
    ): Response {
        if ($request->isMethod('POST')) {
            $durationMinutes = (int) $request->request->get('duration', 60);
            $targetDurationSeconds = $durationMinutes * 60;
            
            $selectedArtistes = $request->request->all('artistes'); // tableau d'IDs
            $selectedGenres = $request->request->all('genres'); // tableau d'IDs

            // Trouver toutes les chansons qui correspondent
            $allSongs = $songRepo->findAll();
            $matchingSongs = [];
            
            $hasSelectedArtistes = !empty($selectedArtistes);
            $hasSelectedGenres = !empty($selectedGenres);

            foreach ($allSongs as $song) {
                $matchArtiste = false;
                if ($hasSelectedArtistes) {
                    foreach ($song->getArtistes() as $artiste) {
                        if (in_array($artiste->getId(), $selectedArtistes)) {
                            $matchArtiste = true;
                            break;
                        }
                    }
                } else {
                    $matchArtiste = true;
                }

                $matchGenre = false;
                if ($hasSelectedGenres) {
                    foreach ($song->getGenres() as $genre) {
                        if (in_array($genre->getId(), $selectedGenres)) {
                            $matchGenre = true;
                            break;
                        }
                    }
                } else {
                    $matchGenre = true;
                }

                $match = $matchArtiste && $matchGenre;

                // S'il n'y a aucun critère sélectionné, on prend tout
                if (!$hasSelectedArtistes && !$hasSelectedGenres) {
                    $match = true;
                }

                if ($match) {
                    $matchingSongs[] = $song;
                }
            }

            // Algorithme glouton (Greedy) aléatoire
            shuffle($matchingSongs);
            $playlistSongs = [];
            $currentDuration = 0;

            foreach ($matchingSongs as $song) {
                if ($currentDuration + $song->getDuration() <= $targetDurationSeconds + (3 * 60)) { // Marge de +3 minutes tolérée
                    $playlistSongs[] = $song;
                    $currentDuration += $song->getDuration();
                }
                if ($currentDuration >= $targetDurationSeconds) {
                    break;
                }
            }

            if (empty($playlistSongs)) {
                $this->addFlash('error', 'Aucune musique ne correspond à vos critères.');
                return $this->redirectToRoute('app_playlist_create');
            }

            // Stocker les données en session pour la page de confirmation
            $playlistName = trim($request->request->get('playlist_name', ''));
            if (empty($playlistName)) {
                $playlistName = 'Playlist du ' . date('d/m/Y H:i');
            }

            $songIds = array_map(fn(Song $s) => $s->getId(), $playlistSongs);

            $session = $requestStack->getSession();
            $session->set('pending_playlist', [
                'name' => $playlistName,
                'song_ids' => $songIds,
                'total_duration' => $currentDuration,
            ]);

            return $this->redirectToRoute('app_playlist_confirm');
        }

        return $this->render('playlist/create.html.twig', [
            'artistes' => $artisteRepo->findBy([], ['nom' => 'ASC']),
            'genres' => $genreRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/confirm', name: 'app_playlist_confirm', methods: ['GET', 'POST'])]
    public function confirm(
        Request $request,
        SongRepository $songRepo,
        EntityManagerInterface $em,
        RequestStack $requestStack
    ): Response {
        $session = $requestStack->getSession();
        $pendingData = $session->get('pending_playlist');

        if (!$pendingData) {
            $this->addFlash('error', 'Aucune playlist en attente de confirmation.');
            return $this->redirectToRoute('app_playlist_create');
        }

        // Charger les songs depuis les IDs
        $songs = $songRepo->findBy(['id' => $pendingData['song_ids']]);

        if ($request->isMethod('POST')) {
            // Récupérer le nom modifié
            $playlistName = trim($request->request->get('playlist_name', $pendingData['name']));
            if (empty($playlistName)) {
                $playlistName = $pendingData['name'];
            }

            // Récupérer les IDs des songs que l'utilisateur a gardés (cochés)
            $keptSongIds = $request->request->all('kept_songs'); // tableau d'IDs
            $keptSongs = $songRepo->findBy(['id' => $keptSongIds]);

            if (empty($keptSongs)) {
                $this->addFlash('error', 'Vous devez garder au moins une musique dans la playlist.');
                return $this->redirectToRoute('app_playlist_confirm');
            }

            // Recalculer la durée totale
            $totalDuration = 0;
            foreach ($keptSongs as $song) {
                $totalDuration += $song->getDuration();
            }

            // Créer et persister la playlist
            $playlist = new Playlist();
            $playlist->setNom($playlistName);
            $playlist->setDureeTotale($totalDuration);
            $playlist->setUser($this->getUser());

            foreach ($keptSongs as $song) {
                $playlist->addSong($song);
            }

            $em->persist($playlist);
            $em->flush();

            // Nettoyer la session
            $session->remove('pending_playlist');

            $this->addFlash('success', 'Playlist "' . $playlistName . '" créée avec succès !');
            return $this->redirectToRoute('app_playlist_index');
        }

        return $this->render('playlist/confirm.html.twig', [
            'playlistName' => $pendingData['name'],
            'songs' => $songs,
            'totalDuration' => $pendingData['total_duration'],
        ]);
    }

    #[Route('/{id}', name: 'app_playlist_show', methods: ['GET'])]
    public function show(Playlist $playlist): Response
    {
        // Vérifier que la playlist appartient à l'utilisateur connecté
        if ($playlist->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette playlist ne vous appartient pas.');
        }

        return $this->render('playlist/show.html.twig', [
            'playlist' => $playlist,
        ]);
    }

    #[Route('/{id}/download', name: 'app_playlist_download', methods: ['GET'])]
    public function downloadZip(Playlist $playlist, EntityManagerInterface $em): Response
    {
        // Vérifier que la playlist appartient à l'utilisateur connecté
        if ($playlist->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette playlist ne vous appartient pas.');
        }

        $zipFile = tempnam(sys_get_temp_dir(), 'playlist_') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Impossible de créer le fichier ZIP.');
        }

        $uploadDir = $this->getParameter('upload_directory'); // On s'assurera que c'est bien configuré
        $added = false;

        foreach ($playlist->getSongs() as $song) {
            $filePath = $uploadDir . '/' . $song->getFilePath();
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $song->getFilename());
                $added = true;
            }
        }

        $zip->close();

        if (!$added) {
            throw new \Exception('Aucun fichier physique trouvé pour cette playlist.');
        }

        // Marquer la playlist comme téléchargée
        $playlist->setDownloaded(true);
        $em->flush();

        $response = new StreamedResponse(function () use ($zipFile) {
            readfile($zipFile);
            @unlink($zipFile);
        });

        $safeName = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_.-] remove; Lower()',
            $playlist->getNom()
        );
        if (empty($safeName)) {
            $safeName = 'playlist_' . $playlist->getId();
        }

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $safeName . '.zip'
        );

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', filesize($zipFile));

        return $response;
    }
}
