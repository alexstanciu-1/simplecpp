#include "scpp_compiler/io.hpp"
#include "tokenize/command.hpp"
#include "scpp_compiler/datetime.hpp"
#include "startup/model.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/textbuilder.hpp"

namespace scpp::compiler
{
	static void print_startup_report(const startup_configuration &config, uint64 begin);

	/**
	 * Initialize the catalog model and report startup counts and optional timings.
	 */
	static int startup_main(const vector_string &arguments)
	{
		if (arguments.size() != 0 && (arguments.read(0).equals("-") || arguments.read(0).equals("-e"))) {
			return tokenize_command(arguments);
		}
		const auto begin = monotonic_micros();

		auto config = parse_configuration(arguments);
		const auto configuration_us = monotonic_micros() - begin;

		if (config.help) {
			print_line(string(
				"Usage: my-try-cpp - | -e SOURCE (in-memory tokenization only; no execution)\n       my-try-cpp --catalogs DIRECTORY [--module NAME ...] [--source DIRECTORY ...] [--jobs N] [--timings]\nInitializes runtime catalogs and optional source inventory; source tokenization is available; parsing is not implemented."));
			return 0;
		}

		Compiler_Lifecycle::initialize(config, configuration_us);
		print_startup_report(config, begin);

		return 0;
	}

	/**
	 * Print the retained catalog counts and optional startup timings as one JSON record.
	 */
	static void print_startup_report(const startup_configuration &config, uint64 begin)
	{
		const auto &catalogs = Model::catalogs();
		const auto &metrics = Model::measurements();

		textbuilder report;
		report.append("{\"state\":\"catalog_metadata_ready\",\"modules\":");
		report.append_number(catalogs.modules.size());
		report.append(",\"functions\":");
		report.append_number(catalogs.function_ids.size());
		report.append(",\"types\":");
		report.append_number(catalogs.type_ids.size());
		report.append(",\"jobs\":");
		report.append_number(uint64(Model::configuration().jobs));
		report.append(",\"files_read\":");
		report.append_number(metrics.files_read);
		report.append(",\"bytes_read\":");
		report.append_number(metrics.bytes_read);

		if (config.source_roots.size() != 0)
		{
			const auto &scan = Model::sources().measurements();
			report.append(",\"source_files\":");
			report.append_number(scan.files);
			report.append(",\"source_directories\":");
			report.append_number(scan.directories);
			report.append(",\"scan_peak_workers\":");
			report.append_number(scan.peak_workers);
			const auto &tokens = Model::sources().token_metrics();
			report.append(",\"tokens\":");
			report.append_number(tokens.token_count);
			report.append(",\"token_capacity_bytes\":");
			report.append_number(tokens.capacity_bytes);
			report.append(",\"token_peak_workers\":");
			report.append_number(tokens.peak_workers);
			if (config.timings) {
				report.append(",\"tokenize_us\":");
				report.append_number(tokens.elapsed_us);
			}
			const auto &reads = Model::sources().reads();
			report.append(",\"source_files_read\":");
			report.append_number(reads.files_read);
			report.append(",\"source_bytes_read\":");
			report.append_number(reads.bytes_read);
			report.append(",\"read_peak_workers\":");
			report.append_number(reads.peak_workers);
			if (config.timings) {
				report.append(",\"source_read_us\":");
				report.append_number(reads.elapsed_us);
				report.append(",\"scan_us\":");
				report.append_number(scan.elapsed_us);
			}
		}

		if (config.timings)
		{
			report.append(",\"configuration_us\":");
			report.append_number(metrics.configuration_us);
			report.append(",\"loading_us\":");
			report.append_number(metrics.loading_us);
			report.append(",\"validation_us\":");
			report.append_number(metrics.validation_us);
			report.append(",\"publication_us\":");
			report.append_number(metrics.publication_us);
			report.append(",\"total_us\":");
			report.append_number(monotonic_micros() - begin);
		}

		report.append("}");
		print_line(report.finish());

	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::startup_main);
}
