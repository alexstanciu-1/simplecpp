#pragma once
#include "scpp/lang/php.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "operators/coalesce/coalesce.hpp"
#include <array>
#include <bit>
#include <limits>
#include <type_traits>
#include <vector>

template<class B,class F,class U,class O,class N>
int boxed_oracle(F band,F bor,F bxor,F shl,F shr,U bnot,O aorder,O oorder,O xorder,O lorder,O rorder,N unary){
 using I=scpp::int_t<>;using S=scpp::string_t;
 static_assert(std::is_same_v<decltype(band(B{},B{})),B>);
 static_assert(std::is_same_v<decltype(bnot(B{})),B>);
 std::array<F,5> fs{band,bor,bxor,shl,shr};std::array<O,5> orders{aorder,oorder,xorder,lorder,rorder};
 std::array<std::int64_t,7> values{0,1,7,-1,-8,std::numeric_limits<std::int64_t>::min(),std::numeric_limits<std::int64_t>::max()};
 for(auto x:values){
  if(bnot(B{I{x}}).get_int().native_value()!=~x)return 1;
  for(auto y:values){
   if(band(B{I{x}},B{I{y}}).get_int().native_value()!=(x&y))return 2;
   if(bor(B{I{x}},B{I{y}}).get_int().native_value()!=(x|y))return 3;
   if(bxor(B{I{x}},B{I{y}}).get_int().native_value()!=(x^y))return 4;
  }
  for(std::int64_t n:{0,1,63}){
   auto expected=std::bit_cast<std::int64_t>(static_cast<std::uint64_t>(x)<<n);
   if(shl(B{I{x}},B{I{n}}).get_int().native_value()!=expected||shr(B{I{x}},B{I{n}}).get_int().native_value()!=(x>>n))return 5;
  }
 }
 B table;if constexpr(std::is_same_v<B,scpp::mixed_t>)table=B{scpp::table_()};else table=B::make_container();
 std::vector<B> invalid{B{},B{scpp::bool_t{false}},B{scpp::bool_t{true}},B{scpp::float_t{2.0}},B{S{"2"}},B{S{""}},table};
 for(const auto& bad:invalid){
  bool caught=false;try{bnot(bad);}catch(const std::runtime_error&){caught=true;}if(!caught)return 6;
  for(auto fn:fs){
   for(bool badleft:{false,true}){
    caught=false;try{fn(badleft?bad:B{I{7}},badleft?B{I{2}}:bad);}catch(const std::runtime_error&){caught=true;}
    if(!caught)return 7;
   }
  }
 }
 std::array<std::int64_t,5> wanted{2,7,5,28,1};
 for(std::size_t k=0;k<5;++k){
  B a{I{7}},b{I{2}};I n{0};auto r=orders[k](a,b,n);
  if(r.get_int().native_value()!=wanted[k]||a.get_int().native_value()!=99||b.get_int().native_value()!=2||n.native_value()!=12)return 8;
  a=B{S{"bad"}};n=I{0};bool caught=false;try{orders[k](a,b,n);}catch(const std::runtime_error&){caught=true;}
  if(!caught||n.native_value()!=12||a.get_int().native_value()!=99)return 9;
 }
 I n{0};if(unary(B{I{7}},n).get_int().native_value()!=-8||n.native_value()!=1)return 10;
 return 0;
}

template<class T>T sample(bool alternate){
 if constexpr(std::is_same_v<T,scpp::string_t>)return T{alternate?"other":""};
 else if constexpr(std::is_same_v<T,scpp::bool_t>)return T{alternate};
 else if constexpr(std::is_same_v<T,scpp::float_t>)return T{alternate?7.0:0.0};
 else return T{alternate?7:0};
}
template<class W,class T,class F,class Store,class Lazy>
int selection_oracle(F choose,Store store,Lazy lazy,W absent){
 using I=scpp::int_t<>;T zero=sample<T>(false),other=sample<T>(true);W good{zero},right{other};
 I n{0};auto a=choose(good,absent,n);static_assert(std::is_same_v<decltype(a),T>);
 if(a.native_value()!=zero.native_value()||n.native_value()!=1)return 1;
 n=I{0};auto b=choose(absent,right,n);if(b.native_value()!=other.native_value()||n.native_value()!=12)return 2;
 right=W{zero};if(b.native_value()!=other.native_value())return 3;
 T output=other;n=I{0};bool caught=false;
 try{store(absent,absent,output,n);}catch(const scpp::runtime_error& e){caught=e.code()=="coalesce_selected_branch_has_no_usable_value_domain";}
 if(!caught||n.native_value()!=12||output.native_value()!=other.native_value())return 4;
 n=I{0};if(lazy(good,n).native_value()!=zero.native_value()||n.native_value()!=0)return 5;
 caught=false;try{lazy(absent,n);}catch(const scpp::runtime_error& e){caught=e.code()=="division_by_zero";}
 if(!caught||n.native_value()!=1)return 6;
 if(!good.has_value().native_value()||absent.has_value().native_value())return 7;
 return 0;
}
