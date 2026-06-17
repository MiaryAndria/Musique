package outil;
import java.io.File;

public interface Uploader {
    boolean upload(File mp3, Mp3Metadata metadata) throws Exception;
}
