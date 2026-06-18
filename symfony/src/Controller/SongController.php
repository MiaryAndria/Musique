<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Artiste;
use App\Entity\Genre;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/songs')]
class SongController extends AbstractController
{
    #[Route('/', name: 'app_song_index', methods: ['GET'])]
    public function index(
        Request $request, 
        SongRepository $songRepository,
        EntityManagerInterface $em
    ): Response {
        $title = $request->query->get('title');
        $artisteId = $request->query->get('artiste');
        $albumId = $request->query->get('album');
        $genreId = $request->query->get('genre');
        
        $artistes = $em->getRepository(Artiste::class)->findBy([], ['nom' => 'ASC']);
        $albums = $em->getRepository(\App\Entity\Album::class)->findBy([], ['nom' => 'ASC']);
        $genres = $em->getRepository(Genre::class)->findBy([], ['nom' => 'ASC']);

        if ($title || $artisteId || $albumId || $genreId) {
            $songs = $songRepository->filterByCriteria(
                $title, 
                $artisteId ? (int)$artisteId : null, 
                $albumId ? (int)$albumId : null, 
                $genreId ? (int)$genreId : null
            );
        } else {
            $songs = $songRepository->findBy([], ['createdAt' => 'DESC']);
        }
        
        return $this->render('song/index.html.twig', [
            'songs' => $songs,
            'searchTitle' => $title,
            'searchArtiste' => $artisteId,
            'searchAlbum' => $albumId,
            'searchGenre' => $genreId,
            'artistes' => $artistes,
            'albums' => $albums,
            'genres' => $genres,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_song_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Song $song, EntityManagerInterface $entityManager): Response
    {
        // On n'utilise pas de FormType complexe pour rester simple et direct avec un HTML form,
        // ou on peut utiliser un formulaire Symfony. Ici, un traitement manuel ou via un formulaire Symfony.
        
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            
            if ($title !== null) {
                $song->setTitle($title);
            }
            
            // --- Modification des albums ---
            $albumsString = $request->request->get('album');
            if ($albumsString !== null) {
                // On vide les albums actuels
                foreach ($song->getAlbums() as $al) {
                    $song->removeAlbum($al);
                }
                
                if (trim($albumsString) !== '') {
                    $albumNames = array_map('trim', explode(',', $albumsString));
                    foreach ($albumNames as $alName) {
                        if ($alName) {
                            $album = $entityManager->getRepository(\App\Entity\Album::class)->createQueryBuilder('a')
                                ->where('LOWER(a.nom) = LOWER(:nom)')
                                ->setParameter('nom', $alName)
                                ->getQuery()
                                ->getOneOrNullResult();
                                
                            if (!$album) {
                                $album = new \App\Entity\Album();
                                $album->setNom($alName);
                                $entityManager->persist($album);
                            }
                            $song->addAlbum($album);
                        }
                    }
                }
            }
            
            // --- Modification des artistes ---
            $artistesString = $request->request->get('artistes');
            if ($artistesString !== null) {
                // On vide les artistes actuels
                foreach ($song->getArtistes() as $a) {
                    $song->removeArtiste($a);
                }
                
                // On ajoute les nouveaux
                if (trim($artistesString) !== '') {
                    $artistNames = preg_split('/(?i)(\s+x\s+|\s+feat\.?\s+|\s+ft\.?\s+|,|&|\/)/', $artistesString);
                    foreach ($artistNames as $aName) {
                        $aName = rtrim(trim($aName), '.');
                        if ($aName) {
                            $artiste = $entityManager->getRepository(Artiste::class)->createQueryBuilder('a')
                                ->where('LOWER(a.nom) = LOWER(:nom)')
                                ->setParameter('nom', $aName)
                                ->getQuery()
                                ->getOneOrNullResult();
                            
                            if (!$artiste) {
                                $artiste = new Artiste();
                                $artiste->setNom($aName);
                                $entityManager->persist($artiste);
                            }
                            $song->addArtiste($artiste);
                        }
                    }
                }
            }

            // --- Modification des genres ---
            $genresString = $request->request->get('genres');
            if ($genresString !== null) {
                // On vide les genres actuels
                foreach ($song->getGenres() as $g) {
                    $song->removeGenre($g);
                }
                
                if (trim($genresString) !== '') {
                    // Séparation par virgule
                    $genreNames = array_map('trim', explode(',', $genresString));
                    foreach ($genreNames as $gName) {
                        if ($gName) {
                            $genre = $entityManager->getRepository(Genre::class)->createQueryBuilder('g')
                                ->where('LOWER(g.nom) = LOWER(:nom)')
                                ->setParameter('nom', $gName)
                                ->getQuery()
                                ->getOneOrNullResult();
                                
                            if (!$genre) {
                                $genre = new Genre();
                                $genre->setNom($gName);
                                $entityManager->persist($genre);
                            }
                            $song->addGenre($genre);
                        }
                    }
                }
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('app_song_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('song/edit.html.twig', [
            'song' => $song,
        ]);
    }

    #[Route('/{id}', name: 'app_song_delete', methods: ['POST'])]
    public function delete(Request $request, Song $song, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$song->getId(), $request->request->get('_token'))) {
            $entityManager->remove($song);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_song_index', [], Response::HTTP_SEE_OTHER);
    }
}
