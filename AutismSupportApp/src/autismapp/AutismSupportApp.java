package autismapp;

import javax.swing.SwingUtilities;
import javax.swing.UIManager;

// Main class - starts the Autism Support Learning App
public class AutismSupportApp {

    public static void main(String[] args) {
        try {
            UIManager.setLookAndFeel(UIManager.getCrossPlatformLookAndFeelClassName());
        } catch (Exception e) {
            // keep default if needed
        }

        SwingUtilities.invokeLater(new Runnable() {
            public void run() {
                MainFrame frame = new MainFrame();
                frame.setVisible(true);
            }
        });
    }
}
