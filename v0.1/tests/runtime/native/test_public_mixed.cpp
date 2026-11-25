#include "public_api_guard.hpp"
#include "scpp/mixed.hpp"
#include <cmath>
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <initializer_list>
#include <new>
#include <utility>

using namespace scpp;
static bool counting=false;
static bool fail_next_allocation=false;
static unsigned allocations=0;
void begin_mixed_allocations() { counting=true; allocations=0; }
unsigned end_mixed_allocations() { counting=false; return allocations; }
void *operator new(std::size_t size)
{
    if (fail_next_allocation) { fail_next_allocation=false; throw std::bad_alloc(); }
    if (counting) ++allocations;
    if (void *memory=std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
unsigned native_mixed_allocations(int phase);
static void check(bool value, int line)
{
    if (!value) { std::fprintf(stderr,"mixed API check failed: %d\n",line); std::abort(); }
}
#define CHECK(x) check(static_cast<bool>(x),__LINE__)

static unsigned public_allocations(int phase)
{
    const string text("a long owned string to exercise the native mixed allocation path");
    begin_mixed_allocations();
    if (phase==0) {
        mixed a, b(null), c(boolean(true)), d(int64(42)), e(float64(1.25));
        a=d; b=e; c=null;
    } else if (phase==1) {
        mixed a(text);
        mixed b(a);
        string extracted=b.get_string();
        a=null;
    } else {
        mixed a=make_mixed_table();
        a.set(int64(0),mixed(int64(10)));
        a.set(int64(1),mixed(int64(20)));
        mixed copy(a);
        auto found=copy.get(int64(0));
        if (phase==3) {
            auto cursor=a.iterate();
            (void)cursor.value();
            a=null;
            cursor.next();
            CHECK(cursor.value().get_int()==int64(20));
        }
    }
    return end_mixed_allocations();
}
static auto escaped_cursor()
{
    mixed owner=make_mixed_table();
    owner.set(string("entry"),mixed(string("owned payload after owner destruction")));
    return owner.iterate();
}
int main()
{
    for (int phase=0; phase<4; ++phase) {
        auto native=native_mixed_allocations(phase), exposed=public_allocations(phase);
        CHECK(native==exposed);
        if (phase==0) CHECK(exposed==0);
        std::printf("mixed allocations phase=%d native=%u public=%u\n",phase,native,exposed);
    }
    CHECK(float64().native_value()==0.0);
    CHECK(float64(int64(-13)).native_value()==-13.0);
    for (double value : {0.0,-0.0,1.25,std::numeric_limits<double>::infinity(),std::numeric_limits<double>::quiet_NaN()}) {
        mixed boxed{float64(value)};
        auto copy=boxed;
        auto moved=std::move(copy);
        const double result=moved.get_float().native_value();
        CHECK(std::isnan(value) ? std::isnan(result) : (result==value && std::signbit(result)==std::signbit(value)));
        CHECK(copy.is_null());
    }
    mixed value;
    CHECK(value.is_null() && value.get(int64(0)).is_null());
    CHECK(!value.remove(int64(0)) && value.is_null());
    value=boolean(true); CHECK(value.is_bool() && value.get_bool());
    value=int64(-42); CHECK(value.is_int() && value.get_int()==int64(-42));
    bool failed=false;
    try { (void)value.get_string(); } catch (...) { failed=true; }
    CHECK(failed && value.get_int()==int64(-42));
    failed=false;
    try { (void)value.get(int64(0)); } catch (...) { failed=true; }
    CHECK(failed);
    failed=false;
    try { value.set(int64(0),mixed(int64(1))); } catch (...) { failed=true; }
    CHECK(failed && value.get_int()==int64(-42));
    failed=false;
    try { value.append(mixed(int64(1))); } catch (...) { failed=true; }
    CHECK(failed && value.is_int());
    value=string("owned string for exact extraction");
    auto text=value.get_string(); value.reset();
    CHECK(text.equals("owned string for exact extraction") && value.is_null());
    failed=false;
    try { auto cursor=value.iterate(); } catch (...) { failed=true; }
    CHECK(failed);
    value=make_mixed_table();
    { auto cursor=value.iterate(); CHECK(!cursor.valid()); }
    value.reset();
    value.set(string("first"),mixed(int64(1))); // Null bootstrap.
    value.set(string("null"),mixed(null));
    CHECK(value.is_hash() && value.get(string("missing")).is_null());
    CHECK(value.remove(string("null")) && !value.remove(string("missing")));
    auto copy=value;
    copy.set(string("first"),mixed(int64(9)));
    CHECK(value.get(string("first")).get_int()==int64(1));
    mixed nested;
    nested.set(string("child"),value);
    value.reset();
    auto child=nested.get(string("child"));
    child.set(string("first"),mixed(int64(11)));
    CHECK(nested.get(string("child")).get(string("first")).get_int()==int64(1));
    auto cursor=copy.iterate();
    CHECK(cursor.key().get_string().equals("first"));
    copy.set(string("first"),mixed(int64(10)));
    CHECK(cursor.value().get_int()==int64(10));
    copy.append(mixed(int64(88)));
    cursor.next(); CHECK(!cursor.valid());
    auto escaped=escaped_cursor();
    CHECK(escaped.key().get_string().equals("entry"));
    CHECK(escaped.value().get_string().equals("owned payload after owner destruction"));
    mixed owner;
    owner.append(mixed(int64(1)));owner.append(mixed(int64(2)));
    auto old=owner.iterate();
    CHECK(old.value().get_int()==int64(1));
    clean(owner); CHECK(owner.is_null());
    old.next(); CHECK(old.value().get_int()==int64(2));
    mixed moved_source;
    moved_source.append(mixed(int64(3)));
    auto active=moved_source.iterate();
    mixed moved=std::move(moved_source);
    CHECK(moved_source.is_null() && active.value().get_int()==int64(3));
    mixed self;
    self.set(int64(0),self); CHECK(self.get(int64(0)).is_null());
    self.reset(); self.append(self); CHECK(self.get(int64(0)).is_null());
    self.set(int64(1),self); CHECK(self.get(int64(1)).get(int64(0)).is_null());
    self=self; CHECK(self.is_hash());
    failed=false;
    fail_next_allocation=true;
    try { self.set(int64(2),self); } catch (const std::bad_alloc &) { failed=true; }
    fail_next_allocation=false;
    CHECK(failed && self.get(int64(2)).is_null() && self.get(int64(1)).is_hash());
    old.next(); CHECK(!old.valid()); old.next(); // Finishing is idempotent.
    failed=false;
    try { (void)old.value(); } catch (...) { failed=true; }
    CHECK(failed);
    std::printf("PASS: public mixed lifetimes and values; mixed=%zu float64=%zu cursor=%zu bytes\n",sizeof(mixed),sizeof(float64),sizeof(escaped));
}
