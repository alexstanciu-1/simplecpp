#include "startup/model.hpp"
#include "scpp_compiler/host.hpp"

namespace scpp::compiler
{

	/**
	 * Construct test inputs through the same argument parser used by startup.
	 */
	static startup_configuration configuration(const string &path, const char *module)
	{
		vector_string arguments;
		arguments.append(string("--catalogs"));
		arguments.append(path);
		arguments.append(string("--module"));
		arguments.append(string(module));
		return parse_configuration(arguments);
	}

	/**
	 * Exercise failed initialization, idempotent reuse and reset while retaining an external JSON node.
	 */
	static int test(const vector_string &arguments)
	{
		const auto good = configuration(arguments.read(0), "left");
		const auto bad = configuration(arguments.read(1), "left");

		bool failed = false;
		try {
			Compiler_Lifecycle::initialize(bad);
		}
		catch (...) {
			failed = true;
		}
		if (!failed || Model::catalogs_ready()) {
			return 1;
		}
		try {
			(void)Model::catalogs();
			return 2;
		}
		catch (...) {
		}

		if (!Compiler_Lifecycle::initialize(good) || !Model::catalogs_ready()) {
			return 3;
		}
		if (Model::catalogs().modules.size() != 2 || Model::measurements().files_read != 2) {
			return 4;
		}
		if (!Model::catalogs().module_names.read(0).equals("base")) {
			return 5;
		}
		if (Compiler_Lifecycle::initialize(good) || Model::measurements().files_read != 2) {
			return 6;
		}

		failed = false;
		try {
			Compiler_Lifecycle::initialize(bad);
		}
		catch (...) {
			failed = true;
		}
		if (!failed || !Model::catalogs_ready() || Model::catalogs().modules.size() != 2) {
			return 7;
		}

		// An external immutable node remains owned after model retirement.
		auto saved = Model::catalogs().modules.read(0);

		Compiler_Lifecycle::reset();
		if (Model::catalogs_ready() || !saved.member("module").text().equals("base")) {
			return 8;
		}

		if (!Compiler_Lifecycle::initialize(good)) {
			return 9;
		}

		Compiler_Lifecycle::reset();
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::test);
}
