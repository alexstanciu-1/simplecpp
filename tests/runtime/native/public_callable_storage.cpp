#include "public_api_guard.hpp"
#include "environment.hpp"
#include <cstdio>
#include <cstdlib>
#include <initializer_list>
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
    {
        auto sum=make_pack_callback();
        vector_int64 pack;
        CHECK(sum(pack)==int64(0));
        pack.append(int64(2));pack.append(int64(5));
        CHECK(sum(pack)==int64(7) && pack.size()==uint64(2));
        auto nested=make_nested_callback();
        auto original=make_owned_callback(int64(10));
        auto returned=nested(original);original.reset();nested.reset();
        CHECK(returned(int64(3))==int64(13));

        vector_callbacks callbacks;callbacks.append(returned);
        hash_callbacks keyed;keyed.set(string("run"),returned);
        auto vector_copy=callbacks;auto hash_copy=keyed;
        callbacks.clear();keyed.clear();returned.reset();
        auto selected=vector_copy.at(uint64(0));vector_copy.clear();
        CHECK(selected(int64(1))==int64(11));
        auto selected_key=hash_copy.at(string("run"));hash_copy.clear();
        CHECK(selected_key(int64(2))==int64(12));

        result_callback success(selected);
        result_or_false_callback falseable(selected);
        result_or_bool_callback boolable(selected);
        const auto observed=allocations;
        CHECK(success.has_value() && success.observe_payload_condition());
        CHECK(falseable.has_value() && falseable.observe_payload_condition());
        CHECK(boolable.has_value() && boolable.observe_payload_condition());
        CHECK(allocations==observed);
        auto extracted=success.value();selected.reset();
        CHECK(extracted(int64(4))==int64(14));
        result_callback empty_success(callback_int{});
        result_or_false_callback empty_falseable(callback_int{});
        result_or_bool_callback empty_boolable(callback_int{});
        const auto empty_observed=allocations;
        CHECK(empty_success.has_value() && !empty_success.observe_payload_condition());
        CHECK(empty_falseable.has_value() && !empty_falseable.is_false() && !empty_falseable.observe_payload_condition());
        CHECK(empty_boolable.has_value() && !empty_boolable.is_false() && !empty_boolable.is_true() && !empty_boolable.observe_payload_condition());
        CHECK(allocations==empty_observed);
    }
    CHECK(fixture::environment::live==0);
    {
        callback_int retained;
        {
            fixture::environment closure(int64(30));
            retained=FROM_CLOSURE(closure,uint8(2));
            CHECK(fixture::environment::live==2);
            CHECK(callback_permissions(retained)==uint64(2));
            closure.offset=int64(99);
            CHECK(retained(int64(1))==int64(31));
            auto local=FROM_CLOSURE(closure,uint8(0));
            CHECK(callback_permissions(local)==uint64(0));
            auto transferable=FROM_CLOSURE(closure,uint8(3));
            CHECK(callback_permissions(transferable)==uint64(3));
            const auto live=fixture::environment::live;
            for (const auto invalid : {1,4,255}) {
                failed=false;
                try { (void)FROM_CLOSURE(closure,uint8(invalid)); } catch(...) { failed=true; }
                CHECK(failed && fixture::environment::live==live);
            }
        }
        CHECK(fixture::environment::live==1 && retained(int64(2))==int64(32));
        retained.reset();CHECK(fixture::environment::live==0);
    }
    std::printf("PASS: callable captures, copy/move/reset, permissions, snapshot, reference/void invocation; creation allocations=%u copy allocations=%u invocation allocations=0\n",creation,copying);
}
