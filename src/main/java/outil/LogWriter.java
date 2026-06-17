package outil;

import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

public class LogWriter {
    private static final String LOG_FILE = "logs.txt";
    private static final DateTimeFormatter formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

    public static synchronized void writeLog(String action, String fileName, String details) {
        try (FileWriter fw = new FileWriter(LOG_FILE, true);
             PrintWriter pw = new PrintWriter(fw)) {
            String time = LocalDateTime.now().format(formatter);
            pw.printf("[%s] %s | Fichier: %s | Infos: %s%n", time, action, fileName, details);
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}
