package outil;

import java.nio.file.Path;

import com.mpatric.mp3agic.ID3v1;
import com.mpatric.mp3agic.ID3v2;
import com.mpatric.mp3agic.Mp3File;

public class MetaDataExtractor {

    public static Mp3Metadata extract(Path fichier) {
        String title = null;
        String artist = null;
        String album = null;
        String genre = null;
        long duration = 0;

        try {
            Mp3File mp3 = new Mp3File(fichier.toString());

            if (mp3.hasId3v2Tag()) {
                ID3v2 tag = mp3.getId3v2Tag();
                title = tag.getTitle();
                artist = tag.getArtist();
                album = tag.getAlbum();
                genre = tag.getGenreDescription();
            } else if (mp3.hasId3v1Tag()) {
                ID3v1 tag = mp3.getId3v1Tag();
                title = tag.getTitle();
                artist = tag.getArtist();
                album = tag.getAlbum();
                genre = tag.getGenreDescription();
            }
            duration = mp3.getLengthInSeconds();
        } catch (Exception e) {
            System.err.println("Erreur lecture tags (peut-être pas un MP3 ou fichier corrompu) : " + fichier);
            try {
                javax.sound.sampled.AudioFileFormat fileFormat = javax.sound.sampled.AudioSystem.getAudioFileFormat(fichier.toFile());
                if (fileFormat != null) {
                    java.util.Map<String, Object> properties = fileFormat.properties();
                    if (properties != null && properties.containsKey("duration")) {
                        duration = (long) ((Long) properties.get("duration") / 1000000);
                    } else {
                        long frames = fileFormat.getFrameLength();
                        float frameRate = fileFormat.getFormat().getFrameRate();
                        if (frames != -1 && frameRate > 0) {
                            duration = (long) (frames / frameRate);
                        }
                    }
                }
            } catch (Exception ex) {
                System.err.println("Erreur lecture WAV : " + ex.getMessage());
            }
        }

        if (title == null || title.isEmpty()) {
            String nameWithoutExt = fichier.getFileName().toString().replaceAll("(?i)\\.(mp3|wav)$", "");
            

            if (nameWithoutExt.contains("-")) {
                title = nameWithoutExt.substring(nameWithoutExt.indexOf("-") + 1);
                if (artist == null || artist.isEmpty()) {
                    artist = nameWithoutExt.substring(0, nameWithoutExt.indexOf("-")).trim();
                }
            } else {
                title = nameWithoutExt;
            }

            title = title.replaceAll("(?i)\\s*(?:\\(.*?\\)|\\[.*?\\])", "").trim();
        }

        if (artist == null || artist.isEmpty()) {
            artist = "Unknown Artist";
        }

        if (album == null || album.isEmpty()) {
            album = "Unknown Album";
        }

        if (genre == null || genre.isEmpty()) {
            genre = "Unknown Genre";
        }

        return new Mp3Metadata(
                fichier.getFileName().toString(),
                title,
                artist,
                album,
                genre,
                duration
        );
    }
}