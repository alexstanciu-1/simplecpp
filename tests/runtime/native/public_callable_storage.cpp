#include "public_api_guard.hpp"
#include "environment.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <utility>
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"callable storage line %d: %s\n",__LINE__,#x);std::abort(); } } while(false)
using namespace scpp;
int main() {
    const auto initial=allocations;callback_int absent;CHECK(!absent.has_value());CHECK(allocations==initial);
    int argument_calls=0;const auto argument=[&] { ++argument_calls;return int64(2); };
    bool failed=false;try { (void)absent(argument()); } catch(...) { failed=true; }
    CHECK(failed && argument_calls==1);
    unsigned creation=0,copying=0;
    {
        auto before=allocations;auto callback=make_owned_callback(int64(7));creation=allocations-before;
        CHECK(fixture::environment::live==1 && callback(int64(2))==int64(9));
        CHECK(callback_permissions(callback)==uint64(3));
        before=allocations;auto copy=callback;copying=allocations-before;
        CHECK(fixture::environment::live==2);
        auto moved=std::move(copy);CHECK(callback_permissions(copy)==uint64(0));
        callback.reset();CHECK(fixture::environment::live==1 && !callback.has_value());
        before=allocations;
        for(int i=0;i<100;++i) CHECK(moved(int64(i))==int64(i+7));
        CHECK(allocations==before && callback_permissions(moved)==uint64(3));
        // Prepared invocation retains the chosen callback before argument effects.
        auto snapshot=moved;
        CHECK(snapshot([&] { moved=make_owned_callback(int64(20));return int64(1); }())==int64(8));
        CHECK(snapshot(int64(1))==int64(8) && moved(int64(1))==int64(21));
        failed=false;try { (void)snapshot(int64(-1)); } catch(...) { failed=true; }
        CHECK(failed && snapshot.has_value());
        moved.reset();snapshot.reset();CHECK(fixture::environment::live==0);
    }
    int64 value(4);
    auto reference=make_reference_callback();auto &alias=reference(value);
    CHECK(&alias==&value && value==int64(5));reference.reset();alias=int64(9);CHECK(value==int64(9));
    auto mutate=make_void_callback();const auto before=allocations;mutate(value,value);
    CHECK(value==int64(18) && allocations==before);
    CHECK(fixture::environment::live==0);
    std::printf("PASS: callable captures, copy/move/reset, permissions, snapshot, reference/void invocation; creation allocations=%u copy allocations=%u invocation allocations=0\n",creation,copying);
}
