#include <iostream>
#include <string>
#include <string_view>

int main() {
    std::string source(65536, 'x');
    unsigned full = 0;
    for (std::size_t n = 1024; n <= source.size(); ++n) {
        std::string original(std::string_view(source.data(), n));
        if ((n % 8 == 7) != (n == original.capacity())) return 1;
        full += n == original.capacity();
    }
    std::cout << full << " full buffers checked in 64513 lengths\n";
}
