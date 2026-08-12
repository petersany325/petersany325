package autismapp;

import java.awt.BorderLayout;
import java.awt.CardLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Font;
import java.awt.Graphics;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.GridLayout;
import java.awt.Insets;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.IOException;
import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.BoxLayout;
import javax.swing.ButtonGroup;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JRadioButton;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.SwingConstants;

// Main GUI for the Autism Support Learning App
public class MainFrame extends JFrame {

    private final Color softBlue = new Color(180, 220, 245);
    private final Color softMint = new Color(210, 240, 230);
    private final Color softLavender = new Color(232, 222, 248);
    private final Color softPeach = new Color(255, 228, 214);
    private final Color softGreen = new Color(190, 230, 190);
    private final Color softPink = new Color(255, 210, 220);
    private final Color headerBlue = new Color(60, 120, 180);
    private final Color headerTeal = new Color(35, 130, 125);
    private final Color buttonBlue = new Color(50, 110, 170);
    private final Color buttonGreen = new Color(40, 140, 80);
    private final Color buttonOrange = new Color(220, 130, 40);
    private final Color buttonTeal = new Color(30, 140, 135);

    private final Font titleFont = new Font("Arial", Font.BOLD, 22);
    private final Font buttonFont = new Font("Arial", Font.BOLD, 14);
    private final Font bodyFont = new Font("Arial", Font.PLAIN, 13);
    private final Font bigBody = new Font("Arial", Font.BOLD, 16);

    private CardLayout cards;
    private JPanel cardPanel;

    private JTextField nameField;
    private JTextField surnameField;
    private JComboBox<String> typeCombo;
    private JComboBox<String> interestCombo;

    private JLabel gameQuestionLabel;
    private JLabel gameInstructionLabel;
    private JLabel gameThemeLabel;
    private JLabel gameFeedbackLabel;
    private JLabel gameCharacterLabel;
    private JLabel levelLabel;
    private JRadioButton option1;
    private JRadioButton option2;
    private JRadioButton option3;
    private ButtonGroup optionGroup;

    private JTextArea awardsArea;
    private JLabel progressSummaryLabel;
    private JLabel progressPercentLabel;
    private ProgressChartPanel chartPanel;

    private Guardian guardian;
    private ChildProgress progress;
    private Awards awards;
    private MathAward mathAward;
    private GameLevel[] levels;
    private int currentLevel;

    public MainFrame() {
        setTitle("Autism Support Learning App");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setMinimumSize(new Dimension(900, 620));
        setPreferredSize(new Dimension(920, 640));
        setLocationRelativeTo(null);

        cards = new CardLayout();
        cardPanel = new JPanel(cards);

        cardPanel.add(buildWelcome(), "WELCOME");
        cardPanel.add(buildDetails(), "DETAILS");
        cardPanel.add(buildAbout(), "ABOUT");
        cardPanel.add(buildHome(), "HOME");
        cardPanel.add(buildGame(), "GAME");
        cardPanel.add(buildAwards(), "AWARDS");
        cardPanel.add(buildProgress(), "PROGRESS");

        add(cardPanel);
        showScreen("WELCOME");
    }

    private void showScreen(String name) {
        if (name.equals("AWARDS") && awards != null) {
            awardsArea.setText(awards.toString());
        }
        if (name.equals("PROGRESS") && progress != null) {
            refreshProgressScreen();
        }
        cards.show(cardPanel, name);
    }

    private JButton makeButton(String text, Color bg) {
        JButton button = new JButton(text);
        button.setFont(buttonFont);
        button.setBackground(bg);
        button.setForeground(Color.WHITE);
        button.setOpaque(true);
        button.setContentAreaFilled(true);
        button.setBorderPainted(true);
        button.setFocusPainted(false);
        button.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(bg.darker(), 1),
                BorderFactory.createEmptyBorder(8, 16, 8, 16)));
        return button;
    }

    private JPanel header(String title, Color bg) {
        JPanel panel = new JPanel(new FlowLayout(FlowLayout.LEFT, 16, 12));
        panel.setBackground(bg);
        JLabel label = new JLabel(title);
        label.setFont(titleFont);
        label.setForeground(Color.WHITE);
        panel.add(label);
        return panel;
    }

    private JPanel buildWelcome() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softBlue);
        root.add(header("WELCOME!", headerBlue), BorderLayout.NORTH);

        JPanel center = new JPanel();
        center.setOpaque(false);
        center.setLayout(new BoxLayout(center, BoxLayout.Y_AXIS));
        center.setBorder(BorderFactory.createEmptyBorder(18, 28, 18, 28));

        JLabel mouseLabel = new JLabel();
        mouseLabel.setAlignmentX(CENTER_ALIGNMENT);
        ImageIcon mouse = ImageAssets.loadScaled("welcome-mouse.png", 150, 150);
        if (mouse != null) {
            mouseLabel.setIcon(mouse);
        } else {
            mouseLabel.setText(":)");
            mouseLabel.setFont(new Font("Arial", Font.BOLD, 42));
        }

        JLabel line1 = new JLabel("Welcome fellow guardians!", SwingConstants.CENTER);
        line1.setFont(new Font("Arial", Font.BOLD, 26));
        line1.setAlignmentX(CENTER_ALIGNMENT);

        JLabel line2 = new JLabel("A calm learning app for children on the autism spectrum.", SwingConstants.CENTER);
        line2.setFont(bodyFont);
        line2.setAlignmentX(CENTER_ALIGNMENT);

        JLabel line3 = new JLabel("Recommended for children aged 7 and older.", SwingConstants.CENTER);
        line3.setFont(bodyFont);
        line3.setAlignmentX(CENTER_ALIGNMENT);

        JLabel animalsLabel = new JLabel();
        animalsLabel.setAlignmentX(CENTER_ALIGNMENT);
        ImageIcon animals = ImageAssets.loadScaled("welcome-animals.png", 720, 250);
        if (animals != null) {
            animalsLabel.setIcon(animals);
        }

        JLabel friendsNote = new JLabel("Meet your learning friends!", SwingConstants.CENTER);
        friendsNote.setFont(new Font("Arial", Font.BOLD, 14));
        friendsNote.setForeground(headerBlue);
        friendsNote.setAlignmentX(CENTER_ALIGNMENT);

        JButton start = makeButton("Start", buttonGreen);
        start.setAlignmentX(CENTER_ALIGNMENT);
        start.setFont(new Font("Arial", Font.BOLD, 18));
        start.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                showScreen("DETAILS");
            }
        });

        center.add(mouseLabel);
        center.add(Box.createVerticalStrut(8));
        center.add(line1);
        center.add(Box.createVerticalStrut(8));
        center.add(line2);
        center.add(Box.createVerticalStrut(4));
        center.add(line3);
        center.add(Box.createVerticalStrut(12));
        center.add(friendsNote);
        center.add(Box.createVerticalStrut(6));
        center.add(animalsLabel);
        center.add(Box.createVerticalStrut(16));
        center.add(start);

        root.add(new JScrollPane(center), BorderLayout.CENTER);
        return root;
    }

    private JPanel buildDetails() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softPeach);
        root.add(header("CHILD INFORMATION", headerTeal), BorderLayout.NORTH);

        JPanel form = new JPanel();
        form.setOpaque(false);
        form.setLayout(new BoxLayout(form, BoxLayout.Y_AXIS));
        form.setBorder(BorderFactory.createEmptyBorder(20, 50, 10, 50));

        nameField = makeTextField();
        surnameField = makeTextField();

        typeCombo = new JComboBox<String>(new String[]{
            "1 - Level 1 (little support)",
            "2 - Level 2 (substantial support)",
            "3 - Level 3 (consistent assistance)"
        });
        styleCombo(typeCombo);

        interestCombo = new JComboBox<String>(new String[]{"Dinosaurs", "Math", "Flowers"});
        styleCombo(interestCombo);

        form.add(labeledField("Child Name:", nameField));
        form.add(Box.createVerticalStrut(10));
        form.add(labeledField("Child Surname:", surnameField));
        form.add(Box.createVerticalStrut(10));
        form.add(labeledField("Support Level:", typeCombo));
        form.add(Box.createVerticalStrut(10));
        form.add(labeledField("Child Interest:", interestCombo));
        form.add(Box.createVerticalStrut(14));

        JTextArea info = new JTextArea(
                "Support level guide:\n"
                + "Level 1: Needs a little support with social communication\n"
                + "Level 2: Needs substantial support with social interaction and behaviour\n"
                + "Level 3: Needs consistent assistance with communication and routines\n\n"
                + "Select the support level from the list above, then click Next.");
        info.setEditable(false);
        info.setFont(bodyFont);
        info.setBackground(softLavender);
        info.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(headerTeal, 1),
                BorderFactory.createEmptyBorder(8, 8, 8, 8)));
        info.setLineWrap(true);
        info.setWrapStyleWord(true);

        JScrollPane infoScroll = new JScrollPane(info);
        infoScroll.setAlignmentX(LEFT_ALIGNMENT);
        infoScroll.setPreferredSize(new Dimension(520, 140));
        infoScroll.setMaximumSize(new Dimension(700, 160));
        form.add(infoScroll);

        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER, 12, 10));
        buttons.setOpaque(false);
        buttons.setBackground(softMint);
        JButton next = makeButton("Next", buttonTeal);
        next.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                if (saveDetails()) {
                    showScreen("ABOUT");
                }
            }
        });
        buttons.add(next);

        // Put form in a scroll pane so nothing gets crushed on smaller screens
        JPanel formWrap = new JPanel(new BorderLayout());
        formWrap.setOpaque(false);
        formWrap.add(form, BorderLayout.NORTH);

        root.add(new JScrollPane(formWrap), BorderLayout.CENTER);
        root.add(buttons, BorderLayout.SOUTH);
        return root;
    }

    private JTextField makeTextField() {
        JTextField field = new JTextField();
        field.setFont(bodyFont);
        field.setColumns(28);
        field.setPreferredSize(new Dimension(320, 30));
        field.setMinimumSize(new Dimension(250, 30));
        field.setMaximumSize(new Dimension(700, 30));
        field.setEditable(true);
        field.setEnabled(true);
        return field;
    }

    private void styleCombo(JComboBox<String> combo) {
        combo.setFont(bodyFont);
        combo.setPreferredSize(new Dimension(320, 30));
        combo.setMinimumSize(new Dimension(250, 30));
        combo.setMaximumSize(new Dimension(700, 30));
        combo.setEditable(false);
        combo.setEnabled(true);
    }

    private JPanel labeledField(String title, java.awt.Component field) {
        JPanel row = new JPanel();
        row.setOpaque(false);
        row.setLayout(new BoxLayout(row, BoxLayout.Y_AXIS));
        row.setAlignmentX(LEFT_ALIGNMENT);

        JLabel lbl = label(title);
        lbl.setAlignmentX(LEFT_ALIGNMENT);
        row.add(lbl);
        row.add(Box.createVerticalStrut(4));

        if (field instanceof JTextField) {
            ((JTextField) field).setAlignmentX(LEFT_ALIGNMENT);
        } else if (field instanceof JComboBox) {
            ((JComboBox<?>) field).setAlignmentX(LEFT_ALIGNMENT);
        }
        row.add(field);
        row.setMaximumSize(new Dimension(700, 70));
        return row;
    }

    private boolean saveDetails() {
        String name = nameField.getText().trim();
        String surname = surnameField.getText().trim();
        if (name.equals("") || surname.equals("")) {
            JOptionPane.showMessageDialog(this, "Please enter the child's name and surname.",
                    "Missing details", JOptionPane.ERROR_MESSAGE);
            return false;
        }

        int type = typeCombo.getSelectedIndex() + 1;
        String interest = interestCombo.getSelectedItem().toString();

        guardian = new Guardian(name, surname, type, interest);
        levels = LevelFactory.createLevels(type, interest);
        progress = new ChildProgress(levels.length);
        awards = new Awards(interest);
        mathAward = new MathAward(interest);
        currentLevel = 0;
        return true;
    }

    private JPanel buildAbout() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softGreen);
        root.add(header("WHAT WE DO!", headerBlue), BorderLayout.NORTH);

        JTextArea about = new JTextArea(
                "App purpose\n\n"
                + "This app helps guardians support children on the autism spectrum.\n"
                + "Children practise emotions, social responses and simple routines through short multiple-choice levels.\n\n"
                + "Benefits:\n"
                + "- Levels match the autism type selected by the guardian\n"
                + "- Characters follow the child's interest (Dinosaurs, Math or Flowers)\n"
                + "- Collector cards reward completed levels\n"
                + "- Progress is tracked and shown on a simple chart\n"
                + "- Encouraging messages are shown after success\n\n"
                + "How it works:\n"
                + "1. Guardian enters child details\n"
                + "2. Child plays short levels\n"
                + "3. Correct answers unlock collector cards\n"
                + "4. Progress can be saved to ChildData.txt");
        about.setEditable(false);
        about.setFont(bodyFont);
        about.setLineWrap(true);
        about.setWrapStyleWord(true);
        about.setBorder(BorderFactory.createEmptyBorder(16, 16, 16, 16));

        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER));
        buttons.setOpaque(false);
        JButton done = makeButton("Done", buttonGreen);
        done.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                showScreen("HOME");
            }
        });
        buttons.add(done);

        root.add(new JScrollPane(about), BorderLayout.CENTER);
        root.add(buttons, BorderLayout.SOUTH);
        return root;
    }

    private JPanel buildHome() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softPink);
        root.add(header("HOME MENU", headerBlue), BorderLayout.NORTH);

        JPanel center = new JPanel();
        center.setOpaque(false);
        center.setLayout(new BoxLayout(center, BoxLayout.Y_AXIS));
        center.setBorder(BorderFactory.createEmptyBorder(40, 40, 40, 40));

        JLabel hello = new JLabel("Ready to play!", SwingConstants.CENTER);
        hello.setFont(bigBody);
        hello.setAlignmentX(CENTER_ALIGNMENT);

        center.add(hello);
        center.add(Box.createVerticalStrut(20));
        center.add(homeButton("Play Levels", buttonBlue, "GAME"));
        center.add(Box.createVerticalStrut(10));
        center.add(homeButton("Awards Backpack", buttonOrange, "AWARDS"));
        center.add(Box.createVerticalStrut(10));
        center.add(homeButton("Child Progress", buttonGreen, "PROGRESS"));
        center.add(Box.createVerticalStrut(10));

        JPanel saveRow = new JPanel(new FlowLayout(FlowLayout.CENTER));
        saveRow.setOpaque(false);
        JButton save = makeButton("Save Progress", new Color(100, 80, 160));
        save.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                saveData();
            }
        });
        saveRow.add(save);
        center.add(saveRow);

        root.add(center, BorderLayout.CENTER);
        return root;
    }

    private JPanel homeButton(String text, Color colour, final String screen) {
        JPanel row = new JPanel(new FlowLayout(FlowLayout.CENTER));
        row.setOpaque(false);
        JButton button = makeButton(text, colour);
        button.setPreferredSize(new Dimension(280, 42));
        button.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                if (screen.equals("GAME")) {
                    loadCurrentLevel();
                }
                showScreen(screen);
            }
        });
        row.add(button);
        return row;
    }

    private JPanel buildGame() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softBlue);
        root.add(header("GAME LEVEL", headerBlue), BorderLayout.NORTH);

        JPanel center = new JPanel();
        center.setOpaque(false);
        center.setLayout(new BoxLayout(center, BoxLayout.Y_AXIS));
        center.setBorder(BorderFactory.createEmptyBorder(12, 40, 10, 40));

        levelLabel = new JLabel("Level 1", SwingConstants.CENTER);
        levelLabel.setFont(bigBody);
        levelLabel.setAlignmentX(CENTER_ALIGNMENT);

        gameThemeLabel = new JLabel("Theme", SwingConstants.CENTER);
        gameThemeLabel.setFont(bodyFont);
        gameThemeLabel.setAlignmentX(CENTER_ALIGNMENT);

        gameCharacterLabel = new JLabel(" ", SwingConstants.CENTER);
        gameCharacterLabel.setAlignmentX(CENTER_ALIGNMENT);
        gameCharacterLabel.setPreferredSize(new Dimension(180, 160));
        gameCharacterLabel.setMinimumSize(new Dimension(140, 120));

        gameInstructionLabel = new JLabel("Instruction", SwingConstants.CENTER);
        gameInstructionLabel.setFont(bodyFont);
        gameInstructionLabel.setAlignmentX(CENTER_ALIGNMENT);

        gameQuestionLabel = new JLabel("Question", SwingConstants.CENTER);
        gameQuestionLabel.setFont(new Font("Arial", Font.BOLD, 18));
        gameQuestionLabel.setAlignmentX(CENTER_ALIGNMENT);

        option1 = new JRadioButton("Option 1");
        option2 = new JRadioButton("Option 2");
        option3 = new JRadioButton("Option 3");
        option1.setFont(bodyFont);
        option2.setFont(bodyFont);
        option3.setFont(bodyFont);
        option1.setOpaque(false);
        option2.setOpaque(false);
        option3.setOpaque(false);

        optionGroup = new ButtonGroup();
        optionGroup.add(option1);
        optionGroup.add(option2);
        optionGroup.add(option3);

        JPanel options = new JPanel(new GridLayout(3, 1, 6, 6));
        options.setOpaque(false);
        options.setMaximumSize(new Dimension(420, 120));
        options.add(option1);
        options.add(option2);
        options.add(option3);

        gameFeedbackLabel = new JLabel(" ", SwingConstants.CENTER);
        gameFeedbackLabel.setFont(bigBody);
        gameFeedbackLabel.setAlignmentX(CENTER_ALIGNMENT);

        center.add(levelLabel);
        center.add(Box.createVerticalStrut(4));
        center.add(gameThemeLabel);
        center.add(Box.createVerticalStrut(6));
        center.add(gameCharacterLabel);
        center.add(Box.createVerticalStrut(6));
        center.add(gameInstructionLabel);
        center.add(Box.createVerticalStrut(8));
        center.add(gameQuestionLabel);
        center.add(Box.createVerticalStrut(12));
        center.add(options);
        center.add(Box.createVerticalStrut(8));
        center.add(gameFeedbackLabel);

        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER, 12, 10));
        buttons.setOpaque(false);

        JButton check = makeButton("Check Answer", buttonGreen);
        JButton next = makeButton("Next Level", buttonBlue);
        JButton home = makeButton("Home", buttonOrange);

        check.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                checkAnswer();
            }
        });
        next.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                goNextLevel();
            }
        });
        home.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                showScreen("HOME");
            }
        });

        buttons.add(check);
        buttons.add(next);
        buttons.add(home);

        root.add(new JScrollPane(center), BorderLayout.CENTER);
        root.add(buttons, BorderLayout.SOUTH);
        return root;
    }

    private void loadCurrentLevel() {
        if (levels == null || levels.length == 0) {
            return;
        }
        if (currentLevel >= levels.length) {
            currentLevel = 0;
        }

        GameLevel level = levels[currentLevel];
        levelLabel.setText("Level " + (currentLevel + 1) + " of " + levels.length);
        gameThemeLabel.setText(level.getHyperFThemeLine(guardian.getHyperF())
                + "   |   Child: " + guardian.getName());
        gameInstructionLabel.setText(level.getInstruction());
        gameQuestionLabel.setText(level.getQuestion());

        String[] options = level.getOptions();
        option1.setText(options[0]);
        option2.setText(options[1]);
        option3.setText(options[2]);
        optionGroup.clearSelection();
        gameFeedbackLabel.setText(" ");
        gameFeedbackLabel.setForeground(Color.DARK_GRAY);
        updateEmotionCharacter(level);
    }

    private void updateEmotionCharacter(GameLevel level) {
        gameCharacterLabel.setIcon(null);
        gameCharacterLabel.setText(" ");

        String imageName = level.getEmotionImage();
        if (imageName == null && level.isEmotionSkill()) {
            imageName = ImageAssets.emotionFile(level.getAnswer());
        }
        if (imageName == null && !level.isEmotionSkill()) {
            // Friendly companion for social levels
            imageName = "ferret.png";
        }

        ImageIcon icon = ImageAssets.loadScaled(imageName, 160, 160);
        if (icon != null) {
            gameCharacterLabel.setIcon(icon);
            if (level.isEmotionSkill()) {
                gameCharacterLabel.setToolTipText("Look at the character's face for clues");
            } else {
                gameCharacterLabel.setToolTipText("Your learning friend");
            }
        }
    }

    private void checkAnswer() {
        if (levels == null) {
            return;
        }

        String chosen = null;
        if (option1.isSelected()) {
            chosen = option1.getText();
        } else if (option2.isSelected()) {
            chosen = option2.getText();
        } else if (option3.isSelected()) {
            chosen = option3.getText();
        }

        if (chosen == null) {
            JOptionPane.showMessageDialog(this, "Please select an answer.",
                    "No answer", JOptionPane.WARNING_MESSAGE);
            return;
        }

        GameLevel level = levels[currentLevel];
        if (level.checkAnswer(chosen)) {
            boolean alreadyDone = level.isCompletedLevel();
            gameFeedbackLabel.setForeground(new Color(20, 120, 50));
            gameFeedbackLabel.setText("Well done, " + guardian.getName() + "! You did great!");
            level.setCompletedLevel(true);
            progress.markLevelComplete(currentLevel, level.isEmotionSkill(), level.isInteractionSkill());
            awards.addCard(level.getCardReward());

            // Math interest gets an extra arithmetic collector card challenge
            // Only offer once when the level is first completed.
            if (!alreadyDone && guardian.getHyperF().equalsIgnoreCase("Math")) {
                offerMathCard();
            }
        } else {
            gameFeedbackLabel.setForeground(new Color(170, 40, 40));
            gameFeedbackLabel.setText("Try again :)");
        }
    }

    private void offerMathCard() {
        mathAward.mathRandom();
        String input = JOptionPane.showInputDialog(this,
                "Math Collector Card!\nSolve: " + mathAward.getQuestion(),
                "Math Award", JOptionPane.QUESTION_MESSAGE);
        if (input == null) {
            return;
        }
        try {
            int value = Integer.parseInt(input.trim());
            if (mathAward.checkAnswer(value)) {
                String card = "Math Card " + mathAward.getSum1() + "+" + mathAward.getSum2();
                awards.addCard(card);
                JOptionPane.showMessageDialog(this, "Correct! Card collected.",
                        "Math Award", JOptionPane.INFORMATION_MESSAGE);
            } else {
                JOptionPane.showMessageDialog(this, "Not quite. The card was not collected this time.",
                        "Math Award", JOptionPane.INFORMATION_MESSAGE);
            }
        } catch (NumberFormatException ex) {
            JOptionPane.showMessageDialog(this, "Please enter a whole number.",
                    "Math Award", JOptionPane.ERROR_MESSAGE);
        }
    }

    private void goNextLevel() {
        if (levels == null) {
            return;
        }
        currentLevel++;
        if (currentLevel >= levels.length) {
            JOptionPane.showMessageDialog(this,
                    "All levels finished for now. You can replay from level 1.",
                    "Levels complete", JOptionPane.INFORMATION_MESSAGE);
            currentLevel = 0;
        }
        loadCurrentLevel();
    }

    private JPanel buildAwards() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softLavender);
        root.add(header("AWARDS BACKPACK", headerBlue), BorderLayout.NORTH);

        awardsArea = new JTextArea("No collector cards yet.");
        awardsArea.setEditable(false);
        awardsArea.setFont(new Font("Arial", Font.PLAIN, 15));
        awardsArea.setBorder(BorderFactory.createEmptyBorder(16, 16, 16, 16));

        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER));
        buttons.setOpaque(false);
        JButton home = makeButton("Home", buttonBlue);
        home.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                showScreen("HOME");
            }
        });
        buttons.add(home);

        root.add(new JScrollPane(awardsArea), BorderLayout.CENTER);
        root.add(buttons, BorderLayout.SOUTH);
        return root;
    }

    private JPanel buildProgress() {
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(softGreen);
        root.add(header("CHILD PROGRESS", headerBlue), BorderLayout.NORTH);

        JPanel center = new JPanel(new BorderLayout(10, 10));
        center.setOpaque(false);
        center.setBorder(BorderFactory.createEmptyBorder(16, 20, 10, 20));

        progressPercentLabel = new JLabel("Progress: 0%", SwingConstants.CENTER);
        progressPercentLabel.setFont(bigBody);

        progressSummaryLabel = new JLabel("No levels completed yet.", SwingConstants.CENTER);
        progressSummaryLabel.setFont(bodyFont);

        chartPanel = new ProgressChartPanel();
        chartPanel.setPreferredSize(new Dimension(500, 220));

        JPanel top = new JPanel();
        top.setOpaque(false);
        top.setLayout(new BoxLayout(top, BoxLayout.Y_AXIS));
        progressPercentLabel.setAlignmentX(CENTER_ALIGNMENT);
        progressSummaryLabel.setAlignmentX(CENTER_ALIGNMENT);
        top.add(progressPercentLabel);
        top.add(Box.createVerticalStrut(8));
        top.add(progressSummaryLabel);

        center.add(top, BorderLayout.NORTH);
        center.add(chartPanel, BorderLayout.CENTER);

        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER));
        buttons.setOpaque(false);
        JButton home = makeButton("Home", buttonBlue);
        home.addActionListener(new ActionListener() {
            public void actionPerformed(ActionEvent e) {
                showScreen("HOME");
            }
        });
        buttons.add(home);

        root.add(center, BorderLayout.CENTER);
        root.add(buttons, BorderLayout.SOUTH);
        return root;
    }

    private void refreshProgressScreen() {
        progressPercentLabel.setText(guardian.getFullChildName()
                + "  |  Progress: " + progress.progressCal() + "%");
        progressSummaryLabel.setText(progress.getProgressReview());
        chartPanel.setValues(progress.getLevelsCompleted(),
                progress.getInteractionSkills(), progress.getEmotionsLearnt());
        chartPanel.repaint();
    }

    private void saveData() {
        if (guardian == null || progress == null || awards == null) {
            JOptionPane.showMessageDialog(this, "No data to save yet.",
                    "Save", JOptionPane.WARNING_MESSAGE);
            return;
        }
        try {
            DataStore.save(guardian, progress, awards);
            JOptionPane.showMessageDialog(this,
                    "Progress saved to ChildData.txt\nChild ID: " + guardian.getChildId(),
                    "Saved", JOptionPane.INFORMATION_MESSAGE);
        } catch (IOException ex) {
            JOptionPane.showMessageDialog(this, "Could not save: " + ex.getMessage(),
                    "Save failed", JOptionPane.ERROR_MESSAGE);
        }
    }

    private JLabel label(String text) {
        JLabel label = new JLabel(text);
        label.setFont(bodyFont);
        return label;
    }

    // Simple green bar chart for progress screen
    private class ProgressChartPanel extends JPanel {

        private int levelsPlayed;
        private int interactions;
        private int emotions;

        public ProgressChartPanel() {
            setBackground(Color.WHITE);
            setBorder(BorderFactory.createLineBorder(new Color(180, 180, 180)));
        }

        public void setValues(int levelsPlayed, int interactions, int emotions) {
            this.levelsPlayed = levelsPlayed;
            this.interactions = interactions;
            this.emotions = emotions;
        }

        protected void paintComponent(Graphics g) {
            super.paintComponent(g);
            int[] values = {levelsPlayed, interactions, emotions};
            String[] names = {"Levels played", "Interaction Skills", "Emotions Learnt"};
            int max = 1;
            for (int i = 0; i < values.length; i++) {
                if (values[i] > max) {
                    max = values[i];
                }
            }

            int baseY = getHeight() - 40;
            int barWidth = 70;
            int gap = 50;
            int startX = 60;

            g.setColor(Color.DARK_GRAY);
            g.drawLine(40, baseY, getWidth() - 20, baseY);
            g.setFont(bodyFont);

            for (int i = 0; i < values.length; i++) {
                int barHeight = (int) ((values[i] / (double) max) * (getHeight() - 90));
                int x = startX + i * (barWidth + gap);
                int y = baseY - barHeight;
                g.setColor(new Color(60, 160, 80));
                g.fillRect(x, y, barWidth, barHeight);
                g.setColor(Color.BLACK);
                g.drawRect(x, y, barWidth, barHeight);
                g.drawString(names[i], x - 5, baseY + 18);
                g.drawString(String.valueOf(values[i]), x + 28, y - 5);
            }
        }
    }
}
