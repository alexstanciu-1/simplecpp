namespace Demo {
    class Box {
        static value(): int {
            return 7;
        }
    }
}

namespace App {
    print(Demo.Box.value(), "\n");
}
