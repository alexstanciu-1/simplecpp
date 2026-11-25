#include "scpp/keyed_path.hpp"
#include <array>
#include <cstdio>
#include <cstdlib>
#include <new>
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
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "prefix failure %d\n", __LINE__); std::abort(); } } while (false)
int main()
{
    using namespace scpp;
    using dynamic = detail::dynamic_value;
    const std::array<mixed_t, 2> keys{mixed_t(int_t<>(4)), mixed_t(int_t<>(8))};
    unsigned reads = 0;
    auto key_at = [&](std::size_t index) -> const mixed_t & { ++reads; return keys.at(index); };
    mixed_t m{std::make_unique<hash_t<mixed_t>>()};
    mixed_t child{std::make_unique<hash_t<mixed_t>>()};
    child[keys[1]] = mixed_t(int_t<>(9)); m[keys[0]] = child;
    auto d = dynamic::make_container(); auto dc = dynamic::make_container();
    dc.get_table().set(keys[1], dynamic(int_t<>(9))); d.get_table().set(keys[0], dc);
    CHECK(keyed_prefix_ready_adapted(&m, 0, key_at, false));
    CHECK(keyed_prefix_ready_adapted(&m, 1, key_at, false));
    CHECK(keyed_path_probe_adapted(&m, 2, key_at, false).native_value());
    CHECK(keyed_path_probe_adapted(&d, 2, key_at, false).native_value());
    counting = true;
    for (int i = 0; i < 100; ++i) {
        CHECK(keyed_prefix_ready_adapted(&m, 1, key_at, false));
        CHECK(keyed_path_probe_adapted(&m, 2, key_at, false).native_value());
        CHECK(keyed_path_probe_adapted(&d, 2, key_at, false).native_value());
    }
    counting = false; CHECK(allocations == 0);
    // A reached key expression removes the earlier prefix. The next check uses current storage.
    CHECK(keyed_prefix_ready_adapted(&m, 1, key_at, false));
    (void)m.remove(keys[0]);
    reads = 0;
    CHECK(!keyed_path_probe_adapted(&m, 2, key_at, false).native_value() && reads == 1);
    bool failed = false;
    try { (void)keyed_path_probe_adapted(&m, 2, key_at, true); } catch (...) { failed = true; }
    CHECK(failed);
    // Root replacement is visible and invalid roots skip even saved-key access.
    d = dynamic(int_t<>(1)); reads = 0;
    CHECK(!keyed_prefix_ready_adapted(&d, 1, key_at, false) && reads == 0);
    failed = false;
    try { (void)keyed_prefix_ready_adapted(&d, 1, key_at, true); } catch (...) { failed = true; }
    CHECK(failed);
    // Existing null is absent; existing false is present but not an empty collection.
    child[keys[1]] = mixed_t{}; m[keys[0]] = child;
    CHECK(!keyed_path_probe_adapted(&m, 2, key_at, false).native_value());
    child[keys[1]] = mixed_t(bool_t(false)); m[keys[0]] = child;
    CHECK(keyed_path_probe_adapted(&m, 2, key_at, false).native_value());
    failed = false;
    try { (void)keyed_path_probe_adapted(&m, 2, key_at, true); } catch (...) { failed = true; }
    CHECK(failed);
    failed = false;
    try { (void)keyed_path_probe_adapted(&m, 0, key_at, false); } catch (const std::logic_error &) { failed = true; }
    CHECK(failed);
    std::puts("PASS: saved-key prefix re-resolution, no escaped interiors, zero-allocation observations");
}
