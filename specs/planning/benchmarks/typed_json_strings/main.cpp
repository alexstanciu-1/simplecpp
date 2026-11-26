#include <algorithm>
#include <chrono>
#include <cstddef>
#include <iostream>
#include <string>
#include <string_view>
#include <type_traits>

static std::size_t allocations, requested;
#ifdef COUNTERS
extern "C" void* __real__Znwm(std::size_t);
extern "C" void* __wrap__Znwm(std::size_t bytes) {
    ++allocations;
    requested += bytes;
    return __real__Znwm(bytes);
}
#endif

void dump(const scpp::string& value) {
    static constexpr char digits[] = "0123456789abcdef";
    for (unsigned char byte : std::string_view(value.data(), value.size()))
        std::cout << digits[byte >> 4] << digits[byte & 15];
}

template<class Result> void verify(const std::string& wire) {
    auto result = [&] {
        scpp::string temporary(wire.data(), static_cast<scpp::int64>(wire.size()));
        return Result::from_text(temporary);
    }();
    if (result.has_error()) {
        std::cout << "error ";
        dump(result.error().get_message());
    } else {
        std::cout << "ok";
        const auto& value = result.value().value;
        if constexpr (std::is_same_v<Result, App::record_result_Scalar>) {
            std::cout << ' ';
            dump(value);
        } else {
            for (scpp::int64 i = 0; i < value.size(); ++i) {
                std::cout << ' ';
                dump(value.at(i));
            }
        }
    }
    std::cout << '\n';
}

template<class Result> void measure(const std::string& wire, int loops) {
    const scpp::string input(wire.data(), static_cast<scpp::int64>(wire.size()));
    for (int repeat : {std::max(1, loops/8), loops}) {
        unsigned long checksum = 0;
        allocations = requested = 0;
        auto start = std::chrono::steady_clock::now();
        for (int i = 0; i < repeat; ++i) {
            auto result = Result::from_text(input);
            checksum += result.value().value.size();
        }
        const auto duration = std::chrono::steady_clock::now() - start;
        auto count = allocations, bytes = requested;
        if (repeat == loops) {
            std::cout << std::chrono::duration<double,std::nano>(duration).count()/loops << ' '
                      << count/loops << ' ' << bytes/loops << ' ' << checksum << '\n';
            return;
        }
    }
}

int main(int argc, char** argv) {
    if (argc < 2 || argc > 3) return 2;
    bool scalar = std::string_view(argv[1]) == "scalar";
    for (std::string hex; std::getline(std::cin, hex);) {
        std::string wire;
        for (std::size_t i = 0; i < hex.size(); i += 2)
            wire.push_back(static_cast<char>(std::stoi(hex.substr(i, 2), nullptr, 16)));
        if (argc == 2) {
            if (scalar) verify<App::record_result_Scalar>(wire);
            else verify<App::record_result_Words>(wire);
        } else {
            const auto loops = std::stoi(argv[2]);
            if (scalar) measure<App::record_result_Scalar>(wire, loops);
            else measure<App::record_result_Words>(wire, loops);
        }
    }
}
