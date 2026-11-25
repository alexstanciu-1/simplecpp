#include "public_api_guard.hpp"
#include "scpp/access/index_existing_read_mixed.hpp"
#include "scpp/access/index_existing_read_dynamic.hpp"
#include "scpp/access/index_existing_store_mixed.hpp"
#include "scpp/access/index_existing_store_dynamic.hpp"
#include "scpp/access/index_path_store_mixed.hpp"
#include "scpp/operators/add_mixed.hpp"
#include "scpp/operators/add_dynamic.hpp"
#include "scpp/operators/numeric_compound_replacement_mixed.hpp"
#include "scpp/operators/numeric_compound_replacement_dynamic.hpp"
#include "scpp/observations/count_mixed.hpp"
#include "scpp/observations/count_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
using namespace scpp;
static bool counting = false;
static unsigned allocations = 0;
void *operator new(std::size_t size)
{
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "existing path failure %d\n", __LINE__); std::abort(); } } while (false)
int main()
{
    const mixed a(int64(4)), b(int64(8));
    vector_mixed path; path.append(a); path.append(b);
    vector_mixed no_keys;
    mixed m;
    int rhs_effects = 0;
    bool failed = false;
    try { auto old = index_existing_read(m, path); ++rhs_effects; (void)old; }
    catch (...) { failed = true; }
    CHECK(failed && rhs_effects == 0 && m.is_null());
    index_path_store(m, path, mixed(int64(7)));
    const auto old = index_existing_read(m, path);
    // RHS replaces the leaf; result is computed from the old owned snapshot.
    ++rhs_effects; index_path_store(m, path, mixed(string("replacement")));
    const auto result = numeric_compound_replacement(old, numeric_add(old, mixed(int64(3))));
    index_existing_store(m, path, result);
    CHECK(rhs_effects == 1 && index_existing_read(m, path).get_int() == int64(10));
    auto independent = m;
    index_existing_store(m, path, mixed(int64(11)));
    CHECK(index_existing_read(independent, path).get_int() == int64(10));
    auto d = make_dynamic_container(); auto child = make_dynamic_container();
    child.set(b, dynamic(int64(7))); d.set(a, child); auto alias = d;
    const auto old_d = index_existing_read(d, path);
    d.get(a).set(b, dynamic(int64(100)));
    index_existing_store(d, path, numeric_compound_replacement(old_d, numeric_add(old_d, dynamic(int64(3)))));
    CHECK(index_existing_read(alias, path).get_int() == int64(10));
    counting = true;
    for (int i = 0; i < 100; ++i) {
        auto left = index_existing_read(m, path);
        auto right = index_existing_read(d, path);
        index_existing_store(m, path, left);
        index_existing_store(d, path, right);
    }
    counting = false; CHECK(allocations == 0);
    // Deleted leaf and parent cannot be recreated by writeback.
    m.remove(a); d.get(a).remove(b);
    bool failed_m = false, failed_d = false;
    try { index_existing_store(m, path, result); } catch (...) { failed_m = true; }
    try { index_existing_store(d, path, dynamic(int64(10))); } catch (...) { failed_d = true; }
    CHECK(failed_m && failed_d && count(m) == int64(0) && count(d.get(a)) == int64(0));
    m.set(a, mixed(int64(2))); d.set(a, dynamic(int64(2)));
    failed_m = failed_d = false;
    try { index_existing_store(m, path, result); } catch (...) { failed_m = true; }
    try { index_existing_store(d, path, dynamic(int64(10))); } catch (...) { failed_d = true; }
    CHECK(failed_m && failed_d && m.get(a).get_int() == int64(2) && d.get(a).get_int() == int64(2));
    // Returned string snapshots survive root reset and do not retain an interior borrow.
    const string text("An owned existing-leaf snapshot that survives destruction of its source container");
    m.reset(); index_path_store(m, path, mixed(text));
    auto snapshot = index_existing_read(m, path); m.reset(); CHECK(snapshot.get_string() == text);
    // Existing null is readable, unlike a missing slot; store may replace it.
    index_path_store(m, path, mixed{});
    CHECK(index_existing_read(m, path).is_null());
    index_existing_store(m, path, mixed(int64(1)));
    CHECK(index_existing_read(m, path).get_int() == int64(1));
    index_existing_store(m, path, m);
    CHECK(index_existing_read(m, path).get(a).get(b).get_int() == int64(1));
    vector_mixed invalid; invalid.append(a); invalid.append(mixed(boolean(false)));
    failed = false;
    try { (void)index_existing_read(m, invalid); } catch (...) { failed = true; }
    CHECK(failed);
    failed = false;
    try { index_existing_store(m, no_keys, mixed{}); } catch (...) { failed = true; }
    CHECK(failed);
    std::puts("PASS: existing-only snapshots/writeback, old-leaf arithmetic, no recreation and zero numeric allocations");
}
