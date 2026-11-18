// Fail every allocation in public vector operations; all failed attempts must leave storage unchanged.
#include <scpp/vectors.hpp>
#include <cassert>
#include <cstdlib>
#include <new>
static long budget = -1;
static long live = 0;
static void charge() { if (budget == 0) throw std::bad_alloc{}; if (budget > 0) --budget; }
void* operator new(std::size_t size) { charge(); if (void* p = std::malloc(size ? size : 1)) { ++live; return p; } throw std::bad_alloc{}; }
void operator delete(void* p) noexcept { if (p) { --live; std::free(p); } }
void operator delete(void* p, std::size_t) noexcept { ::operator delete(p); }
void* operator new[](std::size_t n) { return ::operator new(n); }
void operator delete[](void* p) noexcept { ::operator delete(p); }
void operator delete[](void* p, std::size_t) noexcept { ::operator delete(p); }
void* operator new(std::size_t size, std::align_val_t alignment) {
    charge(); void* p = nullptr;
    if (posix_memalign(&p, static_cast<std::size_t>(alignment), size ? size : 1) != 0) throw std::bad_alloc{};
    ++live; return p;
}
void operator delete(void* p, std::align_val_t) noexcept { ::operator delete(p); }
void operator delete(void* p, std::size_t, std::align_val_t) noexcept { ::operator delete(p); }
int main() {
    const auto baseline = live;
    {
        const scpp::string text{"|alpha||omega|"};
        const auto pieces = scpp::str::split(text, scpp::string{"|"});
        assert(pieces.size() == 5 && pieces.at(0).empty() && pieces.at(2).empty());
        assert(scpp::str::join(pieces, scpp::string{"|"}) == text);
        scpp::vector_int64 empty;
        for (long allowance = 0; allowance < 2; ++allowance) {
            const auto before = live; budget = allowance;
            try { empty.append(scpp::int64{1}); assert(false); } catch (const std::bad_alloc&) {}
            budget = -1; assert(empty.empty() && live == before);
        }
        scpp::vector_int64 numbers;
        for (int i = 0; i < 4; ++i) numbers.append(scpp::int64{i});
        const auto before = live; budget = 0;
        try { numbers.append(numbers.at(0)); assert(false); } catch (const std::bad_alloc&) {}
        budget = -1; assert(numbers.size() == 4 && numbers.at(0) == 0 && live == before);
        for (int i = 4; i < 2048; ++i) numbers.append(scpp::int64{i});
        assert(numbers.at(2047) == 2047);
        const auto allocated = live; numbers.clear();
        assert(live == allocated); budget = 0;
        for (int i = 0; i < 2048; ++i) numbers.append(scpp::int64{i});
        budget = -1;
        scpp::vector_string source;
        for (int i = 0; i < 5; ++i) source.append("A long value that uses heap storage rather than the short-string buffer.");
        bool success = false;
        for (long allowance = 0; allowance < 100 && !success; ++allowance) {
            scpp::vector_string destination; destination.append("keep");
            const auto initial = live; budget = allowance;
            try { destination = source; budget = -1; success = true; }
            catch (const std::bad_alloc&) {
                budget = -1; assert(destination.size() == 1 && destination.at(0) == scpp::string{"keep"} && live == initial);
            }
            assert(source.size() == 5);
            if (success) { destination.set(0, "changed"); assert(source.at(0) != destination.at(0)); }
        }
        assert(success);
    }
    assert(live == baseline);
}
