#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
for (; ((!(local_x).has_value())).native_value(); (void)(([&]() -> scpp::nullable<scpp::int_t<>> { local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(2LL)}; return local_x; }()))) {
}
}
	return static_cast<int>((static_cast<scpp::int_t<>>(1LL)).native_value());
	return 0;
}
