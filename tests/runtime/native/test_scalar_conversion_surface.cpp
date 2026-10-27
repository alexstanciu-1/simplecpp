#include "public_api_guard.hpp"
#include "scpp/conversions/cast_boolean_from_string.hpp"
#include "scpp/conversions/cast_boolean_from_int64.hpp"
#include "scpp/conversions/cast_boolean_from_uint64.hpp"
#include "scpp/conversions/cast_boolean_from_float64.hpp"
#include "scpp/conversions/cast_boolean_from_boolean.hpp"
#include "scpp/conversions/cast_boolean_from_mixed.hpp"
#include "scpp/conversions/cast_boolean_from_dynamic.hpp"
#include "scpp/conversions/cast_int64_from_boolean.hpp"
#include "scpp/conversions/cast_int64_from_mixed.hpp"
#include "scpp/conversions/cast_int64_from_dynamic.hpp"
#include "scpp/conversions/cast_float64_from_boolean.hpp"
#include "scpp/conversions/cast_float64_from_mixed.hpp"
#include "scpp/conversions/cast_float64_from_dynamic.hpp"
#include "scpp/conversions/cast_string_from_int64.hpp"
#include "scpp/conversions/cast_string_from_uint64.hpp"
#include "scpp/conversions/cast_string_from_float64.hpp"
#include "scpp/conversions/cast_string_from_boolean.hpp"
#include "scpp/conversions/cast_string_from_string.hpp"
#include "scpp/conversions/cast_string_from_null.hpp"
#include "scpp/conversions/cast_string_from_mixed.hpp"
#include "scpp/conversions/cast_string_from_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
static void check(bool ok,int line) { if(!ok) { std::fprintf(stderr,"scalar conversion failure %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
template<class F> static void rejects(F f) { bool thrown=false; try { f(); } catch (...) { thrown=true; } CHECK(thrown); }
template<class Box> static void boxed() {
    CHECK(cast_int64(Box(boolean(true))).native_value()==1);
    CHECK(cast_int64(Box(float64(-12.75))).native_value()==-12);
    CHECK(cast_int64(Box(string("42"))).native_value()==42);
    CHECK(cast_float64(Box(string("1.25"))).native_value()==1.25);
    CHECK(cast_float64(Box(boolean(true))).native_value()==1.0);
    CHECK(cast_boolean(Box(int64(-1))));
    CHECK(!cast_boolean(Box(string("false"))));
    CHECK(cast_string(Box(null_t{})).equals(""));
    CHECK(cast_string(Box(boolean(false))).equals(""));
    CHECK(cast_string(Box(int64(42))).equals("42"));
    CHECK(cast_string(Box(float64(1.25))).equals("1.25"));
    rejects([] { (void)cast_int64(Box(null_t{})); });
    rejects([] { (void)cast_float64(Box(null_t{})); });
    rejects([] { (void)cast_boolean(Box(null_t{})); });
    rejects([] { (void)cast_boolean(Box(string("yes"))); });
    rejects([] { (void)cast_int64(Box(float64(std::numeric_limits<double>::infinity()))); });
    const auto owned=cast_string(Box(string("a long boxed string whose lifetime must outlive its temporary source")));
    CHECK(owned.equals("a long boxed string whose lifetime must outlive its temporary source"));
}
int main() {
    CHECK(!cast_boolean(string(""))); CHECK(!cast_boolean(string("0")));
    CHECK(cast_boolean(string("true"))); CHECK(cast_boolean(string("1")));
    rejects([] { (void)cast_boolean(string("yes")); });
    CHECK(cast_string(boolean(true)).equals("1")); CHECK(cast_string(boolean(false)).equals(""));
    CHECK(cast_string(null_t{}).equals("")); CHECK(cast_string(int64(-42)).equals("-42"));
    CHECK(cast_string(uint64(std::numeric_limits<unsigned long long>::max())).equals("18446744073709551615"));
    CHECK(cast_string(float64(1.25)).equals("1.25")); CHECK(cast_string(string("hello")).equals("hello"));
    CHECK(cast_int64(boolean(true)).native_value()==1); CHECK(cast_float64(boolean(false)).native_value()==0);
    boxed<mixed>(); boxed<dynamic>();
    const auto table=make_mixed_table(); const auto container=make_dynamic_container();
    rejects([&] { (void)cast_boolean(table); }); rejects([&] { (void)cast_int64(table); });
    rejects([&] { (void)cast_float64(table); }); rejects([&] { (void)cast_string(table); });
    rejects([&] { (void)cast_boolean(container); }); rejects([&] { (void)cast_int64(container); });
    rejects([&] { (void)cast_float64(container); }); rejects([&] { (void)cast_string(container); });
    const mixed m(int64(42)); const dynamic d(int64(42)); const string truth("true");
    counting=true;
    for(int i=0;i<100;++i) {
        CHECK(cast_int64(m).native_value()==42); CHECK(cast_int64(d).native_value()==42);
        CHECK(cast_float64(m).native_value()==42); CHECK(cast_float64(d).native_value()==42);
        CHECK(cast_boolean(m)); CHECK(cast_boolean(d)); CHECK(cast_boolean(truth));
        CHECK(cast_boolean(boolean(true))); CHECK(cast_boolean(int64(-1)));
        CHECK(cast_boolean(uint64(1))); CHECK(!cast_boolean(float64(0)));
    }
    counting=false; CHECK(allocations==0);
    const string long_text("a long string conversion should allocate only its owned result storage");
    counting=true; const auto copied=cast_string(long_text); counting=false;
    CHECK(copied.equals("a long string conversion should allocate only its owned result storage"));
    std::printf("PASS: scalar/boxed conversions, owned string lifetimes; numeric/bool allocations=0, long string copy allocations=%u\n",allocations);
}
