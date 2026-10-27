#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/filesystem.hpp"
#include "startup/model.hpp"
#include "scpp_compiler/host.hpp"

namespace scpp::compiler
{
	boolean Model::ready_ = false;
	source_inventory Model::sources_;
	catalog_inventory Model::catalogs_;
	startup_configuration Model::configuration_;
	startup_measurements Model::measurements_;
	boolean Model::catalogs_ready() noexcept
	{
		return Model::ready_;
	}

	const catalog_inventory &Model::catalogs()
	{
		if (!Model::ready_) {
			fail(string("Catalog model is not ready"));
		}
		return Model::catalogs_;
	}

	const source_inventory &Model::sources()
	{
		(void)Model::catalogs();
		return Model::sources_;
	}

	const startup_configuration &Model::configuration()
	{
		(void)Model::catalogs();
		return Model::configuration_;
	}

	const startup_measurements &Model::measurements()
	{
		(void)Model::catalogs();
		return Model::measurements_;
	}

	/**
	 * Validate and load a private candidate before publishing the singleton model.
	 * Identical inputs reuse the retained model; failures leave published state untouched.
	 */
	boolean Compiler_Lifecycle::initialize(startup_configuration configuration, uint64 configuration_us)
	{
		const auto validation_start = monotonic_micros();
		if (configuration.help || configuration.jobs == 0 || configuration.jobs > 2147483647u ||
			configuration.modules.size() == 0) {
			fail(string("Incomplete startup configuration"));
		}

		// API callers get the same validation as CLI callers.
		configuration.catalog_directory = fs_canonical_directory(configuration.catalog_directory);
		validate_source_roots(configuration.source_roots);
		vector_string selected;
		for (int64 i = 0; i < configuration.modules.size(); ++i)
		{
			auto module = configuration.modules.read(i);
			validate_module_name(module);
			if (selected.contains(module)) {
				fail(string("Duplicate selected module"));
			}
			selected.append(module);
		}

		if (Model::ready_) {
			if (Model::configuration_.same_inputs(configuration)) {
				return false;
			}
			fail(
				string("Runtime configuration is already initialized; reset explicitly before changing it"));
		}

		catalog_inventory candidate;
		startup_measurements metrics;
		metrics.configuration_us = configuration_us + monotonic_micros() - validation_start;
		load_catalogs(configuration, candidate, metrics);
		source_inventory sources;
		sources.scan(configuration.source_roots, configuration.jobs);
		sources.load(configuration.source_roots, configuration.jobs);
		sources.tokenize(configuration.jobs);

		const auto start = monotonic_micros();

		// All throwing work is complete. Commit via noexcept swaps, then publish readiness.
		Model::configuration_.swap(configuration);
		Model::catalogs_.swap(candidate);
		Model::sources_.swap(sources);
		metrics.publication_us = monotonic_micros() - start;
		Model::measurements_ = metrics;
		Model::ready_ = true;

		return true;
	}

	void Compiler_Lifecycle::rescan_sources()
	{
		const auto &config = Model::configuration();
		Model::sources_.scan(config.source_roots, config.jobs);
	}

	void Compiler_Lifecycle::tokenize_sources(boolean profile)
	{
		const auto &config = Model::configuration();
		Model::sources_.tokenize(config.jobs, profile);
	}

	void Compiler_Lifecycle::load_sources(boolean profile)
	{
		const auto &config = Model::configuration();
		Model::sources_.load(config.source_roots, config.jobs, profile);
	}

	void Compiler_Lifecycle::notify_source(int64 id)
	{
		(void)Model::catalogs();
		Model::sources_.notify(id);
	}

	void Compiler_Lifecycle::acknowledge_source(int64 id)
	{
		(void)Model::catalogs();
		Model::sources_.acknowledge(id);
	}

	/**
	 * Retire the published inventory and inputs through coordinator-only ownership swaps.
	 */
	void Compiler_Lifecycle::reset()
	{
		catalog_inventory empty;
		source_inventory no_sources;
		startup_configuration no_configuration;
		Model::sources_.swap(no_sources);
		Model::catalogs_.swap(empty);
		Model::configuration_.swap(no_configuration);
		Model::measurements_ = startup_measurements{};
		Model::ready_ = false;
	}
}
