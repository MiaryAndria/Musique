package outil;

import java.nio.file.Paths;
import java.util.HashMap;
import java.util.Map;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import com.rabbitmq.client.Channel;
import com.rabbitmq.client.Connection;
import com.rabbitmq.client.DeliverCallback;

import message.RabbitMQConfig;

public class Mp3Consumer {
    private static final Gson gson = new Gson();

    public static void demarrer() throws Exception {
        Connection conn = RabbitMQConfig.getConnection();
        Channel channel = conn.createChannel();

        channel.queueDeclare(RabbitMQConfig.QUEUE_MP3_FOUND, true, false, false, null);
        channel.queueDeclare(RabbitMQConfig.QUEUE_MP3_EXTRACTED, true, false, false, null); // Nouvelle queue
        channel.basicQos(1); 

        System.out.println(" Consumer (Extraction) en attente de messages...");

        DeliverCallback callback = (tag, delivery) -> {
            String json = new String(delivery.getBody());
            Map<String, String> message = gson.fromJson(json, new TypeToken<Map<String, String>>(){}.getType());

            String filePath = message.get("filePath");
            Mp3Metadata metadata = MetaDataExtractor.extract(Paths.get(filePath));
            
            // On prépare le nouveau message avec les métadonnées
            Map<String, Object> nextMessage = new HashMap<>();
            nextMessage.put("filePath", filePath);
            nextMessage.put("metadata", metadata);

            // On publie dans la queue d'upload
            String nextJson = gson.toJson(nextMessage);
            channel.basicPublish("", RabbitMQConfig.QUEUE_MP3_EXTRACTED, null, nextJson.getBytes());

            channel.basicAck(delivery.getEnvelope().getDeliveryTag(), false);
        };

        channel.basicConsume(RabbitMQConfig.QUEUE_MP3_FOUND, false, callback, tag -> {});
    }
}
