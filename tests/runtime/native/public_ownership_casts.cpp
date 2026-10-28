// The driver prepends generated concrete API includes and operation names.
#include "public_api_guard.hpp"
#include "scpp/exception_info.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
#include <utility>
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"ownership casts line %d: %s\n",__LINE__,#x);std::abort(); } } while(false)
using namespace scpp;
int main() {
    {
        auto child=shared_derived::make();auto sibling=shared_sibling::make();
        auto expected=child;auto target=child;
        const auto before=allocations;
        auto base=TO_BASE(child);auto left=TO_LEFT(child);auto wrong=OTHER_BASE(sibling);
        auto narrowed=CAST(child.has_value() ? base : wrong);auto right=CROSS(left);
        CHECK(narrowed.identical(child) && IS(base) && !IS(wrong) && CROSS_IS(left));
        CHECK(right.has_value());
        shared_base empty;shared_left no_interface;
        CHECK(!IS(empty) && !CROSS_IS(no_interface));
        CHECK(allocations==before);
        for(int i=0;i<2;++i) {
            bool failed=false;
            try { target=CAST(i==0 ? wrong : empty); }
            catch(...) { auto error=capture_runtime_exception();failed=static_cast<bool>(error.code()==string("invalid_object_cast")); }
            CHECK(failed && target.identical(expected) && wrong.has_value());
        }
        child.reset();expected.reset();target.reset();base.reset();left.reset();narrowed.reset();
        CHECK(fixture::base::live==2); // right still owns the derived instance.
        right.reset();CHECK(fixture::base::live==1);
    }
    CHECK(fixture::base::live==0);
    {
        weak_derived observer;weak_base parent_observer;
        const auto destroyed=fixture::base::destroyed;
        {
            auto child=shared_derived::make();
            const auto before=allocations;
            observer=weak_derived(child);parent_observer=WEAK_UP(observer);
            auto locked=parent_observer.lock();CHECK(locked.has_value());
            child.reset();CHECK(fixture::base::live==1);locked.reset();
            CHECK(fixture::base::live==0 && fixture::base::destroyed==destroyed+1);
            CHECK(allocations==before);
        }
        const auto before=allocations;
        auto expired=WEAK_UP(observer);CHECK(!expired.lock().has_value() && !parent_observer.lock().has_value());
        weak_derived empty;CHECK(!WEAK_UP(empty).lock().has_value());
        CHECK(allocations==before);
    }
    {
        auto source=unique_derived::make();auto target=unique_base::make();
        const auto before=allocations;const auto destroyed=fixture::base::destroyed;
        const auto child_destroyed=fixture::derived::destroyed;
        target=UNIQUE_UP(std::move(source));CHECK(!source.has_value() && target.has_value());
        CHECK(fixture::base::live==1 && fixture::base::destroyed==destroyed+1);
        target.reset();CHECK(fixture::base::live==0 && fixture::derived::destroyed==child_destroyed+1);
        unique_derived empty;CHECK(!UNIQUE_UP(std::move(empty)).has_value());
        CHECK(allocations==before);
    }
    std::puts("PASS: checked/cross casts, type tests, failure preservation, expired weak upcast, unique destruction; successful operations allocate 0");
}
