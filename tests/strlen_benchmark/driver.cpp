#include <chrono>
#include <cstdio>
#include <cstdint>

extern "C" void* make_value(std::size_t bytes);
extern "C" std::uint64_t length(void* value);
extern "C" void release_value(void* value);

int main() {
    constexpr unsigned count = 1024, rounds = 20000;
    void* values[count];
    std::uint64_t expected = 0;
    for (unsigned i = 0; i < count; ++i) {
        unsigned bytes = (i * 251) % 4097;
        expected += bytes;
        values[i] = make_value(bytes);
    }
    std::uint64_t sum = 0;
    auto start = std::chrono::steady_clock::now();
    for (unsigned r = 0; r < rounds; ++r)
        for (unsigned i = 0; i < count; ++i) sum += length(values[i]);
    auto elapsed = std::chrono::steady_clock::now() - start;
    for (void* value : values) release_value(value);
    if (sum != expected * rounds) return 1;
    std::printf("%.4f %llu\n", std::chrono::duration<double, std::nano>(elapsed).count() / (count * rounds),
                static_cast<unsigned long long>(sum));
}
