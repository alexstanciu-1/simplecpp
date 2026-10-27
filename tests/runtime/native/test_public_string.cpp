#include "public_api_guard.hpp"
#include "scpp/string.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <utility>

static unsigned allocations = 0;
static bool counting = false;
static bool fail_allocation = false;

void *operator new(std::size_t size)
{
    if (fail_allocation) { throw std::bad_alloc(); }
    if (counting) { ++allocations; }
    if (void *memory = std::malloc(size == 0 ? 1 : size)) { return memory; }
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }

static void check(bool condition)
{
    if (!condition) { std::fputs("public string check failed\n", stderr); std::abort(); }
}
#define CHECK(expression) check(static_cast<bool>(expression))

static unsigned stop_counting()
{
    counting = false;
    return allocations;
}
static void start_counting() { allocations = 0; counting = true; }

int main()
{
    using scpp::string;
    using scpp::uint64;
    start_counting();
    string blank;
    string short_value("alpha");
    string short_copy(short_value);
    CHECK(short_value.equals("alpha"));
    CHECK(short_value.is_one_of(" beta  alpha gamma "));
    CHECK(!short_value.is_one_of("alphabet ALPHA"));
    CHECK(!blank.is_one_of("   "));
    const unsigned short_allocations = stop_counting();
    CHECK(short_allocations == 0);
    CHECK(blank.empty());
    CHECK(scpp::isset(blank));
    CHECK(scpp::strlen(short_value) == uint64(5));
    CHECK(short_value.size() == short_value.byte_size());
    CHECK(short_value.estimated_storage_bytes() == uint64(sizeof(string)) + short_value.byte_capacity());

    const char long_bytes[] = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    start_counting();
    string long_value(long_bytes);
    const unsigned long_allocations = stop_counting();
    CHECK(long_allocations == 1);
    start_counting();
    string copied(long_value);
    const unsigned copy_allocations = stop_counting();
    CHECK(copy_allocations == 1);
    start_counting();
    string moved(std::move(copied));
    const unsigned move_allocations = stop_counting();
    CHECK(move_allocations == 0);
    CHECK(moved.equals(long_bytes));
    CHECK(long_value.equals(long_bytes));
    copied = string("reused");
    CHECK(copied.equals("reused"));
    copied = long_value;
    CHECK(copied == long_value);
    copied = std::move(moved);
    CHECK(copied == long_value);
    auto *self = &copied;
    copied = *self;
    copied = std::move(*self);
    CHECK(copied == long_value);
    copied.swap(copied);
    CHECK(copied == long_value);
    blank.swap(copied);
    CHECK(blank == long_value);
    CHECK(copied.empty());
    const auto capacity = blank.byte_capacity();
    blank.clear();
    CHECK(blank.empty());
    CHECK(blank.byte_capacity() == capacity);
    short_copy.append(short_copy);
    CHECK(short_copy.equals("alphaalpha"));
    short_copy._unset_();
    CHECK(scpp::empty(short_copy));
    string joined = string("a") + string("b");
    joined += string("c");
    CHECK(joined.equals("abc"));
    CHECK(string("a") < string("b"));
    CHECK(string("a") <= string("a"));
    CHECK(string("b") > string("a"));
    CHECK(string("b") >= string("b"));
    CHECK(string("a") != string("b"));

    string binary("a\0\xff", uint64(3));
    CHECK(binary.byte_size() == uint64(3));
    CHECK(binary.byte_at(uint64(1)).native_value() == 0);
    CHECK(binary.byte_at(uint64(2)).native_value() == 255);
    CHECK(!binary.is_valid_utf8());
    CHECK(binary.length_cp() == uint64(3));
    CHECK(binary.substr_cp(uint64(1), uint64(2)) == binary.slice(uint64(1), uint64(3)));
    CHECK(binary.substr_cp(uint64(1)) == binary.slice(uint64(1), uint64(3)));
    string unicode("a\xc3\xa9\xf0\x9f\x98\x80");
    CHECK(unicode.is_valid_utf8());
    CHECK(unicode.length_cp() == uint64(3));
    CHECK(unicode.substr_cp(uint64(1), uint64(1)).equals("\xc3\xa9"));
    CHECK(unicode.substr_cp(uint64(2)).equals("\xf0\x9f\x98\x80"));
    CHECK(unicode.slice(uint64(99), uint64(100)).empty());
    CHECK(unicode.slice(uint64(2), uint64(1)).empty());
    CHECK(string(nullptr).empty());
    CHECK(string(nullptr, uint64(0)).empty());
    bool failed = false;
    try { string invalid(nullptr, uint64(1)); } catch (...) { failed = true; }
    CHECK(failed);
    failed = false;
    try { (void)binary.byte_at(uint64(3)); } catch (...) { failed = true; }
    CHECK(failed);
    failed = false;
    try { (void)binary.byte_at(uint64(~0ULL)); } catch (...) { failed = true; }
    CHECK(failed);
    start_counting();
    string sliced = long_value.slice(uint64(0), long_value.byte_size());
    const unsigned slice_allocations = stop_counting();
    CHECK(slice_allocations == 1);
    CHECK(sliced == long_value);
    // Failed copy construction/assignment leaves live values usable and leak-free.
    string preserved("keep");
    failed = false;
    fail_allocation = true;
    try { string failed_copy(long_value); } catch (const std::bad_alloc &) { failed = true; }
    fail_allocation = false;
    CHECK(failed);
    CHECK(long_value.equals(long_bytes));
    failed = false;
    fail_allocation = true;
    try { preserved = long_value; } catch (const std::bad_alloc &) { failed = true; }
    fail_allocation = false;
    CHECK(failed);
    CHECK(preserved.equals("keep"));
    failed = false;
    fail_allocation = true;
    try { (void)long_value.slice(uint64(0), long_value.byte_size()); }
    catch (const std::bad_alloc &) { failed = true; }
    fail_allocation = false;
    CHECK(failed);
    string survivor;
    {
        string owner(long_bytes);
        survivor = owner.slice(uint64(0), owner.byte_size());
        owner.clear();
    }
    CHECK(survivor.equals(long_bytes));
    for (unsigned index = 0; index < 1000; ++index) {
        string original(long_value);
        string result(std::move(original));
        original = result.slice(uint64(2), uint64(80));
        result.append(result);
        original.swap(result);
        CHECK(original.byte_size() == long_value.byte_size() + long_value.byte_size());
    }
    std::printf("string bytes=%zu; allocations short=%u long=%u copy=%u move=%u slice=%u\n",
        sizeof(string), short_allocations, long_allocations, copy_allocations, move_allocations, slice_allocations);
}
