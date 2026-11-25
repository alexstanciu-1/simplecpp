#include "public_api_guard.hpp"
#include "scpp/time/microtime_string.hpp"
#include "scpp/time/microtime_float.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <stdexcept>

static unsigned allocations = 0;
void *operator new(std::size_t size)
{
    ++allocations;
    if (auto memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(value) do { if (!(value)) { std::fprintf(stderr, "microtime failure %d\n", __LINE__); std::abort(); } } while(false)

int main()
{
    using namespace scpp;
    const auto before = allocations;
    for (int i = 0; i < 100; ++i) {
        const auto value = microtime(boolean(true)).native_value();
        CHECK(value > 0.0 && value < 1.0e12);
    }
    CHECK(allocations == before);
    bool rejected = false;
    try { (void)microtime(boolean(false)); }
    catch (const std::logic_error &) { rejected = true; }
    CHECK(rejected);

    const auto text_before = allocations;
    const auto text = microtime();
    const auto text_allocations = allocations - text_before;
    CHECK(text.byte_size().native_value() > 11);
    CHECK(text.byte_at(uint64(0)).native_value() == '0');
    CHECK(text.byte_at(uint64(1)).native_value() == '.');
    CHECK(text.byte_at(uint64(10)).native_value() == ' ');
    for (uint64 i(2); i < text.byte_size(); ++i) {
        if (i == uint64(10)) continue;
        const auto byte = text.byte_at(i).native_value();
        CHECK(byte >= '0' && byte <= '9');
    }
    // The owned result remains valid after subsequent clock calls and destruction.
    { const auto later = microtime(); CHECK(later.byte_size().native_value() > 11); }
    CHECK(text.byte_at(uint64(10)).native_value() == ' ');
    std::printf("PASS: wall-clock forms, false rejection, owned text; numeric allocations=0, text allocations=%u\n", text_allocations);
}
