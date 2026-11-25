// Public-API allocation proof: clear destroys elements but preserves vector capacity.
#include <scpp/vectors.hpp>
#include <cstdlib>
#include <new>

static unsigned allocations = 0;
static unsigned releases = 0;
void* operator new(std::size_t size) {
    if (void* memory = std::malloc(size ? size : 1)) { ++allocations; return memory; }
    throw std::bad_alloc{};
}
void operator delete(void* memory) noexcept { if (memory) { ++releases; std::free(memory); } }
void* operator new[](std::size_t size) { return ::operator new(size); }
void operator delete[](void* memory) noexcept { ::operator delete(memory); }

int main() {
    const auto baseline = allocations - releases;
    {
        scpp::vector_int64 values;
        values.clear();
        for (scpp::int64 i = 0; i < 64; ++i) values.append(i);
        const auto allocated = allocations, released = releases;
        values.clear(); values.clear();
        if (!values.empty() || allocations != allocated || releases != released) return 1;
        for (scpp::int64 i = 0; i < 64; ++i) values.append(i);
        if (allocations != allocated || values.size() != 64) return 2;
    }
    if (allocations - releases != baseline) return 3;
    {
        scpp::vector_string values;
        for (int i = 0; i < 64; ++i) values.append(scpp::string{"heap-backed payload longer than the inline capacity"});
        const auto live = allocations - releases;
        values.clear();
        if (!values.empty() || allocations - releases >= live) return 4;
        const auto allocated = allocations;
        for (int i = 0; i < 64; ++i) values.append(scpp::string{"short"});
        if (allocations != allocated) return 5;
    }
    return allocations - releases == baseline ? 0 : 6;
}
