// Compile with a copy of reader.hpp instrumented by build_probe.py.
#include <chrono>
#include <cstdint>
#include <cstdlib>
#include <iostream>
#include <string>
#include <string_view>

using Clock = std::chrono::steady_clock;
static std::size_t observations, growths, moved, capacity_before, capacity_after;
static Clock::duration append_time;

#include "reader.hpp"

struct Reader : scpp::detail::json::reader_detail::json_reader {
    Reader() : json_reader({}) {}
    std::string decode(std::string_view wire) {
        input_ = wire;
        pos_ = 0;
        return parse_string();
    }
};

int main(int argc, char** argv) {
    if (argc != 3) return 2;
    const auto size = std::stoul(argv[1]);
    const int repeats = std::stoi(argv[2]);
    const std::string expected = std::string(size - 1, 'a') + 'b';
    const std::string wire = '"' + expected.substr(0, size - 1) + "\\u0062\"";
    Reader reader;
    for (int pass = 0; pass < 2; ++pass) {
        observations = growths = moved = 0;
        append_time = {};
        for (int i = 0; i < (pass ? repeats : repeats / 8); ++i) {
            if (reader.decode(wire) != expected) return 1;
        }
    }
    if (observations != static_cast<unsigned>(repeats)) return 1;
    std::cout << observations << ' ' << growths << ' ' << moved << ' '
              << capacity_before << ' ' << capacity_after << ' '
              << std::chrono::duration<double, std::nano>(append_time).count() / repeats << '\n';
}
