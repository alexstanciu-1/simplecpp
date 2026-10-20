#pragma once
#include "cross_identity_support.hpp"
#include <cmath>
namespace completion_probe {
using namespace identity_probe;
enum class mode { ordering, power, concat, append };
inline int classify(const std::exception& e) {
 if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)) {
  if(p->code()=="invalid_nullable_unwrap_empty")return 1;
  if(p->code()=="division_by_zero")return 4;
  if(p->code()=="modulo_by_zero")return 5;
  if(p->code()=="power_negative_exponent")return 7;
  if(p->code()=="power_overflow")return 8;
  if(p->code()=="unordered_comparison")return 9;
 }
 std::string text=e.what();
 if(text.find("scpp::nullable:")!=text.npos)return 1;
 if(text.find("scpp::result_or_false:")!=text.npos)return 3;
 if(text.find("scpp::result_or_bool:")!=text.npos)return 6;
 if(text.find("scpp::result:")!=text.npos)return 2;
 return 90;
}
template<int F,mode M,class P> int extraction(State<P> s) {
 if(s.kind==state_kind::payload)return 0;
 if constexpr(M==mode::concat || M==mode::append)return F==1?2:0;
 return F==0?1:F==1?2:F==2?3:F==3?6:90;
}
template<class R> struct Expected { int error=0; R value{}; };
template<mode M,class P,class Q> auto calculation(State<P> a,State<Q> b) {
 if constexpr(M==mode::concat || M==mode::append) {
  return Expected<std::string>{0,(a.kind==state_kind::payload?a.value:std::string{})+(b.kind==state_kind::payload?b.value:std::string{})};
 } else if constexpr(M==mode::ordering) {
  double x=static_cast<double>(a.value),y=static_cast<double>(b.value);
  return Expected<std::int64_t>{std::isnan(x)||std::isnan(y)?9:0,x<y?-1:x>y?1:0};
 } else {
  auto base=a.value,exp=b.value;
  if(exp<0)return Expected<std::int64_t>{7,0};
  if(exp==0)return Expected<std::int64_t>{0,1};
  if(base==0||base==1)return Expected<std::int64_t>{0,base};
  if(base==-1)return Expected<std::int64_t>{0,(exp%2)?-1:1};
  __int128 value=1;
  for(std::int64_t i=0;i<exp;++i){value*=base;if(value<std::numeric_limits<std::int64_t>::min()||value>std::numeric_limits<std::int64_t>::max())return Expected<std::int64_t>{8,0};}
  return Expected<std::int64_t>{0,static_cast<std::int64_t>(value)};
 }
}
template<bool Compound,class P,class R> State<P> written_state(State<P> prior, int error, R value) {
 if constexpr(Compound) { if(!error)return State<P>{state_kind::payload,value}; }
 return prior;
}
template<bool Compound,class Actual,class R> bool result_matches(const Actual& actual,R expected) {
 if constexpr(Compound)return same(actual,State<R>{state_kind::payload,expected});
 else return actual.native_value()==expected;
}
} // namespace completion_probe
