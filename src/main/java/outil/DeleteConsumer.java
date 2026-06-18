package outil;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import com.rabbitmq.client.Channel;
import com.rabbitmq.client.Connection;
import com.rabbitmq.client.DeliverCallback;
import message.RabbitMQConfig;

import java.io.File;
import java.util.Map;

public class DeleteConsumer {
    private static final Gson gson = new Gson();

    public static void demarrer() throws Exception {
        Connection conn = RabbitMQConfig.getConnection();
        Channel channel = conn.createChannel();
        channel.queueDeclare(RabbitMQConfig.QUEUE_MP3_UPLOADED, true, false, false, null);
        channel.basicQos(1);

        System.out.println(" Consumer (Delete) en attente...");

        DeliverCallback callback = (tag, delivery) -> {
            String json = new String(delivery.getBody());
            Map<String, String> message = gson.fromJson(json, new TypeToken<Map<String, String>>(){}.getType());
            
            String filePath = message.get("filePath");
            File file = new File(filePath);
            
            int retries = 3;
            boolean deleted = false;
            while (retries > 0 && file.exists()) {
                System.gc();
                if (file.delete()) {
                    deleted = true;
                    break;
                }
                try { Thread.sleep(1000); } catch (InterruptedException e) {}
                retries--;
            }
            
            if (deleted) {
                LogWriter.writeLog("DELETE_SUCCESS", file.getName(), "Fichier supprimé localement.");
                System.out.println("Fichier supprimé : " + file.getName());
            } else {
                LogWriter.writeLog("DELETE_ERROR", file.getName(), "Impossible de supprimer le fichier.");
            }

            channel.basicAck(delivery.getEnvelope().getDeliveryTag(), false);
        };

        channel.basicConsume(RabbitMQConfig.QUEUE_MP3_UPLOADED, false, callback, tag -> {});
    }
}
