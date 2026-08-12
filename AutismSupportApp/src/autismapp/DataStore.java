package autismapp;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;

// Saves and loads child data from a text file
public class DataStore {

    public static final String FILE_NAME = "ChildData.txt";

    public static File resolveFile() {
        File local = new File(FILE_NAME);
        if (local.exists()) {
            return local;
        }
        File inData = new File("data", FILE_NAME);
        if (inData.exists()) {
            return inData;
        }
        return local;
    }

    public static void save(Guardian guardian, ChildProgress progress, Awards awards) throws IOException {
        BufferedWriter writer = new BufferedWriter(new FileWriter(resolveFile()));
        writer.write("GUARDIAN:" + guardian.toString());
        writer.newLine();
        writer.write("PROGRESS:" + progress.toStorageString());
        writer.newLine();

        String cards = "";
        for (int i = 0; i < awards.getCollectCards().size(); i++) {
            if (i > 0) {
                cards = cards + "|";
            }
            cards = cards + awards.getCollectCards().get(i);
        }
        writer.write("AWARDS:" + cards);
        writer.newLine();
        writer.close();
    }

    public static boolean exists() {
        return resolveFile().exists();
    }

    public static String[] loadRaw() throws IOException {
        File file = resolveFile();
        if (!file.exists()) {
            throw new IOException("No saved data found.");
        }

        String guardianLine = "";
        String progressLine = "";
        String awardsLine = "";

        BufferedReader reader = new BufferedReader(new FileReader(file));
        String line = reader.readLine();
        while (line != null) {
            if (line.startsWith("GUARDIAN:")) {
                guardianLine = line.substring(9);
            } else if (line.startsWith("PROGRESS:")) {
                progressLine = line.substring(9);
            } else if (line.startsWith("AWARDS:")) {
                awardsLine = line.substring(7);
            }
            line = reader.readLine();
        }
        reader.close();

        return new String[]{guardianLine, progressLine, awardsLine};
    }
}
