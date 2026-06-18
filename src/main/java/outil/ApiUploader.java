package outil;

import org.apache.hc.client5.http.classic.methods.HttpPost;
import org.apache.hc.client5.http.entity.mime.MultipartEntityBuilder;
import org.apache.hc.client5.http.impl.classic.CloseableHttpClient;
import org.apache.hc.client5.http.impl.classic.HttpClients;
import org.apache.hc.core5.http.ContentType;

import java.io.File;
import java.util.Properties;

public class ApiUploader implements Uploader {
    private final String apiUrl;

    public ApiUploader(Properties props) {
        this.apiUrl = props.getProperty("api.url");
    }

    @Override
    public boolean upload(File mp3, Mp3Metadata metadata) {
        try (CloseableHttpClient client = HttpClients.createDefault()) {
            HttpPost post = new HttpPost(apiUrl);

            var entity = MultipartEntityBuilder.create()
                .addBinaryBody("fichier", mp3, ContentType.DEFAULT_BINARY, mp3.getName())
                .addTextBody("title", metadata.getTitle() != null ? metadata.getTitle() : "", ContentType.TEXT_PLAIN)
                .addTextBody("artist", metadata.getArtist() != null ? metadata.getArtist() : "", ContentType.TEXT_PLAIN)
                .addTextBody("genre", metadata.getGenre() != null ? metadata.getGenre() : "", ContentType.TEXT_PLAIN)
                .addTextBody("duration", String.valueOf(metadata.getDuration()), ContentType.TEXT_PLAIN)
                .build();

            post.setEntity(entity);
            try (var response = client.execute(post)) {
                int statusCode = response.getCode();
                return statusCode >= 200 && statusCode < 300;
            }
        } catch (Exception e) {
            System.err.println("Erreur API : " + e.getMessage());
            return false;
        }
    }
}
