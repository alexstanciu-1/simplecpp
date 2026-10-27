#pragma once
#include "startup/catalog.hpp"
#include "sources/inventory.hpp"

namespace scpp::compiler
{
	class Model final
	{
	  public:
		static boolean catalogs_ready() noexcept;
		static const catalog_inventory &catalogs();
		static const source_inventory &sources();
		static const startup_configuration &configuration();
		static const startup_measurements &measurements();

	  private:
		friend class Compiler_Lifecycle;
		static boolean ready_;
		static source_inventory sources_;
		static catalog_inventory catalogs_;
		static startup_configuration configuration_;
		static startup_measurements measurements_;
	};

	class Compiler_Lifecycle final {

	  public:
		// Startup/reset are coordinator-only and must not overlap readers or workers.
		// Returns false for identical retained configuration, with no catalog rereads.
		static boolean initialize(startup_configuration configuration, uint64 configuration_us = 0);
		static void rescan_sources();
		static void tokenize_sources(boolean profile = false);
		static void load_sources(boolean profile = false);
		static void notify_source(int64 id);
		static void acknowledge_source(int64 id);
		static void reset();
	};
}
