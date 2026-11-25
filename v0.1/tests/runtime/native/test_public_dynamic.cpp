#include "public_api_guard.hpp"
#include "scpp/dynamic.hpp"
#include <cmath>
#include <cstdio>
#include <cstdlib>
#include <initializer_list>
#include <limits>
#include <new>
#include <type_traits>
#include <utility>

using namespace scpp;
static bool counting=false;
static bool fail_next=false;
static unsigned allocations=0;
void begin_dynamic_allocations() { counting=true; allocations=0; }
unsigned end_dynamic_allocations() { counting=false; return allocations; }
void *operator new(std::size_t size)
{
    if (fail_next) { fail_next=false; throw std::bad_alloc(); }
    if (counting) ++allocations;
    if (void *memory=std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
unsigned native_dynamic_allocations(int phase);
static void check(bool value, int line)
{
    if (!value) { std::fprintf(stderr,"dynamic API check failed: %d\n",line); std::abort(); }
}
#define CHECK(x) check(static_cast<bool>(x),__LINE__)
static_assert(!std::is_convertible_v<dynamic,bool>);
static_assert(!std::is_constructible_v<dynamic,mixed>);
static_assert(!std::is_constructible_v<mixed,dynamic>);

static unsigned public_allocations(int phase)
{
    const string text("a long owned string for dynamic conversion and allocation parity");
    begin_dynamic_allocations();
    if (phase==0) {
        dynamic a, b(null), c(boolean(true)), d(int64(42)), e(float64(1.25));
        a=d; b=e; c=null;
        auto scalar=to_mixed(d);
        auto roundtrip=to_dynamic(scalar);
    } else if (phase==1) {
        dynamic a(text), b(a);
        string extracted=b.get_string();
        a.reset();
    } else if (phase==4) {
        mixed a(text);
        auto converted=to_dynamic(a);
        auto roundtrip=to_mixed(converted);
    } else {
        auto a=make_dynamic_container();
        a.set(int64(0),dynamic(int64(10)));
        a.set(int64(1),dynamic(int64(20)));
        auto alias=a;
        auto found=alias.get(int64(0));
        if (phase==3) {
            auto cursor=a.iterate();
            (void)cursor.value();
            a.reset();
            cursor.next();
            CHECK(cursor.value().get_int()==int64(20));
        }
    }
    return end_dynamic_allocations();
}
static auto surviving_cursor()
{
    auto owner=make_dynamic_container();
    owner.set(string("entry"),dynamic(string("owned after all external aliases die")));
    return owner.iterate();
}
int main()
{
    for (int phase=0; phase<5; ++phase) {
        const auto native=native_dynamic_allocations(phase), exposed=public_allocations(phase);
        CHECK(native==exposed);
        if (phase==0) CHECK(exposed==0);
        std::printf("dynamic allocations phase=%d native=%u public=%u\n",phase,native,exposed);
    }
    dynamic value;
    CHECK(value.is_null() && value.get(int64(0)).is_null());
    CHECK(!value.remove(int64(0)) && value.is_null());
    bool failed=false;
    try { value.set(int64(0),dynamic(int64(1))); } catch (...) { failed=true; }
    CHECK(failed && value.is_null());
    failed=false;
    try { value.append(dynamic(int64(1))); } catch (...) { failed=true; }
    CHECK(failed && value.is_null());
    failed=false;
    try { auto cursor=value.iterate(); } catch (...) { failed=true; }
    CHECK(failed);
    value=boolean(false); CHECK(value.is_bool() && !value.get_bool());
    CHECK(to_dynamic(to_mixed(value)).is_bool() && !to_dynamic(to_mixed(value)).get_bool());
    CHECK(to_dynamic(mixed(null)).is_null() && to_mixed(dynamic(null)).is_null());
    for (auto integer : {int64(-42),int64(__INT64_MAX__),int64(-__INT64_MAX__-1)}) {
        value=integer;
        CHECK(to_dynamic(to_mixed(value)).get_int()==integer);
    }
    for (double floating : {0.0,-0.0,1.25,std::numeric_limits<double>::infinity(),std::numeric_limits<double>::quiet_NaN()}) {
        value=float64(floating);
        auto roundtrip=to_dynamic(to_mixed(value));
        CHECK(roundtrip.is_float());
        const auto result=roundtrip.get_float().native_value();
        CHECK(std::isnan(floating) ? std::isnan(result) : (result==floating && std::signbit(result)==std::signbit(floating)));
    }
    value=string("owned scalar string");
    auto text=to_mixed(value); value.reset();
    CHECK(text.get_string().equals("owned scalar string"));
    auto table=make_dynamic_container();
    { auto empty=table.iterate(); CHECK(!empty.valid()); }
    table.set(string("a"),dynamic(int64(1)));
    table.set(string("b"),dynamic(int64(2)));
    CHECK(table.get(string("missing")).is_null());
    begin_dynamic_allocations();
    auto alias=table;
    CHECK(end_dynamic_allocations()==0);
    alias.set(string("a"),dynamic(int64(3)));
    CHECK(table.get(string("a")).get_int()==int64(3));
    auto root=make_dynamic_container(); root.set(string("child"),table);
    auto child=root.get(string("child"));
    child.set(string("a"),dynamic(int64(4)));
    CHECK(root.get(string("child")).get(string("a")).get_int()==int64(4));
    auto cursor=table.iterate();
    CHECK(cursor.key().get_string().equals("a"));
    CHECK(cursor.value().get_int()==int64(4));
    alias.set(string("b"),dynamic(int64(5)));
    alias.append(dynamic(int64(6))); // Preserve remaining entries before insertion.
    alias.set(string("b"),dynamic(int64(7)));
    cursor.next(); CHECK(cursor.value().get_int()==int64(5));
    cursor.next(); CHECK(!cursor.valid()); cursor.next();
    auto bound=table.iterate();
    clean(table); CHECK(table.is_null() && alias.is_hash());
    CHECK(bound.value().get_int()==int64(4));
    auto moving=alias.iterate();
    dynamic moved=std::move(alias); CHECK(alias.is_null());
    CHECK(moving.value().get_int()==int64(4));
    auto guarded=make_dynamic_container();
    guarded.append(dynamic(int64(12)));
    auto guarded_cursor=guarded.iterate();
    failed=false; fail_next=true;
    try { dynamic attempted(std::move(guarded)); } catch (const std::bad_alloc &) { failed=true; }
    fail_next=false;
    CHECK(failed && guarded.get(int64(0)).get_int()==int64(12));
    CHECK(guarded_cursor.value().get_int()==int64(12));
    auto escaped=surviving_cursor();
    CHECK(escaped.key().get_string().equals("entry"));
    CHECK(escaped.value().get_string().equals("owned after all external aliases die"));
    // Cycles remain admitted. Break the edge before checking, so this proof reclaims it.
    auto cyclic=make_dynamic_container();
    cyclic.set(int64(0),cyclic);
    auto self=cyclic.get(int64(0));
    self.set(int64(1),dynamic(int64(99)));
    const auto shared=cyclic.get(int64(1)).get_int();
    const auto removed=cyclic.remove(int64(0));
    CHECK(removed && shared==int64(99));
    cyclic.append(cyclic);
    const auto removed_append=cyclic.remove(int64(2));
    CHECK(removed_append);
    dynamic destination(int64(17));
    failed=false;
    try { destination=to_dynamic(make_mixed_table()); } catch (...) { failed=true; }
    CHECK(failed && destination.get_int()==int64(17));
    mixed other(int64(18));
    failed=false;
    try { other=to_mixed(moved); } catch (...) { failed=true; }
    CHECK(failed && other.get_int()==int64(18));
    mixed source(string("long string whose conversion allocates before replacing the target"));
    failed=false; fail_next=true;
    try { destination=to_dynamic(source); } catch (const std::bad_alloc &) { failed=true; }
    fail_next=false;
    CHECK(failed && destination.get_int()==int64(17) && source.is_string());
    failed=false;
    try { (void)destination.get_string(); } catch (...) { failed=true; }
    CHECK(failed && destination.is_int());
    std::printf("PASS: dynamic aliases, conversions, cycle removal and lifetimes; dynamic=%zu cursor=%zu bytes\n",sizeof(dynamic),sizeof(escaped));
}
