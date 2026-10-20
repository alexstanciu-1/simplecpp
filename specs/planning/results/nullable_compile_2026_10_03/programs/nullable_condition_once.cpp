#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n);
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(3LL)};
}


int main()
{
	auto local_n = static_cast<scpp::int_t<>>(0LL);
if (scpp::php::condition_truthy(([&]() -> scpp::nullable<scpp::int_t<>> {
	scpp::int_t<>& argument_0 = local_n;
	return function_probe(argument_0);
}())).native_value()) {
	return static_cast<int>((local_n).native_value());
}
	return static_cast<int>((static_cast<scpp::int_t<>>(9LL)).native_value());
	return 0;
}
