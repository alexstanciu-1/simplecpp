#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"object keys line %d: %s\n",__LINE__,#x);std::abort(); } } while(false)
using namespace scpp;
int main() {
    {
        auto first=key_derived::make();auto second=key_derived::make();
        auto key=TO_BASE(first);auto same=TO_BASE(first);auto distinct=TO_BASE(second);
        object_hash map;
        map.set(key,int64(7));map.set(same,int64(9));map.set(distinct,int64(11));
        CHECK(map.size()==uint64(2));
        const auto before=allocations;
        for(int i=0;i<100;++i) {
            auto converted=TO_BASE(first);
            CHECK(converted.hash_key_hash()==key.hash_key_hash());
            CHECK(converted.hash_key_equal(key) && !converted.hash_key_equal(distinct));
            CHECK(map.has(converted) && map.at(converted)==int64(9));
        }
        CHECK(allocations==before);
        auto copy=map;copy.remove(key);
        CHECK(copy.size()==uint64(1) && map.has(key));
        first.reset();second.reset();key.reset();same.reset();distinct.reset();
        CHECK(fixture::base::live==2); // Maps retain their identities.
        auto cursor=map.iterate();auto saved_key=cursor.key();
        map.clear();copy.clear();
        CHECK(saved_key.has_value());
        object_hash lookup;lookup.set(saved_key,int64(23));
        CHECK(lookup.at(saved_key)==int64(23));
    }
    CHECK(fixture::base::live==0);
    std::puts("PASS: identity/upcast, allocation-free lookup, map value semantics and retained key lifetime");
}
