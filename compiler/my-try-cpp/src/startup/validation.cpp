#include "startup/catalog.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/textbuilder.hpp"

namespace scpp::compiler
{
	using compiler_support::json_kind;

	static void reject_catalog_value(const char *message, const string &detail)
	{
		textbuilder text;
		text.append(message);
		text.append(": ");
		text.append(detail);
		fail(text.finish());
	}

	/**
	 * Attach an array section and zero-based row only on failure.
	 */
	[[noreturn]] static void rethrow_row(const string &section, int64 index)
	{
		textbuilder context;
		context.append(section);
		context.append("[");
		context.append_number(index);
		context.append("]");
		rethrow_with_context(context.finish());
	}

	/**
	 * Read a required field and name missing keys before deeper validation.
	 */
	static json_value catalog_field(const json_value &object, const char *field)
	{
		if (!object.has(field)) {
			reject_catalog_value("Missing catalog field", string(field));
		}
		return object.member(field);
	}

	static void require_kind(const json_value &value, json_kind kind, const char *field = "value")
	{
		if (value.kind() != kind) {
			reject_catalog_value("Invalid catalog field kind", string(field));
		}
	}

	static string require_name(const json_value &value)
	{
		auto text = value.text();
		if (empty(text)) {
			fail(string("Empty catalog name"));
		}
		return text;
	}

	/**
	 * Read a nonempty name while retaining its field context.
	 */
	static string name_field(const json_value &object, const char *field)
	{
		try {
			return require_name(catalog_field(object, field));
		}
		catch (...) {
			rethrow_with_context(string(field));
		}
	}

	/**
	 * Read an exact integer while retaining its field context.
	 */
	static int64 integer_field(const json_value &object, const char *field)
	{
		try {
			return catalog_field(object, field).integer();
		}
		catch (...) {
			rethrow_with_context(string(field));
		}
	}

	/**
	 * Reject fields outside the allowed vocabulary after checking the object kind.
	 */
	static void validate_fields(const json_value &object, const char *allowed)
	{
		require_kind(object, json_kind::object);
		for (int64 i = 0; i < object.size(); ++i) {
			auto key = object.key(i);
			if (!key.is_one_of(allowed)) {
				reject_catalog_value("Unknown catalog field", key);
			}
		}
	}

	static void register_unique_name(vector_string &seen, const string &value)
	{
		if (seen.contains(value)) {
			reject_catalog_value("Duplicate catalog identity/name", value);
		}
		seen.append(value);
	}

	/**
	 * Validate unique nonempty names and, when supplied, their allowed vocabulary.
	 */
	static void validate_names(const json_value &array, const char *vocabulary = "")
	{
		require_kind(array, json_kind::array);
		vector_string seen;
		for (int64 i = 0; i < array.size(); ++i)
		{
			try
			{
				auto item = require_name(array.at(i));
				if (vocabulary[0] != '\0' && !item.is_one_of(vocabulary)) {
					reject_catalog_value("Invalid catalog choice", item);
				}
				register_unique_name(seen, item);
			}
			catch (...) {
				rethrow_row(string("names"), i);
			}
		}
	}

	/**
	 * Validate a named list while preserving its field name for nested errors.
	 */
	static void validate_name_list_field(const json_value &object, const char *field,
		const char *vocabulary = "")
	{
		try {
			validate_names(catalog_field(object, field), vocabulary);
		}
		catch (...) {
			rethrow_with_context(string(field));
		}
	}

	/**
	 * Validate an optional field with explicit diagnostic context.
	 */
	static void validate_optional_kind(const json_value &object, const char *key, json_kind kind)
	{
		if (object.has(key))
		{
			try {
				require_kind(object.member(key), kind, key);
			}
			catch (...) {
				rethrow_with_context(string(key));
			}
		}
	}

	/**
	 * Preserve field context through nested validation without success-path formatting.
	 */
	static void validate_field(const json_value &object, const char *field,
		void (*validate)(const json_value &))
	{
		try {
			validate(catalog_field(object, field));
		}
		catch (...) {
			rethrow_with_context(string(field));
		}
	}

	static void validate_type_spec(const json_value &type);

	/**
	 * Validate parameter identities, type forms and mutually exclusive transfer policies.
	 */
	static void validate_parameters(const json_value &array)
	{
		require_kind(array, json_kind::array);
		vector_string seen;
		for (int64 i = 0; i < array.size(); ++i)
		{
			try
			{
				auto parameter = array.at(i);
				validate_fields(parameter, "name type passing default requires_copy requires_transfer transfer_policy variadic");
				register_unique_name(seen, name_field(parameter, "name"));
				validate_field(parameter, "type", validate_type_spec);

				auto passing = name_field(parameter, "passing");
				if (!passing.is_one_of("value reference readonly_reference observe")) {
					reject_catalog_value("Invalid passing mode", passing);
				}

				validate_optional_kind(parameter, "requires_copy", json_kind::boolean);
				validate_optional_kind(parameter, "requires_transfer", json_kind::boolean);
				validate_optional_kind(parameter, "variadic", json_kind::boolean);

				if (parameter.has("requires_transfer") && parameter.has("transfer_policy")) {
					fail(string("Conflicting transfer policy"));
				}
				if (parameter.has("transfer_policy") &&
					!name_field(parameter, "transfer_policy").is_one_of("isolated_transfer joined_shared")) {
					fail(string("Invalid transfer policy"));
				}
				validate_optional_kind(parameter, "default", json_kind::object);
			}
			catch (...) {
				rethrow_row(string("parameters"), i);
			}
		}
	}

	/**
	 * Validate recursive named, parameter and callable metadata type forms.
	 */
	static void validate_type_spec(const json_value &type)
	{
		if (type.kind() == json_kind::text) {
			(void)require_name(type);
			return;
		}

		validate_fields(type, "name arguments parameter callable void_to_null");
		const boolean parameter = type.has("parameter");
		const boolean callable = type.has("callable");
		const boolean named = type.has("name");
		if (int32(parameter) + int32(callable) + int32(named) != 1) {
			fail(string("Expected exactly one runtime type form"));
		}
		if (type.has("void_to_null") && catalog_field(type, "void_to_null").boolean() && !parameter) {
			fail(string("void_to_null requires a parameter"));
		}

		if (parameter) {
			(void)name_field(type, "parameter");
		}

		if (callable) {
			auto signature = catalog_field(type, "callable");
			validate_fields(signature, "parameters returns");
			validate_field(signature, "parameters", validate_parameters);
			validate_field(signature, "returns", validate_type_spec);
		}

		if (!named && type.has("arguments")) {
			fail(string("Arguments require a named type"));
		}

		if (named)
		{
			(void)name_field(type, "name");
			auto arguments = catalog_field(type, "arguments");
			require_kind(arguments, json_kind::array, "arguments");
			for (int64 i = 0; i < arguments.size(); ++i)
			{
				try {
					validate_type_spec(arguments.at(i));
				}
				catch (...) {
					rethrow_row(string("arguments"), i);
				}
			}
		}
	}

	/**
	 * Validate the native symbol mapping and supported source-context policy.
	 */
	static void validate_binding(const json_value &row)
	{
		validate_fields(row, "id symbol header native_payloads source_context");
		(void)name_field(row, "id");
		(void)name_field(row, "symbol");
		(void)name_field(row, "header");
		validate_optional_kind(row, "native_payloads", json_kind::boolean);
		if (row.has("source_context") && !name_field(row, "source_context").equals("phs_file_line")) {
			fail(string("Unsupported source_context"));
		}
	}

	/**
	 * Validate a function signature and register its identity across loaded catalogs.
	 */
	static void validate_function(const json_value &row, vector_string &ids)
	{
		validate_fields(row,
			"id name parameters returns snapshot_borrow_safe qualified_aliases member boxed_results shared_conversions replacement_results transfer_policy transfer_types type_parameters");
		register_unique_name(ids, name_field(row, "id"));
		(void)name_field(row, "name");
		validate_field(row, "parameters", validate_parameters);
		validate_field(row, "returns", validate_type_spec);
		validate_optional_kind(row, "snapshot_borrow_safe", json_kind::boolean);
		if (row.has("qualified_aliases")) {
			validate_name_list_field(row, "qualified_aliases");
		}
		if (row.has("type_parameters")) {
			auto generics = catalog_field(row, "type_parameters");
			validate_names(generics);
			if (generics.size() == 0) {
				fail(string("Empty type_parameters"));
			}
		}
		if (row.has("transfer_types")) {
			validate_name_list_field(row, "transfer_types");
		}
		if (row.has("transfer_policy") && !name_field(row, "transfer_policy").is_one_of("isolated_transfer joined_shared")) {
			fail(string("Invalid transfer policy"));
		}
		if (row.has("member"))
		{
			auto member = catalog_field(row, "member");
			validate_fields(member, "owner dispatch");
			validate_field(member, "owner", validate_type_spec);
			if (!name_field(member, "dispatch").is_one_of("static instance")) {
				fail(string("Invalid member dispatch"));
			}
		}

		validate_optional_kind(row, "boxed_results", json_kind::array);
		validate_optional_kind(row, "shared_conversions", json_kind::array);
		validate_optional_kind(row, "replacement_results", json_kind::array);
	}

	/**
	 * Validate runtime type capabilities, generic parameters and optional container metadata.
	 */
	static void validate_type_definition(const json_value &row, vector_string &ids)
	{
		validate_fields(row,
			"id name kind construction empty_initialization default_constructible storage capabilities parameters indexing iteration initialization");
		register_unique_name(ids, name_field(row, "id"));
		(void)name_field(row, "name");
		if (!name_field(row, "kind").is_one_of("class struct")) {
			fail(string("Invalid runtime type kind"));
		}
		validate_name_list_field(row, "capabilities", "value_storable copyable_value shared_record hashable comparable result_payload");
		if (row.has("storage") && !name_field(row, "storage").is_one_of("inline shared")) {
			fail(string("Invalid runtime storage"));
		}
		if (row.has("construction")) {
			(void)name_field(row, "construction");
		}
		if (row.has("empty_initialization") && !name_field(row, "empty_initialization").equals("default")) {
			fail(string("Invalid empty_initialization"));
		}
		validate_optional_kind(row, "default_constructible", json_kind::boolean);
		validate_optional_kind(row, "indexing", json_kind::object);
		validate_optional_kind(row, "iteration", json_kind::object);
		validate_optional_kind(row, "initialization", json_kind::array);

		if (row.has("parameters"))
		{
			auto parameters = catalog_field(row, "parameters");
			require_kind(parameters, json_kind::array, "parameters");
			vector_string seen;
			for (int64 i = 0; i < parameters.size(); ++i)
			{
				try
				{
					auto parameter = parameters.at(i);
					validate_fields(parameter, "name requires default capability_dependencies");
					register_unique_name(seen, name_field(parameter, "name"));
					validate_name_list_field(parameter, "requires",
						"value_storable copyable_value shared_record hashable comparable result_payload");
					if (parameter.has("capability_dependencies")) {
						validate_name_list_field(parameter, "capability_dependencies",
							"value_storable copyable_value shared_record hashable comparable result_payload");
					}
					if (parameter.has("default")) {
						validate_field(parameter, "default", validate_type_spec);
					}
				}
				catch (...) {
					rethrow_row(string("parameters"), i);
				}
			}
		}
	}

	/**
	 * Validate concrete C++ type bindings and mutually exclusive type/modifier selection.
	 */
	static void validate_cpp_type(const json_value &row, vector_string &ids)
	{
		validate_fields(row,
			"id symbol header type modifier byte_interface pointee pointee_modified_type concrete_boundary literal_interface arguments");
		register_unique_name(ids, name_field(row, "id"));
		(void)name_field(row, "symbol");
		(void)name_field(row, "header");
		if (row.has("type") && row.has("modifier")) {
			fail(string("C++ binding cannot select type and modifier"));
		}
		if (row.has("type")) {
			(void)name_field(row, "type");
		}
		if (row.has("modifier")) {
			(void)name_field(row, "modifier");
		}
		if (row.has("pointee")) {
			(void)name_field(row, "pointee");
		}
		if (row.has("byte_interface")) {
			(void)name_field(row, "byte_interface");
		}
		validate_optional_kind(row, "concrete_boundary", json_kind::boolean);
		validate_optional_kind(row, "pointee_modified_type", json_kind::boolean);
		validate_optional_kind(row, "arguments", json_kind::array);
		if (row.has("literal_interface") && !name_field(row, "literal_interface").is_one_of("scalar bytes")) {
			fail(string("Invalid literal_interface"));
		}
	}

	/**
	 * Validate integer constants and register owner-qualified identities.
	 */
	static void validate_constant(const json_value &row, catalog_inventory &inventory)
	{
		validate_fields(row, "name type value owner");
		if (!name_field(row, "type").equals("int")) {
			fail(string("Constant type must be int"));
		}
		(void)integer_field(row, "value");
		textbuilder identity;
		if (row.has("owner")) {
			auto owner = name_field(row, "owner");
			identity.append("owner:");
			identity.append_number(owner.byte_size());
			identity.append(":");
			identity.append(owner);
		}
		else {
			identity.append("free:");
		}
		identity.append(name_field(row, "name"));
		register_unique_name(inventory.constant_names, identity.finish());
	}

	/**
	 * Validate property identity, owner, type and access policy.
	 */
	static void validate_property(const json_value &row, catalog_inventory &inventory)
	{
		validate_fields(row, "id name owner type static access");
		register_unique_name(inventory.property_ids, name_field(row, "id"));
		(void)name_field(row, "name");
		validate_field(row, "owner", validate_type_spec);
		validate_field(row, "type", validate_type_spec);
		validate_optional_kind(row, "static", json_kind::boolean);
		if (row.has("access") && !name_field(row, "access").is_one_of("read_only writable")) {
			fail(string("Invalid property access"));
		}
	}

	/**
	 * Validate each build setting as a list of unique names.
	 */
	static void validate_build_settings(const json_value &section)
	{
		validate_fields(section, "sources libraries definitions providers");
		for (int64 i = 0; i < section.size(); ++i)
		{
			const auto field = section.key(i);
			try {
				validate_names(section.member(field));
			}
			catch (...) {
				rethrow_with_context(field);
			}
		}
	}

	/**
	 * Validate unique non-function bindings and reject call-only policy.
	 */
	static void validate_nonfunction_binding(const json_value &row, vector_string &bindings)
	{
		validate_binding(row);
		register_unique_name(bindings, name_field(row, "id"));
		if (row.has("source_context") || row.has("native_payloads")) {
			fail(string("Call-only policy on non-function binding"));
		}
	}

	/**
	 * Route optional catalog sections to their validators and attach section/row context.
	 */
	static void validate_optional_sections(const json_value &root, catalog_inventory &inventory)
	{
		for (int64 i = 0; i < root.size(); ++i)
		{
			const auto key = root.key(i);
			if (!key.is_one_of("types constants properties cpp_types cpp_properties cpp_initialization cpp_build")) {
				continue;
			}
			auto section = root.member(key);
			try {
				if (key.equals("cpp_build")) {
					validate_build_settings(section);
					continue;
				}
				require_kind(section, json_kind::array);
			}
			catch (...) {
				rethrow_with_context(key);
			}
			vector_string bindings;
			for (int64 j = 0; j < section.size(); ++j)
			{
				try
				{
					auto row = section.at(j);
					if (key.equals("types")) {
						validate_type_definition(row, inventory.type_ids);
					}
					else if (key.equals("cpp_types")) {
						validate_cpp_type(row, inventory.cpp_type_ids);
					}
					else if (key.equals("constants")) {
						validate_constant(row, inventory);
					}
					else if (key.equals("properties")) {
						validate_property(row, inventory);
					}
					else {
						validate_nonfunction_binding(row, bindings);
					}
				}
				catch (...) {
					rethrow_row(key, j);
				}
			}
		}
	}

	/**
	 * Validate module metadata and cross-check function bindings before retaining the catalog.
	 */
	void validate_catalog(const json_value &root, const string &expected, catalog_inventory &inventory)
	{
		validate_fields(root,
			"version module dependencies functions cpp constants types properties cpp_types cpp_build cpp_properties cpp_initialization");
		if (integer_field(root, "version") != 1) {
			fail(string("Unsupported runtime catalog version"));
		}
		const auto module = name_field(root, "module");
		validate_module_name(module);
		if (!module.equals(expected)) {
			fail(string("Runtime module identity does not match filename"));
		}

		auto dependencies = catalog_field(root, "dependencies");
		try {
			validate_names(dependencies);
		}
		catch (...) {
			rethrow_with_context(string("dependencies"));
		}
		for (int64 i = 0; i < dependencies.size(); ++i) {
			validate_module_name(dependencies.at(i).text());
		}

		auto functions = catalog_field(root, "functions");
		require_kind(functions, json_kind::array, "functions");
		vector_string local_functions;
		for (int64 i = 0; i < functions.size(); ++i)
		{
			try {
				auto row = functions.at(i);
				validate_function(row, inventory.function_ids);
				register_unique_name(local_functions, name_field(row, "id"));
			}
			catch (...) {
				rethrow_row(string("functions"), i);
			}
		}

		auto cpp = catalog_field(root, "cpp");
		require_kind(cpp, json_kind::array, "cpp");
		vector_string local_bindings;
		for (int64 i = 0; i < cpp.size(); ++i)
		{
			try
			{
				auto row = cpp.at(i);
				validate_binding(row);
				auto id = name_field(row, "id");
				register_unique_name(local_bindings, id);
				if (!local_functions.contains(id)) {
					reject_catalog_value("C++ binding has no matching function", id);
				}
			}
			catch (...) {
				rethrow_row(string("cpp"), i);
			}
		}

		if (local_functions.size() != local_bindings.size()) {
			fail(string("Missing function C++ binding"));
		}

		validate_optional_sections(root, inventory);
	}
}
