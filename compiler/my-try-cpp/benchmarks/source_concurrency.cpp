#include "sources/inventory.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/textbuilder.hpp"
// Benchmark-only host measurement: process CPU includes all worker threads.
#include <time.h>

namespace scpp::compiler
{
	static uint64 process_cpu_ns()
	{
		timespec time{};
		if (clock_gettime(CLOCK_PROCESS_CPUTIME_ID, &time) != 0) {
			fail(string("Cannot measure process CPU time"));
		}
		return uint64(time.tv_sec) * 1000000000ULL + uint64(time.tv_nsec);
	}

	/**
	 * Report whole-stage CPU consumption separately from peak overlapping callbacks.
	 */
	static void report(const char *stage, uint64 wall_us, uint64 cpu_ns, uint64 peak)
	{
		textbuilder text;
		text.append(stage);
		text.append(" wall_us=");
		text.append_number(wall_us);
		text.append(" cpu_ns=");
		text.append_number(cpu_ns);
		text.append(" peak_workers=");
		text.append_number(peak);
		print_line(text.finish());
	}

	/**
	 * Report disjoint wall intervals and overlapping worker sums separately.
	 */
	static void report_breakdown(const read_measurements &metrics)
	{
		textbuilder detail;
		detail.append("breakdown");
		detail.append(" startup_ns=");
		detail.append_number(metrics.startup_ns);
		detail.append(" work_ns=");
		detail.append_number(metrics.work_ns);
		detail.append(" worker_cpu_ns=");
		detail.append_number(metrics.worker_cpu_ns);
		detail.append(" read_callback_ns=");
		detail.append_number(metrics.read_callback_ns);
		detail.append(" read_callback_cpu_ns=");
		detail.append_number(metrics.read_callback_cpu_ns);
		detail.append(" completion_tail_ns=");
		detail.append_number(metrics.completion_tail_ns);
		detail.append(" join_tail_ns=");
		detail.append_number(metrics.join_tail_ns);
		detail.append(" queue_claim_ns=");
		detail.append_number(metrics.queue_claim_ns);
		detail.append(" completion_lock_wait_ns=");
		detail.append_number(metrics.completion_lock_wait_ns);
		detail.append(" completion_lock_hold_ns=");
		detail.append_number(metrics.completion_lock_hold_ns);
		detail.append(" gather_ns=");
		detail.append_number(metrics.gather_ns);
		detail.append(" request_ns=");
		detail.append_number(metrics.request_ns);
		detail.append(" publication_ns=");
		detail.append_number(metrics.publication_ns);
		detail.append(" workers=");
		detail.append_number(metrics.workers);
		print_line(detail.finish());
	}

	/**
	 * Measure initial discovery and reads without allocation-counter contention.
	 */
	static int profile(const vector_string &arguments)
	{
		if (arguments.size() != 2 && arguments.size() != 3) {
			fail(string("Usage: source-concurrency-profile ROOT JOBS(1|8|16) [profile]"));
		}
		const auto option = arguments.read(1);
		if (!option.equals("1") && !option.equals("8") && !option.equals("16")) {
			fail(string("Jobs must be 1, 8 or 16"));
		}
		const uint32 jobs = option.equals("1") ? 1 : (option.equals("8") ? 8 : 16);
		vector_string roots;
		roots.append(arguments.read(0));
		validate_source_roots(roots);
		source_inventory inventory;
		auto wall = monotonic_micros();
		auto cpu = process_cpu_ns();
		inventory.scan(roots, jobs);
		cpu = process_cpu_ns() - cpu;
		wall = monotonic_micros() - wall;
		report("discovery", wall, cpu, inventory.measurements().peak_workers);
		wall = monotonic_micros();
		cpu = process_cpu_ns();
		inventory.load(roots, jobs, arguments.size() == 3);
		cpu = process_cpu_ns() - cpu;
		wall = monotonic_micros() - wall;
		report("reading", wall, cpu, inventory.reads().peak_workers);
		if (arguments.size() == 3) {
			report_breakdown(inventory.reads());
		}

		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::profile);
}
