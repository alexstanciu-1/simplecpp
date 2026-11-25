#include "public_api_guard.hpp"
#include "scpp/access/keyed_path_erase_mixed.hpp"
#include "scpp/access/keyed_path_erase_dynamic.hpp"
#include "scpp/access/keyed_prefix_ready_mixed.hpp"
#include "scpp/access/keyed_prefix_ready_dynamic.hpp"
#include "scpp/access/keyed_path_probe_mixed.hpp"
#include "scpp/access/keyed_path_probe_dynamic.hpp"
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
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "keyed erase failure %d\n", __LINE__); std::abort(); } } while (false)
int main()
{
    const boolean removing(true), presence(false);
    const mixed outer(int64(4)), inner(int64(8)), sibling(int64(9)), missing(int64(99));
    vector_mixed path; path.append(outer); path.append(inner);
    vector_mixed prefix; prefix.append(outer);
    vector_mixed missing_leaf; missing_leaf.append(outer); missing_leaf.append(missing);
    vector_mixed missing_parent; missing_parent.append(missing); missing_parent.append(inner);
    vector_mixed empty_path;
    auto m = make_mixed_table(); auto mc = make_mixed_table();
    auto d = make_dynamic_container(); auto dc = make_dynamic_container();
    mc.set(inner, mixed{}); mc.set(sibling, mixed(int64(11))); m.set(outer, mc);
    dc.set(inner, dynamic{}); dc.set(sibling, dynamic(int64(11))); d.set(outer, dc);
    auto independent = m; auto alias = d;
    counting = true;
    for (int i = 0; i < 100; ++i) {
        keyed_path_erase(m, missing_leaf); keyed_path_erase(m, missing_parent);
        keyed_path_erase(d, missing_leaf); keyed_path_erase(d, missing_parent);
    }
    counting = false; CHECK(allocations == 0);
    CHECK(count(m.get(outer)) == int64(2) && count(d.get(outer)) == int64(2));
    // Null is an existing final slot: it must be erased despite isset being false.
    CHECK(!keyed_path_probe(m, path, presence) && !keyed_path_probe(d, path, presence));
    keyed_path_erase(m, path); keyed_path_erase(d, path);
    CHECK(count(m.get(outer)) == int64(1) && count(independent.get(outer)) == int64(2));
    CHECK(count(d.get(outer)) == int64(1) && count(alias.get(outer)) == int64(1));
    CHECK(m.get(outer).get(sibling).get_int() == int64(11));
    CHECK(d.get(outer).get(sibling).get_int() == int64(11));
    vector_mixed last; last.append(outer); last.append(sibling);
    keyed_path_erase(m, last); keyed_path_erase(d, last);
    CHECK(count(m) == int64(1) && count(d) == int64(1)); // Do not prune empty parents.
    CHECK(m.get(outer).is_hash() && d.get(outer).is_hash());
    CHECK(count(m.get(outer)) == int64(0) && count(d.get(outer)) == int64(0));
    vector_mixed invalid; invalid.append(outer); invalid.append(mixed(boolean(false)));
    bool failed_m = false, failed_d = false;
    try { keyed_path_erase(m, invalid); } catch (...) { failed_m = true; }
    try { keyed_path_erase(d, invalid); } catch (...) { failed_d = true; }
    CHECK(failed_m && failed_d && count(m.get(outer)) == int64(0) && count(d.get(outer)) == int64(0));
    // Existing scalar intermediates throw; missing/null intermediates are no-ops.
    m.set(outer, mixed(int64(7))); d.set(outer, dynamic(int64(7)));
    failed_m = failed_d = false;
    try { keyed_path_erase(m, path); } catch (...) { failed_m = true; }
    try { keyed_path_erase(d, path); } catch (...) { failed_d = true; }
    CHECK(failed_m && failed_d);
    m.set(outer, mixed{}); d.set(outer, dynamic{});
    counting = true; keyed_path_erase(m, path); keyed_path_erase(d, path); counting = false;
    CHECK(allocations == 0 && m.get(outer).is_null() && d.get(outer).is_null());
    // A reached key expression changes the root; commit must use its current path.
    m = independent; d = make_dynamic_container(); d.set(outer, make_dynamic_container());
    int effects = 0, skipped = 0;
    if (keyed_prefix_ready(m, prefix, removing)) {
        ++effects; m.remove(outer);
        if (keyed_prefix_ready(m, path, removing)) ++skipped;
        keyed_path_erase(m, path);
    }
    CHECK(effects == 1 && skipped == 0 && count(m) == int64(0));
    CHECK(count(independent.get(outer)) == int64(2));
    m.reset(); d.reset();
    counting = true; keyed_path_erase(m, path); keyed_path_erase(d, path); counting = false;
    CHECK(allocations == 0 && m.is_null() && d.is_null());
    failed_m = failed_d = false;
    try { keyed_path_erase(m, empty_path); } catch (...) { failed_m = true; }
    try { keyed_path_erase(d, empty_path); } catch (...) { failed_d = true; }
    CHECK(failed_m && failed_d);
    std::puts("PASS: public nested removal, complete recheck, no-op allocations and mixed/dynamic ownership");
}
