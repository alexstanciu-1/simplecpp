#include "public_api_guard.hpp"
#include "modules/filesystem/scan.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <utility>

static unsigned allocation_count = 0;
static bool counting = false;
void begin_allocations() { counting = true; allocation_count = 0; }
unsigned end_allocations() { counting = false; return allocation_count; }
void *operator new(std::size_t size)
{
    if (counting) ++allocation_count;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
unsigned native_scan_allocations(const char *);
bool typed_hash_proof();
static void check(bool condition, int line)
{
    if (!condition) { std::fprintf(stderr, "public composition check failed at %d\n", line); std::abort(); }
}
#define CHECK(x) check(static_cast<bool>(x), __LINE__)
using namespace scpp;
static vector_string_cursor_ surviving_cursor()
{
    vector_string source;
    source.append(string("alive"));
    return source.iterate();
}
int main(int argc, char **argv)
{
    CHECK(argc == 2);
    CHECK(typed_hash_proof());
    string path(argv[1]);
    const auto native_count = native_scan_allocations(argv[1]);
    begin_allocations();
    auto scan = fs::scan(path);
    const auto public_count = end_allocations();
    CHECK(scan.has_value());
    CHECK(public_count == native_count);
    vector_string names;
    error_value error = error_value::make(string("old"));
    CHECK(take(names, error, scan));
    CHECK(!error);
    CHECK(names.size() == uint64(3));
    CHECK(names.at(uint64(0)).equals("a"));
    CHECK(names.at(uint64(1)).equals("b"));
    CHECK(names.at(uint64(2)).equals("long_filename_for_allocation_parity_proof"));
    auto snapshot = names;
    names.set(uint64(0), string("changed"));
    CHECK(snapshot.at(uint64(0)).equals("a"));
    auto failed = fs::scan(path + string("/absent"));
    CHECK(failed.has_error());
    CHECK(!take(names, error, failed));
    CHECK(names.at(uint64(0)).equals("changed"));
    CHECK(error.get_message().equals("fs::scan(): not a directory"));
    bool rejected = false;
    try { scan = error_value(); } catch (...) { rejected = true; }
    CHECK(rejected && static_cast<bool>(scan.has_value()));
    rejected = false;
    try { (void)failed.value(); } catch (...) { rejected = true; }
    CHECK(rejected);
    rejected = false;
    try { (void)names.at(uint64(~0ULL)); } catch (...) { rejected = true; }
    CHECK(rejected);
    CHECK(!names.remove(int64(-1)));
    auto traversal = snapshot.iterate();
    CHECK(traversal.value().equals("a"));
    snapshot.append(string("later"));
    snapshot.set(uint64(1), string("replacement"));
    traversal.next();
    CHECK(traversal.key() == int64(1));
    CHECK(traversal.value().equals("b"));
    traversal.next();
    CHECK(traversal.valid());
    traversal.next();
    CHECK(!traversal.valid());
    auto survivor = surviving_cursor();
    CHECK(survivor.value().equals("alive"));
    names.reserve(uint64(20));
    names.resize(uint64(5), string("fill"));
    CHECK(names.at(uint64(4)).equals("fill"));
    CHECK(names.remove(int64(1)));
    names.compact();
    names.compact(uint64(12));
    CHECK(names.capacity() >= uint64(12));
    names.clear();
    CHECK(empty(names));
    auto moved = std::move(snapshot);
    CHECK(moved.size() == uint64(4));
    std::printf("scan allocations native=%u public=%u; vector bytes=%zu result bytes=%zu; hash/iteration/Result checks passed\n",
        native_count, public_count, sizeof(vector_string), sizeof(result_vector_string));
}
