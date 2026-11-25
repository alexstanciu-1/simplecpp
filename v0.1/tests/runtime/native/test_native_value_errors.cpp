#include "scpp/native_value_errors.hpp"
#include "operators/arithmetic/integer_shift.hpp"
#include "scpp/operators/shift_left_int64.hpp"
#include <cstdint>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <type_traits>
static_assert(std::is_base_of_v<std::runtime_error, scpp::ValueError>);
static_assert(std::is_base_of_v<std::runtime_error, scpp::TypeError>);
#define CHECK(x) do { if (!(x)) std::abort(); } while (false)
int main()
{
    scpp::detail::require_shift_count<std::int64_t>(0);
    scpp::detail::require_shift_count<std::int64_t>(63);
    unsigned caught = 0;
    for (int count : {-1, 64}) {
        try { scpp::detail::require_shift_count<std::int64_t>(count); }
        catch (const scpp::ValueError &error) {
            CHECK(std::strstr(error.what(), "Shift count") != nullptr); ++caught;
        }
    }
    try { throw scpp::TypeError("unchanged native type error"); }
    catch (const std::runtime_error &error) { CHECK(std::strcmp(error.what(), "unchanged native type error") == 0); ++caught; }
    try { (void)scpp::integer_shift_left(scpp::int64(1), scpp::int64(-1)); }
    catch (const scpp::ValueError &error) { CHECK(std::strstr(error.what(), "Shift count") != nullptr); ++caught; }
    CHECK(caught == 4);
    std::puts("PASS: focused native error identities, messages and checked shift boundary");
}
