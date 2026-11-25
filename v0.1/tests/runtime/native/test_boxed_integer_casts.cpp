#include "public_api_guard.hpp"
#include "scpp/conversions/cast_int8_from_boolean.hpp"
#include "scpp/conversions/cast_int16_from_boolean.hpp"
#include "scpp/conversions/cast_int32_from_boolean.hpp"
#include "scpp/conversions/cast_uint8_from_boolean.hpp"
#include "scpp/conversions/cast_uint16_from_boolean.hpp"
#include "scpp/conversions/cast_uint32_from_boolean.hpp"
#include "scpp/conversions/cast_uint64_from_boolean.hpp"
#include "scpp/conversions/cast_int8_from_mixed.hpp"
#include "scpp/conversions/cast_int16_from_mixed.hpp"
#include "scpp/conversions/cast_int32_from_mixed.hpp"
#include "scpp/conversions/cast_uint8_from_mixed.hpp"
#include "scpp/conversions/cast_uint16_from_mixed.hpp"
#include "scpp/conversions/cast_uint32_from_mixed.hpp"
#include "scpp/conversions/cast_uint64_from_mixed.hpp"
#include "scpp/conversions/cast_int8_from_dynamic.hpp"
#include "scpp/conversions/cast_int16_from_dynamic.hpp"
#include "scpp/conversions/cast_int32_from_dynamic.hpp"
#include "scpp/conversions/cast_uint8_from_dynamic.hpp"
#include "scpp/conversions/cast_uint16_from_dynamic.hpp"
#include "scpp/conversions/cast_uint32_from_dynamic.hpp"
#include "scpp/conversions/cast_uint64_from_dynamic.hpp"
#include "scpp/conversions/cast_uint64_from_float64.hpp"
#include "scpp/conversions/cast_int8_from_float64.hpp"
#include "scpp/conversions/cast_int8_from_int64.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
static void check(bool ok,int line) { if(!ok) { std::fprintf(stderr,"boxed integer cast failure %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
template<class F> static void range_error(F f) {
    bool thrown=false;
    try { f(); } catch (...) { CHECK(capture_runtime_exception().code().equals("integer_cast_out_of_range")); thrown=true; }
    CHECK(thrown);
}
template<class Box> static void boundaries() {
    CHECK(cast_uint64(Box(float64(9223372036854775808.0))).native_value()==cast_uint64(float64(9223372036854775808.0)).native_value());
    CHECK(cast_uint64(Box(string("18446744073709551615"))).native_value()==std::numeric_limits<unsigned long long>::max());
    CHECK(cast_int8(Box(int64(127))).native_value()==cast_int8(int64(127)).native_value());
    CHECK(cast_int8(Box(float64(-128.9))).native_value()==cast_int8(float64(-128.9)).native_value());
    CHECK(cast_uint8(Box(boolean(true))).native_value()==1);
    CHECK(cast_uint8(Box(boolean(false))).native_value()==0);
    range_error([] { (void)cast_uint64(Box(int64(-1))); });
    range_error([] { (void)cast_int8(Box(int64(128))); });
    range_error([] { (void)cast_int8(Box(float64(128.0))); });
    range_error([] { (void)cast_uint32(Box(float64(std::numeric_limits<double>::quiet_NaN()))); });
    range_error([] { (void)cast_uint64(Box(float64(18446744073709551616.0))); });
}
int main() {
    boundaries<mixed>(); boundaries<dynamic>();
    const mixed m(int64(12)); const dynamic d(int64(12));
    const string maximum("18446744073709551615"); const mixed ms(maximum); const dynamic ds(maximum);
    counting=true;
    for(int i=0;i<100;++i) {
        CHECK(cast_int8(boolean(true)).native_value()==1);
        CHECK(cast_int16(boolean(true)).native_value()==1);
        CHECK(cast_int32(boolean(true)).native_value()==1);
        CHECK(cast_uint8(boolean(true)).native_value()==1);
        CHECK(cast_uint16(boolean(true)).native_value()==1);
        CHECK(cast_uint32(boolean(true)).native_value()==1);
        CHECK(cast_uint64(boolean(true)).native_value()==1);
        CHECK(cast_int8(m).native_value()==12);
        CHECK(cast_int16(m).native_value()==12);
        CHECK(cast_int32(m).native_value()==12);
        CHECK(cast_uint8(m).native_value()==12);
        CHECK(cast_uint16(m).native_value()==12);
        CHECK(cast_uint32(m).native_value()==12);
        CHECK(cast_uint64(m).native_value()==12);
        CHECK(cast_int8(d).native_value()==12);
        CHECK(cast_int16(d).native_value()==12);
        CHECK(cast_int32(d).native_value()==12);
        CHECK(cast_uint8(d).native_value()==12);
        CHECK(cast_uint16(d).native_value()==12);
        CHECK(cast_uint32(d).native_value()==12);
        CHECK(cast_uint64(d).native_value()==12);
        CHECK(cast_uint64(ms).native_value()==std::numeric_limits<unsigned long long>::max());
        CHECK(cast_uint64(ds).native_value()==std::numeric_limits<unsigned long long>::max());
    }
    counting=false; CHECK(allocations==0);
    CHECK(m.get_int().native_value()==12 && d.get_int().native_value()==12);
    std::printf("PASS: preserved boxed origins, all integer widths, checked failures; successful allocations=%u\n",allocations);
}
