#pragma once
#include "scpp/lang/php.hpp"
namespace payload_probe {
template<class T> T identity(const T& value){return value;}
template<class T> scpp::int_t<> exact(const T&){return scpp::int_t<>{2};}
template<class T> scpp::int_t<> fallback(const T&){return scpp::int_t<>{1};}
template<class A,class B> scpp::int_t<> pair(const A& a,const B&,scpp::int_t<>& trace){trace=scpp::int_t<>{trace.native_value()*10+3};return scpp::int_t<>{a};}
}
