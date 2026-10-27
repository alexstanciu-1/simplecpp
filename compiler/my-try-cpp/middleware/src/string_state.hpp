#pragma once
#include "scpp_compiler/string.hpp"
#include "runtime_bindings.hpp"

namespace scpp::compiler_support
{
	struct string::state {
		bindings::native_string value;
	};

	// Private bridge, never published to compiler-facing headers.
	struct runtime_access
	{
		static const bindings::native_string &get(const string &value)
		{
			return value.state_->value;
		}
		static string wrap(const bindings::native_string &value)
		{
			string result;
			result.state_->value = value;
			return result;
		}
	};
}
