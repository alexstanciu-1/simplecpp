#include "modules/regex/regex.hpp"
#include "regex_replacement_oracle.hpp"
int main(int argc, char**) {
	using S=scpp::string_t; using I=scpp::int_t<>; using V=scpp::vector_t<S>;
	return regex_replacement_oracle(
		[](const S& p,const S& r,const S& s,const I& limit){I count;return scpp::regex::replace(p,r,s,limit,count);},
		[](const S& p,const S& r,const V& s,const I& limit){I count;return scpp::regex::filter(p,r,s,limit,count);}, argc > 1);
}
