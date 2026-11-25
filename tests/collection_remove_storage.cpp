// Isolated public-header proof: ordered removal, strict native arguments and element release.
#include <scpp/vectors.hpp>
#include <scpp/hashes.hpp>
#include <cstdlib>
#include <new>
#include <limits>

static unsigned allocations = 0;
static unsigned releases = 0;
void* operator new(std::size_t size) {
    if (void* memory = std::malloc(size ? size : 1)) { ++allocations; return memory; }
    throw std::bad_alloc{};
}
void operator delete(void* memory) noexcept { if (memory) { ++releases; std::free(memory); } }
void* operator new[](std::size_t size) { return ::operator new(size); }
void operator delete[](void* memory) noexcept { ::operator delete(memory); }

template<class T> concept removable = requires(scpp::vector_int64& v, T i) { v.remove(i); };
static_assert(removable<scpp::int64> && !removable<double> && !removable<bool> && !removable<unsigned long long>);

int main() {
    const auto baseline = allocations - releases;
    {
        scpp::vector_int64 v;
        const auto initial = allocations;
        if (v.remove(scpp::int64{0}) || allocations != initial) return 1;
        for (scpp::int64 i = 0; i < 4; ++i) v.append(i);
        const auto allocated = allocations, released = releases;
        if (v.remove(std::numeric_limits<scpp::int64>::min()) || v.remove(std::numeric_limits<scpp::int64>::max())
            || v.remove(scpp::int64{-1}) || v.remove(scpp::int64{4})) return 2;
        if (!v.remove(scpp::int64{1}) || v.size() != 3 || v.at(scpp::int64{1}) != 2 || v.at(scpp::int64{2}) != 3) return 3;
        if (!v.remove(scpp::int64{2}) || !v.remove(scpp::int64{0}) || !v.remove(scpp::int64{0}) || v.remove(scpp::int64{0})) return 4;
        for (scpp::int64 i = 0; i < 4; ++i) v.append(i);
        if (allocations != allocated || releases != released) return 5;
    }
    {
        scpp::vector_string v;
        for (int i = 0; i < 3; ++i) v.append(scpp::string{"heap-backed element longer than inline storage"});
        const auto live = allocations - releases;
        if (!v.remove(scpp::int64{1}) || v.size() != 2 || allocations - releases >= live) return 6;
    }
    {
        scpp::hash_string_string h;
        scpp::string key{"x\0y", scpp::int64{3}};
        if (h.remove(key)) return 7;
        h.set(key, scpp::string{"heap-backed hash value longer than inline storage"});
        auto copy = h;
        if (!h.remove(key) || h.remove(key) || !h.empty() || copy.size() != 1) return 8;
    }
    return allocations - releases == baseline ? 0 : 9;
}
