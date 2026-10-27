#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/filesystem.hpp"
#include "startup/catalog.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/textbuilder.hpp"

namespace scpp::compiler
{
	using compiler_support::json_value;

	/**
	 * Exchange the complete inventory without allocating during model publication.
	 */
	void catalog_inventory::swap(catalog_inventory &other) noexcept
	{
		this->modules.swap(other.modules);
		this->module_names.swap(other.module_names);
		this->function_ids.swap(other.function_ids);
		this->type_ids.swap(other.type_ids);
		this->property_ids.swap(other.property_ids);
		this->cpp_type_ids.swap(other.cpp_type_ids);
		this->constant_names.swap(other.constant_names);
	}

	/**
	 * Load dependencies before their owner, rejecting cycles and retaining path context on failure.
	 * This serial startup traversal does not schedule compilation work.
	 */
	static void visit(const startup_configuration &config,
		const string &name,
		const vector_string &ancestors,
		catalog_inventory &inventory,
		startup_measurements &metrics)
	{
		validate_module_name(name);
		if (inventory.module_names.contains(name)) {
			return;
		}
		if (ancestors.contains(name)) {
			fail(string("Runtime module dependency cycle"));
		}
		if (ancestors.size() >= 128) {
			fail(string("Runtime dependency depth exceeds 128"));
		}

		vector_string active(ancestors);
		active.append(name);

		textbuilder path;
		path.append(config.catalog_directory);
		path.append("/");
		path.append(name);
		path.append(".json");
		const auto file = path.finish();

		try
		{
			const auto read_started = monotonic_micros();
			const auto text = fs_read(file);
			++metrics.files_read;
			metrics.bytes_read += text.byte_size();
			const auto root = json_value::parse(text);
			metrics.loading_us += monotonic_micros() - read_started;

			const auto validation_started = monotonic_micros();
			validate_catalog(root, name, inventory);
			metrics.validation_us += monotonic_micros() - validation_started;

			const auto dependencies = root.member("dependencies");
			for (int64 i = 0; i < dependencies.size(); ++i) {
				visit(config, dependencies.at(i).text(), active, inventory, metrics);
			}

			inventory.modules.append(root);
			inventory.module_names.append(name);
		}
		catch (...) {
			rethrow_with_context(file);
		}
	}

	void load_catalogs(const startup_configuration &config, catalog_inventory &inventory, startup_measurements &metrics)
	{
		vector_string ancestors;
		for (int64 i = 0; i < config.modules.size(); ++i) {
			visit(config, config.modules.read(i), ancestors, inventory, metrics);
		}
	}
}
