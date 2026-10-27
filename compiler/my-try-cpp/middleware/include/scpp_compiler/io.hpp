#pragma once
#include "scpp_compiler/string.hpp"

namespace scpp::compiler_support
{
	void print_line(const string &message);
}

namespace scpp::compiler
{
	using compiler_support::print_line;
}
