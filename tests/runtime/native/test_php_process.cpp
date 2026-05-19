#include "test_common.hpp"

#include "lang/php/php_process.hpp"

#include <string>

namespace {

static void test_cli_helpers() {
	char arg0[] = "program";
	char arg1[] = "alpha";
	char arg2[] = "beta";
	char* argv[] = {arg0, arg1, arg2};

	scpp::php::set_cli_args(3, argv);

	const auto argc = scpp::php::cli_argc();
	assert(argc.native_value() == 3);

	const auto mixed_argv = scpp::php::cli_argv();
	assert(mixed_argv.kind() == scpp::mixed_t::kind_t::table_v);
	assert(mixed_argv.get_hash().size() == 3);
	assert(mixed_argv.get_hash().at(scpp::int_t(1)).get_string().native_value() == "alpha");
	assert(mixed_argv.get_hash().at(scpp::int_t(2)).get_string().native_value() == "beta");
}

static void test_shell_exec_success() {
	const auto output = scpp::php::shell_exec(scpp::string_t("printf process_runtime_ok"));
	assert(output.has_value().native_value() == true);
	assert(output.value().native_value() == "process_runtime_ok");
}

static void test_cli_args_alias() {
	char arg0[] = "program";
	char arg1[] = "alias";
	char* argv[] = {arg0, arg1};

	scpp::php::set_cli_args(2, argv);

	const auto args = scpp::php::cli_args();
	assert(args.kind() == scpp::mixed_t::kind_t::table_v);
	assert(args.get_hash().at(scpp::int_t(1)).get_string().native_value() == "alias");
}

} // namespace

int main() {
	test_cli_helpers();
	test_cli_args_alias();
	test_shell_exec_success();
	return 0;
}
