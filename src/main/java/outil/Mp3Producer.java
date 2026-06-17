package outil;
import java.io.File;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import com.google.gson.Gson;
import com.rabbitmq.client.Channel;
import com.rabbitmq.client.Connection;

import message.RabbitMQConfig;

public class Mp3Producer {

    private static final Gson gson = new Gson();

    public static void envoyerFichiers(List<File> fichiers) throws Exception {
        try (Connection conn = RabbitMQConfig.getConnection();
             Channel channel = conn.createChannel()) {

            channel.queueDeclare(RabbitMQConfig.QUEUE_MP3_FOUND, true, false, false, null);

            for (File fichier : fichiers) {
                Map<String, String> message = new HashMap<>();
                message.put("filePath", fichier.getAbsolutePath());
                message.put("fileName", fichier.getName());

                String json = gson.toJson(message);
                channel.basicPublish("", RabbitMQConfig.QUEUE_MP3_FOUND, null, json.getBytes());

                System.out.println("Envoyé dans queue : " + fichier.getName());
            }
        }
    }
}