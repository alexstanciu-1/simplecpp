// Check independent width decisions and the byte offsets reported at ASCII block boundaries.
#include "structural_reader.hpp"

using scpp::detail::json::pull_reader;
using scpp::detail::json::read_error;

int main() {
    constexpr std::array<unsigned, 6> tails{0, 0x7f, 0x80, 0xbf, 0xc0, 0xff};
    for (unsigned lead = 0; lead < 256; ++lead) {
        for (unsigned second = 0; second < 256; ++second) {
            for (unsigned third : tails) {
                for (unsigned fourth : tails) {
                    const char data[]{static_cast<char>(lead), static_cast<char>(second),
                                      static_cast<char>(third), static_cast<char>(fourth)};
                    for (std::size_t size = 0; size <= sizeof(data); ++size) {
                        const std::string_view bytes(data, size);
                        std::uint32_t scalar;
                        if (scpp::detail::utf8::validated_width(bytes) != scpp::detail::utf8::decode(bytes, scalar))
                            return 1;
                    }
                }
            }
        }
    }
    for (std::size_t offset = 1; offset <= 96; ++offset) {
        std::string wire(offset, 'a');
        wire.front() = '!'; // Prevalidation precedes the JSON syntax error at byte zero.
        wire.append("\xf4\x90\x80\x80");
        try {
            pull_reader reader(pull_reader::borrow_input{}, wire, "", 128);
            return 2;
        } catch (const read_error& error) {
            if (error.position != offset || error.category != "utf8" || error.path != "$") return 3;
        }
    }
}
