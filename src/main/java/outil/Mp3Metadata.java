package outil;

public class Mp3Metadata {

    private String fileName;
    private String title;
    private String artist;
    private String genre;
    private long duration;

    public Mp3Metadata(String fileName, String title,
            String artist, String genre,
            long duration) {
        this.fileName = fileName;
        this.title = title;
        this.artist = artist;
        this.genre = genre;
        this.duration = duration;
    }

    @Override
    public String toString() {
        return "Mp3Metadata{" +
                "fileName='" + fileName + '\'' +
                ", title='" + title + '\'' +
                ", artist='" + artist + '\'' +
                ", genre='" + genre + '\'' +
                ", duration=" + duration +
                '}';
    }

    public String getFileName() {
        return fileName;
    }

    public String getTitle() {
        return title;
    }

    public String getArtist() {
        return artist;
    }

    public String getGenre() {
        return genre;
    }

    public long getDuration() {
        return duration;
    }
}