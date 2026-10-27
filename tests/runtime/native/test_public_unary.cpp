#include "public_api_guard.hpp"
#include "scpp/operators/positive_int64.hpp"
#include "scpp/operators/positive_float64.hpp"
#include "scpp/operators/positive_mixed.hpp"
#include "scpp/operators/positive_dynamic.hpp"
#include "scpp/operators/negative_int64.hpp"
#include "scpp/operators/negative_float64.hpp"
#include "scpp/operators/negative_mixed.hpp"
#include "scpp/operators/negative_dynamic.hpp"
#include "scpp/operators/complement_int64.hpp"
#include "scpp/operators/complement_mixed.hpp"
#include "scpp/operators/complement_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <cmath>
#include <limits>
#include <new>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"unary failure %d\n",__LINE__); std::abort(); } } while(false)
int main() {
 const auto minimum=std::numeric_limits<long long>::min(), maximum=std::numeric_limits<long long>::max();
 counting=true;
 CHECK(numeric_positive(int64(minimum)).native_value()==minimum);
 CHECK(numeric_negative(int64(maximum)).native_value()==-maximum);
 CHECK(numeric_negative(int64(-maximum)).native_value()==maximum);
 CHECK(numeric_complement(int64(minimum)).native_value()==maximum);
 CHECK(numeric_complement(int64(0)).native_value()==-1);
 const float64 floats[]={float64(-0.0),float64(0.0),float64(-1.5),float64(1.5),float64(std::numeric_limits<double>::infinity()),float64(-std::numeric_limits<double>::infinity())};
 for(const auto &v:floats) {
    const auto positive=numeric_positive(v), negative=numeric_negative(v);
    CHECK(positive.native_value()==v.native_value());
    CHECK(negative.native_value()==-v.native_value());
    CHECK(std::signbit(positive.native_value())==std::signbit(v.native_value()));
    CHECK(std::signbit(negative.native_value())!=std::signbit(v.native_value()));
 }
 CHECK(std::isnan(numeric_negative(float64(std::numeric_limits<double>::quiet_NaN())).native_value()));
 { const mixed value(int64(-42));
    CHECK(numeric_positive(value).get_int().native_value()==-42);
    CHECK(numeric_negative(value).get_int().native_value()==42);
    CHECK(numeric_complement(value).get_int().native_value()==~(-42LL));
    CHECK(value.get_int().native_value()==-42);
    const mixed floating(float64(1.5));
    CHECK(numeric_negative(floating).is_float());
    CHECK(numeric_negative(floating).get_float().native_value()==-1.5);
    CHECK(numeric_positive(floating).get_float().native_value()==1.5);
    int evaluated=0;
    CHECK(numeric_negative(([&]{ ++evaluated; return mixed(int64(2)); })()).get_int().native_value()==-2);
    CHECK(evaluated==1);
 }
 { const dynamic value(int64(-42));
    CHECK(numeric_positive(value).get_int().native_value()==-42);
    CHECK(numeric_negative(value).get_int().native_value()==42);
    CHECK(numeric_complement(value).get_int().native_value()==~(-42LL));
    CHECK(value.get_int().native_value()==-42);
    const dynamic floating(float64(1.5));
    CHECK(numeric_negative(floating).is_float());
    CHECK(numeric_negative(floating).get_float().native_value()==-1.5);
    CHECK(numeric_positive(floating).get_float().native_value()==1.5);
    int evaluated=0;
    CHECK(numeric_negative(([&]{ ++evaluated; return dynamic(int64(2)); })()).get_int().native_value()==-2);
    CHECK(evaluated==1);
 }
 counting=false; CHECK(allocations==0);
 const mixed invalid_mixed[]={mixed(null_t{}),mixed(boolean(false)),mixed(string("12")),make_mixed_table()};
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)numeric_positive(bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)numeric_negative(bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)numeric_complement(bad); } catch (...) { failed=true; } CHECK(failed); }
 { bool failed=false; try { (void)numeric_complement(mixed(float64(1.0))); } catch (...) { failed=true; } CHECK(failed); }
 const dynamic invalid_dynamic[]={dynamic(null_t{}),dynamic(boolean(false)),dynamic(string("12")),make_dynamic_container()};
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)numeric_positive(bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)numeric_negative(bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)numeric_complement(bad); } catch (...) { failed=true; } CHECK(failed); }
 { bool failed=false; try { (void)numeric_complement(dynamic(float64(1.0))); } catch (...) { failed=true; } CHECK(failed); }
 std::puts("PASS: unary value/family preservation, kind rejection, floating signs, operand once; successful allocations=0");
}
