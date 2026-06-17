package message;

import com.rabbitmq.client.Connection;
import com.rabbitmq.client.ConnectionFactory;

public class RabbitMQConfig {
    public static final String QUEUE_MP3_FOUND = "queue_mp3_found";
    public static final String QUEUE_MP3_EXTRACTED = "queue_mp3_extracted";
    public static final String QUEUE_MP3_UPLOADED = "queue_mp3_uploaded";

    public static Connection getConnection() throws Exception {
        ConnectionFactory factory = new ConnectionFactory();
        factory.setHost("localhost");
        factory.setUsername("guest");
        factory.setPassword("guest");
        return factory.newConnection();
    }
}
