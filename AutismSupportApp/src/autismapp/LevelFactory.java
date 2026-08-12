package autismapp;

// Builds levels based on autism type and hyperfixation
public class LevelFactory {

    public static GameLevel[] createLevels(int autismType, String hyperF) {
        String character = getCharacter(hyperF);

        GameLevel[] levels = new GameLevel[6];

        // Type 1 focuses more on emotions and subtle social cues
        // Type 2 focuses on social interaction and behaviour
        // Type 3 focuses on simple routines and basic needs communication
        if (autismType == 1) {
            levels[0] = new GameLevel(
                    "Read the face carefully. Short instruction: choose the emotion.",
                    "What emotion is " + character + " feeling?",
                    new String[]{"Happy", "Sad", "Angry"},
                    "Happy",
                    character + " Happy Card",
                    true, false);
            levels[1] = new GameLevel(
                    "Choose the best social response.",
                    character + " wants to join a game. What should you do?",
                    new String[]{"Ignore them", "Smile and say hello", "Walk away"},
                    "Smile and say hello",
                    character + " Friendship Card",
                    false, true);
            levels[2] = new GameLevel(
                    "Spot the emotion.",
                    character + " lost a favourite toy. How does " + character + " feel?",
                    new String[]{"Sad", "Happy", "Excited"},
                    "Sad",
                    character + " Sad Card",
                    true, false);
            levels[3] = new GameLevel(
                    "Choose a calm response.",
                    "Someone made a joke that was hard to understand. What can you do?",
                    new String[]{"Ask them to explain", "Shout", "Never talk again"},
                    "Ask them to explain",
                    character + " Communication Card",
                    false, true);
            levels[4] = new GameLevel(
                    "Emotion practice.",
                    character + " finished a hard puzzle. How does " + character + " feel?",
                    new String[]{"Proud", "Angry", "Bored"},
                    "Proud",
                    character + " Proud Card",
                    true, false);
            levels[5] = new GameLevel(
                    "Social practice.",
                    "A friend looks upset. What is a kind thing to do?",
                    new String[]{"Laugh at them", "Ask if they are okay", "Take their snack"},
                    "Ask if they are okay",
                    character + " Kindness Card",
                    false, true);
        } else if (autismType == 2) {
            levels[0] = new GameLevel(
                    "Choose the helpful action.",
                    character + " needs a turn in a group activity. What should happen?",
                    new String[]{"Push to the front", "Wait for a turn", "Leave the room"},
                    "Wait for a turn",
                    character + " Turn-Taking Card",
                    false, true);
            levels[1] = new GameLevel(
                    "Emotion check.",
                    "What emotion is " + character + " showing when frowning?",
                    new String[]{"Angry", "Happy", "Sleepy"},
                    "Angry",
                    character + " Angry Card",
                    true, false);
            levels[2] = new GameLevel(
                    "Routine support.",
                    "It is time to change activities. What can help?",
                    new String[]{"Take deep breaths and follow the plan", "Throw objects", "Refuse forever"},
                    "Take deep breaths and follow the plan",
                    character + " Calm Card",
                    false, true);
            levels[3] = new GameLevel(
                    "Social practice.",
                    character + " greets a teacher. What is polite?",
                    new String[]{"Say good morning", "Stay silent and stare", "Run away"},
                    "Say good morning",
                    character + " Greeting Card",
                    false, true);
            levels[4] = new GameLevel(
                    "Emotion practice.",
                    character + " got a surprise gift. How does " + character + " feel?",
                    new String[]{"Happy", "Angry", "Scared"},
                    "Happy",
                    character + " Happy Card",
                    true, false);
            levels[5] = new GameLevel(
                    "Support needs practice.",
                    "If a task feels too hard, what can you do?",
                    new String[]{"Ask for help", "Break things", "Give up quietly forever"},
                    "Ask for help",
                    character + " Help Card",
                    false, true);
        } else {
            levels[0] = new GameLevel(
                    "Simple choice. Choose the feeling.",
                    "What emotion is " + character + " feeling?",
                    new String[]{"Happy", "Sad", "Angry"},
                    "Sad",
                    character + " Sad Card",
                    true, false);
            levels[1] = new GameLevel(
                    "Daily need practice.",
                    character + " is thirsty. What should " + character + " ask for?",
                    new String[]{"Water", "A loud noise", "Nothing"},
                    "Water",
                    character + " Needs Card",
                    false, true);
            levels[2] = new GameLevel(
                    "Routine practice.",
                    "It is bedtime. What comes next?",
                    new String[]{"Brush teeth and rest", "Start a new loud game", "Skip sleep forever"},
                    "Brush teeth and rest",
                    character + " Routine Card",
                    false, true);
            levels[3] = new GameLevel(
                    "Emotion practice.",
                    character + " smiles at a friend. How does " + character + " feel?",
                    new String[]{"Happy", "Angry", "Worried"},
                    "Happy",
                    character + " Happy Card",
                    true, false);
            levels[4] = new GameLevel(
                    "Safe choice.",
                    "If you feel overwhelmed, what can help?",
                    new String[]{"Ask a trusted adult for help", "Hide and never speak", "Break a toy"},
                    "Ask a trusted adult for help",
                    character + " Safety Card",
                    false, true);
            levels[5] = new GameLevel(
                    "Social comfort practice.",
                    character + " wants to say hello. What can " + character + " do?",
                    new String[]{"Wave or say hi", "Push someone", "Yell"},
                    "Wave or say hi",
                    character + " Hello Card",
                    false, true);
        }

        return levels;
    }

    private static String getCharacter(String hyperF) {
        if (hyperF == null) {
            return "Alex";
        }
        if (hyperF.equalsIgnoreCase("Dinosaurs")) {
            return "Fred the Dinosaur";
        }
        if (hyperF.equalsIgnoreCase("Math")) {
            return "Number Nova";
        }
        if (hyperF.equalsIgnoreCase("Flowers")) {
            return "Petal";
        }
        return "Alex";
    }
}
