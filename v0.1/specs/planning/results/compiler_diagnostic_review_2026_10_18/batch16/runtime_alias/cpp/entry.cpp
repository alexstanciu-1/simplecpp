#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "modules/strings/strings.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::string_t argument_0 = scpp::string_t("x");
	return scpp::str::byte_length(argument_0);
}())).native_value());
	return 0;
}
