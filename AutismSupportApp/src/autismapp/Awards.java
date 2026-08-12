package autismapp;

import java.util.ArrayList;

// Collector cards earned after completing levels
public class Awards {

    private ArrayList<String> collectCards;
    private String hyperF;

    public Awards(String hyperF) {
        this.hyperF = hyperF;
        collectCards = new ArrayList<String>();
    }

    public String getHyperF() {
        return hyperF;
    }

    public void addCard(String cardName) {
        if (!collectCards.contains(cardName)) {
            collectCards.add(cardName);
        }
    }

    public ArrayList<String> getCollectCards() {
        return collectCards;
    }

    public int getCardCount() {
        return collectCards.size();
    }

    public String toString() {
        if (collectCards.isEmpty()) {
            return "No collector cards yet.";
        }
        String result = "";
        for (int i = 0; i < collectCards.size(); i++) {
            result = result + (i + 1) + ". " + collectCards.get(i);
            if (i < collectCards.size() - 1) {
                result = result + "\n";
            }
        }
        return result;
    }
}
