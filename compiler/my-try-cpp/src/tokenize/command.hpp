#pragma once
#include "scpp_compiler/vector_string.hpp"

namespace scpp::compiler {
	using compiler_support::vector_string;
	int tokenize_command(const vector_string &arguments);
}
