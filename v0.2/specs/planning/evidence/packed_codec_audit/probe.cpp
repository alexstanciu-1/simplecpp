#include "scpp/hash_t.hpp"
#include <cstdio>
#include <limits>
using table=scpp::hash_detail::packed_table<int>;
using integer=scpp::int_t<>;
int main() {
    table normal; (void)normal.append(10); (void)normal.append(20);
    std::printf("packed: size=%zu key_capacity=%zu index_capacity=%zu\n",normal.size(),normal.key_capacity(),normal.index_capacity());
    table alias; alias.set(integer{1LL},10); alias.set(integer{4294967297LL},20);
    std::printf("wide: size=%zu at(1)=%d at(2^32+1)=%d\n",alias.size(),alias.at(integer{1LL}),alias.at(integer{4294967297LL}));
    const auto token=scpp::global_string_pool::instance().intern(scpp::string_t{"first-key"});
    table tagged; tagged.set(scpp::string_t{"first-key"},30); tagged.set(integer{static_cast<long long>(token)},40);
    std::printf("tagged: token=%u size=%zu string=%d integer=%d\n",token,tagged.size(),tagged.at(scpp::string_t{"first-key"}),tagged.at(integer{static_cast<long long>(token)}));
    table negative; negative.set(integer{-1LL},50);
    std::printf("negative: size=%zu has(-1)=%d packed=%d\n",negative.size(),negative.has(integer{-1LL}).native_value(),negative.is_packed().native_value());
    table boundary; boundary.set(integer{2147483647LL},60);
    auto assigned=boundary.append(70);
    std::printf("append: assigned=%lld is_string_code=%d size=%zu next=%lld\n",static_cast<long long>(assigned.native_value()),scpp::hash_detail::dyn_keys::is_string(static_cast<std::uint32_t>(assigned.native_value())),boundary.size(),static_cast<long long>(boundary.append(80).native_value()));
    const auto before=scpp::global_string_pool::instance().intern(scpp::string_t{"before-probe"});
    table empty; (void)empty.has(scpp::string_t{"probe-only"});
    const auto after=scpp::global_string_pool::instance().intern(scpp::string_t{"after-probe"});
    std::printf("miss-intern: ids advanced=%u (one would mean no miss interning)\n",after-before);
    for(const char* text : {"12","08","-1","2147483648","4294967295","4294967296"}) {
        const auto code=scpp::hash_detail::dyn_keys::pack(scpp::string_t{text});
        std::printf("text-code: %s => %u tagged=%d\n",text,code,scpp::hash_detail::dyn_keys::is_string(code));
    }
    table dense, sparse;
    for(int i=0;i<1000;++i) { (void)dense.append(i); sparse.set(integer{static_cast<long long>(i)*2},i); }
    std::printf("layout: object=%zu dense1000=%zu sparse1000=%zu sparse_key_capacity=%zu\n",sizeof(table),dense.estimated_storage_bytes(),sparse.estimated_storage_bytes(),sparse.key_capacity());
    union pooled_payload { const std::string* text; std::int64_t integer; };
    std::printf("candidate-layout-only: code=%zu old-pool-slot=%zu union-pool-slot=%zu kind-bitmap-per-1024=%zu\n",sizeof(std::uint32_t),sizeof(const std::string*),sizeof(pooled_payload),std::size_t{1024/8});

}
