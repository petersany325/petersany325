package autismapp;

import java.awt.Image;
import java.io.File;
import java.net.URL;
import javax.swing.ImageIcon;

// Loads bundled character images for welcome screen and emotion levels
public final class ImageAssets {

    private ImageAssets() {
    }

    public static ImageIcon load(String fileName) {
        if (fileName == null || fileName.trim().isEmpty()) {
            return null;
        }

        URL resource = ImageAssets.class.getResource("/autismapp/images/" + fileName);
        if (resource == null) {
            resource = ImageAssets.class.getResource("images/" + fileName);
        }
        if (resource != null) {
            return new ImageIcon(resource);
        }

        String[] candidates = {
            "src/autismapp/images/" + fileName,
            "AutismSupportApp/src/autismapp/images/" + fileName,
            "images/" + fileName
        };
        for (String path : candidates) {
            File file = new File(path);
            if (file.exists()) {
                return new ImageIcon(file.getAbsolutePath());
            }
        }
        return null;
    }

    public static ImageIcon loadScaled(String fileName, int width, int height) {
        ImageIcon icon = load(fileName);
        if (icon == null || icon.getIconWidth() <= 0) {
            return null;
        }
        Image scaled = icon.getImage().getScaledInstance(width, height, Image.SCALE_SMOOTH);
        return new ImageIcon(scaled);
    }

    public static String emotionFile(String emotion) {
        if (emotion == null) {
            return null;
        }
        String key = emotion.trim().toLowerCase();
        if (key.equals("happy") || key.equals("excited")) {
            return "dino-happy.png";
        }
        if (key.equals("sad") || key.equals("worried") || key.equals("scared")) {
            return "dino-sad.png";
        }
        if (key.equals("angry")) {
            return "dino-angry.png";
        }
        if (key.equals("proud")) {
            return "dino-proud.png";
        }
        return null;
    }
}
