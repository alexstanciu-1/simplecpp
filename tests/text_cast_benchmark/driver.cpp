#include <chrono>
#include <cstdint>
#include <cstdio>

extern "C" std::uint64_t format_sample(unsigned kind, unsigned index);
extern "C" std::uint64_t consume(const char* data, std::size_t count) {
    std::uint64_t hash = count;
    for (std::size_t i = 0; i < count; ++i) hash = hash * 31 + static_cast<unsigned char>(data[i]);
    return hash;
}

int main() {
    constexpr unsigned samples = 300000;
    for (unsigned kind = 0; kind < 4; ++kind) {
        std::uint64_t sum = 0;
        const auto start = std::chrono::steady_clock::now();
        for (unsigned i = 0; i < samples; ++i) sum += format_sample(kind, i);
        const auto elapsed = std::chrono::steady_clock::now() - start;
        std::printf("%.3f %llu\n", std::chrono::duration<double, std::nano>(elapsed).count() / samples,
                    static_cast<unsigned long long>(sum));
    }
}
