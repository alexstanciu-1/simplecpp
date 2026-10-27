#include "sources/inventory.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/textbuilder.hpp"
// Host clocks/RSS and allocation interception are benchmark-only.
#include <time.h>
#include <stdio.h>
#ifdef TOKEN_ALLOCATIONS
#include "token_allocations.hpp"
#endif

namespace scpp::compiler
{
	static uint64 cpu_ns()
	{
		timespec time{};
		if (clock_gettime(CLOCK_PROCESS_CPUTIME_ID, &time) != 0) {
			fail(string("CPU clock unavailable"));
		}
		return uint64(time.tv_sec) * 1000000000ULL + uint64(time.tv_nsec);
	}

	struct memory_sample {
		uint64 current_kib = 0;
		uint64 peak_kib = 0;
	};

	/**
	 * Read this exec's memory map counters; getrusage may retain launcher high-water RSS.
	 */
	static memory_sample memory_usage()
	{
		auto *file = fopen("/proc/self/status", "r");
		if (file == nullptr) {
			fail(string("Process memory counters unavailable"));
		}
		memory_sample result;
		char line[256];
		unsigned long long value = 0;
		while (fgets(line, sizeof(line), file) != nullptr) {
			if (sscanf(line, "VmRSS: %llu kB", &value) == 1) {
				result.current_kib = value;
			}
			if (sscanf(line, "VmHWM: %llu kB", &value) == 1) {
				result.peak_kib = value;
			}
		}
		fclose(file);
		if (result.current_kib == 0 || result.peak_kib == 0) {
			fail(string("Missing process memory counters"));
		}
		return result;
	}

	static void field(textbuilder &report, const char *name, uint64 value)
	{
		report.append(",\"");
		report.append(name);
		report.append("\":");
		report.append_number(value);
	}

	/**
	 * Dump spans and tags outside timed work for independent corpus comparison.
	 */
	static void dump(const source_inventory &sources)
	{
		for (int64 i = 0; i < sources.size(); ++i)
		{
			const auto &record = sources.at(i);
			textbuilder file;
			file.append("FILE ");
			file.append(record.path);
			print_line(file.finish());
			textbuilder rows;
			for (int64 index = 0; index < record.tokens.size(); ++index)
			{
				const auto token = record.tokens.at(index);
				rows.append_number(uint64(token.offset()));
				rows.append(" ");
				rows.append_number(uint64(token.length()));
				rows.append(" ");
				rows.append_number(uint64(static_cast<unsigned char>(token.kind())));
				rows.append("\n");
			}
			print_line(rows.finish());
		}
	}

	/**
	 * Measure cold token construction and same-process no-op reuse after checked loading.
	 */
	static int profile(const vector_string &arguments)
	{
		if (arguments.size() != 3) {
			fail(string("Usage: tokenization-profile ROOT JOBS(1|16) MODE(normal|profile|dump)"));
		}
		const uint32 jobs = arguments.read(1).equals("1") ? 1 : 16;
		const auto mode = arguments.read(2);
		vector_string roots;
		roots.append(arguments.read(0));
		validate_source_roots(roots);
		source_inventory sources;
		sources.scan(roots, jobs);
		sources.load(roots, jobs);
		const auto before_memory = memory_usage();
		const auto begin = monotonic_micros();
		const auto cpu = cpu_ns();
#ifdef TOKEN_ALLOCATIONS
		tracking = true;
#endif
		sources.tokenize(jobs, mode.equals("profile"));
#ifdef TOKEN_ALLOCATIONS
		tracking = false;
#endif
		const auto elapsed = monotonic_micros() - begin;
		const auto used_cpu = cpu_ns() - cpu;
		const auto metrics = sources.token_metrics();
		const auto after_memory = memory_usage();
#ifdef TOKEN_ALLOCATIONS
		const uint64 count = allocations;
		const uint64 bytes = requested_bytes;
		allocations = 0;
		requested_bytes = 0;
		tracking = true;
#endif
		const auto reuse_begin = monotonic_micros();
		sources.tokenize(jobs);
		const auto reuse_us = monotonic_micros() - reuse_begin;
#ifdef TOKEN_ALLOCATIONS
		tracking = false;
#endif
		if (sources.token_metrics().files_tokenized != 0 || sources.token_metrics().reused != uint64(sources.size())) {
			fail(string("Unchanged generation was retokenized"));
		}
		if (mode.equals("dump")) {
			dump(sources);
			return 0;
		}
		textbuilder report;
		report.append("{\"files\":");
		report.append_number(uint64(sources.size()));
		field(report, "source_bytes", metrics.bytes_scanned);
		field(report, "tokens", metrics.token_count);
		field(report, "token_bytes", metrics.token_count * 8);
		field(report, "capacity_bytes", metrics.capacity_bytes);
		field(report, "wall_us", elapsed);
		field(report, "cpu_ns", used_cpu);
		field(report, "peak_workers", metrics.peak_workers);
		field(report, "rss_before_kib", before_memory.peak_kib);
		field(report, "rss_current_before_kib", before_memory.current_kib);
		field(report, "rss_current_kib", after_memory.current_kib);
		field(report, "rss_peak_kib", after_memory.peak_kib);
		field(report, "reuse_us", reuse_us);
		field(report, "startup_ns", metrics.startup_ns);
		field(report, "work_ns", metrics.work_ns);
		field(report, "worker_cpu_ns", metrics.worker_cpu_ns);
		field(report, "scan_callback_ns", metrics.scan_callback_ns);
		field(report, "scan_callback_cpu_ns", metrics.scan_callback_cpu_ns);
		field(report, "completion_tail_ns", metrics.completion_tail_ns);
		field(report, "join_tail_ns", metrics.join_tail_ns);
		field(report, "queue_claim_ns", metrics.queue_claim_ns);
		field(report, "completion_lock_wait_ns", metrics.completion_lock_wait_ns);
		field(report, "completion_lock_hold_ns", metrics.completion_lock_hold_ns);
		field(report, "gather_ns", metrics.gather_ns);
		field(report, "request_ns", metrics.request_ns);
		field(report, "publication_ns", metrics.publication_ns);
		field(report, "workers", metrics.workers);
#ifdef TOKEN_ALLOCATIONS
		field(report, "allocations", count);
		field(report, "allocated_bytes", bytes);
		field(report, "reuse_allocations", allocations);
#endif
		report.append("}");
		print_line(report.finish());
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::profile);
}
