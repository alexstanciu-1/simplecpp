#include "public_api_guard.hpp"
#include "scpp/observations/isset_float64.hpp"
#include "scpp/observations/isset_null.hpp"
#include "scpp/observations/isset_mixed.hpp"
#include "scpp/observations/isset_dynamic.hpp"
#include "scpp/observations/empty_mixed.hpp"
#include "scpp/observations/empty_dynamic.hpp"
#include "scpp/observations/clean_int8.hpp"
#include "scpp/observations/clean_int16.hpp"
#include "scpp/observations/clean_int32.hpp"
#include "scpp/observations/clean_int64.hpp"
#include "scpp/observations/clean_uint8.hpp"
#include "scpp/observations/clean_uint16.hpp"
#include "scpp/observations/clean_uint32.hpp"
#include "scpp/observations/clean_uint64.hpp"
#include "scpp/observations/clean_boolean.hpp"
#include "scpp/observations/clean_float64.hpp"
#include "scpp/observations/clean_string.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"observation failure %d\n",__LINE__); std::abort(); } } while(false)
using namespace scpp;
int main() {
    CHECK(isset(int64(0))); CHECK(isset(boolean(false))); CHECK(isset(string("")));
    CHECK(isset(float64(0.0))); CHECK(isset(float64(-0.0))); CHECK(!isset(null_t{}));
    CHECK(!isset(mixed{})); CHECK(!isset(dynamic{}));
    CHECK(isset(mixed(boolean(false)))); CHECK(isset(dynamic(boolean(false))));
    CHECK(isset(mixed(int64(0)))); CHECK(isset(dynamic(float64(0.0))));
    CHECK(isset(mixed(string("")))); CHECK(isset(dynamic(string(""))));
    CHECK(empty(string(""))); CHECK(!empty(string("0")));
    CHECK(empty(mixed(string("")))); CHECK(!empty(mixed(string("0"))));
    CHECK(empty(dynamic(string("")))); CHECK(!empty(dynamic(string("0"))));
    auto table=make_mixed_table(); auto object=make_dynamic_container();
    CHECK(isset(table)); CHECK(isset(object)); CHECK(empty(table)); CHECK(empty(object));
    table.append(mixed{}); object.append(dynamic{});
    CHECK(!empty(table)); CHECK(!empty(object));
    const mixed rejected_m[]={mixed{},mixed(boolean(false)),mixed(int64(0)),mixed(float64(0.0))};
    const dynamic rejected_d[]={dynamic{},dynamic(boolean(false)),dynamic(int64(0)),dynamic(float64(0.0))};
    for(const auto &value:rejected_m) {
        bool failed=false;
        try { (void)empty(value); } catch (...) { failed=true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
        CHECK(failed);
    }
    for(const auto &value:rejected_d) {
        bool failed=false;
        try { (void)empty(value); } catch (...) { failed=true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
        CHECK(failed);
    }
    auto retained=object; auto &alias=object;
    clean(alias); CHECK(!isset(object)); CHECK(isset(retained)); CHECK(!empty(retained));
    CHECK(retained.get(int64(0)).is_null());
    clean(table); CHECK(!isset(table));
    string text("a long string whose reset must preserve aliases and allocate nothing");
    auto &text_alias=text;
    mixed boxed_text(text); dynamic dynamic_text(text);
    counting=true;
    { int8 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { int16 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { int32 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { int64 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { uint8 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { uint16 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { uint32 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { uint64 value(9); auto &alias=value; clean(alias); CHECK(value.native_value()==0); CHECK(isset(value)); }
    { boolean value(true); clean(value); CHECK(!value); CHECK(isset(value)); }
    { float64 value(9.5); auto &alias=value; clean(alias); CHECK(value.native_value()==0.0); CHECK(isset(value)); }
    for(int i=0;i<100;++i) {
        CHECK(isset(boxed_text)); CHECK(isset(dynamic_text)); CHECK(!empty(boxed_text)); CHECK(!empty(dynamic_text));
        CHECK(isset(retained)); CHECK(!empty(retained));
    }
    clean(text_alias); CHECK(empty(text)); CHECK(isset(text));
    clean(boxed_text); clean(dynamic_text); CHECK(!isset(boxed_text)); CHECK(!isset(dynamic_text));
    counting=false; CHECK(allocations==0);
    std::puts("PASS: primitive presence, boxed emptiness, reset alias/ownership semantics; successful allocations=0");
}
