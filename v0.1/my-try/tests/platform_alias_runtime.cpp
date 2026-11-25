#include "scpp/lang/php.hpp"
#include <chrono>
#include <cmath>
#include <cstdlib>
#include <iostream>
#include <regex>
int main(int argc,char**){
 using S=scpp::string_t;
 auto before=std::chrono::system_clock::now();
 auto numeric=scpp::php::microtime(scpp::bool_t{true}).native_value();
 auto text=scpp::php::microtime().native_value();
 auto after=std::chrono::system_clock::now();
 double low=std::chrono::duration<double>(before.time_since_epoch()).count()-1.0;
 double high=std::chrono::duration<double>(after.time_since_epoch()).count()+1.0;
 if(!std::isfinite(numeric)||numeric<low||numeric>high||!std::regex_match(text,std::regex{"0\\.[0-9]{8} [0-9]+"}))return 1;
 bool caught=false;try{scpp::php::microtime(scpp::bool_t{false});}catch(const std::logic_error&){caught=true;}
 if(!caught)return 2;
 if(!scpp::php::getenv(S{"SCPP_ALIAS_MISSING"}).is_false().native_value())return 3;
 auto empty=scpp::php::getenv(S{"SCPP_ALIAS_EMPTY"});if(!empty.has_value().native_value()||!empty.value().native_value().empty())return 4;
 auto value=scpp::php::getenv(S{"SCPP_ALIAS_VALUE"});if(value.value().native_value()!="original")return 5;
 ::setenv("SCPP_ALIAS_VALUE","changed",1);if(value.value().native_value()!="original")return 6;
 caught=false;try{scpp::php::getenv(S{std::string{"SCPP_ALIAS_VALUE\0suffix",23}});}catch(const scpp::ValueError&){caught=true;}
 std::cout<<"clock modes; missing/empty/copied environment; nul_rejected="<<caught<<'\n';
 return (argc>1||caught)?0:7;
}
