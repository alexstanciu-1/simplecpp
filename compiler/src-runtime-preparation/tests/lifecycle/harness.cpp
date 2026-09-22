#include <cstdint>
#include <cstdio>
#include <cstdlib>

extern "C" void proof_run();

extern "C" void proof_event(std::int32_t kind, std::int32_t value) noexcept {
    std::printf("%d:%d\n", kind, value);
}

extern "C" void proof_equal(std::int32_t actual, std::int32_t expected) noexcept {
    if (actual != expected) {
        std::fprintf(stderr, "proof mismatch: %d != %d\n", actual, expected);
        std::exit(1);
    }
}

int main() { proof_run(); }
