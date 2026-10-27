#pragma once
#include "scpp_compiler/string.hpp"
#include "scpp_compiler/vector_string.hpp"

namespace scpp::compiler
{
	using compiler_support::vector_string;
	struct startup_configuration final
	{
		string catalog_directory;
		vector_string modules;
		vector_string source_roots;
		uint32 jobs = 12;
		boolean help = false;
		boolean timings = false;
		void swap(startup_configuration &other) noexcept;
		boolean same_inputs(const startup_configuration &other) const;
	};

	void validate_module_name(const string &name);
	startup_configuration parse_configuration(const vector_string &arguments);
}
