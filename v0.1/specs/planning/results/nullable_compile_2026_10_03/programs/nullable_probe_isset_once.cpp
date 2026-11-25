#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/operators/isset/isset.hpp"
scpp::int_t<> function_probe(scpp::int_t<>& local_n);
scpp::int_t<> function_probe(scpp::int_t<>& local_n)
{
	static_cast<scpp::int_t<>>(local_n++);
	return static_cast<scpp::int_t<>>(0LL);
}


int main()
{
	auto local_n = static_cast<scpp::int_t<>>(0LL);
	auto local_v = scpp::php::isset(([&]() -> scpp::int_t<> {
	scpp::int_t<>& argument_0 = local_n;
	return function_probe(argument_0);
}()));
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((local_v).native_value()) { return local_n; } return static_cast<scpp::int_t<>>(9LL); }())).native_value());
	return 0;
}
