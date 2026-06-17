package outil;

import java.nio.file.Path;

import com.mpatric.mp3agic.ID3v1;
import com.mpatric.mp3agic.ID3v2;
import com.mpatric.mp3agic.Mp3File;

public class MetaDataExtractor {

    public static Mp3Metadata extract(Path fichier) {
        try {

            Mp3File mp3 = new Mp3File(fichier.toString());

            String title = null;
            String artist = null;
            String genre = null;

            if (mp3.hasId3v2Tag()) {
                ID3v2 tag = mp3.getId3v2Tag();

                title = tag.getTitle();
                artist = tag.getArtist();
                genre = tag.getGenreDescription();
            }

            else if (mp3.hasId3v1Tag()) {
                ID3v1 tag = mp3.getId3v1Tag();

                title = tag.getTitle();
                artist = tag.getArtist();
                genre = tag.getGenreDescription();
            }

            if (title == null || title.isEmpty()) {
                title = fichier.getFileName().toString()
                        .replace(".mp3", "");
            }

            if (artist == null || artist.isEmpty()) {
                artist = "Unknown Artist";
            }

            if (genre == null || genre.isEmpty()) {
                genre = "Unknown Genre";
            }

            long duration = mp3.getLengthInSeconds();

            return new Mp3Metadata(
                    fichier.getFileName().toString(),
                    title,
                    artist,
                    genre,
                    duration
            );

        } catch (Exception e) {
            System.err.println("Erreur lecture fichier : " + fichier);
            e.printStackTrace();

            return new Mp3Metadata(
                    fichier.getFileName().toString(),
                    fichier.getFileName().toString().replace(".mp3", ""),
                    "Unknown Artist",
                    "Unknown Genre",
                    0
            );
        }
    }
}