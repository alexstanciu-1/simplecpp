#include "public_api_guard.hpp"
#include "scpp/identity/strict_identical_int64.hpp"
#include "scpp/identity/strict_identical_float64.hpp"
#include "scpp/identity/strict_identical_boolean.hpp"
#include "scpp/identity/strict_identical_string.hpp"
#include "scpp/identity/strict_identical_null.hpp"
#include "scpp/identity/strict_identical_mixed.hpp"
#include "scpp/identity/strict_identical_dynamic.hpp"
#include "scpp/identity/strict_not_identical_int64.hpp"
#include "scpp/identity/strict_not_identical_float64.hpp"
#include "scpp/identity/strict_not_identical_boolean.hpp"
#include "scpp/identity/strict_not_identical_string.hpp"
#include "scpp/identity/strict_not_identical_null.hpp"
#include "scpp/identity/strict_not_identical_mixed.hpp"
#include "scpp/identity/strict_not_identical_dynamic.hpp"
#include "scpp/operators/equal_mixed.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
static void check(bool ok,int line) { if(!ok) { std::fprintf(stderr,"identity check failed %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
template<class Box> static void matrix() {
    const Box values[]={Box(null_t{}),Box(boolean(false)),Box(boolean(true)),Box(int64(0)),Box(int64(1)),
        Box(float64(0)),Box(float64(1)),Box(string("")),Box(string("1"))};
    for(unsigned i=0;i<9;++i) for(unsigned j=0;j<9;++j) {
        CHECK(static_cast<bool>(strict_identical(values[i],values[j]))==(i==j));
        CHECK(static_cast<bool>(strict_not_identical(values[i],values[j]))==(i!=j));
    }
    CHECK(strict_identical(Box(float64(0.0)),Box(float64(-0.0))));
    const Box nan{float64(std::numeric_limits<double>::quiet_NaN())};
    CHECK(!strict_identical(nan,nan)); CHECK(strict_not_identical(nan,nan));
}
int main() {
    CHECK(strict_identical(int64(42),int64(42))); CHECK(strict_not_identical(int64(42),int64(43)));
    CHECK(strict_identical(boolean(false),boolean(false))); CHECK(strict_not_identical(boolean(false),boolean(true)));
    CHECK(strict_identical(null_t{},null_t{})); CHECK(!strict_not_identical(null_t{},null_t{}));
    CHECK(strict_identical(float64(0.0),float64(-0.0))); CHECK(strict_not_identical(float64(1),float64(2)));
    const float64 nan(std::numeric_limits<double>::quiet_NaN());
    CHECK(!strict_identical(nan,nan)); CHECK(strict_not_identical(nan,nan));
    CHECK(strict_identical(float64(std::numeric_limits<double>::infinity()),float64(std::numeric_limits<double>::infinity())));
    const string binary("a\0b",uint64(3)); CHECK(strict_identical(binary,binary));
    CHECK(strict_not_identical(binary,string("a\0c",uint64(3))));
    matrix<mixed>(); matrix<dynamic>();
    CHECK(value_equal(mixed(int64(1)),mixed(float64(1))));
    CHECK(!strict_identical(mixed(int64(1)),mixed(float64(1))));
    auto a=make_mixed_table(), b=make_mixed_table();
    CHECK(strict_identical(a,b)); a.set(string("x"),mixed(int64(1)));
    CHECK(strict_not_identical(a,b)); b.set(string("x"),mixed(int64(1))); CHECK(strict_identical(a,b));
    auto first=make_dynamic_container(), second=make_dynamic_container(); const auto alias=first;
    CHECK(strict_identical(first,alias)); CHECK(strict_not_identical(first,second));
    const string long_text("long strings compare their complete bytes without allocating copies or parsing numbers");
    const mixed m(long_text); const dynamic d(long_text);
    counting=true;
    for(int i=0;i<100;++i) {
        CHECK(strict_identical(long_text,long_text)); CHECK(strict_identical(m,m)); CHECK(strict_identical(d,d));
        CHECK(!strict_not_identical(m,m)); CHECK(!strict_not_identical(d,d));
        CHECK(strict_identical(first,alias));
    }
    counting=false; CHECK(allocations==0);
    counting=true; CHECK(strict_identical(a,b)); counting=false;
    std::printf("PASS: strict identity, both 81-pair boxed matrices, NaN and container distinctions; scalar allocations=0, one-entry mixed-table allocations=%u\n",allocations);
}
