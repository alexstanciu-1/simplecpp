#pragma once
#include "scpp/string.hpp"

namespace scpp::api_generation
{
	// Prepared metadata/request JSON in; deterministic generation plan JSON out.
	// No file writes, compiler invocation or process-global generation state.
	string generate(const string &request);
}
