#pragma once
#include "lang/php/operators/type_predicates/type_predicates.hpp"
#include "scpp/hash_t.hpp"
#include <bit>
#include <limits>
#include <sstream>
#include <vector>
namespace predicate_probe {
using M=scpp::mixed_t;using I=scpp::int_t<>;using F=scpp::float_t;using B=scpp::bool_t;using S=scpp::string_t;
// Observe storage without conversion, copying the carrier, or detaching its table.
inline std::string storage(const M& v){
 std::ostringstream s;s<<static_cast<int>(v.kind())<<":";
 if(auto p=v.try_get_bool())s<<p->native_value();
 if(auto p=v.try_get_int())s<<p->native_value();
 if(auto p=v.try_get_float())s<<std::bit_cast<std::uint64_t>(p->native_value());
 if(auto p=v.string_if())s<<p->native_value();
 if(auto p=v.table_if())s<<p;
 if(auto p=v.shared_table_if())s<<p->get();
 if(auto p=v.weak_table_if())s<<p->lock().get();
 return s.str();
}
template<class Bool,class Int,class Float> long verify(Bool isbool,Int isint,Float isfloat){
 struct Case{M value;int mask;};std::vector<Case> rows;
 rows.push_back({M{},0});for(bool v:{false,true})rows.push_back({M{B{v}},1});
 for(std::int64_t v:{std::numeric_limits<std::int64_t>::min(),std::int64_t{-1},std::int64_t{0},std::int64_t{7},std::numeric_limits<std::int64_t>::max()})rows.push_back({M{I{v}},2});
 for(double v:{0.,-0.,7.,3.5,std::numeric_limits<double>::infinity(),-std::numeric_limits<double>::infinity(),std::numeric_limits<double>::quiet_NaN()})rows.push_back({M{F{v}},4});
 for(std::string v:{std::string{},std::string{"7"},std::string{"false"},std::string{"a\0b",3}})rows.push_back({M{S{v}},0});
 auto owned=std::make_unique<scpp::hash_t<M>>();owned->append(M{I{7}});rows.push_back({M{std::move(owned)},0});
 auto shared=std::make_shared<scpp::hash_t<M>>();shared->append(M{I{7}});rows.push_back({M{shared},0});rows.push_back({M{std::weak_ptr<scpp::hash_t<M>>{shared}},0});
 auto expired=std::make_shared<scpp::hash_t<M>>();std::weak_ptr<scpp::hash_t<M>> weak=expired;expired.reset();rows.push_back({M{weak},0});
 long checks=0;
 for(auto& row:rows){const M& v=row.value;auto before=storage(v);
  int actual=(isbool(v).native_value()?1:0)|(isint(v).native_value()?2:0)|(isfloat(v).native_value()?4:0);
  if(actual!=row.mask||storage(v)!=before)throw std::runtime_error("predicate kind/state mismatch");
  // A retained observation borrow would reject this kind change.
  row.value=S{"after"};if(row.value.get_string().native_value()!="after")throw std::runtime_error("predicate retained storage");checks+=3;
 }
 if(shared->at(I{0}).get_int().native_value()!=7)throw std::runtime_error("table contents changed");
 return checks;
}
}
