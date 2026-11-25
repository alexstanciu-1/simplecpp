#include "public_api_guard.hpp"
#include "scpp/operators/add_int8.hpp"
#include "scpp/operators/add_int16.hpp"
#include "scpp/operators/add_int32.hpp"
#include "scpp/operators/add_int64.hpp"
#include "scpp/operators/add_uint8.hpp"
#include "scpp/operators/add_uint16.hpp"
#include "scpp/operators/add_uint32.hpp"
#include "scpp/operators/add_uint64.hpp"
#include "scpp/operators/add_float64.hpp"
#include "scpp/operators/subtract_int8.hpp"
#include "scpp/operators/subtract_int16.hpp"
#include "scpp/operators/subtract_int32.hpp"
#include "scpp/operators/subtract_int64.hpp"
#include "scpp/operators/subtract_uint8.hpp"
#include "scpp/operators/subtract_uint16.hpp"
#include "scpp/operators/subtract_uint32.hpp"
#include "scpp/operators/subtract_uint64.hpp"
#include "scpp/operators/subtract_float64.hpp"
#include "scpp/operators/multiply_int8.hpp"
#include "scpp/operators/multiply_int16.hpp"
#include "scpp/operators/multiply_int32.hpp"
#include "scpp/operators/multiply_int64.hpp"
#include "scpp/operators/multiply_uint8.hpp"
#include "scpp/operators/multiply_uint16.hpp"
#include "scpp/operators/multiply_uint32.hpp"
#include "scpp/operators/multiply_uint64.hpp"
#include "scpp/operators/multiply_float64.hpp"
#include "scpp/operators/divide_int8.hpp"
#include "scpp/operators/divide_int16.hpp"
#include "scpp/operators/divide_int32.hpp"
#include "scpp/operators/divide_int64.hpp"
#include "scpp/operators/divide_uint8.hpp"
#include "scpp/operators/divide_uint16.hpp"
#include "scpp/operators/divide_uint32.hpp"
#include "scpp/operators/divide_uint64.hpp"
#include "scpp/operators/divide_float64.hpp"
#include "scpp/operators/remainder_int8.hpp"
#include "scpp/operators/remainder_int16.hpp"
#include "scpp/operators/remainder_int32.hpp"
#include "scpp/operators/remainder_int64.hpp"
#include "scpp/operators/remainder_uint8.hpp"
#include "scpp/operators/remainder_uint16.hpp"
#include "scpp/operators/remainder_uint32.hpp"
#include "scpp/operators/remainder_uint64.hpp"
#include "scpp/operators/numeric_equal_int8.hpp"
#include "scpp/operators/numeric_equal_int16.hpp"
#include "scpp/operators/numeric_equal_int32.hpp"
#include "scpp/operators/numeric_equal_int64.hpp"
#include "scpp/operators/numeric_equal_uint8.hpp"
#include "scpp/operators/numeric_equal_uint16.hpp"
#include "scpp/operators/numeric_equal_uint32.hpp"
#include "scpp/operators/numeric_equal_uint64.hpp"
#include "scpp/operators/numeric_equal_float64.hpp"
#include "scpp/operators/numeric_not_equal_int8.hpp"
#include "scpp/operators/numeric_not_equal_int16.hpp"
#include "scpp/operators/numeric_not_equal_int32.hpp"
#include "scpp/operators/numeric_not_equal_int64.hpp"
#include "scpp/operators/numeric_not_equal_uint8.hpp"
#include "scpp/operators/numeric_not_equal_uint16.hpp"
#include "scpp/operators/numeric_not_equal_uint32.hpp"
#include "scpp/operators/numeric_not_equal_uint64.hpp"
#include "scpp/operators/numeric_not_equal_float64.hpp"
#include "scpp/operators/less_int8.hpp"
#include "scpp/operators/less_int16.hpp"
#include "scpp/operators/less_int32.hpp"
#include "scpp/operators/less_int64.hpp"
#include "scpp/operators/less_uint8.hpp"
#include "scpp/operators/less_uint16.hpp"
#include "scpp/operators/less_uint32.hpp"
#include "scpp/operators/less_uint64.hpp"
#include "scpp/operators/less_float64.hpp"
#include "scpp/operators/less_equal_int8.hpp"
#include "scpp/operators/less_equal_int16.hpp"
#include "scpp/operators/less_equal_int32.hpp"
#include "scpp/operators/less_equal_int64.hpp"
#include "scpp/operators/less_equal_uint8.hpp"
#include "scpp/operators/less_equal_uint16.hpp"
#include "scpp/operators/less_equal_uint32.hpp"
#include "scpp/operators/less_equal_uint64.hpp"
#include "scpp/operators/less_equal_float64.hpp"
#include "scpp/operators/greater_int8.hpp"
#include "scpp/operators/greater_int16.hpp"
#include "scpp/operators/greater_int32.hpp"
#include "scpp/operators/greater_int64.hpp"
#include "scpp/operators/greater_uint8.hpp"
#include "scpp/operators/greater_uint16.hpp"
#include "scpp/operators/greater_uint32.hpp"
#include "scpp/operators/greater_uint64.hpp"
#include "scpp/operators/greater_float64.hpp"
#include "scpp/operators/greater_equal_int8.hpp"
#include "scpp/operators/greater_equal_int16.hpp"
#include "scpp/operators/greater_equal_int32.hpp"
#include "scpp/operators/greater_equal_int64.hpp"
#include "scpp/operators/greater_equal_uint8.hpp"
#include "scpp/operators/greater_equal_uint16.hpp"
#include "scpp/operators/greater_equal_uint32.hpp"
#include "scpp/operators/greater_equal_uint64.hpp"
#include "scpp/operators/greater_equal_float64.hpp"
#include <initializer_list>

#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
static void check(bool valid,int line) { if(!valid) { std::fprintf(stderr,"numeric check failed: %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
template<class T> static void ordinary() {
    const T a(7), b(3);
    CHECK(numeric_add(a,b).native_value()==10);
    CHECK(numeric_subtract(a,b).native_value()==4);
    CHECK(numeric_multiply(a,b).native_value()==21);
    CHECK(numeric_equal(a,a)); CHECK(!numeric_equal(a,b));
    CHECK(numeric_not_equal(a,b)); CHECK(!numeric_not_equal(a,a));
    CHECK(numeric_less(b,a)); CHECK(!numeric_less(a,a));
    CHECK(numeric_less_equal(a,a)); CHECK(!numeric_less_equal(a,b));
    CHECK(numeric_greater(a,b)); CHECK(!numeric_greater(a,a));
    CHECK(numeric_greater_equal(a,a)); CHECK(!numeric_greater_equal(b,a));
}
template<class T> static void integers() {
    ordinary<T>();
    CHECK(numeric_divide(T(7),T(3)).native_value()==2);
    CHECK(numeric_remainder(T(7),T(3)).native_value()==1);
    for(int op=0;op<2;++op) {
        bool failed=false;
        try { if(op==0) (void)numeric_divide(T(1),T(0)); else (void)numeric_remainder(T(1),T(0)); }
        catch (...) { auto error=capture_runtime_exception(); CHECK(error.code().equals(op==0?"division_by_zero":"modulo_by_zero")); failed=true; }
        CHECK(failed);
    }
}
int main() {
    integers<int8>(); integers<int16>(); integers<int32>(); integers<int64>();
    integers<uint8>(); integers<uint16>(); integers<uint32>(); integers<uint64>();
    ordinary<float64>();
    CHECK(numeric_divide(float64(7.5),float64(3.0)).native_value()==2.5);
    for(double zero : {0.0,-0.0}) {
        bool failed=false;
        try { (void)numeric_divide(float64(1.0),float64(zero)); }
        catch (...) { auto error=capture_runtime_exception(); CHECK(error.code().equals("division_by_zero")); failed=true; }
        CHECK(failed);
    }
    CHECK(numeric_divide(int64(-7),int64(3)).native_value()==-2);
    CHECK(numeric_remainder(int64(-7),int64(3)).native_value()==-1);
    CHECK(numeric_add(uint64(std::numeric_limits<unsigned long long>::max()),uint64(1)).native_value()==0);
    const float64 nan{std::numeric_limits<double>::quiet_NaN()}, one(1.0);
    CHECK(!numeric_equal(nan,nan)); CHECK(numeric_not_equal(nan,nan));
    CHECK(!numeric_less(nan,one)); CHECK(!numeric_less_equal(nan,one));
    CHECK(!numeric_greater(nan,one)); CHECK(!numeric_greater_equal(nan,one));
    const float64 inf{std::numeric_limits<double>::infinity()};
    CHECK(numeric_greater(inf,one));
    // These are the explicit operands selected by the common-type plan.
    const int16 original(-2); const int32 other(70000);
    CHECK(numeric_add(int64(original.native_value()),int64(other.native_value())).native_value()==69998);
    counting=true;
    for(int i=0;i<100;++i) { ordinary<int64>(); ordinary<float64>(); CHECK(numeric_divide(int64(8),int64(2)).native_value()==4); }
    counting=false; CHECK(allocations==0);
    std::printf("PASS: nine numeric types, checked zero divisors, NaN, signed remainder, unsigned wrap; successful allocations=%u\n",allocations);
}
