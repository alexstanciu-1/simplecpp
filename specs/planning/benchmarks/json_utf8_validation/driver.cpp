#include <scpp/json/decode_record.hpp>
#include <algorithm>
#include <chrono>
#include <iostream>
#include <string>
#include <string_view>
#include <type_traits>
#if defined(__x86_64__)
#include <x86intrin.h>
#endif

static bool use_ticks;
static unsigned long long ticks() {
#if defined(__x86_64__)
    _mm_lfence();
    auto value = __rdtsc();
    _mm_lfence();
    return value;
#else
    std::abort();
#endif
}

std::size_t validate(std::string_view);

void dump(const scpp::string& text) {
    constexpr char hex[] = "0123456789abcdef";
    for (unsigned char c : std::string_view(text.data(), text.size()))
        std::cout << hex[c >> 4] << hex[c & 15];
}

template<class F> void measure(F operation, int loops) {
    for (int count : {std::max(1, loops / 8), loops}) {
        std::size_t checksum = 0;
        auto start = std::chrono::steady_clock::now();
        auto begin_ticks = use_ticks ? ticks() : 0;
        for (int i = 0; i < count; ++i) checksum += operation();
        auto elapsed_ticks = use_ticks ? ticks() - begin_ticks : 0;
        auto elapsed = std::chrono::steady_clock::now() - start;
        if (count == loops) {
            std::cout << (use_ticks ? static_cast<double>(elapsed_ticks) : std::chrono::duration<double, std::nano>(elapsed).count())/count
                      << ' ' << checksum << '\n';
            return;
        }
    }
}

template<class Record> void typed(const std::string& wire, int loops) {
    scpp::string input(wire.data(), static_cast<scpp::int64>(wire.size()));
    if (loops) {
        measure([&] { return Record::from_text(input).value().value.size(); }, loops);
        return;
    }
    auto result = Record::from_text(input);
    if (result.has_error()) {
        std::cout << "error ";
        dump(result.error().get_message());
    } else {
        std::cout << "ok";
        const auto& value = result.value().value;
        if constexpr (std::is_same_v<Record, App::record_result_Scalar>) {
            std::cout << ' ';
            dump(value);
        } else {
            for (scpp::int64 i = 0; i < value.size(); ++i) {
                std::cout << ' ';
                if constexpr (std::is_same_v<Record, App::record_result_Words>) dump(value.at(i));
                else std::cout << value.at(i);
            }
        }
    }
    std::cout << '\n';
}

int main(int argc, char** argv) {
    if (argc < 2 || argc > 4) return 2;
    const std::string_view mode = argv[1];
    use_ticks = argc == 4 && std::string_view(argv[3]) == "ticks";
    int loops = argc >= 3 ? std::stoi(argv[2]) : 0;
    for (std::string hex; std::getline(std::cin, hex);) {
        std::string wire;
        for (std::size_t i = 0; i < hex.size(); i += 2)
            wire.push_back(static_cast<char>(std::stoi(hex.substr(i, 2), nullptr, 16)));
        if (mode == "validate") measure([&] { return validate(wire); }, loops);
        else if (mode == "construct") {
            scpp::string input(wire.data(), static_cast<scpp::int64>(wire.size()));
            measure([&] {
                std::size_t called = 0;
                scpp::json::read_leaf_record(input, &called, [](void* out, scpp::Decoder&) {
                    ++*static_cast<std::size_t*>(out);
                });
                return called;
            }, loops);
        }
        else if (mode == "scalar") typed<App::record_result_Scalar>(wire, loops);
        else if (mode == "words") typed<App::record_result_Words>(wire, loops);
        else if (mode == "numbers") typed<App::record_result_Numbers>(wire, loops);
        else return 2;
    }
}
