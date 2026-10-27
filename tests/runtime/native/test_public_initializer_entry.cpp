#include "public_api_guard.hpp"
#include "scpp/string_entry_input.hpp"
#include "scpp/integer_entry_input.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <type_traits>
#include <utility>
using namespace scpp;
static bool counting = false, fail_next = false;
static unsigned allocations = 0;
void *operator new(std::size_t size)
{
    if (fail_next) { fail_next = false; throw std::bad_alloc(); }
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "initializer entry failure %d\n", __LINE__); std::abort(); } } while (false)
static_assert(std::is_copy_constructible_v<string_entry>);
static_assert(std::is_move_constructible_v<string_entry>);
static_assert(!std::is_copy_assignable_v<string_entry>);
static_assert(!std::is_move_assignable_v<string_entry>);
static_assert(!std::is_default_constructible_v<string_entry>);
static_assert(std::is_same_v<decltype(std::declval<const string_entry &>().key()), string>);
static_assert(std::is_same_v<decltype(std::declval<const string_entry &>().value()), string>);
int main()
{
    counting = true;
    {
        integer_entry entry(int64(1), int64(9));
        integer_entry copy(entry), moved(std::move(copy));
        integer_entry_input input(uint64(2)); input.append(entry); input.append(moved);
        CHECK(input.count() == int64(2));
        CHECK(input.at(int64(1)).key() == int64(1) && input.at(int64(1)).value() == int64(9));
    }
    counting = false; CHECK(allocations == 0);
    string key("key");
    string value("A long immutable initializer entry payload that owns its backing independently");
    string_entry entry(key, value);
    key = string("changed");
    value = string("changed");
    CHECK(entry.key().equals("key") && !entry.value().equals("changed"));
    auto copied_key = entry.key(); copied_key = string("another");
    CHECK(entry.key().equals("key"));
    auto escaped = [&]() {
        string_entry_input input(uint64(2));
        input.append(entry);
        fail_next = true; bool failed = false;
        try { input.append(entry); } catch (const std::bad_alloc &) { failed = true; }
        CHECK(failed && !fail_next);
        input.append(entry);
        CHECK(input.count() == int64(2));
        CHECK(input.at(int64(0)).key() == input.at(int64(1)).key()); // Preserve duplicates for the factory.
        return input.at(int64(1));
    }();
    CHECK(escaped.key() == entry.key() && escaped.value() == entry.value());
    try {
        string_entry_input partial(uint64(3)); partial.append(entry); throw 1;
    } catch (int) {}
    std::puts("PASS: immutable entry construction, owned reads, ordered duplicate preservation and partial cleanup");
}
