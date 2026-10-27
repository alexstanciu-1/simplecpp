#pragma once
#include "startup/configuration.hpp"
#include "scpp_compiler/json_value.hpp"

namespace scpp::compiler
{
	using compiler_support::json_value;
	using compiler_support::vector_json;
	// Immutable unresolved metadata inventory, not the semantic type registry.
	struct catalog_inventory final
	{
		vector_json modules;
		vector_string module_names;
		vector_string function_ids;
		vector_string type_ids;
		vector_string property_ids;
		vector_string cpp_type_ids;
		vector_string constant_names;
		void swap(catalog_inventory &other) noexcept;
	};

	struct startup_measurements final
	{
		uint64 configuration_us = 0;
		uint64 loading_us = 0;
		uint64 validation_us = 0;
		uint64 publication_us = 0;
		uint64 files_read = 0;
		uint64 bytes_read = 0;
	};

	void validate_catalog(const json_value &root, const string &expected, catalog_inventory &inventory);
	void load_catalogs(const startup_configuration &, catalog_inventory &, startup_measurements &);
}
