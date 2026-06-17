import java.io.File;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

import outil.Mp3Consumer;
import outil.UploadConsumer;
import outil.DeleteConsumer;
import outil.Mp3Finder;
import outil.Mp3Producer;

public class Main {

    public static void main(String[] args) {
        // Lancer le consommateur 1 : Extraction
        new Thread(() -> { try { Mp3Consumer.demarrer(); } catch (Exception e) { e.printStackTrace(); } }).start();
        
        // Lancer le consommateur 2 : Upload
        new Thread(() -> { try { UploadConsumer.demarrer(); } catch (Exception e) { e.printStackTrace(); } }).start();
        
        // Lancer le consommateur 3 : Suppression
        new Thread(() -> { try { DeleteConsumer.demarrer(); } catch (Exception e) { e.printStackTrace(); } }).start();

        // Le Producer (Etape 1) tourne toutes les 5 minutes
        ScheduledExecutorService scheduler = Executors.newSingleThreadScheduledExecutor();
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
