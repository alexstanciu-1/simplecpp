#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/null_t.hpp"
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n);
scpp::int_t<> function_fallback(scpp::int_t<>& local_n);
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return scpp::nullable<scpp::int_t<>>{scpp::null};
}

scpp::int_t<> function_fallback(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return static_cast<scpp::int_t<>>(7LL);
}


int main()
{
	auto local_n = static_cast<scpp::int_t<>>(0LL);
	auto local_x = ([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_2 = ([&]() -> scpp::nullable<scpp::int_t<>> {
	scpp::int_t<>& argument_0 = local_n;
	return function_probe(argument_0);
}()); if (coalesce_2.has_value().native_value()) { return scpp::int_t<>((coalesce_2).require_value("nullable required-value extraction")); } return ([&]() -> scpp::int_t<> {
	scpp::int_t<>& argument_1 = local_n;
	return function_fallback(argument_1);
}()); }());
	return static_cast<int>(((local_x + local_n)).native_value());
	return 0;
}
