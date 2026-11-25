#include "public_api_guard.hpp"
#include "scpp/operators/compare_int8.hpp"
#include "scpp/operators/compare_int16.hpp"
#include "scpp/operators/compare_int32.hpp"
#include "scpp/operators/compare_int64.hpp"
#include "scpp/operators/compare_uint8.hpp"
#include "scpp/operators/compare_uint16.hpp"
#include "scpp/operators/compare_uint32.hpp"
#include "scpp/operators/compare_uint64.hpp"
#include "scpp/operators/compare_float64.hpp"
#include "scpp/conversions/cast_float64_from_int64.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"compare failure %d\n",__LINE__); std::abort(); } } while(false)
int main() {
 counting=true;
 { const int8 a(-128), b(127); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const int16 a(-32768), b(32767); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const int32 a(-2147483647-1), b(2147483647); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const int64 a(-9223372036854775807LL-1), b(9223372036854775807LL); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const uint8 a(0), b(255); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const uint16 a(0), b(65535); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const uint32 a(0), b(4294967295U); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 { const uint64 a(0), b(18446744073709551615ULL); CHECK(numeric_compare(a,b).native_value()==-1); CHECK(numeric_compare(b,a).native_value()==1); CHECK(numeric_compare(a,a).native_value()==0); }
 const float64 values[]={float64(-std::numeric_limits<double>::infinity()),float64(-1.0),float64(-0.0),float64(0.0),float64(1.0),float64(std::numeric_limits<double>::infinity())};
 for(const auto &a:values) for(const auto &b:values) {
    const auto expected=a.native_value()<b.native_value()?-1:a.native_value()>b.native_value()?1:0;
    CHECK(numeric_compare(a,b).native_value()==expected);
 }
 CHECK(numeric_compare(cast_float64(int64(9007199254740993LL)),float64(9007199254740992.0)).native_value()==0);
 counting=false; CHECK(allocations==0);
 const float64 nan(std::numeric_limits<double>::quiet_NaN());
 for(const auto &value:values) {
    bool failed=false;
    try { (void)numeric_compare(nan,value); } catch (...) { failed=true; const auto error=capture_runtime_exception(); CHECK(error.code().equals("unordered_comparison")); CHECK(error.component().equals("scpp::numeric_compare")); CHECK(error.operation().equals("<=>")); } CHECK(failed);
    failed=false;
    try { (void)numeric_compare(value,nan); } catch (...) { failed=true; CHECK(capture_runtime_exception().code().equals("unordered_comparison")); } CHECK(failed);
 }
 bool failed=false; try { (void)numeric_compare(nan,nan); } catch (...) { failed=true; } CHECK(failed);
 std::puts("PASS: integer boundary comparison without subtraction, signed zero, infinity, NaN errors, permitted precision loss; allocations=0");
}
