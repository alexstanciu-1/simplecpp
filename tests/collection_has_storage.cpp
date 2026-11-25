// Const presence probes neither allocate storage nor copy heap-backed elements.
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

template<class T> concept probes = requires(const scpp::vector_int64& v, T i) { v.has(i); };
static_assert(probes<scpp::int64> && !probes<double> && !probes<bool> && !probes<unsigned long long>);

int main() {
    const auto baseline = allocations - releases;
    {
        scpp::vector_string v;
        scpp::hash_string_string h;
        scpp::string key{"a\0b", scpp::int64{3}}, absent{"absent"};
        const auto before = allocations;
        if (v.has(scpp::int64{0}) || h.has(key) || allocations != before) return 1;
        v.append(scpp::string{"long payload to detect an accidental value copy"});
        h.set(key, scpp::string{"another heap-backed value that must not be copied"});
        const auto& cv = v;
        const auto& ch = h;
        const auto allocated = allocations, released = releases;
        for (int i = 0; i < 100; ++i) {
            if (!cv.has(scpp::int64{0}) || !ch.has(key) || ch.has(absent)) return 2;
            if (cv.has(scpp::int64{-1}) || cv.has(scpp::int64{1})
                || cv.has(std::numeric_limits<scpp::int64>::min())
                || cv.has(std::numeric_limits<scpp::int64>::max())) return 3;
        }
        if (allocations != allocated || releases != released || cv.size() != 1 || ch.size() != 1) return 4;
        v.clear(); h.clear();
        if (cv.has(scpp::int64{0}) || ch.has(key)) return 5;
    }
    return allocations - releases == baseline ? 0 : 6;
}
