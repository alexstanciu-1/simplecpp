#include "scpp/hashes.hpp"
#include "allocation_probe.hpp"
#include <cstdio>
#include <cstring>
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>
using namespace scpp;
static void require(bool ok) { if (!ok) throw std::runtime_error("hash consumer proof failed"); }
template<class E, class F> void rejects(F f) { try { f(); } catch(const E&) { return; } throw std::runtime_error("expected rejection"); }
template<class H, class K, class V> concept accepts_set = requires(H h, K k, V v) { h.set(k,v); };
template<class H> concept appends = requires(H h) { h.append(int64{1}); };
template<class H> concept traverses = requires(H h) { h.begin(); h.end(); };
static_assert(!accepts_set<hash_int64_int64, int, int64>);
static_assert(!accepts_set<hash_int64_string, const char*, int64>);
static_assert(!accepts_set<hash_string_int64, int64, const char*>);
static_assert(!appends<hash_int64_string> && appends<hash_int64_int64>);
static_assert(!traverses<hash_int64_int64>);
static_assert(!std::is_reference_v<decltype(std::declval<hash_string_int64>().at(int64{1}))>);
int main() {
    hash_int64_int64 h;
    require(h.empty().native_value());
    rejects<std::out_of_range>([&]{ (void)h.at(int64{0}); });
    require(!h.find(int64{0}).has_value().native_value());
    h.set(int64{-5},int64{7});
    require(h.append(int64{8}).native_value()==0);
    h.set(int64{10},int64{9}); h.remove(int64{10});
    require(h.append(int64{9}).native_value()==1);
    h.set(int64{1},int64{99}); require(h.size().native_value()==3);
    require(h.find(int64{1}).value().native_value()==99);
    h.set(int64{std::numeric_limits<long long>::max()},int64{3});
    rejects<std::overflow_error>([&]{ h.append(int64{4}); });
    require(h.size().native_value()==4);
    auto copy=h; copy.set(int64{1},int64{11}); require(h.at(int64{1}).native_value()==99);
    auto moved=std::move(copy); require(copy.empty().native_value()); copy.append(int64{8});
    auto& self=moved; moved=self; moved=std::move(self); require(moved.size().native_value()==4);
    hash_int64_string keys;
    keys.set(string{"12"},int64{12}); keys.set(string{"012"},int64{13});
    keys.set(string{"a\0b",int64{3}},int64{14});
    require(keys.size().native_value()==3 && keys.at(string{"a\0b",int64{3}}).native_value()==14);
    hash_string_int64 values; values.set(int64{2},string{"hello"});
    auto detached=values.at(int64{2}); values.set(int64{2},string{"changed"});
    require(std::strcmp(detached.data(),"hello")==0);
    require(values.append(string{"next"}).native_value()==3);
    hash_string_string both;
    string long_key{"a sufficiently long key to require separately owned storage"};
    string long_value{"a sufficiently long value to require separately owned storage"};
    both.set(long_key,long_value);
    auto count=allocation_probe::calls;
    for(int i=0;i<10000;++i) require(both.has(long_key).native_value());
    require(allocation_probe::calls==count);
    for(long fail=0;;++fail) {
        hash_string_string destination; destination.set(string{"old"},string{"value"});
        allocation_probe::fail_after=fail;
        bool failed=false;
        try { destination=both; } catch(const std::bad_alloc&) { failed=true; }
        allocation_probe::fail_after=-1;
        if(!failed) { require(destination.has(long_key).native_value()); break; }
        require(destination.size().native_value()==1 && destination.has(string{"old"}).native_value());
    }
    for(long fail=0;;++fail) {
        hash_string_string table=both;
        allocation_probe::fail_after=fail;
        bool failed=false;
        try { table.set(string{"new long key requiring an allocation in its own right"},long_value); }
        catch(const std::bad_alloc&) { failed=true; }
        allocation_probe::fail_after=-1;
        if(!failed) { require(table.size().native_value()==2); break; }
        require(table.size().native_value()==1 && table.has(long_key).native_value());
    }
    allocation_probe::fail_after=0;
    rejects<std::bad_alloc>([&]{ (void)both.at(long_key); });
    rejects<std::bad_alloc>([&]{ (void)both.find(long_key); });
    allocation_probe::fail_after=-1;
    require(both.has(long_key).native_value());
    require(both.remove(long_key).native_value() && !both.remove(long_key).native_value());
    both.clear(); require(both.empty().native_value());
    std::puts("hash consumer: all four combinations, failure and ownership proofs; 10000 borrowed lookups: zero allocations");
}
