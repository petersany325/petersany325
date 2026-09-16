#include "app.hpp"

#include <cstring>

int main(int argc, char** argv) {
    for (int i = 1; i < argc; ++i) {
        if (std::strcmp(argv[i], "--cli") == 0 || std::strcmp(argv[i], "--self-test") == 0) {
            return hsc::run_cli(argc, argv);
        }
        if (std::strcmp(argv[i], "--help") == 0 || std::strcmp(argv[i], "-h") == 0) {
            return hsc::run_cli(argc, argv);
        }
    }
    return hsc::run_gui(argc, argv);
}
