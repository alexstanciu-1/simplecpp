#pragma once
#include "scpp_compiler/types.hpp"

namespace scpp::compiler_support
{
	uint64 monotonic_micros();
}

namespace scpp::compiler
{
	using compiler_support::monotonic_micros;
}
