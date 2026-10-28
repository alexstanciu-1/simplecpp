#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"application functions line %d: %s\n",__LINE__,#x);std::abort(); } } while(false)
using namespace scpp;
int main() {
    {
        auto owner=member_owner::make();write_member(owner,int64(1));
        auto retained=owner.retain_present();
        owner=member_owner::make(); // RHS replaces the originating handle.
        CHECK(write_member(retained,int64(8))==int64(8));
        CHECK(read_member(retained)==int64(8) && read_member(owner)==int64(0));
        const auto before=allocations;
        for(int i=0;i<100;++i) {
            auto selected=retained.retain_present();
            CHECK(write_member(selected,int64(i))==int64(i));
            CHECK(read_member(selected)==int64(i));
        }
        CHECK(allocations==before);
        member_owners rows;rows.append(retained);
        auto selected=rows.at(uint64(0)).retain_present();
        rows.clear();retained.reset(); // RHS removes the container element and original handle.
        CHECK(write_member(selected,int64(23))==int64(23));
        CHECK(read_member(selected)==int64(23));
        member_owner absent;int rhs_calls=0;bool failed=false;
        try { auto target=absent.retain_present();++rhs_calls;write_member(target,int64(2)); }
        catch(...) { failed=true; } CHECK(failed && rhs_calls==0);
        failed=false;
        try { auto target=rows.at(uint64(0)).retain_present();++rhs_calls;write_member(target,int64(2)); }
        catch(...) { failed=true; } CHECK(failed && rhs_calls==0);
        // Exceptions during RHS release temporary retention without redirecting a write.
        const auto live=fixture::record::live;
        try { auto target=selected.retain_present();throw 7; } catch(int) {}
        CHECK(fixture::record::live==live && read_member(selected)==int64(23));
    }
    CHECK(fixture::record::live==0);
    std::puts("PASS: retained property receiver, RHS replacement/removal, failure ordering and zero-allocation access");
}
