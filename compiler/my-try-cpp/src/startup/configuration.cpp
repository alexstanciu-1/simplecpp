#include "startup/configuration.hpp"
#include "scpp_compiler/host.hpp"

namespace scpp::compiler
{
	/**
	 * Restrict module names to safe catalog filename components.
	 */
	void validate_module_name(const string &name)
	{
		if (empty(name)) {
			fail(string("Empty runtime module name"));
		}

		for (uint64 index = 0; index < name.byte_size(); ++index) {
			const auto byte = name.byte_at(index);
			if (!((byte >= 'a' && byte <= 'z') || (byte >= '0' && byte <= '9') || byte == '_')) {
				fail(string("Invalid runtime module name"));
			}
		}
	}

	/**
	 * Exchange the retained startup inputs and reporting flags without allocating.
	 */
	void startup_configuration::swap(startup_configuration &other) noexcept
	{
		this->catalog_directory.swap(other.catalog_directory);
		this->modules.swap(other.modules);
		this->source_roots.swap(other.source_roots);

		const auto old_jobs = this->jobs;
		this->jobs = other.jobs;
		other.jobs = old_jobs;

		const auto old_help = this->help;
		this->help = other.help;
		other.help = old_help;

		const auto old_timings = this->timings;
		this->timings = other.timings;
		other.timings = old_timings;
	}

	/**
	 * Compare catalog-affecting inputs; help and timing flags do not change the retained model.
	 */
	boolean startup_configuration::same_inputs(const startup_configuration &other) const
	{
		if (!this->catalog_directory.equals(other.catalog_directory) || this->jobs != other.jobs ||
			this->modules.size() != other.modules.size() ||
			this->source_roots.size() != other.source_roots.size()) {
			return false;
		}
		for (int64 i = 0; i < this->modules.size(); ++i) {
			if (!this->modules.read(i).equals(other.modules.read(i))) {
				return false;
			}
		}
		for (int64 i = 0; i < this->source_roots.size(); ++i) {
			if (!this->source_roots.read(i).equals(other.source_roots.read(i))) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate startup options without catalog IO; supply the default modules when none are selected.
	 */
	startup_configuration parse_configuration(const vector_string &arguments)
	{
		startup_configuration result;
		boolean directory_seen = false;
		boolean jobs_seen = false;

		for (int64 index = 0; index < arguments.size(); ++index)
		{
			const auto option = arguments.read(index);
			if (option.equals("--help")) {
				result.help = true;
				continue;
			}
			if (option.equals("--timings")) {
				result.timings = true;
				continue;
			}
			if (!option.equals("--catalogs") && !option.equals("--module") && !option.equals("--jobs") && !option.equals("--source")) {
				fail(string("Unknown startup option"));
			}
			if (++index == arguments.size()) {
				fail(string("Missing startup option value"));
			}

			const auto value = arguments.read(index);
			if (option.equals("--catalogs")) {
				if (directory_seen || empty(value)) {
					fail(string("Expected one nonempty --catalogs directory"));
				}
				directory_seen = true;
				result.catalog_directory = value;
			}
			else if (option.equals("--source")) {
				if (empty(value)) {
					fail(string("Expected nonempty --source directory"));
				}
				result.source_roots.append(value);
			}
			else if (option.equals("--module")) {
				validate_module_name(value);
				if (result.modules.contains(value)) {
					fail(string("Duplicate selected module"));
				}
				result.modules.append(value);
			}
			else
			{
				if (jobs_seen || empty(value)) {
					fail(string("Expected one positive --jobs count"));
				}
				jobs_seen = true;
				uint32 jobs = 0;
				for (uint64 digit = 0; digit < value.byte_size(); ++digit) {
					const auto byte = value.byte_at(digit);
					if (byte < '0' || byte > '9' || jobs > (2147483647u - (byte - '0')) / 10) {
						fail(string("Invalid --jobs count"));
					}
					jobs = jobs * 10 + byte - '0';
				}
				if (jobs == 0) {
					fail(string("--jobs must be positive"));
				}
				result.jobs = jobs;
			}
		}

		if (result.help) {
			return result; // No catalog IO for help.
		}

		if (!directory_seen) {
			fail(string("--catalogs DIRECTORY is required; use --help"));
		}

		if (result.modules.size() == 0) {
			result.modules.append(string("core"));
			result.modules.append(string("json"));
			result.modules.append(string("strings"));
			result.modules.append(string("datetime"));
			result.modules.append(string("filesystem"));
		}

		return result;
	}
}
