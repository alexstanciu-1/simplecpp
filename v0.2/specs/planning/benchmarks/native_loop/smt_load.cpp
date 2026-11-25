// Compile once with GCC and link the same load kernel into both benchmark binaries.
#include <atomic>
#include <bit>
#include <cstdint>
extern "C" std::uint64_t integer_load(const std::atomic<bool>& stop) {
    std::uint64_t a = 17, b = 29, c = 43, d = 53;
    while (!stop.load(std::memory_order_relaxed)) {
        for (unsigned i = 0; i < 128; ++i) {
            a = std::rotl((a ^ (a >> 27)) * 0x2545f4914f6cdd1dULL, 17);
            b = std::rotl((b ^ (b >> 27)) * 0x2545f4914f6cdd1dULL, 17);
            c = std::rotl((c ^ (c >> 27)) * 0x2545f4914f6cdd1dULL, 17);
            d = std::rotl((d ^ (d >> 27)) * 0x2545f4914f6cdd1dULL, 17);
        }
    }
    return a ^ b ^ c ^ d;
}
