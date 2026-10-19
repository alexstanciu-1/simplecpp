#pragma once
#include "lang/php/operators/identity/strict_identity.hpp"
#include <bit>
#include <limits>
#include <vector>
#include <string>
#include <iostream>
#include <stdexcept>
namespace identity_probe {
using I=scpp::int_t<>; using B=scpp::bool_t;
// State tags describe storage. The oracle separately maps them to visible kinds.
enum class state_kind { payload, null_value, false_value, true_value, error };
template<class P> struct State { state_kind kind; P value{}; int error=0; };
template<class P> bool storage_equal(const P& a,const P& b) {
 if constexpr(std::is_same_v<P,double>) return std::bit_cast<std::uint64_t>(a)==std::bit_cast<std::uint64_t>(b);
 else return a==b;
}
// Normalize each operand independently; storage tags remain unchanged.
template<class P> state_kind visible_kind(State<P> s) {
 if constexpr(std::is_same_v<P,bool>) {
  if(s.kind==state_kind::payload) return s.value?state_kind::true_value:state_kind::false_value;
 }
 return s.kind;
}
template<class P,class Q> bool oracle(State<P> a,State<Q> b) {
 auto left_kind=visible_kind(a); auto right_kind=visible_kind(b);
 if(left_kind!=right_kind) return false;
 if(left_kind==state_kind::payload) {
  if constexpr(std::is_same_v<P,Q>) return a.value==b.value;
  else return false; // Numeric kinds stay distinct; never coerce to bool.
 }
 if(left_kind==state_kind::error) return a.error==b.error;
 return true;
}
inline scpp::error_t make_error(int id) { return scpp::error_t{scpp::string_t{id==1?"other":"failure"},I{id==2?18:17},scpp::string_t{id==3?"elsewhere":"origin"}}; }
template<class P> std::vector<P> values() {
 if constexpr(std::is_same_v<P,bool>) return {false,true};
 else if constexpr(std::is_same_v<P,double>) return {0.,-0.,1.,-1.,1.5,9007199254740992.,9007199254740994.,-9007199254740992.,9223372036854775808.,-9223372036854775808.,std::numeric_limits<P>::infinity(),-std::numeric_limits<P>::infinity(),std::numeric_limits<P>::max(),std::numeric_limits<P>::denorm_min(),std::bit_cast<double>(std::uint64_t{0x7ff8000000000001ULL}),std::bit_cast<double>(std::uint64_t{0xfff8000000000002ULL})};
 else if constexpr(std::is_same_v<P,std::string>) return {"","0","1","a","ab",P{"a\0b",3},P{"a\0c",3},P{"\x80",1},P{"\xff",1},"é","é"};
 else return {std::numeric_limits<P>::min(),std::numeric_limits<P>::max(),-9007199254740993LL,-9007199254740992LL,9007199254740992LL,9007199254740993LL,9007199254740994LL,-1,0,1};
}
// Families: 0 nullable, 1 structured Result, 2 false sentinel, 3 bool sentinels, 4 plain.
template<int F,class V> using Carrier=std::conditional_t<F==4,V,std::conditional_t<F==0,scpp::nullable<V>,std::conditional_t<F==1,scpp::result<V>,std::conditional_t<F==2,scpp::result_or_false<V>,scpp::result_or_bool<V>>>>>;
template<int F,class P> std::vector<State<P>> states() {
 std::vector<State<P>> out;
 for(P value:values<P>()) out.push_back({state_kind::payload,value});
 if constexpr(F==0) out.push_back({state_kind::null_value});
 if constexpr(F==1) for(int e=0;e<4;++e) out.push_back({state_kind::error,P{},e});
 if constexpr(F==2 || F==3) out.push_back({state_kind::false_value});
 if constexpr(F==3) out.push_back({state_kind::true_value});
 return out;
}
template<int F,class V,class P> Carrier<F,V> make(State<P> s) {
 if(s.kind==state_kind::payload) return Carrier<F,V>{V{s.value}};
 if constexpr(F==0) return Carrier<F,V>{scpp::null};
 if constexpr(F==1) return Carrier<F,V>{make_error(s.error)};
 if constexpr(F==2) return Carrier<F,V>{scpp::false_sentinel};
 if constexpr(F==4) throw std::logic_error("plain value cannot have an absent state");
 if constexpr(F==3) return s.kind==state_kind::true_value?Carrier<F,V>{scpp::true_sentinel}:Carrier<F,V>{scpp::false_sentinel};
}
template<class C,class P> bool same(const C& v,State<P> s) {
 if constexpr(!requires { v.has_value(); }) return s.kind==state_kind::payload && storage_equal(P{v.native_value()},s.value);
 else {
 if(v.has_value().native_value()!=(s.kind==state_kind::payload)) return false;
 if(s.kind==state_kind::payload) return storage_equal(P{v.require_value("observe").native_value()},s.value);
 if constexpr(requires { v.has_error(); }) {
  const auto* e=v.error();auto expected=make_error(s.error);
  return e && e->get_message()==expected.get_message() && e->get_line()==expected.get_line() && e->get_file()==expected.get_file();
 } else if constexpr(requires { v.is_false(); }) return v.is_false().native_value()==(s.kind==state_kind::false_value);
 else return s.kind==state_kind::null_value;
 }
}
template<int L,int R,class V,class P,class W=V,class Q=P> long runtime_pair() {
 long count=0;
 for(auto a:states<L,P>()) for(auto b:states<R,Q>()) {
  auto left=make<L,V>(a);auto right=make<R,W>(b);bool expected=oracle(a,b);
  if(scpp::php::identical(left,right).native_value()!=expected || scpp::php::not_identical(left,right).native_value()==expected || !same(left,a) || !same(right,b)) throw std::runtime_error("runtime identity/state mismatch");
  ++count;
 }
 return count;
}
}
