#include <scpp/json.hpp>
#include <algorithm>
#include <chrono>
#include <cstdint>
#include <iostream>
#include <iterator>
#include <string>
#include <string_view>

template<auto decode>
void run(const scpp::string& input, int loops) {
    if (loops == -1) {
        const auto parsed = decode(input);
        const auto result = parsed.has_error() ? parsed.error().get_message() : scpp::json::encode(parsed.value()).value();
        std::cout << (parsed.has_error() ? "error " : "ok ");
        std::cout.write(result.data(), result.size());
        return;
    }
    if (loops == 0) {
        const auto result = scpp::json::encode(decode(input).value()).value();
        std::cout.write(result.data(), result.size());
        return;
    }
    const scpp::string key{"value"};
    auto measure = [&](int count) {
        std::int64_t checksum = 0;
        const auto start = std::chrono::steady_clock::now();
        for (int i = 0; i < count; ++i)
            checksum += decode(input).value().read(key).container_size();
        const auto elapsed = std::chrono::steady_clock::now() - start;
        return std::pair{std::chrono::duration<double, std::nano>(elapsed).count() / count, checksum};
    };
    measure(std::max(1, loops / 8));
    const auto [ns, checksum] = measure(loops);
    std::cout << ns << ' ' << checksum << '\n';
}

int main(int argc, char** argv) {
    if (argc != 3) return 2;
    const std::string wire{std::istreambuf_iterator<char>(std::cin), {}};
    const scpp::string input{wire.data(), static_cast<scpp::int64>(wire.size())};
    const int loops = std::stoi(argv[2]);
    if (std::string_view(argv[1]) == "dynamic") run<scpp::json::decode>(input, loops);
    else if (std::string_view(argv[1]) == "mixed") run<scpp::json::decode_mixed>(input, loops);
    else return 2;
}
