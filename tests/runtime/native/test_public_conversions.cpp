#include "public_api_guard.hpp"
#include "scpp/conversions/cast_int8_from_int64.hpp"
#include "scpp/conversions/cast_int16_from_int64.hpp"
#include "scpp/conversions/cast_int32_from_int64.hpp"
#include "scpp/conversions/cast_int64_from_int64.hpp"
#include "scpp/conversions/cast_uint8_from_int64.hpp"
#include "scpp/conversions/cast_uint16_from_int64.hpp"
#include "scpp/conversions/cast_uint32_from_int64.hpp"
#include "scpp/conversions/cast_uint64_from_int64.hpp"
#include "scpp/conversions/cast_float64_from_int64.hpp"
#include "scpp/conversions/cast_int8_from_uint64.hpp"
#include "scpp/conversions/cast_int16_from_uint64.hpp"
#include "scpp/conversions/cast_int32_from_uint64.hpp"
#include "scpp/conversions/cast_int64_from_uint64.hpp"
#include "scpp/conversions/cast_uint8_from_uint64.hpp"
#include "scpp/conversions/cast_uint16_from_uint64.hpp"
#include "scpp/conversions/cast_uint32_from_uint64.hpp"
#include "scpp/conversions/cast_uint64_from_uint64.hpp"
#include "scpp/conversions/cast_float64_from_uint64.hpp"
#include "scpp/conversions/cast_int8_from_float64.hpp"
#include "scpp/conversions/cast_int16_from_float64.hpp"
#include "scpp/conversions/cast_int32_from_float64.hpp"
#include "scpp/conversions/cast_int64_from_float64.hpp"
#include "scpp/conversions/cast_uint8_from_float64.hpp"
#include "scpp/conversions/cast_uint16_from_float64.hpp"
#include "scpp/conversions/cast_uint32_from_float64.hpp"
#include "scpp/conversions/cast_uint64_from_float64.hpp"
#include "scpp/conversions/cast_float64_from_float64.hpp"
#include "scpp/conversions/cast_int8_from_string.hpp"
#include "scpp/conversions/cast_int16_from_string.hpp"
#include "scpp/conversions/cast_int32_from_string.hpp"
#include "scpp/conversions/cast_int64_from_string.hpp"
#include "scpp/conversions/cast_uint8_from_string.hpp"
#include "scpp/conversions/cast_uint16_from_string.hpp"
#include "scpp/conversions/cast_uint32_from_string.hpp"
#include "scpp/conversions/cast_uint64_from_string.hpp"
#include "scpp/conversions/cast_float64_from_string.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
static unsigned allocations = 0;
static bool counting = false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
static void check(bool valid,int line) { if(!valid) { std::fprintf(stderr,"conversion check failed: %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
int main() {
    const string text("12"), maximum("18446744073709551615");
    counting = true;
    for (int iteration=0; iteration<100; ++iteration) {
        CHECK(cast_int8(int64(12)).native_value()==12);
        CHECK(cast_int16(int64(12)).native_value()==12);
        CHECK(cast_int32(int64(12)).native_value()==12);
        CHECK(cast_int64(int64(12)).native_value()==12);
        CHECK(cast_uint8(int64(12)).native_value()==12);
        CHECK(cast_uint16(int64(12)).native_value()==12);
        CHECK(cast_uint32(int64(12)).native_value()==12);
        CHECK(cast_uint64(int64(12)).native_value()==12);
        CHECK(cast_float64(int64(12)).native_value()==12);
        CHECK(cast_int8(uint64(12)).native_value()==12);
        CHECK(cast_int16(uint64(12)).native_value()==12);
        CHECK(cast_int32(uint64(12)).native_value()==12);
        CHECK(cast_int64(uint64(12)).native_value()==12);
        CHECK(cast_uint8(uint64(12)).native_value()==12);
        CHECK(cast_uint16(uint64(12)).native_value()==12);
        CHECK(cast_uint32(uint64(12)).native_value()==12);
        CHECK(cast_uint64(uint64(12)).native_value()==12);
        CHECK(cast_float64(uint64(12)).native_value()==12);
        CHECK(cast_int8(float64(12)).native_value()==12);
        CHECK(cast_int16(float64(12)).native_value()==12);
        CHECK(cast_int32(float64(12)).native_value()==12);
        CHECK(cast_int64(float64(12)).native_value()==12);
        CHECK(cast_uint8(float64(12)).native_value()==12);
        CHECK(cast_uint16(float64(12)).native_value()==12);
        CHECK(cast_uint32(float64(12)).native_value()==12);
        CHECK(cast_uint64(float64(12)).native_value()==12);
        CHECK(cast_float64(float64(12)).native_value()==12);
        CHECK(cast_int8(text).native_value()==12);
        CHECK(cast_int16(text).native_value()==12);
        CHECK(cast_int32(text).native_value()==12);
        CHECK(cast_int64(text).native_value()==12);
        CHECK(cast_uint8(text).native_value()==12);
        CHECK(cast_uint16(text).native_value()==12);
        CHECK(cast_uint32(text).native_value()==12);
        CHECK(cast_uint64(text).native_value()==12);
        CHECK(cast_float64(text).native_value()==12);
        CHECK(cast_uint64(maximum).native_value()==std::numeric_limits<unsigned long long>::max());
    }
    counting = false; CHECK(allocations==0);
    CHECK(cast_int8(float64(-128.9)).native_value()==-128);
    CHECK(cast_uint8(float64(-0.9)).native_value()==0);
    CHECK(cast_float64(uint64(std::numeric_limits<unsigned long long>::max())).native_value()>1e19);
    bool failed=false;
    try { (void)cast_int64(uint64(std::numeric_limits<unsigned long long>::max())); }
    catch (...) { CHECK(capture_runtime_exception().code().equals("integer_cast_out_of_range")); failed=true; }
    CHECK(failed); failed=false;
    try { (void)cast_uint8(int64(-1)); }
    catch (...) { CHECK(capture_runtime_exception().code().equals("integer_cast_out_of_range")); failed=true; }
    CHECK(failed); failed=false;
    try { (void)cast_int32(float64(std::numeric_limits<double>::infinity())); }
    catch (...) { CHECK(capture_runtime_exception().code().equals("integer_cast_out_of_range")); failed=true; }
    CHECK(failed); failed=false;
    try { (void)cast_float64(string("0x1p2")); }
    catch (...) { CHECK(capture_runtime_exception().code().equals("invalid_cast_float_literal")); failed=true; }
    CHECK(failed);
    std::printf("PASS: 36 public checked casts, range errors and zero successful allocations\n");
}
