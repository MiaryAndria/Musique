// package outil;

// import org.apache.hc.client5.http.classic.methods.HttpPost;
// import org.apache.hc.client5.http.entity.mime.MultipartEntityBuilder;
// import org.apache.hc.client5.http.impl.classic.CloseableHttpClient;
// import org.apache.hc.client5.http.impl.classic.HttpClients;
// import org.apache.hc.core5.http.ContentType;

// import java.io.File;

// public class ApiSender {

//     private static final String API_URL = "https://ton-api.com/upload";

//     public static boolean envoyerMp3(File fichier, Mp3Metadata metadata) {
//         try (CloseableHttpClient client = HttpClients.createDefault()) {

//             HttpPost post = new HttpPost(API_URL);

//             var entity = MultipartEntityBuilder.create()
//                 .addBinaryBody("fichier", fichier, ContentType.DEFAULT_BINARY, fichier.getName())
//                 .addTextBody("title",    metadata.getTitle(),    ContentType.TEXT_PLAIN)
//                 .addTextBody("artist",   metadata.getArtist(),   ContentType.TEXT_PLAIN)
//                 .addTextBody("genre",    metadata.getGenre(),    ContentType.TEXT_PLAIN)
//                 .addTextBody("duration", String.valueOf(metadata.getDuration()), ContentType.TEXT_PLAIN)
//                 .build();

//             post.setEntity(entity);

//             var response = client.execute(post);
//             int statusCode = response.getCode();

//             System.out.println("→ Envoi " + fichier.getName() + " : HTTP " + statusCode);

//             return statusCode == 200 || statusCode == 201; 

//         } catch (Exception e) {
//             System.err.println(" Erreur envoi " + fichier.getName() + " : " + e.getMessage());
//             return false;
//         }
//     }
// }