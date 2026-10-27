#include "scpp/cast.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
#include <cmath>
#include <initializer_list>

static unsigned allocations = 0;
static bool counting = false;
void *operator new(std::size_t size) {
    if (counting) ++allocations;
    if (auto memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
static void check(bool valid, int line) {
    if (!valid) { std::fprintf(stderr, "checked cast failure: %d\n", line); std::abort(); }
}
#define CHECK(x) check(static_cast<bool>(x), __LINE__)
using namespace scpp;

template <typename To, typename From>
static void rejects(const From &value) {
    bool failed = false;
    try { (void)cast<To>(value); }
    catch (const runtime_error &error) {
        CHECK(error.code() == "integer_cast_out_of_range"); failed = true;
    }
    CHECK(failed);
}
int main() {
    using signed8 = int_t<signed char>;
    using unsigned8 = int_t<unsigned char>;
    using unsigned64 = int_t<unsigned long long>;
    CHECK(cast<signed8>(scpp::float_t(127.9)).native_value() == 127);
    CHECK(cast<signed8>(scpp::float_t(-128.9)).native_value() == -128);
    CHECK(cast<unsigned8>(scpp::float_t(-0.9)).native_value() == 0);
    CHECK(cast<unsigned8>(scpp::float_t(255.9)).native_value() == 255);
    rejects<signed8>(scpp::float_t(128.0)); rejects<signed8>(scpp::float_t(-129.0));
    rejects<unsigned8>(scpp::float_t(-1.0)); rejects<unsigned8>(scpp::float_t(256.0));
    const double signed_upper = std::ldexp(1.0,63);
    const double unsigned_upper = std::ldexp(1.0,64);
    CHECK(cast<int_t<>>(scpp::float_t(-signed_upper)).native_value() == std::numeric_limits<long long>::min());
    CHECK(cast<int_t<>>(scpp::float_t(std::nextafter(signed_upper,0.0))).native_value() > 0);
    CHECK(cast<unsigned64>(scpp::float_t(std::nextafter(unsigned_upper,0.0))).native_value() > 0);
    rejects<int_t<>>(scpp::float_t(signed_upper)); rejects<unsigned64>(scpp::float_t(unsigned_upper));
    for (double invalid : {std::numeric_limits<double>::quiet_NaN(),
            std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity(), signed_upper}) {
        rejects<int_t<>>(invalid); rejects<int_t<>>(scpp::float_t(invalid));
        bool failed = false;
        try { (void)cast<int_t<>,double>(invalid); }
        catch (const runtime_error &error) { CHECK(error.code() == "integer_cast_out_of_range"); failed=true; }
        CHECK(failed);
    }
    for (const auto *text : {"1", "+1", "1.", ".5", "+.5", "-0", "1e+2", "1e-2"}) {
        CHECK(std::isfinite(cast<scpp::float_t>(string_t(text)).native_value()));
    }
    for (const auto *text : {"", "+", "+-1", "++1", " 1", "1 ", "1x", "nan", "inf", "-inf", "0x1p2", "+0x1p2", "1e9999", "1e-9999"}) {
        bool failed=false;
        try { (void)cast<scpp::float_t>(string_t(text)); }
        catch (const runtime_error &error) { CHECK(error.code() == "invalid_cast_float_literal"); failed=true; }
        CHECK(failed);
    }
    double untouched = 42;
    CHECK(!detail::parse_double_string_strict(std::string("1\0x",3), untouched));
    CHECK(untouched==42);
    const string_t long_decimal("1.23456789012345678901234567890123456789");
    counting=true;
    for(int i=0;i<100;++i) {
        CHECK(cast<signed8>(scpp::float_t(12.75)).native_value()==12);
        CHECK(cast<scpp::float_t>(long_decimal).native_value()>1.0);
    }
    counting=false;
    CHECK(allocations==0);
    std::printf("PASS: checked floating casts and strict decimal parsing; successful allocations=%u\n",allocations);
}
