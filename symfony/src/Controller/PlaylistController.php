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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/playlist')]
class PlaylistController extends AbstractController
{
    #[Route('/', name: 'app_playlist_index', methods: ['GET'])]
    public function index(PlaylistRepository $playlistRepo): Response
    {
        return $this->render('playlist/index.html.twig', [
            'playlists' => $playlistRepo->findBy([], ['createdAt' => 'DESC'])
        ]);
    }

    #[Route('/create', name: 'app_playlist_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        ArtisteRepository $artisteRepo,
        GenreRepository $genreRepo,
        SongRepository $songRepo,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $durationMinutes = (int) $request->request->get('duration', 60);
            $targetDurationSeconds = $durationMinutes * 60;
            
            $selectedArtistes = $request->request->all('artistes'); // tableau d'IDs
            $selectedGenres = $request->request->all('genres'); // tableau d'IDs

            // Trouver toutes les chansons qui correspondent
            $allSongs = $songRepo->findAll();
            $matchingSongs = [];

            foreach ($allSongs as $song) {
                $match = false;
                // Check artistes
                foreach ($song->getArtistes() as $artiste) {
                    if (in_array($artiste->getId(), $selectedArtistes)) {
                        $match = true;
                        break;
                    }
                }
                // Check genres
                if (!$match) {
                    foreach ($song->getGenres() as $genre) {
                        if (in_array($genre->getId(), $selectedGenres)) {
                            $match = true;
                            break;
                        }
                    }
                }

                // S'il n'y a aucun critère sélectionné, on prend tout
                if (empty($selectedArtistes) && empty($selectedGenres)) {
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

            // Création de la playlist
            $playlistName = trim($request->request->get('playlist_name', ''));
            if (empty($playlistName)) {
                $playlistName = 'Playlist du ' . date('d/m/Y H:i');
            }

            $playlist = new Playlist();
            $playlist->setNom($playlistName);
            $playlist->setDureeTotale($currentDuration);

            foreach ($playlistSongs as $song) {
                $playlist->addSong($song);
            }

            $em->persist($playlist);
            $em->flush();

            return $this->redirectToRoute('app_playlist_show', ['id' => $playlist->getId()]);
        }

        return $this->render('playlist/create.html.twig', [
            'artistes' => $artisteRepo->findBy([], ['nom' => 'ASC']),
            'genres' => $genreRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'app_playlist_show', methods: ['GET'])]
    public function show(Playlist $playlist): Response
    {
        return $this->render('playlist/show.html.twig', [
            'playlist' => $playlist,
        ]);
    }

    #[Route('/{id}/download', name: 'app_playlist_download', methods: ['GET'])]
    public function downloadZip(Playlist $playlist, EntityManagerInterface $em): Response
    {
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
