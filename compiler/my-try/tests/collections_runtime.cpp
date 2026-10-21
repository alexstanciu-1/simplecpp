#include "scpp/collections.hpp"
#include <cassert>
#include <stdexcept>
using namespace scpp;
int main() {
 vector_t<int_t<>> source; source.append(int_t<>(10)); source.append(int_t<>(20)); source.append(int_t<>(30));
 int calls=0;
 auto mapped=collections::sequence_map(source,[&](int_t<> item)->string_t { ++calls; return string_t("v"); });
 assert(calls==3 && mapped.size()==3 && source.size()==3);
 auto filtered=collections::sequence_filter(source,[](int_t<> item)->bool_t { return bool_t(item.native_value()>10); });
 assert(filtered.size()==2 && filtered[int_t<>(0)].native_value()==20);
 hash_t<int_t<>,string_t> keyed; keyed.set(string_t("b"),int_t<>(2));keyed.set(string_t("a"),int_t<>(1));
 auto keys=collections::keyed_filter(keyed,[](int_t<>)->bool_t{return bool_t(true);});
 assert(keys.size()==2);
 int order=0;for(auto entry:value_iteration(keys)){order=order*10+entry.value_copy().native_value();}assert(order==21);
 vector_t<int_t<>> empty; calls=0;
 (void)collections::map(empty,[&](int_t<> item)->int_t<>{++calls;return item;});assert(calls==0);
 bool threw=false;try{(void)collections::map(source,[](int_t<>)->int_t<>{throw std::runtime_error("callback");});}catch(const std::runtime_error&){threw=true;}assert(threw && source.size()==3);
 mixed_t mixed(std::make_unique<hash_t<mixed_t>>()); mixed.table_if()->set(string_t("x"),mixed_t(int_t<>(7)));
 auto mixed_out=collections::map(mixed,[](mixed_t item)->mixed_t{return item;});assert(mixed_out.table_if()!=mixed.table_if());
 auto dynamic=dynamic_t<>::make_container();dynamic.get_table().set(string_t("x"),dynamic_t<>(int_t<>(8)));
 auto dynamic_out=collections::map(dynamic,[](dynamic_t<> item)->dynamic_t<>{return item;});assert(dynamic_out.table_if()!=dynamic.table_if());
 threw=false;try{(void)collections::map(mixed_t(int_t<>(1)),[](mixed_t item)->mixed_t{return item;});}catch(const std::exception&){threw=true;}assert(threw);
 threw=false;try{(void)collections::map(dynamic_t<>(int_t<>(1)),[](dynamic_t<> item)->dynamic_t<>{return item;});}catch(const std::exception&){threw=true;}assert(threw);
}
