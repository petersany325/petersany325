package autismapp;

// Stores guardian and child setup details
public class Guardian {

    private String name;
    private String surname;
    private int aType;
    private String hyperF;
    private String childId;

    public Guardian(String inN, String inS, int inAT, String inHF) {
        name = inN;
        surname = inS;
        aType = inAT;
        hyperF = inHF;
        childId = createId();
    }

    // ID uses initials + random number (as in the design document)
    private String createId() {
        String initials = "";
        if (name != null && name.length() > 0) {
            initials = initials + Character.toUpperCase(name.charAt(0));
        }
        if (surname != null && surname.length() > 0) {
            initials = initials + Character.toUpperCase(surname.charAt(0));
        }
        int randomNum = (int) (Math.random() * 90) + 10;
        return initials + randomNum;
    }

    public String getName() {
        return name;
    }

    public String getSurname() {
        return surname;
    }

    public int getAType() {
        return aType;
    }

    public String getHyperF() {
        return hyperF;
    }

    public String getChildId() {
        return childId;
    }

    public String getFullChildName() {
        return name + " " + surname;
    }

    public String toString() {
        return childId + "," + name + "," + surname + "," + aType + "," + hyperF;
    }
}
