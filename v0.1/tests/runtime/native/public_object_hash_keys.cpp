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
    {
        auto original=key_derived::make();optional_key optional(original);
        key_base saved;
        { const auto extracted=optional.require_value("indexed key");saved=TO_BASE(extracted); }
        optional=optional_key();original.reset(); // Saved key retains the captured identity.
        key_row row;row.append(int64(4));key_rows rows;rows.set(saved,row);
        const auto before=allocations;
        for(int i=0;i<100;++i) {
            store_key_row(rows,saved,int64(0),int64(8));
            CHECK(read_key_row(rows,saved,int64(0))==int64(8));
        }
        CHECK(allocations==before);
        optional_key missing;int rhs_calls=0;bool failed=false;
        try {
            const auto key=TO_BASE(missing.require_value("indexed key"));
            ++rhs_calls;store_key_row(rows,key,int64(0),int64(99));
        } catch(...) { failed=true; }
        CHECK(failed && rhs_calls==0);
        auto other=key_derived::make();const auto absent=TO_BASE(other);int later_key_calls=0;
        if(ready_key_row(rows,absent)) { ++later_key_calls;(void)missing.require_value("later key"); }
        CHECK(later_key_calls==0);
        // A saved key survives RHS effects, but existing-store must recheck membership.
        erase_key_row(rows,saved);failed=false;
        try { store_key_row(rows,saved,int64(0),int64(99)); } catch(...) { failed=true; }
        CHECK(failed && !rows.has(saved));
        erase_key_row(rows,saved); // Absent removal is a no-op.
    }
    CHECK(fixture::base::live==0);
    std::puts("PASS: identity/upcast, allocation-free lookup, map value semantics and retained key lifetime");
}
