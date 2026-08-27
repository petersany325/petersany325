package autismapp;

// Tracks levels completed and builds a progress summary
public class ChildProgress {

    private String progressReview;
    private int[] progArr;
    private int progCount;
    private int levelsCompleted;
    private int totalLevels;
    private int interactionSkills;
    private int emotionsLearnt;

    public ChildProgress(int totalLevels) {
        this.totalLevels = totalLevels;
        progArr = new int[totalLevels];
        progCount = 0;
        levelsCompleted = 0;
        interactionSkills = 0;
        emotionsLearnt = 0;
        progressReview = "No levels completed yet.";
    }

    public void markLevelComplete(int levelIndex, boolean emotionSkill, boolean interactionSkill) {
        if (levelIndex < 0 || levelIndex >= totalLevels) {
            return;
        }
        if (progArr[levelIndex] == 1) {
            return;
        }

        progArr[levelIndex] = 1;
        levelsCompleted++;
        progCount++;

        if (emotionSkill) {
            emotionsLearnt++;
        }
        if (interactionSkill) {
            interactionSkills++;
        }
        updateReview();
    }

    public int progressCal() {
        if (totalLevels == 0) {
            return 0;
        }
        return (levelsCompleted * 100) / totalLevels;
    }

    public int levelCount() {
        return levelsCompleted;
    }

    private void updateReview() {
        int percent = progressCal();
        if (percent >= 80) {
            progressReview = "Excellent progress! Your child is developing strong social and emotion skills.";
        } else if (percent >= 50) {
            progressReview = "Good progress. Your child is learning important skills through the levels.";
        } else if (percent > 0) {
            progressReview = "Your child has started well. Keep practising the levels for more growth.";
        } else {
            progressReview = "No levels completed yet.";
        }
    }

    public String getProgressReview() {
        return progressReview;
    }

    public int getInteractionSkills() {
        return interactionSkills;
    }

    public int getEmotionsLearnt() {
        return emotionsLearnt;
    }

    public int getLevelsCompleted() {
        return levelsCompleted;
    }

    public int getTotalLevels() {
        return totalLevels;
    }

    public boolean isLevelComplete(int index) {
        if (index < 0 || index >= totalLevels) {
            return false;
        }
        return progArr[index] == 1;
    }

    public String toStorageString() {
        StringBuilder sb = new StringBuilder();
        sb.append(levelsCompleted).append(",");
        sb.append(interactionSkills).append(",");
        sb.append(emotionsLearnt).append(",");
        for (int i = 0; i < progArr.length; i++) {
            if (i > 0) {
                sb.append("|");
            }
            sb.append(progArr[i]);
        }
        return sb.toString();
    }

    public void loadFromStorage(String data) {
        String[] parts = data.split(",", 4);
        if (parts.length < 4) {
            return;
        }
        levelsCompleted = Integer.parseInt(parts[0]);
        interactionSkills = Integer.parseInt(parts[1]);
        emotionsLearnt = Integer.parseInt(parts[2]);
        String[] flags = parts[3].split("\\|");
        for (int i = 0; i < flags.length && i < progArr.length; i++) {
            progArr[i] = Integer.parseInt(flags[i]);
        }
        progCount = levelsCompleted;
        updateReview();
    }

    public String toString() {
        return "Levels: " + levelsCompleted + "/" + totalLevels
                + " | Progress: " + progressCal() + "% | " + progressReview;
    }
}
