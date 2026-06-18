package outil;

import java.io.File;
import java.io.InputStream;
import java.util.HashMap;
import java.util.Map;
import java.util.Properties;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import com.rabbitmq.client.Channel;
import com.rabbitmq.client.Connection;
import com.rabbitmq.client.DeliverCallback;

import message.RabbitMQConfig;

public class UploadConsumer {
    private static final Gson gson = new Gson();
    private static Uploader uploader;

    public static void demarrer() throws Exception {
        // Charger la configuration
        Properties props = new Properties();
        try (InputStream in = UploadConsumer.class.getClassLoader().getResourceAsStream("config.properties")) {
            if (in != null) props.load(in);
        }
        
        // Choix de l'uploader
        String mode = props.getProperty("upload.mode", "api");
        if ("ftp".equalsIgnoreCase(mode)) {
            uploader = new FtpUploader(props);
        } else {
            uploader = new ApiUploader(props);
        }

        Connection conn = RabbitMQConfig.getConnection();
        Channel channel = conn.createChannel();
        channel.queueDeclare(RabbitMQConfig.QUEUE_MP3_EXTRACTED, true, false, false, null);
        channel.queueDeclare(RabbitMQConfig.QUEUE_MP3_UPLOADED, true, false, false, null);
        channel.basicQos(1);

        System.out.println(" Consumer (Upload via " + mode.toUpperCase() + ") en attente...");

        DeliverCallback callback = (tag, delivery) -> {
            String json = new String(delivery.getBody());
            // Désérialisation complexe car il y a un objet metadata imbriqué
            Map<String, Object> message = gson.fromJson(json, new TypeToken<Map<String, Object>>(){}.getType());
            
            String filePath = (String) message.get("filePath");
            File file = new File(filePath);
            
            // Re-sérialiser / désérialiser juste l'objet metadata pour retrouver la classe Mp3Metadata
            String metaJson = gson.toJson(message.get("metadata"));
            Mp3Metadata metadata = gson.fromJson(metaJson, Mp3Metadata.class);

            try {
                boolean success = uploader.upload(file, metadata);
                if (success) {
                    LogWriter.writeLog("UPLOAD_SUCCESS", file.getName(), "Mode: " + mode.toUpperCase());
                    
                    // On transmet à la queue de suppression
                    Map<String, String> nextMessage = new HashMap<>();
                    nextMessage.put("filePath", filePath);
                    channel.basicPublish("", RabbitMQConfig.QUEUE_MP3_UPLOADED, null, gson.toJson(nextMessage).getBytes());
                } else {
                    LogWriter.writeLog("UPLOAD_ERROR", file.getName(), "Échec du mode: " + mode.toUpperCase());
                }
            } catch (Exception e) {
                LogWriter.writeLog("UPLOAD_EXCEPTION", file.getName(), e.getMessage());
            }

            channel.basicAck(delivery.getEnvelope().getDeliveryTag(), false);
        };

        channel.basicConsume(RabbitMQConfig.QUEUE_MP3_EXTRACTED, false, callback, tag -> {});
    }
}
