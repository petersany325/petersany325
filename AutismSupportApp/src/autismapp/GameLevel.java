package autismapp;

// One multiple-choice level (emotions / social skills)
public class GameLevel {

    private String instruction;
    private String question;
    private String[] options;
    private String answer;
    private boolean completedLevel;
    private String cardReward;
    private boolean emotionSkill;
    private boolean interactionSkill;
    private String emotionImage;

    public GameLevel(String instruction, String question, String[] options,
            String answer, String cardReward, boolean emotionSkill, boolean interactionSkill) {
        this.instruction = instruction;
        this.question = question;
        this.options = options;
        this.answer = answer;
        this.cardReward = cardReward;
        this.emotionSkill = emotionSkill;
        this.interactionSkill = interactionSkill;
        completedLevel = false;
        emotionImage = pickEmotionImage(answer, emotionSkill);
    }

    private String pickEmotionImage(String emotion, boolean isEmotion) {
        if (!isEmotion || emotion == null) {
            return null;
        }
        if (emotion.equalsIgnoreCase("Happy") || emotion.equalsIgnoreCase("Excited")) {
            return "dino-happy.png";
        }
        if (emotion.equalsIgnoreCase("Sad") || emotion.equalsIgnoreCase("Worried")
                || emotion.equalsIgnoreCase("Scared")) {
            return "dino-sad.png";
        }
        if (emotion.equalsIgnoreCase("Angry")) {
            return "dino-angry.png";
        }
        if (emotion.equalsIgnoreCase("Proud")) {
            return "dino-proud.png";
        }
        return null;
    }

    public String getInstruction() {
        return instruction;
    }

    public String getQuestion() {
        return question;
    }

    public String[] getOptions() {
        return options;
    }

    public String getAnswer() {
        return answer;
    }

    public boolean isCompletedLevel() {
        return completedLevel;
    }

    public void setCompletedLevel(boolean completedLevel) {
        this.completedLevel = completedLevel;
    }

    public String getCardReward() {
        return cardReward;
    }

    public boolean isEmotionSkill() {
        return emotionSkill;
    }

    public boolean isInteractionSkill() {
        return interactionSkill;
    }

    public String getEmotionImage() {
        return emotionImage;
    }

    public boolean checkAnswer(String chosen) {
        return answer.equalsIgnoreCase(chosen);
    }

    public String getHyperFThemeLine(String hyperF) {
        return "Theme: " + hyperF;
    }

    public String toString() {
        return question + " (Answer: " + answer + ")";
    }
}
