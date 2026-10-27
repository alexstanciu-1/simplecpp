#pragma once
#include "scpp_compiler/string.hpp"
#include "scpp_compiler/vector_string.hpp"

namespace scpp::compiler_support
{
	[[noreturn]] void fail(const string &message);
	// Call only from an active catch handler.
	[[noreturn]] void rethrow_with_context(const string &context);
	string read_standard_input();
	int run_host(int argc, char **argv, int (*entry)(const vector_string &));
}

namespace scpp::compiler
{
	using compiler_support::fail;
	using compiler_support::rethrow_with_context;
	using compiler_support::run_host;
	using compiler_support::read_standard_input;
}
