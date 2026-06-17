package outil;

import java.nio.file.Paths;
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
        channel.basicQos(1); 

        System.out.println(" Consumer en attente de messages...");

        DeliverCallback callback = (tag, delivery) -> {
            String json = new String(delivery.getBody());
            
            Map<String, String> message = gson.fromJson(json,
                new TypeToken<Map<String, String>>(){}.getType());

            String filePath = message.get("filePath");
            System.out.println("\n Reçu : " + message.get("fileName"));
            Mp3Metadata metadata = MetaDataExtractor.extract(Paths.get(filePath));
            System.out.println(" " + metadata);

            channel.basicAck(delivery.getEnvelope().getDeliveryTag(), false);
        };

        channel.basicConsume(RabbitMQConfig.QUEUE_MP3_FOUND, false, callback, tag -> {});
    }
}