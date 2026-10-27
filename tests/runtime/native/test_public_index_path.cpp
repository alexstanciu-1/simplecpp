#include "public_api_guard.hpp"
#include "scpp/access/index_path_store_mixed.hpp"
#include "scpp/access/index_path_store_dynamic.hpp"
#include "scpp/access/index_path_append_mixed.hpp"
#include "scpp/access/index_path_append_dynamic.hpp"
#include "scpp/access/require_write_key_mixed.hpp"
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
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "index path failure %d\n", __LINE__); std::abort(); } } while (false)
int main()
{
    const mixed a(int64(4)), b(int64(8)), c(int64(12));
    vector_mixed path; path.append(a); path.append(b); path.append(c);
    vector_mixed parent; parent.append(a); parent.append(b);
    vector_mixed no_keys;
    mixed m;
    index_path_store(m, path, mixed(int64(7)));
    CHECK(m.get(a).get(b).get(c).get_int() == int64(7));
    auto independent = m;
    index_path_store(m, path, mixed(int64(9)));
    CHECK(independent.get(a).get(b).get(c).get_int() == int64(7));
    CHECK(m.get(a).get(b).get(c).get_int() == int64(9));
    index_path_append(m, parent, mixed(int64(11)));
    CHECK(m.get(a).get(b).get(int64(13)).get_int() == int64(11)); // Next key, not count.
    mixed created;
    vector_mixed invalid; invalid.append(a); invalid.append(b); invalid.append(mixed(boolean(false)));
    bool failed = false;
    try { index_path_store(created, invalid, mixed(int64(1))); } catch (...) { failed = true; }
    CHECK(failed && created.is_null()); // No intermediate bootstrap on validation failure.
    auto wrong = make_mixed_table(); wrong.set(a, mixed(int64(2))); auto unchanged = wrong;
    failed = false;
    try { index_path_store(wrong, path, mixed(int64(1))); } catch (...) { failed = true; }
    CHECK(failed && wrong.get(a).get_int() == int64(2) && unchanged.get(a).get_int() == int64(2));
    dynamic d;
    failed = false;
    try { index_path_store(d, path, dynamic(int64(1))); } catch (...) { failed = true; }
    CHECK(failed && d.is_null());
    d = make_dynamic_container();
    failed = false;
    try { index_path_store(d, path, dynamic(int64(1))); } catch (...) { failed = true; }
    CHECK(failed && count(d) == int64(0));
    auto child = make_dynamic_container(); auto grandchild = make_dynamic_container();
    child.set(b, grandchild); d.set(a, child); auto alias = d;
    index_path_store(d, path, dynamic(int64(5)));
    CHECK(alias.get(a).get(b).get(c).get_int() == int64(5));
    index_path_append(d, parent, dynamic(int64(6)));
    CHECK(alias.get(a).get(b).get(int64(13)).get_int() == int64(6));
    counting = true;
    for (int i = 0; i < 100; ++i) {
        index_path_store(m, path, mixed(int64(i)));
        index_path_store(d, path, dynamic(int64(i)));
    }
    counting = false; CHECK(allocations == 0);
    // An invalid reached key prevents later key and RHS expressions.
    int key_effects = 0, rhs_effects = 0;
    failed = false;
    try {
        ++key_effects; require_write_key(mixed(boolean(false)));
        ++key_effects; ++rhs_effects; index_path_store(m, path, mixed(int64(0)));
    } catch (...) { failed = true; }
    CHECK(failed && key_effects == 1 && rhs_effects == 0);
    // A completed RHS replaces the bound root; commit resolves that current root.
    auto rhs = [&]() { ++rhs_effects; m.reset(); return mixed(int64(21)); };
    const auto value = rhs(); index_path_store(m, path, value);
    CHECK(rhs_effects == 1 && m.get(a).get(b).get(c).get_int() == int64(21));
    // Self RHS is snapshotted for mixed, including a previously null root.
    mixed self;
    index_path_store(self, path, self);
    CHECK(self.get(a).get(b).get(c).is_null());
    auto original = self;
    index_path_append(self, no_keys, self);
    CHECK(count(self) == int64(2));
    CHECK(count(self.get(int64(5))) == count(original));
    CHECK(self.get(int64(5)).get(a).get(b).get(c).is_null());
    // Dynamic retains identity. Break the intentional self edge before teardown.
    auto self_d = make_dynamic_container();
    index_path_append(self_d, no_keys, self_d);
    self_d.set(int64(9), dynamic(int64(3)));
    CHECK(self_d.get(int64(0)).get(int64(9)).get_int() == int64(3));
    CHECK(self_d.remove(int64(0)));
    mixed root_append;
    index_path_append(root_append, no_keys, mixed(int64(1)));
    CHECK(root_append.get(int64(0)).get_int() == int64(1));
    failed = false;
    try { index_path_store(root_append, no_keys, mixed(int64(1))); } catch (...) { failed = true; }
    CHECK(failed && count(root_append) == int64(1));
    std::puts("PASS: public nested store/append, prevalidation, staged RHS, alias snapshots and allocation-free replacement");
}
