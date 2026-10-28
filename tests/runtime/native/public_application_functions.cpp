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
    {
        auto origin=member_owner::make();member_values initial;initial.append(int64(3));
        replace_values(origin,initial);
        auto selected=origin.retain_present(); // Capture owner before key/RHS effects.
        origin=member_owner::make();
        member_values replacement;replacement.append(int64(17));
        replace_values(selected,replacement); // RHS replaces the selected object's field.
        CHECK(write_field_value(selected,int64(0),int64(29))==int64(29));
        CHECK(read_field_value(selected,int64(0))==int64(29));
        bool failed=false;try { (void)read_field_value(origin,int64(0)); } catch(...) { failed=true; }
        CHECK(failed); // The originating variable now denotes a different object.
        const auto before=allocations;
        for(int i=0;i<100;++i) {
            CHECK(write_field_value(selected,int64(0),int64(i))==int64(i));
            CHECK(read_field_value(selected,int64(0))==int64(i));
        }
        CHECK(allocations==before); // No extracted field-container copy.
        member_values empty;replace_values(selected,empty);failed=false;
        try { (void)write_field_value(selected,int64(0),int64(42)); } catch(...) { failed=true; }
        CHECK(failed); // Existing-only write rechecks the current field bounds.
        failed=false;try { (void)read_field_value(selected,int64(0)); } catch(...) { failed=true; }
        CHECK(failed); // A failed write did not regrow the cleared field.
    }
    CHECK(fixture::record::live==0);
    string owned_result;
    {
        auto derived=derived_member_owner::make();auto original=MEMBER_UPCAST(derived);
        auto receiver=original.retain_present();
        original=member_owner::make();derived.reset(); // Argument evaluation replaces origin.
        CHECK(invoke_member(receiver,int64(7))==int64(107)); // Dispatch stays virtual.
        CHECK(read_member(original)==int64(0) && read_member(receiver)==int64(7));
        int64 value(0);const auto before=allocations;
        for(int i=0;i<100;++i) {
            adjust_member(receiver,value,value); // Both fixed refs alias the same whole storage.
            CHECK(invoke_member(receiver,int64(0))==int64(107));
        }
        CHECK(value==int64(300) && allocations==before);
        owned_result=describe_member(receiver);
        member_owner absent;int argument_calls=0;bool failed=false;
        try { auto selected=absent.retain_present();++argument_calls;(void)invoke_member(selected,int64(1)); }
        catch(...) { failed=true; } CHECK(failed && argument_calls==0);
        fixture::record inline_receiver;
        CHECK(invoke_inline(inline_receiver,int64(9))==int64(9));
        CHECK(inline_receiver.value==int64(9)); // Borrowed whole-storage receiver, no record copy.
    }
    CHECK(owned_result.equals("owned method result survives the receiver lifetime"));
    CHECK(fixture::record::live==0);
    std::puts("PASS: retained property receiver, RHS replacement/removal, failure ordering and zero-allocation access");
}
