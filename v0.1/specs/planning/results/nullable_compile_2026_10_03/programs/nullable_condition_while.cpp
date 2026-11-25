#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/null_t.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(3LL)};
	auto local_n = static_cast<scpp::int_t<>>(0LL);
while (scpp::php::condition_truthy(local_x).native_value()) {
	static_cast<scpp::int_t<>>(local_n++);
	local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
}
	return static_cast<int>((local_n).native_value());
	return 0;
}
