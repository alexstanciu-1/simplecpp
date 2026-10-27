#include "public_api_guard.hpp"
#include "scpp/error_value.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <utility>
#include <type_traits>
#include <initializer_list>

[[noreturn]] void throw_error_fixture();
bool verify_error_fixture(const scpp::exception_info &);
[[noreturn]] void throw_foreign_fixture();
bool active_foreign_fixture();
static unsigned allocations = 0;
static bool counting = false;
static int fail_after = -1;
static unsigned heap_allocations = 0;
static unsigned last_heap_allocations = 0;
#ifdef SCPP_MEASURE_GLIBC_ALLOCATIONS
#ifndef __GLIBC__
#error This diagnostic allocator probe requires glibc and must not run with sanitizers.
#endif
extern "C" void *__libc_malloc(std::size_t) noexcept;
extern "C" void *__libc_calloc(std::size_t, std::size_t) noexcept;
extern "C" void *__libc_realloc(void *, std::size_t) noexcept;
extern "C" void *malloc(std::size_t size) noexcept
{
    if (counting) { ++heap_allocations; }
    return __libc_malloc(size);
}
extern "C" void *calloc(std::size_t count, std::size_t size) noexcept
{
    if (counting) { ++heap_allocations; }
    return __libc_calloc(count, size);
}
extern "C" void *realloc(void *memory, std::size_t size) noexcept
{
    if (counting) { ++heap_allocations; }
    return __libc_realloc(memory, size);
}
#endif
void *operator new(std::size_t size)
{
    if (fail_after == 0) { throw std::bad_alloc(); }
    if (fail_after > 0) { --fail_after; }
    if (counting) { ++allocations; }
    if (void *memory = std::malloc(size == 0 ? 1 : size)) { return memory; }
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
static void check(bool condition, int line)
{
    if (!condition) { std::fprintf(stderr, "error API check failed at %d\n", line); std::abort(); }
}
#define CHECK(x) check(static_cast<bool>(x), __LINE__)
static void start() { allocations = 0; heap_allocations = 0; counting = true; }
static unsigned stop() { counting = false; last_heap_allocations = heap_allocations; return allocations; }
using namespace scpp;
static unsigned capture_allocations;
static unsigned capture_heap_allocations;
static exception_info retained()
{
    try { throw_error_fixture(); }
    catch (...) {
        start();
        auto result = capture_runtime_exception();
        capture_allocations = stop();
        capture_heap_allocations = last_heap_allocations;
        return result;
    }
}
int main()
{
    static_assert(!std::is_default_constructible_v<exception_info>);
    static_assert(std::is_nothrow_copy_constructible_v<exception_info>);
    start();
    error_value absent;
    auto blank = error_value::make(string());
    CHECK(!absent);
    CHECK(!error_value::no_error);
    CHECK(blank);
    CHECK(blank.get_message().empty());
    CHECK(absent.get_file().empty());
    CHECK(absent.get_line() == int64(0));
    unsigned empty_allocations = stop();
    CHECK(empty_allocations == 0);
    string long_text("This is a long string that exceeds inline short string capacity comfortably");
    start();
    auto value = error_value::make(long_text, int64(42), long_text);
    unsigned make_allocations = stop();
    CHECK(make_allocations == 2);
    start();
    auto copy = value;
    unsigned copy_allocations = stop();
    CHECK(copy_allocations == 2);
    start();
    auto moved = std::move(copy);
    unsigned move_allocations = stop();
    CHECK(move_allocations == 0);
    CHECK(moved.get_line() == int64(42));
    start();
    auto message = value.get_message();
    unsigned getter_allocations = stop();
    CHECK(getter_allocations == 1);
    CHECK(message == long_text);
    value = error_value();
    CHECK(message == long_text);
    CHECK(moved.get_file() == long_text);
    auto *self = &moved;
    moved = *self;
    moved = std::move(*self);
    CHECK(moved.get_message() == long_text);
    copy = moved;
    copy = std::move(moved);
    CHECK(copy.get_line() == int64(42));
    // Fail the second string copy; the already constructed first field must unwind.
    for (int allowed = 0; allowed < 2; ++allowed) {
        bool failed = false;
        fail_after = allowed;
        try { auto failed_value = error_value::make(long_text, int64(7), long_text); }
        catch (const std::bad_alloc &) { failed = true; }
        fail_after = -1;
        CHECK(failed);
    }
    for (int allowed = 0; allowed < 2; ++allowed) {
        bool failed = false;
        fail_after = allowed;
        try { auto failed_copy = copy; }
        catch (const std::bad_alloc &) { failed = true; }
        fail_after = -1;
        CHECK(failed);
        CHECK(copy.get_file() == long_text);
    }
    auto info = retained();
    start();
    auto info_copy = info;
    auto rvalue_copy = std::move(info);
    info_copy = rvalue_copy;
    auto *info_self = &info_copy;
    info_copy = *info_self;
    unsigned handle_allocations = stop();
    [[maybe_unused]] unsigned handle_heap_allocations = last_heap_allocations;
    CHECK(handle_allocations == 0);
    CHECK(info.code().equals("test_code")); // rvalue copy leaves the source valid
    CHECK(info.component().equals("test_component"));
    CHECK(info.operation().equals("test_operation"));
    CHECK(info.detail_count() == int64(2));
    CHECK(info.detail_key(int64(0)).equals("field"));
    CHECK(info.detail_value(int64(1)).equals("27"));
    CHECK(verify_error_fixture(info_copy));
    try { throw 7; } catch (...) { CHECK(verify_error_fixture(info)); }
    start();
    auto retained_message = info.message();
    unsigned inspection_allocations = stop();
    [[maybe_unused]] unsigned inspection_heap_allocations = last_heap_allocations;
    CHECK(inspection_allocations == 1);
    for (auto index : {-1LL, 2LL}) {
        bool failed = false;
        try { (void)info.detail_key(int64(index)); }
        catch (...) { failed = capture_runtime_exception().code().equals("index_out_of_range").native_value(); }
        CHECK(failed);
    }
    bool failed = false;
    try { (void)info.trace_address(info.trace_count()); }
    catch (...) { failed = capture_runtime_exception().code().equals("index_out_of_range").native_value(); }
    CHECK(failed);
    CHECK(verify_error_fixture(info));
    failed = false;
    try { (void)capture_runtime_exception(); }
    catch (...) { failed = capture_runtime_exception().code().equals("no_active_exception").native_value(); }
    CHECK(failed);
    failed = false;
    try {
        try { throw_foreign_fixture(); }
        catch (...) { (void)capture_runtime_exception(); }
    } catch (...) { failed = active_foreign_fixture(); }
    CHECK(failed);
    fail_after = 0;
    failed = false;
    try { (void)info.message(); } catch (const std::bad_alloc &) { failed = true; }
    fail_after = -1;
    CHECK(failed);
    CHECK(verify_error_fixture(info));
#ifdef SCPP_MEASURE_GLIBC_ALLOCATIONS
    std::printf("glibc heap calls capture=%u handle_copy=%u long_message_inspection=%u\n",
        capture_heap_allocations, handle_heap_allocations, inspection_heap_allocations);
#endif
    std::printf("sizes error=%zu exception=%zu; allocations empty=%u make=%u copy=%u move=%u getter=%u capture=%u handle_copy=%u inspection=%u\n",
        sizeof(error_value), sizeof(exception_info), empty_allocations, make_allocations, copy_allocations,
        move_allocations, getter_allocations, capture_allocations, handle_allocations, inspection_allocations);
}
