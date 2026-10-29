#include "scpp/hash_t.hpp"
#include "allocation_probe.hpp"
#include <cstdio>
#include <cstdlib>
using table=scpp::hash_detail::packed_table<int>;
using integer=scpp::int_t<>;
int main(int argc,char** argv) {
    if(argc!=3) return 2;
    const long fail=std::strtol(argv[2],nullptr,10);
    if(argv[1][0]=='t') {
        table values; (void)values.append(10); (void)values.append(20);
        allocation_probe::fail_after=fail;
        bool failed=false; try { values.set(integer{5LL},30); } catch(const std::bad_alloc&) { failed=true; }
        allocation_probe::fail_after=-1;
        // Inspect safe public counts/lookup only; do not iterate potentially malformed arrays.
        std::printf("transition fail=%ld threw=%d packed=%d size=%zu has0=%d has1=%d has5=%d\n",fail,failed,values.is_packed().native_value(),values.size(),values.has(integer{0LL}).native_value(),values.has(integer{1LL}).native_value(),values.has(integer{5LL}).native_value());
    } else {
        auto& pool=scpp::global_string_pool::instance();
        const scpp::string_t input{"pool-failure-key-long-enough-to-allocate"};
        allocation_probe::fail_after=fail;
        bool failed=false; try { (void)pool.intern(input); } catch(const std::bad_alloc&) { failed=true; }
        allocation_probe::fail_after=-1;
        bool resolves=false;
        try { auto id=pool.intern(input); resolves=pool.resolve(id).native_value()==input.native_value(); }
        catch(const std::exception&) {}
        std::printf("pool fail=%ld threw=%d retry_resolves=%d\n",fail,failed,resolves);
    }
}
