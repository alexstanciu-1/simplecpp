#include "public_api_guard.hpp"
#include "scpp/text/concat_text_int8.hpp"
#include "scpp/text/concat_text_int16.hpp"
#include "scpp/text/concat_text_int32.hpp"
#include "scpp/text/concat_text_int64.hpp"
#include "scpp/text/concat_text_uint8.hpp"
#include "scpp/text/concat_text_uint16.hpp"
#include "scpp/text/concat_text_uint32.hpp"
#include "scpp/text/concat_text_float64.hpp"
#include "scpp/text/concat_text_boolean.hpp"
#include "scpp/text/concat_text_string.hpp"
#include "scpp/text/concat_text_null.hpp"
#include "scpp/text/concat_text_mixed.hpp"
#include "scpp/text/concat_text_dynamic.hpp"
#include "scpp/conversions/cast_string_from_float64.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
static void check(bool ok,int line) { if(!ok) { std::fprintf(stderr,"text check failed: %d\n",line); std::abort(); } }
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
using namespace scpp;
int main() {
    CHECK(concat_text(int8(42)).equals("42"));
    CHECK(concat_text(int16(42)).equals("42"));
    CHECK(concat_text(int32(42)).equals("42"));
    CHECK(concat_text(int64(42)).equals("42"));
    CHECK(concat_text(uint8(42)).equals("42"));
    CHECK(concat_text(uint16(42)).equals("42"));
    CHECK(concat_text(uint32(42)).equals("42"));
    CHECK(concat_text(int64(-9223372036854775807LL-1)).equals("-9223372036854775808"));
    CHECK(concat_text(uint32(4294967295U)).equals("4294967295"));
    CHECK(concat_text(boolean(true)).equals("1")); CHECK(concat_text(boolean(false)).equals(""));
    CHECK(concat_text(null_t{}).equals(""));
    CHECK(concat_text(float64(1.23456789)).equals("1.23457"));
    CHECK(cast_string(float64(1.23456789)).equals("1.23456789"));
    const string binary("a\0b",uint64(3));
    CHECK(concat_text(binary).equals(binary));
    const auto joined=concat_text(binary)+concat_text(int64(42));
    CHECK(joined.equals(string("a\0b42",uint64(5))));
    auto destination=concat_text(binary); destination+=concat_text(boolean(true));
    CHECK(destination.equals(string("a\0b1",uint64(4))));
    const auto survivor=concat_text(string("a long temporary string whose text result must own all of its bytes"));
    CHECK(survivor.equals("a long temporary string whose text result must own all of its bytes"));
    const mixed mixed_values[]={mixed{},mixed(boolean(false)),mixed(boolean(true)),mixed(int64(-42)),mixed(float64(1.23456789)),mixed(binary),make_mixed_table()};
    const dynamic dynamic_values[]={dynamic{},dynamic(boolean(false)),dynamic(boolean(true)),dynamic(int64(-42)),dynamic(float64(1.23456789)),dynamic(binary),make_dynamic_container()};
    const string expected[]={string(""),string(""),string("1"),string("-42"),string("1.23457"),binary};
    for(int i=0;i<6;++i) {
        CHECK(concat_text(mixed_values[i]).equals(expected[i]));
        CHECK(concat_text(dynamic_values[i]).equals(expected[i]));
    }
    CHECK(concat_text(mixed_values[6]).equals("Array"));
    CHECK(concat_text(dynamic_values[6]).equals("Object"));
    const auto boxed_survivor=concat_text(dynamic(string("long temporary boxed string whose returned bytes must survive destruction")));
    CHECK(boxed_survivor.equals("long temporary boxed string whose returned bytes must survive destruction"));
    counting=true;
    for(int i=0;i<100;++i) {
        CHECK(concat_text(int32(42)).equals("42")); CHECK(concat_text(boolean(false)).equals(""));
        CHECK(concat_text(null_t{}).equals("")); CHECK(concat_text(binary).equals(binary));
        CHECK(concat_text(mixed_values[3]).equals("-42"));
        CHECK(concat_text(dynamic_values[5]).equals(binary));
        CHECK(concat_text(mixed_values[6]).equals("Array"));
        CHECK(concat_text(dynamic_values[6]).equals("Object"));
    }
    counting=false; CHECK(allocations==0);
    const string long_text("long source text requires one owned result allocation and no wrapper allocation");
    counting=true; const auto copy=concat_text(long_text); counting=false;
    CHECK(copy.equals(long_text)); CHECK(allocations==1);
    const mixed boxed_long(long_text); const dynamic dynamic_long(long_text);
    allocations=0; counting=true; const auto mixed_copy=concat_text(boxed_long); counting=false;
    CHECK(mixed_copy.equals(long_text)); CHECK(allocations==1);
    allocations=0; counting=true; const auto dynamic_copy=concat_text(dynamic_long); counting=false;
    CHECK(dynamic_copy.equals(long_text)); CHECK(allocations==1);
    std::printf("PASS: bounded text, distinct float formatting, byte preservation, owned lifetime; small conversions=0 allocations, long text=1\n");
}
