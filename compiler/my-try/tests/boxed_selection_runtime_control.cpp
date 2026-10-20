#include "scpp/lang/php.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "operators/coalesce/coalesce.hpp"
#include <iostream>
#include <type_traits>
template<class W,class T> bool selected_control(W good,W absent){
 int left=0,right=0;
 auto result=scpp::php::coalesce_eval([&](){++left;return good;},[&](){++right;return absent;});
 static_assert(std::is_same_v<decltype(result),T>);
 if(left!=1||right!=0)return false;
 auto payload=scpp::detail::normalize_coalesce_branch<T>(good);
 static_assert(std::is_same_v<decltype(payload),T>);
 bool caught=false;
 try{scpp::php::coalesce_eval([&](){++left;return absent;},[&](){++right;return absent;});}
 catch(const scpp::runtime_error& e){caught=e.code()=="coalesce_selected_branch_has_no_usable_value_domain";}
 return caught&&left==2&&right==1;
}
int main(){
 using I=scpp::int_t<>;using B=scpp::bool_t;using S=scpp::string_t;
 // Selection returns a payload while retaining lazy state checks.
 bool a=selected_control<scpp::nullable<I>,I>(scpp::nullable<I>{I{0}},scpp::nullable<I>{scpp::null});
 bool b=selected_control<scpp::result<B>,B>(scpp::result<B>{B{false}},scpp::result<B>{scpp::error_t{S{"failure"}}});
 bool c=selected_control<scpp::result_or_false<S>,S>(scpp::result_or_false<S>{S{""}},scpp::result_or_false<S>{scpp::false_sentinel});
 std::cout<<"selection controls="<<(a&&b&&c)<<"; existing selected-payload normalization available\n";
 return a&&b&&c?0:1;
}
