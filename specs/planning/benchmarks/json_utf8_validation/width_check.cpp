// Compare width and validity, including truncation after each byte.
#include "utf8_codepoints.hpp"
#include <array>

int main() {
    constexpr std::array<unsigned, 6> tails{0, 0x7f, 0x80, 0xbf, 0xc0, 0xff};
    for (unsigned a = 0; a < 256; ++a) {
        for (unsigned b = 0; b < 256; ++b) {
            for (unsigned c : tails) {
                for (unsigned d : tails) {
                    const char text[]{static_cast<char>(a), static_cast<char>(b),
                                      static_cast<char>(c), static_cast<char>(d)};
                    for (std::size_t n = 0; n <= sizeof(text); ++n) {
                        std::string_view view(text, n);
                        std::uint32_t scalar;
                        if (scpp::detail::utf8::decode(view, scalar) != scpp::detail::utf8::validated_width(view))
                            return 1;
                    }
                }
            }
        }
    }
}
