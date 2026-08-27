package autismapp;

// Math collector card challenge for children who chose Math interest
public class MathAward extends Awards {

    private int sum1;
    private int sum2;

    public MathAward(String hyperF) {
        super(hyperF);
        mathRandom();
    }

    public void mathRandom() {
        sum1 = (int) (Math.random() * 10) + 1;
        sum2 = (int) (Math.random() * 10) + 1;
    }

    public int getSum1() {
        return sum1;
    }

    public int getSum2() {
        return sum2;
    }

    public int getCorrectAnswer() {
        return sum1 + sum2;
    }

    public String getQuestion() {
        return sum1 + " + " + sum2 + " = ?";
    }

    public boolean checkAnswer(int answer) {
        return answer == getCorrectAnswer();
    }

    public String toString() {
        return "Math card: " + getQuestion() + "\n" + super.toString();
    }
}
