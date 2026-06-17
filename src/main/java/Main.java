import java.io.File;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

import outil.Mp3Consumer;
import outil.Mp3Finder;
import outil.Mp3Producer;

public class Main {

    public static void main(String[] args) {
        new Thread(() -> {
            try {
                Mp3Consumer.demarrer();
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();

        ScheduledExecutorService scheduler =
                Executors.newSingleThreadScheduledExecutor();
        scheduler.scheduleAtFixedRate(Main::scanEtEnvoyer, 0, 5, TimeUnit.MINUTES);
    }

    private static void scanEtEnvoyer() {
        try {
            List<File> fichiers = Mp3Finder.getMp3Files("./musique");
            System.out.println("\n=== SCAN : " + fichiers.size() + " fichiers ===");
            if (!fichiers.isEmpty()) {
                Mp3Producer.envoyerFichiers(fichiers);
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}