package outil;

import java.io.File;
import java.util.ArrayList;
import java.util.List;

public class Mp3Finder {

    public static List<File> getMp3Files(String repertoire) {

        List<File> result = new ArrayList<>();
        File folder = new File(repertoire);
        File[] files = folder.listFiles();
        if (files == null) {
            System.out.println("Dossier introuvable ou vide : " + repertoire);
            return result;
        }
        
        for (File file : files) {
            if (file.isFile() && file.getName().toLowerCase().endsWith(".mp3")) {
                result.add(file);
            }
        }
        return result;
    }
}