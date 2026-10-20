#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n);
scpp::int_t<> function_other(scpp::int_t<>& local_n);
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(0LL)};
}

scpp::int_t<> function_other(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return static_cast<scpp::int_t<>>(7LL);
}


int main()
{
	auto local_n = static_cast<scpp::int_t<>>(0LL);
	auto local_x = ([&]() -> scpp::nullable<scpp::int_t<>> { scpp::nullable<scpp::int_t<>> conditional_1 = ([&]() -> scpp::nullable<scpp::int_t<>> {
	scpp::int_t<>& argument_0 = local_n;
	return function_probe(argument_0);
}()); if (scpp::php::condition_truthy(conditional_1).native_value()) { return conditional_1; } return scpp::nullable<scpp::int_t<>>{([&]() -> scpp::int_t<> {
	scpp::int_t<>& argument_2 = local_n;
	return function_other(argument_2);
}())}; }());
	scpp::int_t<> local_v = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
	return static_cast<int>(((local_v + local_n)).native_value());
	return 0;
}
