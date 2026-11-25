#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n);
scpp::nullable<scpp::int_t<>> function_probe(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)};
}


int main()
{
	auto local_n = static_cast<scpp::int_t<>>(0LL);
	scpp::int_t<> local_x = scpp::int_t<>((([&]() -> scpp::nullable<scpp::int_t<>> {
	scpp::int_t<>& argument_0 = local_n;
	return function_probe(argument_0);
}())).require_value("nullable required-value extraction"));
	return static_cast<int>(((local_x + local_n)).native_value());
	return 0;
}
