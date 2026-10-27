#include "public_api_guard.hpp"
#include "scpp/conditions/condition_truthy_int8.hpp"
#include "scpp/conditions/condition_truthy_int16.hpp"
#include "scpp/conditions/condition_truthy_int32.hpp"
#include "scpp/conditions/condition_truthy_int64.hpp"
#include "scpp/conditions/condition_truthy_uint8.hpp"
#include "scpp/conditions/condition_truthy_uint16.hpp"
#include "scpp/conditions/condition_truthy_uint32.hpp"
#include "scpp/conditions/condition_truthy_uint64.hpp"
#include "scpp/conditions/condition_truthy_float64.hpp"
#include "scpp/conditions/condition_truthy_mixed.hpp"
#include "scpp/conditions/condition_truthy_dynamic.hpp"
#include "scpp/conditions/condition_truthy_boolean.hpp"
#include "scpp/conditions/condition_truthy_string.hpp"
#include "scpp/conditions/condition_truthy_error_value.hpp"
#include "scpp/conversions/cast_boolean_from_string.hpp"
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
static void check(bool ok,int line) { if(!ok) { std::fprintf(stderr,"condition failure %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
int main() {
    CHECK(!condition_truthy(int8(0))); CHECK(condition_truthy(int8(1)));
    CHECK(!condition_truthy(int16(0))); CHECK(condition_truthy(int16(1)));
    CHECK(!condition_truthy(int32(0))); CHECK(condition_truthy(int32(1)));
    CHECK(!condition_truthy(int64(0))); CHECK(condition_truthy(int64(1)));
    CHECK(!condition_truthy(uint8(0))); CHECK(condition_truthy(uint8(1)));
    CHECK(!condition_truthy(uint16(0))); CHECK(condition_truthy(uint16(1)));
    CHECK(!condition_truthy(uint32(0))); CHECK(condition_truthy(uint32(1)));
    CHECK(!condition_truthy(uint64(0))); CHECK(condition_truthy(uint64(1)));
    CHECK(!condition_truthy(float64(0))); CHECK(condition_truthy(float64(1)));
    CHECK(condition_truthy(int64(-1))); CHECK(!condition_truthy(boolean(false)));
    CHECK(condition_truthy(boolean(true))); CHECK(!condition_truthy(float64(-0.0)));
    CHECK(condition_truthy(float64(std::numeric_limits<double>::quiet_NaN())));
    CHECK(condition_truthy(float64(std::numeric_limits<double>::infinity())));
    CHECK(!condition_truthy(string(""))); CHECK(!condition_truthy(string("0")));
    CHECK(condition_truthy(string("false"))); CHECK(!cast_boolean(string("false")));
    CHECK(condition_truthy(string("yes")));
    bool failed=false;
    try { (void)cast_boolean(string("yes")); } catch (...) { failed=true; } CHECK(failed);
    CHECK(!condition_truthy(mixed(null_t{}))); CHECK(!condition_truthy(dynamic(null_t{})));
    CHECK(!condition_truthy(mixed(string("0")))); CHECK(!condition_truthy(dynamic(string("0"))));
    CHECK(condition_truthy(mixed(string("false")))); CHECK(condition_truthy(dynamic(string("false"))));
    const auto table=make_mixed_table(); const auto object=make_dynamic_container();
    failed=false;
    try { (void)condition_truthy(table); }
    catch (...) { CHECK(capture_runtime_exception().code().equals("condition_truthy_reject_mixed_kind")); failed=true; }
    CHECK(failed); CHECK(condition_truthy(object));
    const error_value absent; const auto error=error_value::make(string("failure"));
    CHECK(!condition_truthy(absent)); CHECK(condition_truthy(error));
    const string text("a long false-looking string does not require a bool cast or temporary copy");
    const mixed m(text); const dynamic d(text);
    counting=true;
    for(int i=0;i<100;++i) {
        CHECK(condition_truthy(text)); CHECK(condition_truthy(m)); CHECK(condition_truthy(d));
        CHECK(condition_truthy(int64(-1))); CHECK(condition_truthy(uint64(1)));
        CHECK(condition_truthy(error)); CHECK(condition_truthy(object));
    }
    counting=false; CHECK(allocations==0);
    std::printf("PASS: 14 condition APIs, string cast distinction, boxed admission; allocations=%u\n",allocations);
}
