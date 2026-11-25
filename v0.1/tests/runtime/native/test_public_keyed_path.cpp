#include "public_api_guard.hpp"
#include "scpp/access/keyed_prefix_ready_mixed.hpp"
#include "scpp/access/keyed_prefix_ready_dynamic.hpp"
#include "scpp/access/keyed_path_probe_mixed.hpp"
#include "scpp/access/keyed_path_probe_dynamic.hpp"
#include "scpp/exception_info.hpp"
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
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "public keyed path failure %d\n", __LINE__); std::abort(); } } while (false)
int main()
{
    const boolean probe(false), removing(true), empty(true);
    const mixed outer(int64(4)), inner(int64(8));
    vector_mixed prefix; prefix.append(outer);
    vector_mixed path; path.append(outer); path.append(inner);
    vector_mixed no_keys;
    auto m = make_mixed_table(); auto mc = make_mixed_table();
    auto d = make_dynamic_container(); auto dc = make_dynamic_container();
    const string text("A long payload observed without copying its owned string backing");
    mc.set(inner, mixed(text)); m.set(outer, mc);
    dc.set(inner, dynamic(text)); d.set(outer, dc);
    CHECK(keyed_prefix_ready(m, no_keys, probe) && keyed_prefix_ready(d, no_keys, probe));
    counting = true;
    for (int i = 0; i < 100; ++i) {
        CHECK(keyed_prefix_ready(m, prefix, probe) && keyed_prefix_ready(d, prefix, probe));
        CHECK(keyed_path_probe(m, path, probe) && keyed_path_probe(d, path, probe));
        CHECK(!keyed_path_probe(m, path, empty) && !keyed_path_probe(d, path, empty));
    }
    counting = false; CHECK(allocations == 0);
    // Caller staging evaluates the bound root and reached keys once.
    int roots = 0, first = 0, second = 0, skipped = 0;
    auto bind = [&]() -> mixed & { ++roots; return m; };
    auto &bound = bind();
    vector_mixed saved;
    if (keyed_prefix_ready(bound, saved, probe)) {
        ++first; saved.append(outer);
        if (keyed_prefix_ready(bound, saved, probe)) {
            ++second; bound.remove(outer); saved.append(inner);
            if (keyed_prefix_ready(bound, saved, probe)) ++skipped;
            CHECK(!keyed_path_probe(bound, saved, probe));
        }
    }
    CHECK(roots == 1 && first == 1 && second == 1 && skipped == 0);
    bool failed = false;
    try { (void)keyed_path_probe(bound, saved, empty); }
    catch (...) { failed = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
    CHECK(failed);
    // A dynamic alias can replace a reached child between key stages.
    auto alias = d;
    CHECK(keyed_prefix_ready(d, prefix, probe));
    alias.set(outer, dynamic(int64(1)));
    CHECK(!keyed_prefix_ready(d, prefix, probe) && !keyed_path_probe(d, path, probe));
    failed = false;
    try { (void)keyed_prefix_ready(d, prefix, removing); } catch (...) { failed = true; }
    CHECK(failed);
    // Null roots prevent even the first source key expression from running.
    m.reset(); d.reset(); first = 0;
    if (keyed_prefix_ready(m, no_keys, probe)) ++first;
    if (keyed_prefix_ready(d, no_keys, probe)) ++first;
    CHECK(first == 0);
    // Saved keys own their values. Replacing the source key cannot redirect the path.
    mixed key(string("saved")); vector_mixed owned; owned.append(key); key = mixed(string("changed"));
    m = make_mixed_table(); m.set(string("saved"), mixed(boolean(false)));
    CHECK(keyed_path_probe(m, owned, probe));
    m.set(string("saved"), mixed{}); CHECK(!keyed_path_probe(m, owned, probe));
    m.set(string("saved"), mixed(string{})); CHECK(keyed_path_probe(m, owned, empty));
    vector_mixed invalid; invalid.append(mixed(boolean(false)));
    CHECK(!keyed_path_probe(m, invalid, probe));
    failed = false;
    try { (void)keyed_path_probe(m, invalid, empty); }
    catch (...) { failed = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
    CHECK(failed);
    failed = false;
    try { (void)keyed_path_probe(m, no_keys, probe); } catch (...) { failed = true; }
    CHECK(failed);
    std::puts("PASS: public saved-key paths, staged effects, owned keys and zero observation allocations");
}
