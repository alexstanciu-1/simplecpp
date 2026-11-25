#include "public_api_guard.hpp"
#include "scpp/vector_factory.hpp"
#include "scpp/hash_factory.hpp"
#include "scpp/mixed_list_initializer.hpp"
#include "scpp/dynamic_list_initializer.hpp"
#include "scpp/observations/count_mixed.hpp"
#include "scpp/observations/count_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <type_traits>
using namespace scpp;
static bool counting = false;
static unsigned allocations = 0;
static int fail_after = -1;
void *operator new(std::size_t size)
{
    if (fail_after == 0) { fail_after = -1; throw std::bad_alloc(); }
    if (fail_after > 0) --fail_after;
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "list factory failure %d\n", __LINE__); std::abort(); } } while (false)
static_assert(!std::is_default_constructible_v<vector_factory>);
static_assert(!std::is_copy_constructible_v<mixed_list_initializer>);
int main()
{
    string_input empty_string(uint64(0)); integer_input empty_integer(uint64(0));
    mixed_input empty_mixed(uint64(0)); dynamic_input empty_dynamic(uint64(0));
    counting = true;
    auto empty_v = vector_factory::from_list(empty_string);
    auto empty_h = hash_factory::from_list(empty_integer);
    counting = false; CHECK(allocations == 0);
    CHECK(empty_v.empty() && empty_h.empty());
    auto empty_m = mixed_list_initializer::from_list(empty_mixed);
    auto empty_d = dynamic_list_initializer::from_list(empty_dynamic);
    CHECK(empty_m.is_hash() && empty_d.is_hash());
    CHECK(count(empty_m) == int64(0) && count(empty_d) == int64(0));
    vector_string escaped;
    const string text("A long source string whose factory copies must own their backing independently");
    {
        string_input input(uint64(1));
        bool incomplete = false;
        try { (void)vector_factory::from_list(input); } catch (...) { incomplete = true; }
        CHECK(incomplete);
        input.append(text);
        counting = true; allocations = 0;
        escaped = vector_factory::from_list(input);
        counting = false;
        std::printf("public vector factory allocations=%u\n", allocations);
        fail_after = 2; bool failed = false;
        try { (void)vector_factory::from_list(input); } catch (const std::bad_alloc &) { failed = true; }
        CHECK(failed && fail_after == -1 && input.at(int64(0)) == text);
        CHECK(escaped.at(uint64(0)) == text);
    }
    CHECK(escaped.at(uint64(0)) == text);
    integer_input numbers(uint64(3));
    numbers.append(int64(9)); numbers.append(int64(4)); numbers.append(int64(7));
    auto hash = hash_factory::from_list(numbers);
    CHECK(hash.size() == uint64(3));
    CHECK(hash.at(int64(0)) == int64(9) && hash.at(int64(2)) == int64(7));
    mixed copied;
    dynamic retained;
    {
        auto child_m = make_mixed_table(); child_m.set(int64(0), mixed(int64(1)));
        auto child_d = make_dynamic_container(); child_d.set(int64(0), dynamic(int64(1)));
        mixed_input m(uint64(2)); dynamic_input d(uint64(2));
        m.append(mixed{}); m.append(child_m); d.append(dynamic{}); d.append(child_d);
        copied = mixed_list_initializer::from_list(m);
        retained = dynamic_list_initializer::from_list(d);
        auto fresh = dynamic_list_initializer::from_list(d);
        retained.set(int64(0), dynamic(int64(8)));
        CHECK(fresh.get(int64(0)).is_null());
        child_m.set(int64(0), mixed(int64(2))); child_d.set(int64(0), dynamic(int64(2)));
        CHECK(copied.get(int64(1)).get(int64(0)).get_int() == int64(1));
        CHECK(retained.get(int64(1)).get(int64(0)).get_int() == int64(2));
        CHECK(fresh.get(int64(1)).get(int64(0)).get_int() == int64(2));
    }
    CHECK(copied.get(int64(0)).is_null());
    CHECK(retained.get(int64(1)).get(int64(0)).get_int() == int64(2));
    bool incomplete_m = false, incomplete_d = false;
    mixed_input partial_m(uint64(1)); dynamic_input partial_d(uint64(1));
    try { (void)mixed_list_initializer::from_list(partial_m); } catch (...) { incomplete_m = true; }
    try { (void)dynamic_list_initializer::from_list(partial_d); } catch (...) { incomplete_d = true; }
    CHECK(incomplete_m && incomplete_d);
    std::puts("PASS: public positional factory ownership, ordered entries, failure cleanup and fresh containers");
}
